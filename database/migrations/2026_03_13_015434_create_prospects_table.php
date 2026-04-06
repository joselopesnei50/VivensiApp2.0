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
        Schema::create('prospects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index(); // Multi-tenancy
            $table->string('company_name');
            $table->string('category')->nullable();
            $table->string('address')->nullable();
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('google_rating', 3, 1)->default(0);
            $table->integer('total_reviews')->default(0);
            
            // Inteligência Artificial
            $table->integer('lead_score')->default(0);
            $table->text('ai_analysis')->nullable(); // Dor detectada
            $table->text('personalized_pitch')->nullable(); // Texto p/ WhatsApp
            
            $table->enum('status', ['raw', 'analyzed', 'contacted'])->default('raw');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prospects');
    }
};
