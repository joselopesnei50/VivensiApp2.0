<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('stage', 20)->default('active')->after('type')->index();
            $table->timestamp('last_contact_at')->nullable()->after('relationship_notes');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['stage']);
            $table->dropColumn(['stage', 'last_contact_at']);
        });
    }
};
