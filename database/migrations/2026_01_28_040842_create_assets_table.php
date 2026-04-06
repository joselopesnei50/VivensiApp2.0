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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name'); // Nome do bem (ex: Notebook Dell)
            $table->string('code')->nullable(); // Código de patrimônio / Plaqueta
            $table->text('description')->nullable();
            $table->date('acquisition_date');
            $table->decimal('value', 10, 2); // Valor de compra
            $table->enum('status', ['active', 'maintenance', 'disposed', 'lost'])->default('active');
            $table->string('location')->nullable(); // Sala 01, Sede, etc
            $table->string('responsible')->nullable(); // Quem está com o bem
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assets');
    }
};
