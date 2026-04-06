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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('asaas_customer_id')->nullable()->after('type');
            $table->unsignedBigInteger('plan_id')->nullable()->after('asaas_customer_id');
            $table->string('subscription_status')->default('pending')->after('plan_id'); // pending, active, trialing, past_due, canceled
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            
            $table->foreign('plan_id')->references('id')->on('subscription_plans')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['asaas_customer_id', 'plan_id', 'subscription_status', 'trial_ends_at']);
        });
    }

};
