<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\NgoDonor;
use App\Models\NgoGrant;
use App\Models\Project;
use App\Models\Task;
use App\Services\BrevoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendWeeklyReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function handle(): void
    {
        $weekStart = Carbon::now()->subDays(7)->startOfDay();
        $weekEnd   = Carbon::now()->endOfDay();
        $weekLabel = $weekStart->format('d/m') . ' a ' . $weekEnd->format('d/m/Y');

        // Itera por todos os tenants com relatório semanal habilitado
        Tenant::where('weekly_report_enabled', true)->each(function (Tenant $tenant) use ($weekStart, $weekEnd, $weekLabel) {
            try {
                $this->sendForTenant($tenant, $weekStart, $weekEnd, $weekLabel);
            } catch (\Throwable $e) {
                Log::error("[WeeklyReport] Falha para tenant #{$tenant->id}: " . $e->getMessage());
            }
        });
    }

    private function sendForTenant(Tenant $tenant, Carbon $start, Carbon $end, string $weekLabel): void
    {
        // Destinatário: report_email ou email do primeiro admin do tenant
        $adminUser = $tenant->users()
            ->whereIn('role', ['manager', 'ngo', 'super_admin'])
            ->first();

        if (!$adminUser) {
            Log::info("[WeeklyReport] Tenant #{$tenant->id} sem usuário admin. Ignorando.");
            return;
        }

        $toEmail = $tenant->report_email ?: $adminUser->email;
        $toName  = $adminUser->name;
        $tid     = $tenant->id;
        $role    = $adminUser->role;

        // ── Coleta de dados da semana ──────────────────────────────────────
        $kpis = [];

        // Receitas e despesas da semana
        $income = Transaction::where('tenant_id', $tid)
            ->where('type', 'income')
            ->whereBetween('date', [$start, $end])
            ->sum('amount');

        $expense = Transaction::where('tenant_id', $tid)
            ->where('type', 'expense')
            ->whereBetween('date', [$start, $end])
            ->sum('amount');

        $kpis['receitas']  = $income;
        $kpis['despesas']  = $expense;
        $kpis['saldo']     = $income - $expense;

        // Tarefas concluídas (manager)
        if (in_array($role, ['manager', 'super_admin'])) {
            $kpis['tarefas_concluidas'] = Task::where('tenant_id', $tid)
                ->where('status', 'done')
                ->whereBetween('updated_at', [$start, $end])
                ->count();

            $kpis['projetos_ativos'] = Project::where('tenant_id', $tid)
                ->where('status', 'in_progress')
                ->count();
        }

        // Novos doadores (ngo)
        if (in_array($role, ['ngo', 'super_admin'])) {
            $kpis['novos_doadores'] = NgoDonor::where('tenant_id', $tid)
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $kpis['editais_ativos'] = NgoGrant::where('tenant_id', $tid)
                ->where('status', 'active')
                ->count();
        }

        // ── Geração de texto com Bruce AI (Gemini) ─────────────────────────
        $aiSummary = $this->generateAiSummary($kpis, $role, $tenant->brand_name ?: $tenant->name, $weekLabel);

        // ── Montagem do HTML do email ──────────────────────────────────────
        $html = $this->buildEmailHtml($tenant, $adminUser, $kpis, $aiSummary, $weekLabel, $role);

        // ── Envio via Brevo ───────────────────────────────────────────────
        $brevo = app(BrevoService::class);
        $subject = "📊 Resumo Semanal — {$weekLabel} | Vivensi";
        $sent = $brevo->sendEmail($toEmail, $toName, $subject, $html, $tid);

        if ($sent) {
            Log::info("[WeeklyReport] Enviado para tenant #{$tid} ({$toEmail})");
        } else {
            Log::warning("[WeeklyReport] Falha no envio para tenant #{$tid} ({$toEmail})");
        }
    }

    private function generateAiSummary(array $kpis, string $role, string $orgName, string $weekLabel): string
    {
        $apiKey = \App\Models\SystemSetting::getValue('gemini_api_key');

        if (!$apiKey) {
            return "Sua equipe teve uma semana de trabalho. Continue monitorando os indicadores no painel para tomar as melhores decisões estratégicas.";
        }

        $kpiText = collect($kpis)->map(fn($v, $k) => "- {$k}: " . (is_numeric($v) ? 'R$ ' . number_format($v, 2, ',', '.') : $v))->implode("\n");

        $prompt = "Você é o Bruce AI, assistente de gestão da plataforma Vivensi. "
            . "Escreva um parágrafo breve (3-4 frases) em português brasileiro, "
            . "tom profissional mas amigável, resumindo a semana de {$weekLabel} para a organização '{$orgName}'. "
            . "Baseie-se nos seguintes indicadores:\n{$kpiText}\n"
            . "Termine com uma sugestão estratégica curta. Não use markdown.";

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 200],
                ]);

            return $response->json('candidates.0.content.parts.0.text')
                ?? "Uma boa semana encerrou. Acesse o painel para ver todos os detalhes.";
        } catch (\Throwable $e) {
            Log::warning("[WeeklyReport] Gemini AI falhou: " . $e->getMessage());
            return "Acesse seu painel para conferir os resultados da semana e planejar os próximos passos.";
        }
    }

    private function buildEmailHtml(Tenant $tenant, $user, array $kpis, string $aiText, string $weekLabel, string $role): string
    {
        $orgName  = e($tenant->brand_name ?: $tenant->name);
        $userName = e($user->name);
        $color    = $tenant->brand_color ?: '#4f46e5';
        $year     = date('Y');
        $panelUrl = url('/dashboard');

        // Monta cards de KPIs
        $kpiCards = '';
        $labels = [
            'receitas'           => ['💰', 'Receitas', true],
            'despesas'           => ['📤', 'Despesas', true],
            'saldo'              => ['📈', 'Saldo', true],
            'tarefas_concluidas' => ['✅', 'Tarefas Concluídas', false],
            'projetos_ativos'    => ['🗂️', 'Projetos Ativos', false],
            'novos_doadores'     => ['❤️', 'Novos Doadores', false],
            'editais_ativos'     => ['📋', 'Editais Ativos', false],
        ];

        foreach ($kpis as $key => $value) {
            [$icon, $label, $isMoney] = $labels[$key] ?? ['📌', $key, false];
            $display = $isMoney ? 'R$ ' . number_format((float) $value, 2, ',', '.') : $value;
            $kpiCards .= "
            <td style='width:33%; padding:8px; vertical-align:top;'>
                <div style='background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px; text-align:center;'>
                    <div style='font-size:22px;margin-bottom:6px;'>{$icon}</div>
                    <div style='font-size:18px;font-weight:800;color:#0f172a;'>{$display}</div>
                    <div style='font-size:11px;color:#64748b;font-weight:600;margin-top:4px;'>{$label}</div>
                </div>
            </td>";
        }

        return "<!DOCTYPE html>
<html lang='pt-br'>
<head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'></head>
<body style='margin:0;padding:0;background:#f1f5f9;font-family:Inter,Segoe UI,Roboto,Arial,sans-serif;'>
<table width='100%' border='0' cellspacing='0' cellpadding='0'>
<tr><td align='center' style='padding:40px 0;'>
<table width='600' border='0' cellspacing='0' cellpadding='0' style='background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.08);border:1px solid #e2e8f0;'>

    <!-- Cabeçalho -->
    <tr><td style='background:linear-gradient(135deg,{$color},#3730a3);padding:36px;text-align:center;'>
        <div style='color:rgba(255,255,255,0.7);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:2px;margin-bottom:8px;'>Relatório Semanal</div>
        <h1 style='color:#fff;margin:0;font-size:22px;font-weight:900;letter-spacing:-0.5px;'>{$orgName}</h1>
        <div style='color:rgba(255,255,255,0.65);font-size:13px;margin-top:6px;font-weight:600;'>Período: {$weekLabel}</div>
    </td></tr>

    <!-- Saudação -->
    <tr><td style='padding:32px 36px 16px;'>
        <p style='font-size:16px;color:#1e293b;margin:0 0 8px;font-weight:700;'>Olá, {$userName}! 👋</p>
        <p style='font-size:14px;color:#475569;margin:0;line-height:1.7;'>Confira o resumo da sua semana na <strong>Central de Comando</strong>.</p>
    </td></tr>

    <!-- Bruce AI insight -->
    <tr><td style='padding:0 36px 24px;'>
        <div style='background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border:1px solid #bae6fd;border-radius:14px;padding:20px 22px;'>
            <div style='display:flex;align-items:center;gap:10px;margin-bottom:10px;'>
                <span style='font-size:18px;'>🤖</span>
                <strong style='font-size:12px;color:#0369a1;text-transform:uppercase;letter-spacing:1px;'>Bruce AI — Análise da Semana</strong>
            </div>
            <p style='font-size:14px;color:#0c4a6e;line-height:1.7;margin:0;'>{$aiText}</p>
        </div>
    </td></tr>

    <!-- KPIs -->
    <tr><td style='padding:0 36px 28px;'>
        <div style='font-size:11px;font-weight:900;color:#94a3b8;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:14px;'>Indicadores da Semana</div>
        <table width='100%' border='0' cellspacing='0' cellpadding='0'><tr>{$kpiCards}</tr></table>
    </td></tr>

    <!-- CTA -->
    <tr><td style='padding:0 36px 36px;text-align:center;'>
        <a href='{$panelUrl}' style='background:{$color};color:#fff;padding:14px 32px;border-radius:12px;text-decoration:none;font-weight:800;font-size:14px;display:inline-block;box-shadow:0 6px 16px rgba(79,70,229,0.3);'>
            Acessar Meu Painel →
        </a>
    </td></tr>

    <!-- Rodapé -->
    <tr><td style='background:#f8fafc;padding:20px 36px;text-align:center;border-top:1px solid #e2e8f0;'>
        <p style='font-size:12px;color:#94a3b8;margin:0 0 6px;'>© {$year} <strong>Vivensi</strong>. Tecnologia e Propósito.</p>
        <p style='font-size:11px;color:#cbd5e1;margin:0;'>Para desativar este relatório, acesse Configurações → Identidade Visual no seu painel.</p>
    </td></tr>

</table>
</td></tr></table>
</body></html>";
    }
}
