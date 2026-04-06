<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Configurações do Portal da Transparência
        Schema::create('transparency_portals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('slug')->unique();
            $table->string('title')->nullable();
            $table->string('cnpj')->nullable();
            $table->text('mission')->nullable();
            $table->text('vision')->nullable();
            $table->text('values')->nullable();
            $table->string('sic_email')->nullable();
            $table->string('sic_phone')->nullable();
            $table->json('settings')->nullable(); // Cores, customizações
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            // Link com tenants (independente se a tabela foi vista agora ou não, o sistema usa)
            // Se falhar no seu ambiente por falta da tabela tenants, remova a FK no teste
            // $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // Corpo Diretivo (Diretoria)
        Schema::create('transparency_board', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('position');
            $table->string('tenure_start')->nullable();
            $table->string('tenure_end')->nullable();
            $table->text('bio')->nullable();
            $table->string('photo_url')->nullable();
            $table->timestamps();
        });

        // Documentos e Compliance (Estatuto, Atas, Balanços)
        Schema::create('transparency_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('title');
            $table->enum('type', ['statute', 'election_minutes', 'financial_balance', 'activity_report', 'tax_certificate', 'audit', 'other']);
            $table->string('file_path');
            $table->integer('year')->nullable();
            $table->date('document_date')->nullable();
            $table->timestamps();
        });

        // Parcerias Públicas (MROSC)
        Schema::create('public_partnerships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('agency_name'); // Órgão Público
            $table->string('project_name');
            $table->decimal('value', 15, 2);
            $table->string('gazette_link')->nullable(); // Link Diário Oficial
            $table->enum('status', ['active', 'concluded', 'analysis', 'pending'])->default('active');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('public_partnerships');
        Schema::dropIfExists('transparency_documents');
        Schema::dropIfExists('transparency_board');
        Schema::dropIfExists('transparency_portals');
    }
};
