<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_chats', function (Blueprint $table) {
            // JID exato validado pela Evolution API (resolve o "9º dígito" brasileiro)
            $table->string('wa_jid', 32)->nullable()->after('wa_id');
            $table->timestamp('whatsapp_validated_at')->nullable()->after('wa_jid');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_chats', function (Blueprint $table) {
            $table->dropColumn(['wa_jid', 'whatsapp_validated_at']);
        });
    }
};
