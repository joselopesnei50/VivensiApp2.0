<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona coluna pra guardar o QR code PIX base64 (PNG) retornado pelo
 * endpoint /transparents/create da AbacatePay. UI renderiza como <img>.
 *
 * abacatepay_pix_url (que já existe) passa a guardar o brCode (copia-e-cola).
 * abacatepay_billing_url continua guardando URL de checkout hospedado (se
 * usarmos no futuro — hoje null pra invoices PIX).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->text('abacatepay_pix_qr_base64')->nullable()->after('abacatepay_pix_url')
                  ->comment('QR code PIX em base64 PNG (data:image/png;base64,...). Vindo do brCodeBase64 de /transparents/create.');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('abacatepay_pix_qr_base64');
        });
    }
};
