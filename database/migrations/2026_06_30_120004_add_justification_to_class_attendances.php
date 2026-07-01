<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('class_attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('class_attendances', 'justification')) {
                $table->text('justification')->nullable()->after('checked_in_via');
            }
        });
    }

    public function down(): void
    {
        Schema::table('class_attendances', function (Blueprint $table) {
            if (Schema::hasColumn('class_attendances', 'justification')) {
                $table->dropColumn('justification');
            }
        });
    }
};
