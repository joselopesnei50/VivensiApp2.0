<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();  // super admin user_id
            $table->string('action');                            // tenant.suspend, tenant.activate, tenant.delete, tenant.create
            $table->string('target_type')->default('tenant');
            $table->unsignedBigInteger('target_id')->nullable(); // ID do tenant afetado
            $table->string('target_name')->nullable();           // nome preservado mesmo após deleção
            $table->json('context')->nullable();                 // dados adicionais
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('admin_id');
            $table->index('action');
            $table->index('target_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
    }
};
