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
        Schema::create('financial_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('type'); // income, expense
            $table->string('color')->nullable();
            $table->boolean('is_system_default')->default(false); // Categorias padrão que não podem ser deletadas
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // Adicionar coluna category_id na tabela transactions se não existir
        if (!Schema::hasColumn('transactions', 'category_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                // Se já houver dados, allow null inicialmente
                $table->unsignedBigInteger('category_id')->nullable()->after('tenant_id');
                $table->foreign('category_id')->references('id')->on('financial_categories')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
        
        Schema::dropIfExists('financial_categories');
    }
};
