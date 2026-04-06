<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('whatsapp_chats', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_chats', 'last_inbound_at')) {
                $table->timestamp('last_inbound_at')->nullable()->after('last_message_at');
            }
        });

        Schema::table('whatsapp_configs', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_configs', 'enforce_24h_window')) {
                $table->boolean('enforce_24h_window')->default(true)->after('min_outbound_delay_seconds');
            }
            if (!Schema::hasColumn('whatsapp_configs', 'allow_templates_outside_window')) {
                $table->boolean('allow_templates_outside_window')->default(true)->after('enforce_24h_window');
            }
        });

        Schema::create('whatsapp_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('chat_id')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('actor_type', 20)->default('system'); // user|ai|system|webhook
            $table->string('event', 60); // outbound_allowed|outbound_blocked|outbound_error|compliance_action|webhook_inbound
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'chat_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_audit_logs');

        Schema::table('whatsapp_configs', function (Blueprint $table) {
            foreach (['allow_templates_outside_window', 'enforce_24h_window'] as $col) {
                if (Schema::hasColumn('whatsapp_configs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('whatsapp_chats', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_chats', 'last_inbound_at')) {
                $table->dropColumn('last_inbound_at');
            }
        });
    }
};

