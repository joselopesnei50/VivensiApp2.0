<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Verificar lista": resultado da verificação de cada contato e o resumo da
 * última verificação da lista. Contato inválido vira status 'invalid' (o
 * enum já existe) e fica fora de qualquer campanha.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_contacts', function (Blueprint $table) {
            $table->string('verification_result', 10)->nullable()->after('status'); // valid | risky | invalid
            $table->string('verification_detail', 255)->nullable()->after('verification_result');
            $table->timestamp('verified_at')->nullable()->after('verification_detail');
        });

        Schema::table('email_contact_lists', function (Blueprint $table) {
            $table->string('verification_status', 12)->nullable()->after('opt_in_confirmed_at'); // running | done | failed
            $table->boolean('verification_deep')->default(false)->after('verification_status');
            $table->json('verification_summary')->nullable()->after('verification_deep');
            $table->timestamp('verification_started_at')->nullable()->after('verification_summary');
            $table->timestamp('verification_finished_at')->nullable()->after('verification_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('email_contact_lists', function (Blueprint $table) {
            $table->dropColumn(['verification_status', 'verification_deep', 'verification_summary', 'verification_started_at', 'verification_finished_at']);
        });
        Schema::table('email_contacts', function (Blueprint $table) {
            $table->dropColumn(['verification_result', 'verification_detail', 'verified_at']);
        });
    }
};
