<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('format')->default('facebook_post');
            $table->unsignedInteger('width')->default(1200);
            $table->unsignedInteger('height')->default(628);
            $table->json('settings')->nullable();
            $table->string('thumbnail')->nullable();
            $table->foreignId('scheduled_post_id')->nullable()->constrained('scheduled_posts')->nullOnDelete();
            $table->timestamps();
            $table->index('tenant_id');
        });

        Schema::create('banner_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banner_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->json('content')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index('banner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_sections');
        Schema::dropIfExists('banners');
    }
};
