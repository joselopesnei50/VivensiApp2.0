<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\NgoDonor;
use App\Models\Beneficiary;
use App\Models\Project;
use App\Models\Task;
use App\Models\ProjectTimelineRecord;
use App\Models\ProjectLog;
use App\Services\BrevoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Relatório semanal — versão motivacional, sem números financeiros.
 *
 * Mudanças importantes em relação à versão anterior:
 * - Filtra Tenant por subscription_status (active/trialing/trial). Contas
 *   canceled/suspended/past_due NÃO recebem email.
 * - Sem receitas, despesas ou saldo no conteúdo. KPIs são de ATIVIDADE
 *   (tarefas concluídas, projetos ativos, marcos registrados, doadores e
 *   beneficiários novos, entradas no diário).
 * - Prompt do Bruce: parceiro motivacional com UMA dica acionável de
 *   produto para a próxima semana. Sem jargão executivo.
 */
class SendWeeklyReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Lista de status de assinatura que recebem o relatório semanal. */
    private const ELIGIBLE_SUBSCRIPTION_STATUSES = ['active', 'trialing', 'trial'];

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        $weekStart = Carbon::now()->subDays(7)->startOfDay();
        $weekEnd   = Carbon::now()->endOfDay();
        $weekLabel = $weekStart->format('d/m') . ' a ' . $weekEnd->format('d/m/Y');

        Tenant::query()
            ->where('weekly_report_enabled', true)
            ->whereIn('subscription_status', self::ELIGIBLE_SUBSCRIPTION_STATUSES)
            ->each(function (Tenant $tenant) use ($weekStart, $weekEnd, $weekLabel) {
                try {
                    $this->sendForTenant($tenant, $weekStart, $weekEnd, $weekLabel);
                } catch (\Throwable $e) {
                    Log::error("[WeeklyReport] Falha para tenant #{$tenant->id}: " . $e->getMessage());
                }
            });
    }

    private function sendForTenant(Tenant $tenant, Carbon $start, Carbon $end, string $weekLabel): void
    {
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

        $activities = $this->collectActivities($tid, $role, $start, $end);

        if ($this->isEmptyWeek($activities)) {
            Log::info("[WeeklyReport] Tenant #{$tid} sem atividades na semana. Pulando para nao spammar.");
            return;
        }

        $aiSummary = $this->generateAiSummary($activities, $role, $tenant->brand_name ?: $tenant->name, $weekLabel);
        $html      = $this->buildEmailHtml($tenant, $adminUser, $activities, $aiSummary, $weekLabel, $role);

        $brevo   = app(BrevoService::class);
        $subject = "🚀 Sua semana na " . ($tenant->brand_name ?: $tenant->name) . " — {$weekLabel}";
        $sent    = $brevo->sendEmail($toEmail, $toName, $subject, $html, $tid);

        if ($sent) {
            Log::info("[WeeklyReport] Enviado para tenant #{$tid} ({$toEmail})");
        } else {
            Log::warning("[WeeklyReport] Falha no envio para tenant #{$tid} ({$toEmail})");
        }
    }

    /**
     * Coleta indicadores de ATIVIDADE (não-financeiros) da semana.
     * Os valores são contagens; o que aparece depende do papel do destinatário.
     */
    private function collectActivities(int $tenantId, string $role, Carbon $start, Carbon $end): array
    {
        $a = [];

        $a['tarefas_concluidas'] = Task::where('tenant_id', $tenantId)
            ->whereIn('status', ['done', 'completed'])
            ->whereBetween('updated_at', [$start, $end])
            ->count();

        $a['projetos_ativos'] = Project::where('tenant_id', $tenantId)
            ->whereIn('status', ['active', 'in_progress'])
            ->when(method_exists(Project::class, 'scopeActive'), function ($q) {
                // Project::active() filtra archived_at IS NULL
                $q->active();
            })
            ->count();

        $a['marcos_registrados'] = ProjectTimelineRecord::whereHas('project', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $a['diario_entradas'] = ProjectLog::whereHas('project', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->whereBetween('created_at', [$start, $end])
            ->count();

        if (in_array($role, ['ngo', 'super_admin'])) {
            $a['novos_doadores'] = NgoDonor::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $a['novos_beneficiarios'] = Beneficiary::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$start, $end])
                ->count();
        }

        return $a;
    }

    private function isEmptyWeek(array $activities): bool
    {
        return array_sum($activities) === 0;
    }

    /**
     * Gera mensagem motivacional via Bruce/DeepSeek.
     * NUNCA recebe valores financeiros no prompt.
     */
    private function generateAiSummary(array $activities, string $role, string $orgName, string $weekLabel): string
    {
        $kpiText = collect($activities)
            ->map(fn ($v, $k) => "- " . str_replace('_', ' ', $k) . ": {$v}")
            ->implode("\n");

        $prompt = "Você é o Bruce, parceiro motivacional dos gestores na plataforma Vivensi "
            . "(gestão para ONGs e organizações do terceiro setor). Escreva em português brasileiro, "
            . "tom CALOROSO e MOTIVACIONAL — como um amigo que torce pela equipe, não como relatório corporativo.\n\n"
            . "Organização: {$orgName}\n"
            . "Período: {$weekLabel}\n\n"
            . "ATIVIDADES DA SEMANA (somente contagens de ações, jamais cite valores em dinheiro):\n{$kpiText}\n\n"
            . "Escreva 3 a 4 frases curtas:\n"
            . "1) Reconheça uma conquista concreta da semana baseada nessas atividades.\n"
            . "2) Dê UMA dica acionável e específica para a próxima semana — algo prático no sistema, "
            . "como registrar um marco, agendar tarefas, escrever no diário do projeto, reativar doadores inativos, "
            . "cadastrar novo beneficiário ou criar uma campanha.\n"
            . "3) Encerre com encorajamento curto.\n\n"
            . "REGRAS:\n"
            . "- NUNCA mencione valores em reais, receitas, despesas, orçamento, lucro ou saldo.\n"
            . "- Não use markdown, asteriscos, bullet points ou cabeçalhos.\n"
            . "- Não cite números crus ('12 tarefas') a menos que seja para celebrar uma conquista.\n"
            . "- Se a semana foi de pouca atividade, encoraje sem culpar.\n";

        try {
            $ds     = new \App\Services\DeepSeekService();
            $result = $ds->chat([['role' => 'user', 'content' => $prompt]]);
            $text   = $result['choices'][0]['message']['content'] ?? null;

            if (!$text) {
                return $this->fallbackSummary($orgName);
            }

            return trim($text);
        } catch (\Throwable $e) {
            Log::warning("[WeeklyReport] DeepSeek falhou: " . $e->getMessage());
            return $this->fallbackSummary($orgName);
        }
    }

    private function fallbackSummary(string $orgName): string
    {
        return "Mais uma semana de trabalho dedicado em {$orgName}! Que tal abrir o painel e registrar "
            . "um novo marco no projeto que mais avançou? Pequenos passos viram histórias incríveis. "
            . "Continue assim — você está construindo algo que importa. 💚";
    }

    /**
     * Permite pré-visualizar o HTML do relatório sem enviar email.
     * Útil para testes locais via tinker.
     */
    public function previewForTenant(Tenant $tenant): string
    {
        $weekStart = Carbon::now()->subDays(7)->startOfDay();
        $weekEnd   = Carbon::now()->endOfDay();
        $weekLabel = $weekStart->format('d/m') . ' a ' . $weekEnd->format('d/m/Y');

        $adminUser = $tenant->users()->whereIn('role', ['manager', 'ngo', 'super_admin'])->first();
        if (!$adminUser) {
            return '<p>Tenant sem usuário admin.</p>';
        }

        $activities = $this->collectActivities($tenant->id, $adminUser->role, $weekStart, $weekEnd);
        $aiSummary  = $this->generateAiSummary($activities, $adminUser->role, $tenant->brand_name ?: $tenant->name, $weekLabel);

        return $this->buildEmailHtml($tenant, $adminUser, $activities, $aiSummary, $weekLabel, $adminUser->role);
    }

    private function buildEmailHtml(Tenant $tenant, $user, array $activities, string $aiText, string $weekLabel, string $role): string
    {
        $orgName  = e($tenant->brand_name ?: $tenant->name);
        $userName = e($user->name);
        $color    = $tenant->brand_color ?: '#4f46e5';
        $year     = date('Y');
        $panelUrl = url('/dashboard');

        // Cards de ATIVIDADE (sem números financeiros, com tom celebratório)
        $labels = [
            'tarefas_concluidas'   => ['✅', 'Tarefas concluídas', 'Cada uma conta!'],
            'projetos_ativos'      => ['🗂️', 'Projetos ativos', 'Em construção'],
            'marcos_registrados'   => ['🏁', 'Marcos registrados', 'História sendo escrita'],
            'diario_entradas'      => ['📝', 'Entradas no diário', 'Memória viva'],
            'novos_doadores'       => ['❤️', 'Novos doadores', 'Bem-vindos!'],
            'novos_beneficiarios'  => ['🤝', 'Pessoas atendidas', 'Impacto real'],
        ];

        $cards = '';
        foreach ($activities as $key => $value) {
            if ((int) $value === 0) {
                continue; // não polui email com cards zerados
            }
            [$icon, $label, $hint] = $labels[$key] ?? ['📌', ucfirst(str_replace('_', ' ', $key)), ''];
            $cards .= "
            <td style='width:50%; padding:6px; vertical-align:top;'>
                <div style='background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:18px;'>
                    <div style='font-size:24px;margin-bottom:6px;'>{$icon}</div>
                    <div style='font-size:24px;font-weight:900;color:#0f172a;letter-spacing:-0.5px;'>{$value}</div>
                    <div style='font-size:12px;color:#1e293b;font-weight:700;margin-top:2px;'>{$label}</div>
                    <div style='font-size:10px;color:#94a3b8;font-weight:600;margin-top:4px;text-transform:uppercase;letter-spacing:1px;'>{$hint}</div>
                </div>
            </td>";
        }

        // Quebra em linhas de 2 cards
        $cardsHtml = '';
        if ($cards) {
            $matches = [];
            preg_match_all('/<td[^>]*>.*?<\/td>/s', $cards, $matches);
            $pairs = array_chunk($matches[0], 2);
            foreach ($pairs as $pair) {
                $row = implode('', $pair);
                if (count($pair) === 1) {
                    $row .= "<td style='width:50%; padding:6px;'></td>";
                }
                $cardsHtml .= "<tr>{$row}</tr>";
            }
        }

        $kpiSection = $cardsHtml
            ? "<tr><td style='padding:0 36px 28px;'>
                    <div style='font-size:11px;font-weight:900;color:#94a3b8;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:14px;'>O que rolou na semana</div>
                    <table width='100%' border='0' cellspacing='0' cellpadding='0'>{$cardsHtml}</table>
                </td></tr>"
            : "<tr><td style='padding:0 36px 28px;'>
                    <div style='background:#fff7ed; border:1px dashed #fdba74; border-radius:14px; padding:18px; text-align:center;'>
                        <div style='font-size:22px;margin-bottom:6px;'>✨</div>
                        <div style='font-size:13px;color:#7c2d12;font-weight:700;'>Semana mais tranquila. Bora reaquecer? Abra o painel e dê o próximo passo.</div>
                    </div>
                </td></tr>";

        return "<!DOCTYPE html>
<html lang='pt-br'>
<head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'></head>
<body style='margin:0;padding:0;background:#f1f5f9;font-family:Inter,Segoe UI,Roboto,Arial,sans-serif;'>
<table width='100%' border='0' cellspacing='0' cellpadding='0'>
<tr><td align='center' style='padding:40px 0;'>
<table width='600' border='0' cellspacing='0' cellpadding='0' style='background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.08);border:1px solid #e2e8f0;'>

    <!-- Cabeçalho -->
    <tr><td style='background:linear-gradient(135deg,{$color},#3730a3);padding:36px;text-align:center;'>
        <div style='color:rgba(255,255,255,0.75);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:2px;margin-bottom:8px;'>Sua semana na Vivensi</div>
        <h1 style='color:#fff;margin:0;font-size:24px;font-weight:900;letter-spacing:-0.5px;'>{$orgName}</h1>
        <div style='color:rgba(255,255,255,0.75);font-size:13px;margin-top:6px;font-weight:600;'>{$weekLabel}</div>
    </td></tr>

    <!-- Saudação motivacional -->
    <tr><td style='padding:32px 36px 16px;'>
        <p style='font-size:18px;color:#1e293b;margin:0 0 8px;font-weight:800;'>Olá, {$userName}! 🙌</p>
        <p style='font-size:14px;color:#475569;margin:0;line-height:1.7;'>Toda semana, um pouquinho mais perto do impacto que vocês querem deixar no mundo. Aqui vai um abraço do Bruce e um resumo do que rolou:</p>
    </td></tr>

    <!-- Bruce: mensagem motivacional + dica -->
    <tr><td style='padding:0 36px 24px;'>
        <div style='background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border:1px solid #bae6fd;border-radius:14px;padding:22px;'>
            <div style='display:flex;align-items:center;gap:10px;margin-bottom:12px;'>
                <span style='font-size:20px;'>🤖</span>
                <strong style='font-size:12px;color:#0369a1;text-transform:uppercase;letter-spacing:1.5px;'>Bruce — Recado da semana</strong>
            </div>
            <p style='font-size:14px;color:#0c4a6e;line-height:1.7;margin:0;'>{$aiText}</p>
        </div>
    </td></tr>

    {$kpiSection}

    <!-- CTA -->
    <tr><td style='padding:0 36px 36px;text-align:center;'>
        <a href='{$panelUrl}' style='background:{$color};color:#fff;padding:14px 32px;border-radius:12px;text-decoration:none;font-weight:800;font-size:14px;display:inline-block;box-shadow:0 6px 16px rgba(79,70,229,0.3);'>
            Continuar a Jornada →
        </a>
    </td></tr>

    <!-- Rodapé -->
    <tr><td style='background:#f8fafc;padding:20px 36px;text-align:center;border-top:1px solid #e2e8f0;'>
        <p style='font-size:12px;color:#94a3b8;margin:0 0 6px;'>© {$year} <strong>Vivensi</strong>. Tecnologia e Propósito.</p>
        <p style='font-size:11px;color:#cbd5e1;margin:0;'>Para pausar este recado semanal, acesse Configurações → Identidade Visual no painel.</p>
    </td></tr>

</table>
</td></tr></table>
</body></html>";
    }
}
