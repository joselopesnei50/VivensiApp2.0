<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('device', 100)->nullable();    // Mobile | Desktop | Tablet
            $table->string('browser', 100)->nullable();   // Chrome | Firefox | Safari
            $table->string('platform', 100)->nullable();  // Windows | macOS | Linux | iOS | Android
            $table->string('country', 100)->nullable();
            $table->boolean('success')->default(true);
            $table->timestamp('logged_in_at');
            $table->index(['user_id', 'logged_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_activities');
    }
};
