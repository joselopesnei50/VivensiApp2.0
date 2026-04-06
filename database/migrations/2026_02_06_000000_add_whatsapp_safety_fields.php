<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('whatsapp_chats', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_chats', 'opt_in_at')) {
                $table->timestamp('opt_in_at')->nullable()->after('last_message_at');
            }
            if (!Schema::hasColumn('whatsapp_chats', 'opt_out_at')) {
                $table->timestamp('opt_out_at')->nullable()->after('opt_in_at');
            }
            if (!Schema::hasColumn('whatsapp_chats', 'blocked_at')) {
                $table->timestamp('blocked_at')->nullable()->after('opt_out_at');
            }
            if (!Schema::hasColumn('whatsapp_chats', 'blocked_reason')) {
                $table->string('blocked_reason', 255)->nullable()->after('blocked_at');
            }
            if (!Schema::hasColumn('whatsapp_chats', 'last_outbound_at')) {
                $table->timestamp('last_outbound_at')->nullable()->after('blocked_reason');
            }
        });

        Schema::table('whatsapp_configs', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_configs', 'outbound_enabled')) {
                $table->boolean('outbound_enabled')->default(true)->after('ai_enabled');
            }
            if (!Schema::hasColumn('whatsapp_configs', 'require_opt_in')) {
                $table->boolean('require_opt_in')->default(true)->after('outbound_enabled');
            }
            if (!Schema::hasColumn('whatsapp_configs', 'max_outbound_per_minute')) {
                $table->unsignedSmallInteger('max_outbound_per_minute')->default(12)->after('require_opt_in');
            }
            if (!Schema::hasColumn('whatsapp_configs', 'min_outbound_delay_seconds')) {
                $table->unsignedSmallInteger('min_outbound_delay_seconds')->default(2)->after('max_outbound_per_minute');
            }
        });
    }

    public function down()
    {
        Schema::table('whatsapp_chats', function (Blueprint $table) {
            foreach (['last_outbound_at', 'blocked_reason', 'blocked_at', 'opt_out_at', 'opt_in_at'] as $col) {
                if (Schema::hasColumn('whatsapp_chats', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('whatsapp_configs', function (Blueprint $table) {
            foreach (['min_outbound_delay_seconds', 'max_outbound_per_minute', 'require_opt_in', 'outbound_enabled'] as $col) {
                if (Schema::hasColumn('whatsapp_configs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

