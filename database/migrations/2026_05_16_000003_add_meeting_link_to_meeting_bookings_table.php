<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meeting_bookings', function (Blueprint $table) {
            $table->string('meeting_link')->nullable()->after('notes');
            $table->string('admin_notes')->nullable()->after('meeting_link');
        });
    }

    public function down(): void
    {
        Schema::table('meeting_bookings', function (Blueprint $table) {
            $table->dropColumn(['meeting_link', 'admin_notes']);
        });
    }
};
