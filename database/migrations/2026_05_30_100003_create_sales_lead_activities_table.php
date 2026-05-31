<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('sales_leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['created', 'stage_changed', 'note_added', 'converted']);
            $table->text('content')->nullable();
            $table->timestamp('created_at')->useCurrent();
            // sem updated_at — atividades são imutáveis
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_lead_activities');
    }
};
