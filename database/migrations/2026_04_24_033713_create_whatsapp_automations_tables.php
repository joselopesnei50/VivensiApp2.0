<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_automations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->enum('trigger', [
                'no_contact_days',
                'open_conversation_days',
                'donor_inactive_days',
                'sponsorship_stale_days',
            ]);
            $table->unsignedInteger('trigger_days');
            $table->text('message_template');
            $table->enum('audience', ['all', 'donors', 'sponsors', 'contacts'])->default('all');
            $table->boolean('is_active')->default(true);
            $table->time('send_window_start')->default('08:00');
            $table->time('send_window_end')->default('20:00');
            $table->timestamps();
        });

        Schema::create('whatsapp_automation_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('automation_id')->index();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('contact_phone');
            $table->string('contact_name')->nullable();
            $table->text('message_sent');
            $table->enum('status', ['sent', 'failed'])->default('sent');
            $table->string('error_message')->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->foreign('automation_id')->references('id')->on('whatsapp_automations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_automation_logs');
        Schema::dropIfExists('whatsapp_automations');
    }
};
