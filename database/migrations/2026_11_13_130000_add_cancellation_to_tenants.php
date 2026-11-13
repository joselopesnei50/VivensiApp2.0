<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Cancelamento de assinatura pelo cliente (Minha conta → Faturas). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'cancel_requested_at')) {
                $table->timestamp('cancel_requested_at')->nullable();
                $table->string('cancel_reason', 500)->nullable();
                $table->date('access_until')->nullable();   // acesso até o fim do período pago
                $table->timestamp('canceled_at')->nullable(); // quando o acesso terminou de fato
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'cancel_requested_at')) {
                $table->dropColumn(['cancel_requested_at', 'cancel_reason', 'access_until', 'canceled_at']);
            }
        });
    }
};
