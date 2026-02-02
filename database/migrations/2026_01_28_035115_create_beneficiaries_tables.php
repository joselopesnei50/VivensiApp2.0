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
        // Tabela de Beneficiários (Titular)
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('cpf')->nullable();
            $table->string('nis')->nullable(); // Número de Identificação Social
            $table->date('birth_date')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->enum('status', ['active', 'inactive', 'graduated'])->default('active'); // graduated = saiu da vulnabilidade
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // Tabela de Membros da Família
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('beneficiary_id');
            $table->string('name');
            $table->string('kinship'); // Parentesco: Filho, Esposa, Avó
            $table->date('birth_date')->nullable();
            $table->timestamps();

            $table->foreign('beneficiary_id')->references('id')->on('beneficiaries')->onDelete('cascade');
        });

        // Tabela de Atendimentos / Evolução
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('beneficiary_id');
            $table->unsignedBigInteger('user_id'); // Quem atendeu (Assistente Social, Psicólogo)
            $table->date('date');
            $table->string('type'); // Ex: Psicológico, Cesta Básica, Visita Domiciliar
            $table->text('description'); // Descrição / Evolução do caso
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('beneficiary_id')->references('id')->on('beneficiaries')->onDelete('cascade');
            // $table->foreign('user_id')->references('id')->on('users'); // Comentado para evitar erro se user for deletado, ideal set null
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('beneficiaries_tables');
    }
};
