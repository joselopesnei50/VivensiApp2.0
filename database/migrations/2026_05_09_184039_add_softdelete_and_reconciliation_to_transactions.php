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
        Schema::table('transactions', function (Blueprint $table) {
            $table->softDeletes();
            $table->string('ofx_fitid')->nullable()
                  ->comment('ID único da transação no OFX bancário — evita importação duplicada');
            $table->timestamp('reconciled_at')->nullable()
                  ->comment('Quando esta transação foi importada/conciliada via OFX');

            $table->index(['tenant_id', 'ofx_fitid'], 'idx_transactions_tenant_fitid');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_tenant_fitid');
            $table->dropColumn(['deleted_at', 'ofx_fitid', 'reconciled_at']);
        });
    }
};
