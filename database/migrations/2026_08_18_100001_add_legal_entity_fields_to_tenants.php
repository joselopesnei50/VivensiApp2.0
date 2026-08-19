<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'razao_social')) {
                $table->string('razao_social')->nullable()->after('name');
            }
            if (!Schema::hasColumn('tenants', 'endereco')) {
                $table->string('endereco')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'numero_endereco')) {
                $table->string('numero_endereco', 20)->nullable();
            }
            if (!Schema::hasColumn('tenants', 'complemento')) {
                $table->string('complemento')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'bairro')) {
                $table->string('bairro')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'cidade')) {
                $table->string('cidade')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'estado')) {
                $table->string('estado', 2)->nullable();
            }
            if (!Schema::hasColumn('tenants', 'cep')) {
                $table->string('cep', 9)->nullable();
            }
            if (!Schema::hasColumn('tenants', 'contract_signed_at')) {
                $table->timestamp('contract_signed_at')->nullable()->index();
            }
        });
    }

    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $drop = [];
            foreach ([
                'razao_social', 'endereco', 'numero_endereco', 'complemento',
                'bairro', 'cidade', 'estado', 'cep', 'contract_signed_at',
            ] as $col) {
                if (Schema::hasColumn('tenants', $col)) {
                    $drop[] = $col;
                }
            }
            if ($drop) {
                $table->dropColumn($drop);
            }
        });
    }
};
