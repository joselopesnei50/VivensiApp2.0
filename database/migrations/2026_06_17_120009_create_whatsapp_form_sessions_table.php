<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_form_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('chat_id')->constrained('whatsapp_chats')->cascadeOnDelete();
            $table->foreignId('form_id')->constrained('whatsapp_forms')->cascadeOnDelete();
            $table->foreignId('current_question_id')->nullable()->constrained('whatsapp_form_questions')->nullOnDelete();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('in_progress'); // in_progress | completed | cancelled | abandoned
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable(); // reservado pra Fase 5 (sem FK ainda — modelo não existe)
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            // Garante no máximo uma sessão "in_progress" por chat (negocia no Service via lock).
            $table->index(['chat_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_form_sessions');
    }
};
