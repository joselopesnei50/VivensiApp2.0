<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id')->index();
        });

        // Expand audience_type enum to include NGO-specific audience values
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE email_campaigns MODIFY COLUMN audience_type ENUM('tenant_admins','all_users','leads','all','manual','none','donors','donors_optins') NOT NULL DEFAULT 'tenant_admins'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE email_campaigns MODIFY COLUMN audience_type ENUM('tenant_admins','all_users','leads','all','manual','none') NOT NULL DEFAULT 'tenant_admins'");
        }

        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
