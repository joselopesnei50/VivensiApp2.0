<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Models\WhatsappConfig;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

/**
 * ProjectAlertService
 *
 * Envia alertas de prazo e orçamento por WhatsApp para o gestor do projeto.
 * Integrado ao Kernel para rodar diariamente às 08:00.
 */
class ProjectAlertService
{
    /**
     * Verifica todos os projetos ativos do tenant e dispara alertas necessários.
     */
    public function runAlertsForTenant(int $tenantId): array
    {
        $config = WhatsappConfig::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (!$config) {
            return ['sent' => 0, 'reason' => 'no_whatsapp_config'];
        }

        // Busca o gestor principal do tenant (role manager ou ngo)
        $manager = User::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('role', ['manager', 'ngo'])
            ->whereNotNull('phone')
            ->first();

        if (!$manager) {
            return ['sent' => 0, 'reason' => 'no_manager_with_phone'];
        }

        $projects = Project::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->get();

        $sent = 0;

        foreach ($projects as $project) {
            $alerts = $this->buildAlerts($project, $tenantId);

            if (!empty($alerts)) {
                $result = $this->sendAlertMessage($manager, $config, $project, $alerts);
                if ($result) {
                    $sent++;
                    sleep(rand(5, 10)); // Anti-ban
                }
            }
        }

        return ['sent' => $sent, 'projects_checked' => $projects->count()];
    }

    /**
     * Avalia um projeto e retorna lista de alertas ativos.
     */
    protected function buildAlerts(Project $project, int $tenantId): array
    {
        $alerts = [];

        // Alerta de prazo
        if ($project->end_date) {
            $daysLeft = now()->diffInDays($project->end_date, false);

            if ($daysLeft < 0) {
                $alerts[] = "❌ *Prazo VENCIDO* há " . abs((int)$daysLeft) . " dia(s)";
            } elseif ($daysLeft <= 7) {
                $alerts[] = "⏰ *Prazo crítico:* apenas " . (int)$daysLeft . " dia(s) restantes";
            }
        }

        // Alerta de orçamento
        if ($project->budget > 0) {
            $spent = Transaction::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('project_id', $project->id)
                ->where('type', 'expense')
                ->where('status', 'paid')
                ->sum('amount');

            $percent = ($spent / $project->budget) * 100;

            if ($percent >= 100) {
                $alerts[] = "💸 *Orçamento ESGOTADO:* R$ " . number_format($spent, 0, ',', '.') . " / R$ " . number_format($project->budget, 0, ',', '.');
            } elseif ($percent >= 90) {
                $alerts[] = "⚠️ *Orçamento crítico:* " . round($percent, 0) . "% consumido";
            }
        }

        // Alerta de tarefas vencidas
        $overdueTasks = \App\Models\Task::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('project_id', $project->id)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['done', 'completed'])
            ->count();

        if ($overdueTasks > 0) {
            $alerts[] = "📋 *" . $overdueTasks . " tarefa(s) vencida(s)* sem conclusão";
        }

        return $alerts;
    }

    /**
     * Envia a mensagem de alerta via WhatsApp.
     */
    protected function sendAlertMessage(User $manager, WhatsappConfig $config, Project $project, array $alerts): bool
    {
        $evo      = new EvolutionApiService($config);
        $phone    = preg_replace('/\D/', '', $manager->phone);
        $firstName = explode(' ', $manager->name)[0];

        if (!str_starts_with($phone, '55')) {
            $phone = '55' . $phone;
        }

        $alertLines = implode("\n", array_map(fn($a) => "  • {$a}", $alerts));
        $projectUrl = url('/projects/' . $project->id);

        $message = "Olá, *{$firstName}*! 🔔\n\n"
            . "Alertas do projeto *{$project->name}*:\n\n"
            . $alertLines . "\n\n"
            . "🔗 Acesse o projeto: {$projectUrl}\n\n"
            . "_Vivensi · Sistema de Gestão_";

        try {
            $result = $evo->sendMessage($phone, $message, null, 3);

            if (isset($result['error'])) {
                Log::warning('ProjectAlertService: falha no envio', [
                    'project_id' => $project->id,
                    'error'      => $result['error'],
                ]);
                return false;
            }

            Log::info('ProjectAlertService: alerta enviado', [
                'project_id' => $project->id,
                'phone'      => $phone,
                'alerts'     => $alerts,
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('ProjectAlertService: exceção', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
