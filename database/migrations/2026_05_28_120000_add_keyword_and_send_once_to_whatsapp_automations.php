<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_automations', function (Blueprint $table) {
            $table->string('keyword', 100)->nullable()->after('trigger_days');
            $table->boolean('send_once')->default(false)->after('send_window_end');
        });

        // Adiciona novos valores ao ENUM de trigger (MySQL only — SQLite usa TEXT)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE whatsapp_automations MODIFY COLUMN `trigger` ENUM(
                'no_contact_days',
                'open_conversation_days',
                'donor_inactive_days',
                'sponsorship_stale_days',
                'after_opt_in_days',
                'keyword_received'
            ) NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::table('whatsapp_automations', function (Blueprint $table) {
            $table->dropColumn(['keyword', 'send_once']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE whatsapp_automations MODIFY COLUMN `trigger` ENUM(
                'no_contact_days',
                'open_conversation_days',
                'donor_inactive_days',
                'sponsorship_stale_days'
            ) NOT NULL");
        }
    }
};
