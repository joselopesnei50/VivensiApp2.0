<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P3 (2026-08-05) — auto-assign round-robin de chats WhatsApp.
 *
 * - whatsapp_configs.auto_assign_mode: off | round_robin (per-tenant, default off)
 * - users.last_auto_assigned_at: usado como cursor round-robin (quem foi
 *   atribuido ha mais tempo entra primeiro). Nulo = nunca recebeu.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('whatsapp_configs', function (Blueprint $table) {
            $table->string('auto_assign_mode', 20)->default('off')->after('ai_enabled');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_auto_assigned_at')->nullable()->after('availability_changed_at');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_configs', function (Blueprint $table) {
            $table->dropColumn('auto_assign_mode');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_auto_assigned_at');
        });
    }
};
