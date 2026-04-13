<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Contas sociais conectadas por tenant
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('platform')->default('facebook'); // facebook | instagram
            $table->string('page_id');
            $table->string('page_name');
            $table->string('page_picture')->nullable();
            $table->text('access_token');
            $table->timestamp('token_expires_at')->nullable();
            $table->string('instagram_business_id')->nullable();
            $table->string('instagram_username')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
            $table->unique(['tenant_id', 'page_id']);
        });

        // Posts agendados
        Schema::create('scheduled_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // quem criou
            $table->string('platform'); // facebook | instagram | both
            $table->text('caption');
            $table->string('media_url')->nullable();   // URL pública da mídia
            $table->string('media_type')->default('none'); // none | image | video
            $table->timestamp('scheduled_at');
            $table->string('status')->default('scheduled'); // scheduled | published | failed | cancelled
            $table->string('facebook_post_id')->nullable();
            $table->string('instagram_post_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['scheduled_at', 'status']);
        });

        // Métricas dos posts publicados
        Schema::create('post_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduled_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('likes')->default(0);
            $table->unsignedInteger('comments')->default(0);
            $table->unsignedInteger('shares')->default(0);
            $table->unsignedInteger('reach')->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_metrics');
        Schema::dropIfExists('scheduled_posts');
        Schema::dropIfExists('social_accounts');
    }
};
