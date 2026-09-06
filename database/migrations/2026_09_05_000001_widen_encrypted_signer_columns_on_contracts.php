<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite tem typing flexivel, MODIFY COLUMN nao aplica.
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // Campos com cast 'encrypted' no Contract precisam de TEXT: a saida
        // do Crypt::encryptString e um JSON base64 que passa de 230 chars
        // ate pro menor input (11 digitos de CPF).
        //
        // Colunas antigas — signer_cpf/rg/phone eram VARCHAR(32), name/email
        // VARCHAR(255) — estouravam com SQLSTATE 22001 em MySQL strict.
        DB::statement('ALTER TABLE contracts MODIFY signer_name TEXT NOT NULL');
        DB::statement('ALTER TABLE contracts MODIFY signer_email TEXT NULL');
        DB::statement('ALTER TABLE contracts MODIFY signer_phone TEXT NULL');
        DB::statement('ALTER TABLE contracts MODIFY signer_cpf TEXT NULL');
        DB::statement('ALTER TABLE contracts MODIFY signer_rg TEXT NULL');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE contracts MODIFY signer_name VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE contracts MODIFY signer_email VARCHAR(255) NULL');
        DB::statement('ALTER TABLE contracts MODIFY signer_phone VARCHAR(32) NULL');
        DB::statement('ALTER TABLE contracts MODIFY signer_cpf VARCHAR(32) NULL');
        DB::statement('ALTER TABLE contracts MODIFY signer_rg VARCHAR(32) NULL');
    }
};
