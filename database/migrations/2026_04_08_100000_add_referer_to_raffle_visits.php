<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('raffle_visits', function (Blueprint $table) {
            if (!Schema::hasColumn('raffle_visits', 'referer')) {
                $table->string('referer', 500)->nullable()->after('user_agent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raffle_visits', function (Blueprint $table) {
            $table->dropColumn('referer');
        });
    }
};
