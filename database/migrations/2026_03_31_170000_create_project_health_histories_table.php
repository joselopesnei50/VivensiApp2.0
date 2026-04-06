<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('project_health_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->integer('financial_score')->default(100);
            $table->integer('execution_score')->default(100);
            $table->integer('team_score')->default(100);
            $table->integer('compliance_score')->default(100);
            $table->integer('overall_score')->default(100);
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_health_histories');
    }
};
