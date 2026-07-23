<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radar_territories', function (Blueprint $table) {
            $table->id();
            $table->string('ibge_code', 7)->unique();
            $table->string('name');
            $table->string('uf', 2);
            $table->boolean('active')->default(true);
            $table->timestamp('last_collected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radar_territories');
    }
};
