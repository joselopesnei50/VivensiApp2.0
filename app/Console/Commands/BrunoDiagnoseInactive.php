<?php

namespace App\Console\Commands;

use App\Models\BrunoHandoff;
use App\Models\SystemSetting;
use App\Models\WhatsappChat;
use Illuminate\Console\Command;

/**
 * Diagnostica/reativa Bruno em chats da Vivensi (tenant super admin) onde
 * is_bot_active=false. Util quando cliente fala pelo whatsapp e Bruno nao
 * responde porque conversa foi auto-escalada em algum momento passado.
 */
class BrunoDiagnoseInactive extends Command
{
    protected $signature = 'bruno:diagnose-inactive
        {--days=14 : janela em dias pra listar chats com atividade recente}
        {--reactivate= : chat_id especifico pra reativar (is_bot_active=true, limpa assigned_to)}
        {--reactivate-all : reativa TODOS os chats inativos no tenant Bruno (pede confirmacao)}';

    protected $description = 'Lista chats da Vivensi onde Bruno esta desativado + handoff aberto; opcional reativar';

    public function handle(): int
    {
        $tenantId = (int) SystemSetting::getValue('bruno_sales_bot_tenant_id', 0);
        if ($tenantId === 0) {
            $this->error('SystemSetting bruno_sales_bot_tenant_id nao configurado.');
            return self::FAILURE;
        }
        $this->line("Tenant Bruno: <info>$tenantId</info>");

        // Reativar chat especifico
        if ($chatId = $this->option('reactivate')) {
            return $this->reactivateOne($tenantId, (int) $chatId);
        }

        // Diagnostico
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $inactive = WhatsappChat::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_bot_active', false)
            ->where(function ($q) use ($cutoff) {
                $q->where('last_inbound_at', '>=', $cutoff)
                  ->orWhere('updated_at', '>=', $cutoff);
            })
            ->orderByDesc('last_inbound_at')
            ->get(['id', 'wa_id', 'contact_name', 'assigned_to', 'last_inbound_at', 'opt_out_at', 'blocked_at', 'updated_at']);

        $this->newLine();
        $this->line("<comment>Chats com Bruno desativado (ultimos {$days} dias): " . $inactive->count() . "</comment>");

        if ($inactive->isEmpty()) {
            $this->info('Nenhum chat afetado — Bruno nao bloqueou ninguem nesse periodo.');
            return self::SUCCESS;
        }

        $rows = [];
        foreach ($inactive as $c) {
            $handoff = BrunoHandoff::withoutGlobalScope('tenant')
                ->where('whatsapp_chat_id', $c->id)
                ->latest('id')
                ->first();
            $rows[] = [
                $c->id,
                $c->wa_id,
                substr((string) $c->name, 0, 20),
                $c->assigned_to ?: '-',
                $c->last_inbound_at?->format('d/m H:i') ?? '-',
                $handoff ? "{$handoff->status} ({$handoff->created_at->format('d/m H:i')})" : '-',
                $c->opt_out_at ? 'OPT-OUT' : ($c->blocked_at ? 'BLOCKED' : 'ok'),
            ];
        }

        $this->table(
            ['chat_id', 'wa_id', 'nome', 'assigned_to', 'ultimo inbound', 'handoff', 'flag'],
            $rows
        );

        if ($this->option('reactivate-all')) {
            return $this->reactivateAll($tenantId, $inactive);
        }

        $this->newLine();
        $this->comment('Pra reativar 1 chat: --reactivate=<chat_id>');
        $this->comment('Pra reativar todos: --reactivate-all');

        return self::SUCCESS;
    }

    private function reactivateOne(int $tenantId, int $chatId): int
    {
        $chat = WhatsappChat::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('id', $chatId)
            ->first();

        if (!$chat) {
            $this->error("Chat $chatId nao encontrado no tenant Bruno.");
            return self::FAILURE;
        }

        $chat->update([
            'is_bot_active' => true,
            'assigned_to'   => null,
        ]);

        // Marca handoff pendente como resolvido pra limpar inbox do humano
        BrunoHandoff::withoutGlobalScope('tenant')
            ->where('whatsapp_chat_id', $chat->id)
            ->whereIn('status', [BrunoHandoff::STATUS_PENDING, BrunoHandoff::STATUS_ASSUMED])
            ->update([
                'status'          => BrunoHandoff::STATUS_RESOLVED,
                'resolved_at'     => now(),
                'resolution_note' => 'Reativado via bruno:diagnose-inactive',
            ]);

        $this->info("Chat $chatId reativado (is_bot_active=true, assigned_to=null, handoff resolvido).");
        return self::SUCCESS;
    }

    private function reactivateAll(int $tenantId, $inactive): int
    {
        if (!$this->confirm("Reativar Bruno em {$inactive->count()} chats?", false)) {
            $this->line('Abortado.');
            return self::SUCCESS;
        }
        $ids = $inactive->pluck('id')->all();

        $affected = WhatsappChat::withoutGlobalScope('tenant')
            ->whereIn('id', $ids)
            ->update(['is_bot_active' => true, 'assigned_to' => null]);

        BrunoHandoff::withoutGlobalScope('tenant')
            ->whereIn('whatsapp_chat_id', $ids)
            ->whereIn('status', [BrunoHandoff::STATUS_PENDING, BrunoHandoff::STATUS_ASSUMED])
            ->update([
                'status'          => BrunoHandoff::STATUS_RESOLVED,
                'resolved_at'     => now(),
                'resolution_note' => 'Reativado em lote via bruno:diagnose-inactive',
            ]);

        $this->info("Reativados $affected chats + handoffs pendentes marcados como resolvidos.");
        return self::SUCCESS;
    }
}
