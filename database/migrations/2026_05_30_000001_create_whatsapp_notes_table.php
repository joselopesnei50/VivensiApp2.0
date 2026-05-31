<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_notes')) {
            return;
        }

        Schema::create('whatsapp_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('chat_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('content');
            $table->string('type', 50)->default('manual');
            $table->timestamps();

            $table->foreign('chat_id')->references('id')->on('whatsapp_chats')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_notes');
    }
};
