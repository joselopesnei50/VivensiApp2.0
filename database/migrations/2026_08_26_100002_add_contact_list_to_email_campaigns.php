<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vincula EmailCampaign a uma EmailContactList quando o cliente escolhe uma
 * lista salva. Nullable = campanhas antigas + campanhas com contatos ad-hoc
 * (upload direto sem salvar como lista) continuam funcionando.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->foreignId('email_contact_list_id')->nullable()->after('brevo_campaign_id')
                  ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->dropForeign(['email_contact_list_id']);
            $table->dropColumn('email_contact_list_id');
        });
    }
};
