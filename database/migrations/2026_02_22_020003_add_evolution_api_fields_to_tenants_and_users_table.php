<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('evolution_instance_name')->nullable();
            $table->string('evolution_instance_token')->nullable();
            $table->string('evolution_instance_status')->default('disconnected');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('evolution_instance_name')->nullable();
            $table->string('evolution_instance_token')->nullable();
            $table->string('evolution_instance_status')->default('disconnected');
        });
    }

    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['evolution_instance_name', 'evolution_instance_token', 'evolution_instance_status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['evolution_instance_name', 'evolution_instance_token', 'evolution_instance_status']);
        });
    }
};
