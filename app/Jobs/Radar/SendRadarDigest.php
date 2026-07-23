<?php

namespace App\Jobs\Radar;

use App\Models\RadarMatch;
use App\Models\RadarNotification;
use App\Models\Tenant;
use App\Models\WhatsappInstance;
use App\Services\BrevoService;
use App\Services\EmailQuotaService;
use App\Services\EvolutionApiService;
use App\Services\Messaging\AntiBanManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendRadarDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 2;
    public int   $timeout = 180;
    public array $backoff = [60];

    private const MAX_FINDINGS = 5;

    public function __construct()
    {
        $this->onQueue('emails');
    }

    public function handle(
        BrevoService      $brevo,
        EmailQuotaService $quota,
        AntiBanManager    $antiban,
    ): void {
        Tenant::query()
            ->whereNotNull('radar_ibge_code')
            ->whereNotNull('radar_digest_channel')
            ->where('radar_digest_channel', '!=', 'desligado')
            ->whereIn('subscription_status', ['active', 'trialing', 'trial'])
            ->each(function (Tenant $tenant) use ($brevo, $quota, $antiban) {
                try {
                    $this->processForTenant($tenant, $brevo, $quota, $antiban);
                } catch (\Throwable $e) {
                    Log::error("[RadarDigest] Falha para tenant #{$tenant->id}: " . $e->getMessage());
                }
            });
    }

    private function processForTenant(
        Tenant            $tenant,
        BrevoService      $brevo,
        EmailQuotaService $quota,
        AntiBanManager    $antiban,
    ): void {
        $channel  = $tenant->radar_digest_channel;
        $minScore = $tenant->radar_min_score ?? 30;

        // Find approved matches not yet sent via this channel
        $sentIds = RadarNotification::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('channel', $channel)
            ->pluck('radar_finding_id');

        $matches = RadarMatch::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('score', '>=', $minScore)
            ->whereNotIn('radar_finding_id', $sentIds)
            ->with('finding')
            ->whereHas('finding', fn($q) => $q->where('status', 'aprovado'))
            ->orderByDesc('score')
            ->limit(self::MAX_FINDINGS)
            ->get();

        if ($matches->isEmpty()) {
            Log::info("[RadarDigest] Tenant #{$tenant->id}: nenhum achado novo para enviar.");
            return;
        }

        $sent = match ($channel) {
            'email'     => $this->sendEmail($tenant, $matches, $brevo, $quota),
            'whatsapp'  => $this->sendWhatsApp($tenant, $matches, $antiban),
            default     => false,
        };

        if ($sent) {
            foreach ($matches as $match) {
                RadarNotification::withoutGlobalScopes()->insertOrIgnore([[
                    'tenant_id'        => $tenant->id,
                    'radar_finding_id' => $match->radar_finding_id,
                    'channel'          => $channel,
                    'sent_at'          => now(),
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]]);
            }

            $tenant->withoutGlobalScopes()->where('id', $tenant->id)
                ->update(['radar_last_digest_at' => now()]);

            Log::info("[RadarDigest] Tenant #{$tenant->id}: {$matches->count()} achados enviados via {$channel}.");
        }
    }

    private function sendEmail(
        Tenant            $tenant,
        \Illuminate\Support\Collection $matches,
        BrevoService      $brevo,
        EmailQuotaService $quota,
    ): bool {
        if ($quota->wouldExceed($tenant, 1)) {
            Log::warning("[RadarDigest] Tenant #{$tenant->id}: cota de e-mail esgotada.");
            return false;
        }

        $adminUser = $tenant->users()
            ->whereIn('role', ['admin', 'manager', 'ngo', 'super_admin'])
            ->first();

        if (!$adminUser) {
            return false;
        }

        $html    = $this->buildEmailHtml($tenant, $matches);
        $subject = '📡 Radar de Editais — ' . $matches->count() . ' novo(s) achado(s) para ' . ($tenant->brand_name ?: $tenant->name);

        $ok = $brevo->sendEmail($adminUser->email, $adminUser->name, $subject, $html, $tenant->id);

        if ($ok) {
            $quota->tryConsume($tenant, 1);
        }

        return (bool) $ok;
    }

    private function sendWhatsApp(
        Tenant                         $tenant,
        \Illuminate\Support\Collection $matches,
        AntiBanManager                 $antiban,
    ): bool {
        $instance = WhatsappInstance::forTenant($tenant->id)->active()->first();

        if (!$instance) {
            Log::info("[RadarDigest] Tenant #{$tenant->id}: sem instância WhatsApp ativa.");
            return false;
        }

        if (!$antiban->canSendMessage($instance)) {
            Log::warning("[RadarDigest] Tenant #{$tenant->id}: AntiBan bloqueou o envio.");
            return false;
        }

        $adminUser = $tenant->users()
            ->whereIn('role', ['admin', 'manager', 'ngo', 'super_admin'])
            ->whereNotNull('phone')
            ->first();

        if (!$adminUser?->phone) {
            return false;
        }

        $text = $this->buildWhatsAppText($matches);

        $api    = app(EvolutionApiService::class, ['instance' => $instance]);
        $result = $api->sendMessage($adminUser->phone, $text);

        return !empty($result['key']['id']);
    }

    private function buildEmailHtml(Tenant $tenant, \Illuminate\Support\Collection $matches): string
    {
        $orgName = e($tenant->brand_name ?: $tenant->name);
        $color   = $tenant->brand_color ?: '#3b82f6';
        $date    = now()->format('d/m/Y');
        $url     = url('/ngo/radar');

        $items = '';
        foreach ($matches as $match) {
            $f           = $match->finding;
            $title       = e($f->title);
            $excerpt     = e(\Illuminate\Support\Str::limit($f->excerpt, 200));
            $src         = e($f->source_url);
            $published   = $f->published_at ? $f->published_at->format('d/m/Y') : '';
            $score       = $match->score;
            $scoreColor  = $score >= 70 ? '#10b981' : ($score >= 40 ? '#f59e0b' : '#94a3b8');
            $sourceLabel = $f->source === 'transferegov' ? 'Transferegov' : 'Querido Diário';

            $items .= "
            <div style='border:1px solid #e2e8f0;border-radius:12px;padding:18px;margin-bottom:14px;border-left:4px solid {$scoreColor};'>
                <div style='margin-bottom:6px;'>
                    <span style='font-size:11px;font-weight:800;color:{$scoreColor};background:{$scoreColor}1a;padding:2px 8px;border-radius:10px;'>{$score}% relevante</span>
                    <span style='font-size:11px;color:#6366f1;background:#ede9fe;padding:2px 8px;border-radius:10px;margin-left:6px;'>{$sourceLabel}</span>
                    <span style='font-size:11px;color:#94a3b8;margin-left:6px;'>{$published}</span>
                </div>
                <div style='font-weight:800;font-size:14px;color:#1e293b;margin:6px 0;'>{$title}</div>
                <div style='font-size:13px;color:#64748b;line-height:1.6;margin-bottom:10px;'>{$excerpt}</div>
                <a href='{$src}' style='color:{$color};font-weight:700;font-size:13px;text-decoration:none;'>Ver edital →</a>
            </div>";
        }

        return "<!DOCTYPE html><html lang='pt-br'><head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f1f5f9;font-family:Inter,Arial,sans-serif;'>
<table width='100%'><tr><td align='center' style='padding:40px 0;'>
<table width='600' style='background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.07);border:1px solid #e2e8f0;'>
    <tr><td style='background:linear-gradient(135deg,{$color},#1d4ed8);padding:28px 36px;'>
        <div style='color:rgba(255,255,255,.75);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:2px;margin-bottom:6px;'>Radar de Editais</div>
        <h1 style='color:#fff;margin:0;font-size:22px;font-weight:900;'>{$orgName}</h1>
        <div style='color:rgba(255,255,255,.75);font-size:12px;margin-top:4px;'>{$date}</div>
    </td></tr>
    <tr><td style='padding:28px 36px 8px;'>
        <p style='font-size:15px;color:#1e293b;font-weight:700;margin:0 0 6px;'>📡 Novos achados para a sua organização</p>
        <p style='font-size:13px;color:#64748b;margin:0 0 20px;'>Encontramos {$matches->count()} chamamento(s) público(s) relevante(s) para o seu perfil. Confira abaixo:</p>
        {$items}
    </td></tr>
    <tr><td style='padding:8px 36px 32px;text-align:center;'>
        <a href='{$url}' style='background:{$color};color:#fff;padding:12px 28px;border-radius:10px;text-decoration:none;font-weight:800;font-size:14px;display:inline-block;'>Ver todos no painel →</a>
    </td></tr>
    <tr><td style='background:#f8fafc;padding:18px 36px;text-align:center;border-top:1px solid #e2e8f0;'>
        <p style='font-size:11px;color:#94a3b8;margin:0;'>Para alterar as preferências, acesse <strong>Radar de Editais → Configurações</strong> no painel Vivensi.</p>
    </td></tr>
</table>
</td></tr></table></body></html>";
    }

    private function buildWhatsAppText(\Illuminate\Support\Collection $matches): string
    {
        $lines = ["📡 *Radar de Editais — Vivensi*\n"];
        $lines[] = "Encontramos " . $matches->count() . " novo(s) chamamento(s) relevante(s) para a sua organização:\n";

        $i = 1;
        foreach ($matches as $match) {
            $f = $match->finding;
            $lines[] = "*{$i}. " . \Illuminate\Support\Str::limit($f->title, 80) . "*";
            $lines[] = "Relevância: {$match->score}% | " . ($f->source === 'transferegov' ? 'Transferegov' : 'Querido Diário');
            $lines[] = $f->source_url;
            $lines[] = '';
            $i++;
        }

        $lines[] = "👉 Veja todos em: " . url('/ngo/radar');

        return implode("\n", $lines);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendRadarDigest falhou.', ['error' => $e->getMessage()]);
    }
}
