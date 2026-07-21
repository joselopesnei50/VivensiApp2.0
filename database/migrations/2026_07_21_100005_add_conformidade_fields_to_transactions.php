<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->boolean('elegivel_mrosc')->default(false)->after('amount');
            $table->string('fonte_recurso', 30)->nullable()->after('elegivel_mrosc');
            // proprio|cebas|suas|mrosc|doacao|emenda_parlamentar|fundo_municipal|outro
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['elegivel_mrosc', 'fonte_recurso']);
        });
    }
};
