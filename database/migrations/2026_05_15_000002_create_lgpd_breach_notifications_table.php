<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lgpd_breach_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['identified', 'contained', 'notified_anpd', 'closed'])->default('identified');
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('identified_at')->useCurrent();
            $table->timestamp('anpd_notified_at')->nullable();
            // LGPD Art. 48: notificação à ANPD em prazo razoável (interpretado como 72h a 2 dias)
            $table->boolean('anpd_required')->default(false);
            $table->text('affected_data_types')->nullable();
            $table->integer('estimated_affected_count')->nullable();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->text('remediation_actions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lgpd_breach_notifications');
    }
};
