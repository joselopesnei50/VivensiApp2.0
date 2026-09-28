<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('location', 200)->nullable();

            $table->date('starts_on');
            $table->string('starts_at', 5)->nullable();
            $table->string('ends_at', 5)->nullable();
            $table->boolean('all_day')->default(false);

            $table->enum('status', ['pending', 'done', 'cancelled'])->default('pending');
            $table->enum('kind', ['meeting', 'visit', 'call', 'deadline', 'renewal', 'other'])->default('meeting');
            $table->string('color', 20)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'starts_on']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_events');
    }
};
