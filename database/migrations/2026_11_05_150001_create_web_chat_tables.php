<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chat do site: conversas de visitantes com o Bruno (e com a equipe quando
 * ele passa o atendimento). Canal próprio, sem custo por mensagem.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('secret_hash', 64);
            $table->string('status', 16)->default('bot'); // bot | waiting_human | human | closed
            $table->string('visitor_name', 120)->nullable();
            $table->string('visitor_email', 190)->nullable();
            $table->string('visitor_phone', 30)->nullable();
            $table->string('page_url', 500)->nullable();
            $table->string('page_title', 200)->nullable();
            $table->string('source', 12)->nullable();
            $table->string('device', 8)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('meeting_booking_id')->nullable();
            $table->unsignedInteger('visitor_messages')->default(0);
            $table->unsignedInteger('ai_replies')->default(0);
            $table->unsignedSmallInteger('unread_for_team')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('human_requested_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_message_at'], 'idx_web_chat_status_last');
            $table->index(['ip_address', 'created_at'], 'idx_web_chat_ip_date');
        });

        Schema::create('web_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('web_chat_conversation_id')->constrained('web_chat_conversations')->cascadeOnDelete();
            $table->string('sender', 8); // visitor | bot | agent | system
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['web_chat_conversation_id', 'id'], 'idx_web_chat_msg_conv');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_chat_messages');
        Schema::dropIfExists('web_chat_conversations');
    }
};
