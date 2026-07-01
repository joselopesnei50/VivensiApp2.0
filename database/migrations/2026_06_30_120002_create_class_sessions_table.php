<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('class_sessions')) {
            return;
        }

        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            $table->string('title');
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            // FK omitida de proposito: mesmo padrao de attendances.user_id — evita erro se usuario for deletado.
            $table->unsignedBigInteger('teacher_user_id')->nullable()->index();

            $table->enum('mode', ['fechada', 'aberta'])->default('fechada');

            // Token publico cifrado at-rest (Crypt::encryptString) — payload > 64 chars.
            $table->text('public_token')->nullable();
            $table->string('public_token_bidx', 64)->nullable()->index();

            $table->timestamp('public_enabled_until')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'project_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
