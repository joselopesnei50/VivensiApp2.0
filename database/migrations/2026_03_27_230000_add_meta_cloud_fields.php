<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('meta_waba_id')->nullable()->comment('WhatsApp Business Account ID');
            $table->string('meta_phone_number_id')->nullable()->comment('ID do numero do WhatsApp Cloud API');
            $table->text('meta_access_token')->nullable()->comment('Token de acesso OAuth da Meta');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('meta_waba_id')->nullable();
            $table->string('meta_phone_number_id')->nullable();
            $table->text('meta_access_token')->nullable();
        });
        
        // Também vamos adicionar no WhatsappConfig
        Schema::table('whatsapp_configs', function (Blueprint $table) {
            $table->string('meta_waba_id')->nullable();
            $table->string('meta_phone_number_id')->nullable();
            $table->text('meta_access_token')->nullable();
        });
    }

    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['meta_waba_id', 'meta_phone_number_id', 'meta_access_token']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['meta_waba_id', 'meta_phone_number_id', 'meta_access_token']);
        });
        
        Schema::table('whatsapp_configs', function (Blueprint $table) {
            $table->dropColumn(['meta_waba_id', 'meta_phone_number_id', 'meta_access_token']);
        });
    }
};
