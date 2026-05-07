<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('chat_id')->index();
            $table->text('content');
            $table->timestamp('scheduled_at')->index();
            $table->timestamp('sent_at')->nullable();
            $table->string('status', 20)->default('pending')->index(); // pending, sent, failed, cancelled
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('chat_id')->references('id')->on('whatsapp_chats')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_whatsapp_messages');
    }
};
