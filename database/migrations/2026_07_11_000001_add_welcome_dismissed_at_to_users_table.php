<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca quando o usuário fechou o modal de boas-vindas destacando Bruce IA + Sala de Estratégia.
 * Nullable — enquanto null, modal aparece; após dismiss, guarda timestamp e não reaparece.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('welcome_dismissed_at')->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('welcome_dismissed_at');
        });
    }
};
