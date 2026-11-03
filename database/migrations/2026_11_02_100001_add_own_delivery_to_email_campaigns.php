<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Envio próprio de campanhas (super admin) — sai do marketing da Brevo.
 *
 *  - email_campaigns.delivery_driver: 'brevo' (legado, Manager/NGO) | 'own' (SES/SMTP próprio)
 *  - email_campaigns.status ganha 'paused' (pausa manual ou freio automático de bounce/reclamação)
 *  - email_campaign_recipients: 1 linha por destinatário — fila de envio + métricas próprias
 *  - email_suppressions: bloqueio global (hard bounce, reclamação de spam, manual)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->string('delivery_driver', 10)->default('brevo')->after('status');
            $table->unsignedSmallInteger('send_rate_per_minute')->nullable()->after('delivery_driver');
            $table->unsignedInteger('stat_sent')->nullable()->after('stat_spam');
            $table->unsignedInteger('stat_failed')->nullable()->after('stat_sent');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE email_campaigns MODIFY COLUMN status ENUM('draft','sending','sent','scheduled','error','paused') NOT NULL DEFAULT 'draft'");
        }

        Schema::create('email_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_campaign_id')->constrained('email_campaigns')->cascadeOnDelete();
            $table->string('email', 190);
            $table->string('name', 190)->nullable();
            $table->string('token', 40)->unique();          // tracking (pixel, clique, descadastro)
            // queued → sending → sent | failed | skipped ; sent → bounced | complained
            $table->string('status', 12)->default('queued');
            $table->string('claim_token', 40)->nullable();
            $table->string('provider_message_id', 190)->nullable()->index();
            $table->string('error', 500)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('complained_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->unsignedInteger('open_count')->default(0);
            $table->timestamp('clicked_at')->nullable();
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->unique(['email_campaign_id', 'email'], 'uq_campaign_recipient_email');
            $table->index(['email_campaign_id', 'status'], 'idx_campaign_recipient_status');
            $table->index('sent_at');
        });

        Schema::create('email_suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('email', 190)->unique();
            $table->string('reason', 20);           // hard_bounce | complaint | unsubscribe | manual
            $table->string('detail', 500)->nullable();
            $table->foreignId('email_campaign_id')->nullable()->constrained('email_campaigns')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_suppressions');
        Schema::dropIfExists('email_campaign_recipients');

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("UPDATE email_campaigns SET status = 'error' WHERE status = 'paused'");
            DB::statement("ALTER TABLE email_campaigns MODIFY COLUMN status ENUM('draft','sending','sent','scheduled','error') NOT NULL DEFAULT 'draft'");
        }

        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->dropColumn(['delivery_driver', 'send_rate_per_minute', 'stat_sent', 'stat_failed']);
        });
    }
};
