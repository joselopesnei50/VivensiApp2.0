<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_form_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('whatsapp_forms')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('field_key', 60);                              // chave estável para 'answers.field_key' (mapeia ao lead na Fase 5)
            $table->text('text');                                         // texto da pergunta
            $table->string('type', 20)->default('text');                  // text | number | yes_no | buttons | list
            $table->json('options')->nullable();                          // pra buttons/list: [{id, label}]
            $table->boolean('required')->default(true);
            $table->string('validation_regex', 200)->nullable();
            $table->integer('min_value')->nullable();
            $table->integer('max_value')->nullable();
            $table->timestamps();

            $table->index(['form_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_form_questions');
    }
};
