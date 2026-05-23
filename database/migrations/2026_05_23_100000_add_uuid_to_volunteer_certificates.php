<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('volunteer_certificates', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        DB::table('volunteer_certificates')->whereNull('uuid')->orderBy('id')->each(function ($row) {
            DB::table('volunteer_certificates')
                ->where('id', $row->id)
                ->update(['uuid' => (string) Str::uuid()]);
        });
    }

    public function down(): void
    {
        Schema::table('volunteer_certificates', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
