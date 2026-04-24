<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('broadcast_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name')->nullable();
            $table->text('message')->nullable();
            $table->boolean('has_image')->default(false);
            $table->string('audience_type')->default('all'); // all, selected, groups
            $table->integer('total_sent')->default(0);
            $table->integer('total_failed')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('broadcast_campaigns');
    }
};
