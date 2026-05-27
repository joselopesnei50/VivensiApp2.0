<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('volunteer_hour_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('volunteer_id');
            $table->integer('hours');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('logged_by')->nullable();
            $table->timestamps();

            $table->foreign('volunteer_id')->references('id')->on('volunteers')->onDelete('cascade');
            $table->index(['volunteer_id', 'created_at']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volunteer_hour_logs');
    }
};
