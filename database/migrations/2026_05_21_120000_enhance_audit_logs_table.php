<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('session_id', 100)->nullable()->after('url');
            $table->string('device_type', 20)->nullable()->after('session_id'); // desktop, mobile, tablet
            $table->string('browser', 60)->nullable()->after('device_type');
            $table->string('platform', 40)->nullable()->after('browser');    // Windows, iOS, Android
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['session_id', 'device_type', 'browser', 'platform']);
        });
    }
};
