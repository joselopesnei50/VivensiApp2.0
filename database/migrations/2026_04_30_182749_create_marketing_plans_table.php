<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('marketing_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title')->nullable();

            // Briefing
            $table->text('objective');
            $table->text('target_audience');
            $table->string('scope')->default('online'); // online | online_offline
            $table->text('competitor_links')->nullable();
            $table->string('budget_range')->nullable();
            $table->string('tone')->default('professional'); // professional | friendly | inspirational | urgent
            $table->boolean('has_whatsapp_groups')->default(false);
            $table->text('extra_info')->nullable();

            // IA Output
            $table->json('mindmap_data')->nullable(); // markmap markdown string
            $table->string('ai_provider')->nullable(); // gemini | deepseek
            $table->enum('status', ['pending', 'processing', 'done', 'failed'])->default('pending');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('marketing_plans');
    }
};
