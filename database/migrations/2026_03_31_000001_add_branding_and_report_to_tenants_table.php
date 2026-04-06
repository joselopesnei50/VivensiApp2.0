<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('brand_logo')->nullable()->after('billing_cycle');
            $table->string('brand_color', 7)->default('#4F46E5')->after('brand_logo');
            $table->string('brand_name')->nullable()->after('brand_color');
            $table->boolean('weekly_report_enabled')->default(true)->after('brand_name');
            $table->string('report_email')->nullable()->after('weekly_report_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['brand_logo', 'brand_color', 'brand_name', 'weekly_report_enabled', 'report_email']);
        });
    }
};
