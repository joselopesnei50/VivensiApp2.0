<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_timeline_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('title');
            $table->text('content')->nullable();
            $table->enum('type', ['milestone', 'photo', 'video', 'status'])->default('status');
            $table->string('media_path')->nullable();
            $table->string('external_url')->nullable();
            $table->date('date')->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            // Assuming tenants table exists, but following Project.php pattern
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_timeline_records');
    }
};
