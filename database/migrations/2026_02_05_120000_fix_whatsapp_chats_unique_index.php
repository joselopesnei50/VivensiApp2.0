<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('whatsapp_chats', function (Blueprint $table) {
            // In multi-tenant, the same WhatsApp ID can exist across different tenants.
            // Replace global unique(wa_id) with composite unique(tenant_id, wa_id).
            try {
                $table->dropUnique('whatsapp_chats_wa_id_unique');
            } catch (\Throwable $e) {
                // ignore if index name differs or already dropped
            }
        });

        Schema::table('whatsapp_chats', function (Blueprint $table) {
            $table->unique(['tenant_id', 'wa_id'], 'whatsapp_chats_tenant_wa_unique');
        });
    }

    public function down()
    {
        Schema::table('whatsapp_chats', function (Blueprint $table) {
            try {
                $table->dropUnique('whatsapp_chats_tenant_wa_unique');
            } catch (\Throwable $e) {
            }
        });

        Schema::table('whatsapp_chats', function (Blueprint $table) {
            // Restore previous global unique constraint
            $table->unique('wa_id');
        });
    }
};

