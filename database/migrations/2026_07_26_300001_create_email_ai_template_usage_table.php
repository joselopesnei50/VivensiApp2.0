<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cota mensal de geracao de template de e-mail via IA (DeepSeek).
// Uma linha por chamada consumida (auditavel). Contador do mes = COUNT WHERE year_month = 'YYYY-MM'.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_ai_template_usage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->char('year_month', 7); // formato YYYY-MM (ex: 2026-07)
            $table->timestamps();

            $table->index(['tenant_id', 'year_month'], 'eatu_tenant_month_idx');
            $table->index('user_id', 'eatu_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_ai_template_usage');
    }
};
