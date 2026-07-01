<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('project_people', function (Blueprint $table) {
            if (!Schema::hasColumn('project_people', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('project_people', 'guardian_name')) {
                $table->string('guardian_name')->nullable()->after('birth_date');
            }
            if (!Schema::hasColumn('project_people', 'guardian_phone')) {
                $table->string('guardian_phone')->nullable()->after('guardian_name');
            }
            if (!Schema::hasColumn('project_people', 'enrollment_status')) {
                $table->enum('enrollment_status', ['ativo', 'inativo'])
                      ->default('ativo')
                      ->after('guardian_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_people', function (Blueprint $table) {
            foreach (['enrollment_status', 'guardian_phone', 'guardian_name', 'birth_date'] as $col) {
                if (Schema::hasColumn('project_people', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
