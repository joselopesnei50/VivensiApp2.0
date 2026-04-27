<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    // [AUDIT A08 - MÉDIO] Índice composto (raffle_id, status).
    // Queries como WHERE raffle_id = ? AND status = 'available' (usadas em toda reserva)
    // faziam full scan sem este índice. Em rifas com 10.000 bilhetes isso é crítico.
    public function up()
    {
        Schema::table('raffle_tickets', function (Blueprint $table) {
            $table->index(['raffle_id', 'status'], 'idx_raffle_tickets_raffle_status');
        });
    }

    public function down()
    {
        Schema::table('raffle_tickets', function (Blueprint $table) {
            $table->dropIndex('idx_raffle_tickets_raffle_status');
        });
    }
};
