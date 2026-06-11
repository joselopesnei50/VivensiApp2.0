<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adiciona o valor 'pausada' ao enum de status da tabela campanhas.
     *
     * Necessário para o EnviarMensagemCampanhaJob (pipeline legado) pausar
     * a campanha quando a instância WhatsApp atinge limite anti-ban:
     * janela horária fora, MAX_PER_HOUR atingido, ou warming profile do dia
     * excedido. "pausada" permite retomada manual; diferente de "cancelada",
     * que é estado terminal.
     *
     * Operação compatível: ADICIONAR valor ao enum não invalida registros
     * existentes. Nenhum dado é tocado no up().
     */
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE campanhas MODIFY COLUMN status "
            . "ENUM('rascunho', 'agendada', 'processando', 'pausada', 'concluida', 'cancelada') "
            . "NOT NULL DEFAULT 'rascunho'"
        );
    }

    /**
     * Reversível: registros com status='pausada' viram 'cancelada' antes do
     * shrink do enum, evitando ERROR 1265 (data truncated).
     */
    public function down(): void
    {
        DB::statement("UPDATE campanhas SET status = 'cancelada' WHERE status = 'pausada'");
        DB::statement(
            "ALTER TABLE campanhas MODIFY COLUMN status "
            . "ENUM('rascunho', 'agendada', 'processando', 'concluida', 'cancelada') "
            . "NOT NULL DEFAULT 'rascunho'"
        );
    }
};
