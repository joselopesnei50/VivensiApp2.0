<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lgpd_data_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['export', 'delete', 'rectify', 'restrict']);
            $table->enum('status', ['pending', 'processing', 'completed', 'rejected'])->default('pending');
            $table->string('ip_address', 45)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lgpd_data_requests');
    }
};
