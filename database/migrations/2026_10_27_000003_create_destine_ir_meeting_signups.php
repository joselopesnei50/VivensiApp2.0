<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destine_ir_meeting_signups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('email', 160);
            $table->string('phone', 40)->nullable();
            $table->foreignId('partner_id')->nullable()->constrained('destine_ir_partners')->nullOnDelete();
            $table->boolean('lgpd_consent')->default(false);
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index('created_at');
            $table->index('notified_at');
        });

        // Adiciona textos editáveis + toggle no settings singleton
        Schema::table('destine_ir_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('destine_ir_settings', 'show_meeting_cta')) {
                $table->boolean('show_meeting_cta')->default(true)->after('show_share_bar');
            }
            if (!Schema::hasColumn('destine_ir_settings', 'meeting_title')) {
                $table->string('meeting_title', 255)->default('Participe de uma reunião online.')->after('show_meeting_cta');
            }
            if (!Schema::hasColumn('destine_ir_settings', 'meeting_description')) {
                $table->string('meeting_description', 500)->default('Entidades e doadores de IR se reúnem online pra tirar dúvidas e caminhar junto. Cadastre e enviaremos o dia e o link.')->after('meeting_title');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destine_ir_meeting_signups');
        Schema::table('destine_ir_settings', function (Blueprint $table) {
            foreach (['show_meeting_cta', 'meeting_title', 'meeting_description'] as $col) {
                if (Schema::hasColumn('destine_ir_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
