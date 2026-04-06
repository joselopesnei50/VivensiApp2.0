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
        // Tabela de Funcionários (RH)
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('project_id')->nullable(); // Projeto vinculado
            $table->string('name');
            $table->string('position'); // Função/Cargo
            $table->decimal('salary', 10, 2);
            $table->decimal('bonus', 10, 2)->default(0);
            $table->string('work_hours_weekly'); // Carga Horária Ex: 40h
            $table->enum('contract_type', ['clt', 'pj', 'trainee', 'temporary']);
            $table->date('hired_at');
            $table->enum('status', ['active', 'vacation', 'terminated'])->default('active');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            // $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null'); // Comentado pois projects podem não existir ainda para alguns
        });

        // Tabela de Voluntários
        Schema::create('volunteers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('skills')->nullable(); // Habilidades
            $table->enum('availability', ['morning', 'afternoon', 'night', 'weekends'])->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // Tabela de Log de Certificados (para Voluntários)
        Schema::create('volunteer_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('volunteer_id');
            $table->string('activity_description');
            $table->integer('hours');
            $table->timestamp('issued_at');
            $table->string('file_path')->nullable(); // Caminho do PDF
            $table->timestamps();

            $table->foreign('volunteer_id')->references('id')->on('volunteers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hr_tables');
    }
};
