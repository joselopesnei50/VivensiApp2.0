<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'signer_address')) {
                $table->string('signer_address')->nullable();
            }
            if (!Schema::hasColumn('contracts', 'signer_phone')) {
                $table->string('signer_phone', 32)->nullable();
            }
            if (!Schema::hasColumn('contracts', 'signer_cpf')) {
                $table->string('signer_cpf', 32)->nullable();
            }
            if (!Schema::hasColumn('contracts', 'signer_rg')) {
                $table->string('signer_rg', 32)->nullable();
            }
            if (!Schema::hasColumn('contracts', 'signer_ip')) {
                $table->string('signer_ip', 45)->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $drop = [];
            foreach (['signer_address', 'signer_phone', 'signer_cpf', 'signer_rg', 'signer_ip'] as $col) {
                if (Schema::hasColumn('contracts', $col)) {
                    $drop[] = $col;
                }
            }
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};

