<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->enum('origin', ['manual', 'demo_agendada', 'indicacao'])->default('manual');
            $table->foreignId('plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            $table->decimal('estimated_value', 10, 2)->nullable();
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('next_step')->nullable();
            $table->date('next_step_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('stage_id')->constrained('sales_stages');
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('meeting_booking_id')->nullable()->constrained('meeting_bookings')->nullOnDelete();
            $table->foreignId('converted_tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['stage_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_leads');
    }
};
