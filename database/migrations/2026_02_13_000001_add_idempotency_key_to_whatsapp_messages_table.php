<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->string('idempotency_key', 100)->nullable()->unique()->after('message_id');
            $table->string('status', 50)->nullable()->after('type'); // pending, delivered, read, failed
            $table->index('idempotency_key');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropIndex(['idempotency_key']);
            $table->dropColumn(['idempotency_key', 'status']);
        });
    }
};
