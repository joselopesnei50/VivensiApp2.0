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
        Schema::table('beneficiaries', function (Blueprint $table) {
            if (!Schema::hasColumn('beneficiaries', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after(Schema::hasColumn('beneficiaries', 'address') ? 'address' : 'phone');
            }
            if (!Schema::hasColumn('beneficiaries', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
        });

        Schema::table('ngo_donors', function (Blueprint $table) {
            // Garante que ngo_donors tenha o campo address para geocodificação
            if (!Schema::hasColumn('ngo_donors', 'address')) {
                $table->string('address')->nullable()->after('phone');
            }

            if (!Schema::hasColumn('ngo_donors', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('address');
            }
            if (!Schema::hasColumn('ngo_donors', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });

        Schema::table('ngo_donors', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
