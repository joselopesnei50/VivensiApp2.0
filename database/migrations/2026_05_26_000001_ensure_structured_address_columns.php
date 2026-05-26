<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            if (!Schema::hasColumn('beneficiaries', 'address_zip'))          $table->string('address_zip', 10)->nullable()->after('address');
            if (!Schema::hasColumn('beneficiaries', 'address_street'))        $table->string('address_street')->nullable()->after('address_zip');
            if (!Schema::hasColumn('beneficiaries', 'address_number'))        $table->string('address_number', 20)->nullable()->after('address_street');
            if (!Schema::hasColumn('beneficiaries', 'address_complement'))    $table->string('address_complement', 100)->nullable()->after('address_number');
            if (!Schema::hasColumn('beneficiaries', 'address_neighborhood'))  $table->string('address_neighborhood')->nullable()->after('address_complement');
            if (!Schema::hasColumn('beneficiaries', 'address_city'))          $table->string('address_city')->nullable()->after('address_neighborhood');
            if (!Schema::hasColumn('beneficiaries', 'address_state'))         $table->string('address_state', 2)->nullable()->after('address_city');
        });

        Schema::table('ngo_donors', function (Blueprint $table) {
            if (!Schema::hasColumn('ngo_donors', 'address_zip'))          $table->string('address_zip', 10)->nullable()->after('address');
            if (!Schema::hasColumn('ngo_donors', 'address_street'))        $table->string('address_street')->nullable()->after('address_zip');
            if (!Schema::hasColumn('ngo_donors', 'address_number'))        $table->string('address_number', 20)->nullable()->after('address_street');
            if (!Schema::hasColumn('ngo_donors', 'address_complement'))    $table->string('address_complement', 100)->nullable()->after('address_number');
            if (!Schema::hasColumn('ngo_donors', 'address_neighborhood'))  $table->string('address_neighborhood')->nullable()->after('address_complement');
            if (!Schema::hasColumn('ngo_donors', 'address_city'))          $table->string('address_city')->nullable()->after('address_neighborhood');
            if (!Schema::hasColumn('ngo_donors', 'address_state'))         $table->string('address_state', 2)->nullable()->after('address_city');
        });
    }

    public function down(): void
    {
        // Colunas adicionadas por ensure — não remover no rollback
    }
};
