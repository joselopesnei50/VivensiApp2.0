<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            // ID do produto na AbacatePay (ex: prod_abc123xyz)
            $table->string('abacatepay_product_id')->nullable()->after('asaas_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('abacatepay_product_id');
        });
    }
};
