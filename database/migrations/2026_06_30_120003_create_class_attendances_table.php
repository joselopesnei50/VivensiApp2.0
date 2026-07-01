<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('class_attendances')) {
            return;
        }

        Schema::create('class_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('class_session_id')->constrained('class_sessions')->cascadeOnDelete();
            $table->foreignId('project_person_id')->constrained('project_people')->cascadeOnDelete();

            $table->enum('status', ['presente', 'falta', 'falta_justificada']);
            $table->enum('checked_in_via', ['professor', 'link_publico', 'auto_cadastro']);
            $table->timestamp('checked_in_at')->nullable();

            // Sempre gravar hash_hmac('sha256', $ip, app.key). Nunca IP cru.
            $table->string('ip_hash')->nullable();

            $table->timestamps();

            $table->unique(['class_session_id', 'project_person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_attendances');
    }
};
