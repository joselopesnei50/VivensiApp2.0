<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raffle_tickets', function (Blueprint $table) {
            // Agrupa os números de uma mesma compra e dá acesso à página do PIX (link reabrível).
            if (!Schema::hasColumn('raffle_tickets', 'reservation_token')) {
                $table->string('reservation_token', 64)->nullable()->after('reserved_at')->index();
            }
            // Aviso para a ONG de que a reserva está perto de vencer (enviado uma vez).
            if (!Schema::hasColumn('raffle_tickets', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('reservation_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('raffle_tickets', function (Blueprint $table) {
            $table->dropColumn(['reservation_token', 'reminder_sent_at']);
        });
    }
};
