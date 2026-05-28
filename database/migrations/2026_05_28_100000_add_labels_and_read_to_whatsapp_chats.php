<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_chats', function (Blueprint $table) {
            $table->timestamp('last_read_at')->nullable()->after('last_outbound_at');
            $table->json('labels')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_chats', function (Blueprint $table) {
            $table->dropColumn(['last_read_at', 'labels']);
        });
    }
};
