<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // attendances: composite covering tenant + beneficiary + date for period reports
        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['tenant_id', 'beneficiary_id', 'date'], 'idx_att_tenant_beneficiary_date');
        });

        // transactions: tenant + category + status for filtered DRE queries
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['tenant_id', 'category_id', 'status'], 'idx_tx_tenant_category_status');
        });

        // beneficiaries: status and state filters scoped by tenant
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->index(['tenant_id', 'status'], 'idx_ben_tenant_status');
            $table->index(['tenant_id', 'address_state'], 'idx_ben_tenant_state');
        });

        // audit_logs: lookup by auditable entity (type + id) scoped by tenant
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['tenant_id', 'auditable_type', 'auditable_id'], 'idx_audit_tenant_auditable');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_att_tenant_beneficiary_date');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_tx_tenant_category_status');
        });

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropIndex('idx_ben_tenant_status');
            $table->dropIndex('idx_ben_tenant_state');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_tenant_auditable');
        });
    }
};
