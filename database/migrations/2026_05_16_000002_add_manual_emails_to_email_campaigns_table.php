<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->longText('manual_emails')->nullable()->after('audience_type');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE email_campaigns MODIFY COLUMN audience_type ENUM('tenant_admins','all_users','leads','all','manual','none') NOT NULL DEFAULT 'tenant_admins'");
        }
    }

    public function down(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->dropColumn('manual_emails');
        });
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE email_campaigns MODIFY COLUMN audience_type ENUM('tenant_admins','all_users','leads','all') NOT NULL DEFAULT 'tenant_admins'");
        }
    }
};
