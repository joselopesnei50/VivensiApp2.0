<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('subject');
            $table->longText('html_content');
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->nullable();
            $table->enum('audience_type', ['tenant_admins', 'all_users', 'leads', 'all'])->default('tenant_admins');
            $table->integer('recipient_count')->default(0);
            $table->bigInteger('brevo_list_id')->nullable();
            $table->bigInteger('brevo_campaign_id')->nullable();
            $table->enum('status', ['draft', 'sending', 'sent', 'scheduled', 'error'])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            // Métricas Brevo
            $table->integer('stat_delivered')->nullable();
            $table->integer('stat_opens')->nullable();
            $table->integer('stat_clicks')->nullable();
            $table->integer('stat_bounces')->nullable();
            $table->integer('stat_unsubscribes')->nullable();
            $table->integer('stat_spam')->nullable();
            $table->timestamp('stats_fetched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
    }
};
