<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // transactions: queries de dashboard sempre filtram tenant+type+status+date
        $this->safeIndex('transactions', ['tenant_id', 'type', 'status'], 'idx_tx_tenant_type_status');
        $this->safeIndex('transactions', ['tenant_id', 'approval_status'], 'idx_tx_tenant_approval');

        // tasks: queries de tarefas vencidas e por responsável
        $this->safeIndex('tasks', ['tenant_id', 'status', 'due_date'], 'idx_tasks_tenant_status_due');
        $this->safeIndex('tasks', ['tenant_id', 'assigned_to', 'status'], 'idx_tasks_tenant_assignee_status');

        // whatsapp_messages: analytics de volume por direção/data
        if (Schema::hasTable('whatsapp_messages')) {
            $this->safeIndex('whatsapp_messages', ['chat_id', 'direction'], 'idx_wa_msg_chat_direction');
            $this->safeIndex('whatsapp_messages', ['tenant_id', 'direction'], 'idx_wa_msg_tenant_direction');
        }

        // audit_logs: relatórios de auditoria por tenant+data
        if (Schema::hasTable('audit_logs')) {
            $this->safeIndex('audit_logs', ['tenant_id', 'created_at'], 'idx_audit_tenant_created');
            $this->safeIndex('audit_logs', ['tenant_id', 'auditable_type', 'event'], 'idx_audit_tenant_type_event');
        }

        // ngo_donors: busca por tenant + tipo
        if (Schema::hasTable('ngo_donors')) {
            $this->safeIndex('ngo_donors', ['tenant_id', 'type'], 'idx_ngo_donors_tenant_type');
        }

        // ngo_grants: busca por tenant + status + deadline
        if (Schema::hasTable('ngo_grants')) {
            $this->safeIndex('ngo_grants', ['tenant_id', 'status'], 'idx_ngo_grants_tenant_status');
            $this->safeIndex('ngo_grants', ['tenant_id', 'deadline'], 'idx_ngo_grants_tenant_deadline');
        }
    }

    public function down(): void
    {
        $drop = fn(string $table, string $index) =>
            Schema::hasTable($table)
                ? Schema::table($table, fn(Blueprint $t) => $t->dropIndexIfExists($index))
                : null;

        $drop('transactions', 'idx_tx_tenant_type_status');
        $drop('transactions', 'idx_tx_tenant_approval');
        $drop('tasks', 'idx_tasks_tenant_status_due');
        $drop('tasks', 'idx_tasks_tenant_assignee_status');
        $drop('whatsapp_messages', 'idx_wa_msg_chat_direction');
        $drop('whatsapp_messages', 'idx_wa_msg_tenant_direction');
        $drop('audit_logs', 'idx_audit_tenant_created');
        $drop('audit_logs', 'idx_audit_tenant_type_event');
        $drop('ngo_donors', 'idx_ngo_donors_tenant_type');
        $drop('ngo_grants', 'idx_ngo_grants_tenant_status');
        $drop('ngo_grants', 'idx_ngo_grants_tenant_deadline');
    }

    private function safeIndex(string $table, array $columns, string $name): void
    {
        if (!Schema::hasTable($table)) return;
        Schema::table($table, function (Blueprint $t) use ($columns, $name, $table) {
            try {
                $t->index($columns, $name);
            } catch (\Throwable) {
                // índice já existe — ignorar
            }
        });
    }
};
