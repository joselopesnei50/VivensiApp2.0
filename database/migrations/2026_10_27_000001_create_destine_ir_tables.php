<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destine_ir_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_published')->default(false);
            $table->string('hero_title', 255)->default('Deixe seu imposto em Araraquara e ajude centenas de crianças e adolescentes.');
            $table->string('hero_subtitle', 500)->default('Parte do seu Imposto de Renda pode ficar na nossa cidade, sem você pagar nada a mais.');
            $table->string('deadline_label', 120)->default('Até 31 de dezembro para destinar até 6%');
            $table->date('deadline_date')->nullable();

            $table->string('video_vertical_url', 500)->nullable();
            $table->string('video_horizontal_url', 500)->nullable();
            $table->string('video_poster_url', 500)->nullable();

            $table->string('fund_name', 255)->default('Fundo Municipal dos Direitos da Criança e do Adolescente de Araraquara');
            $table->string('fund_cnpj', 32)->default('17.837.230/0001-02');
            $table->string('fund_email', 160)->default('cmdca.araraquara@gmail.com');
            $table->string('council_name', 255)->default('COMCRIAR — Conselho Municipal dos Direitos da Criança e do Adolescente de Araraquara');

            // Textos de seções — editáveis sem precisar virar código
            $table->longText('what_is_text')->nullable();
            $table->longText('who_can_text')->nullable();
            $table->json('path1_steps')->nullable();       // caminho 1 (até 31/12)
            $table->json('path2_steps')->nullable();       // caminho 2 (declaração)
            $table->longText('cost_text')->nullable();
            $table->json('faq_items')->nullable();         // [{q, a}]
            $table->string('footer_tagline', 300)->default('Uma ação da Vivensi para colaborar com as entidades que cuidam de gente.');
            $table->longText('footer_legal_text')->nullable();

            // Open Graph (preview WhatsApp/Insta/Face)
            $table->string('og_title', 255)->nullable();
            $table->string('og_description', 500)->nullable();
            $table->string('og_image_url', 500)->nullable();

            // Toggles das 5 melhorias
            $table->boolean('show_deadline_banner')->default(true);
            $table->boolean('show_simulator')->default(true);
            $table->boolean('show_partners_wall')->default(true);
            $table->boolean('show_share_bar')->default(true);

            $table->unsignedBigInteger('total_visits')->default(0);

            $table->timestamps();
        });

        Schema::create('destine_ir_partners', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('name', 255);
            $table->string('logo_url', 500)->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->string('contact_name', 160)->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->string('contact_email', 160)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedBigInteger('visits_count')->default(0);
            $table->unsignedBigInteger('shares_count')->default(0);
            $table->timestamp('last_visit_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('destine_ir_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->nullable()->constrained('destine_ir_partners')->nullOnDelete();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('referer', 500)->nullable();
            $table->string('utm_source', 120)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['partner_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destine_ir_visits');
        Schema::dropIfExists('destine_ir_partners');
        Schema::dropIfExists('destine_ir_settings');
    }
};
