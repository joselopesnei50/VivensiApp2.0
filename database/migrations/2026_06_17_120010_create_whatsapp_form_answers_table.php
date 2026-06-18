<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_form_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('whatsapp_form_sessions')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('whatsapp_form_questions')->cascadeOnDelete();
            $table->string('field_key', 60); // duplicada da question pra simplificar export
            $table->text('answer_text');
            $table->json('raw_payload')->nullable(); // payload original (button id, list selection, etc)
            $table->timestamps();

            $table->index('session_id');
            $table->unique(['session_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_form_answers');
    }
};
