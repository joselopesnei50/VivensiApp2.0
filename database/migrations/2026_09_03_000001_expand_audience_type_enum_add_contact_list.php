<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adiciona 'contact_list' ao ENUM audience_type de email_campaigns.
 *
 * A migration 2026_08_26_100002 introduziu a coluna email_contact_list_id
 * mas esqueceu de expandir o ENUM, entao INSERT com audience_type='contact_list'
 * quebrava com "Data truncated for column 'audience_type'" (erro 500 ao salvar
 * campanha no modo Lista de Contatos).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE email_campaigns MODIFY COLUMN audience_type ENUM('tenant_admins','all_users','leads','all','manual','none','donors','donors_optins','contact_list') NOT NULL DEFAULT 'tenant_admins'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE email_campaigns MODIFY COLUMN audience_type ENUM('tenant_admins','all_users','leads','all','manual','none','donors','donors_optins') NOT NULL DEFAULT 'tenant_admins'");
        }
    }
};
