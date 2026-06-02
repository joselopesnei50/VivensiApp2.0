<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Salary/bonus must change from decimal to text to store encrypted strings.
        DB::statement('ALTER TABLE employees MODIFY salary TEXT NULL');
        DB::statement('ALTER TABLE employees MODIFY bonus TEXT NULL');

        // birth_date must change from date to text.
        DB::statement('ALTER TABLE family_members MODIFY birth_date TEXT NULL');

        // Encrypt existing data in all GRUPO SEGURO tables.
        $this->encryptTable('users',           ['two_factor_secret', 'two_factor_recovery_codes']);
        $this->encryptTable('tenants',         ['pix_key', 'pix_key_type', 'openpix_app_id']);
        $this->encryptTable('employees',       ['salary', 'bonus']);
        $this->encryptTable('contracts',       ['signer_cpf', 'signer_rg', 'signer_name', 'signer_email', 'signer_phone']);
        $this->encryptTable('social_accounts', ['access_token']);
        $this->encryptTable('webhooks',        ['secret']);
        $this->encryptTable('family_members',  ['birth_date']);
    }

    public function down(): void
    {
        // Decrypt data before restoring column types.
        $this->decryptTable('users',           ['two_factor_secret', 'two_factor_recovery_codes']);
        $this->decryptTable('tenants',         ['pix_key', 'pix_key_type', 'openpix_app_id']);
        $this->decryptTable('employees',       ['salary', 'bonus']);
        $this->decryptTable('contracts',       ['signer_cpf', 'signer_rg', 'signer_name', 'signer_email', 'signer_phone']);
        $this->decryptTable('social_accounts', ['access_token']);
        $this->decryptTable('webhooks',        ['secret']);
        $this->decryptTable('family_members',  ['birth_date']);

        DB::statement('ALTER TABLE employees MODIFY salary DECIMAL(15,2) NULL');
        DB::statement('ALTER TABLE employees MODIFY bonus DECIMAL(15,2) NULL');
        DB::statement('ALTER TABLE family_members MODIFY birth_date DATE NULL');
    }

    // Laravel encrypted values are always base64-encoded JSON starting with "eyJ".
    private function isAlreadyEncrypted(?string $value): bool
    {
        return $value === null || $value === '' || str_starts_with($value, 'eyJ');
    }

    private function encryptTable(string $table, array $fields): void
    {
        DB::table($table)->orderBy('id')->chunk(500, function ($rows) use ($table, $fields) {
            foreach ($rows as $row) {
                $updates = [];
                foreach ($fields as $field) {
                    $val = (string) ($row->{$field} ?? '');
                    if ($val !== '' && !$this->isAlreadyEncrypted($val)) {
                        $updates[$field] = Crypt::encryptString($val);
                    }
                }
                if (!empty($updates)) {
                    DB::table($table)->where('id', $row->id)->update($updates);
                }
            }
        });
    }

    private function decryptTable(string $table, array $fields): void
    {
        DB::table($table)->orderBy('id')->chunk(500, function ($rows) use ($table, $fields) {
            foreach ($rows as $row) {
                $updates = [];
                foreach ($fields as $field) {
                    $val = (string) ($row->{$field} ?? '');
                    if ($val !== '' && $this->isAlreadyEncrypted($val)) {
                        try {
                            $updates[$field] = Crypt::decryptString($val);
                        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                            // Already plaintext — skip.
                        }
                    }
                }
                if (!empty($updates)) {
                    DB::table($table)->where('id', $row->id)->update($updates);
                }
            }
        });
    }
};
