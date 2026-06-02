<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Migration 000006 could not encrypt two_factor_secret (varchar 128 — too small for
 * encrypted strings ~220 chars) nor two_factor_recovery_codes (json column — MySQL
 * rejects non-JSON values).  Both columns must be TEXT before Eloquent's encrypted
 * cast works correctly.
 *
 * This migration:
 *   1. Widens both columns to TEXT.
 *   2. Clears rows with corrupted (truncated) encrypted data — the user will need to
 *      re-configure 2FA.  This is safe: better to require re-setup than to leave
 *      broken encrypted data that blocks every page load.
 *   3. Encrypts any remaining plaintext values that migration 000006 missed.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Step 1 — change column types to TEXT so encrypted strings fit.
        // SQLite has flexible typing and no MODIFY COLUMN syntax; skip on SQLite (CI).
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE users MODIFY two_factor_secret TEXT NULL');
            DB::statement('ALTER TABLE users MODIFY two_factor_recovery_codes TEXT NULL');
        }

        // Step 2 — fix data for users that have any 2FA fields populated.
        DB::table('users')
            ->where(function ($q) {
                $q->whereNotNull('two_factor_secret')
                  ->orWhereNotNull('two_factor_recovery_codes');
            })
            ->orderBy('id')
            ->chunk(100, function ($rows) {
                foreach ($rows as $row) {
                    $this->fixUserRow($row);
                }
            });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // Decrypt before narrowing the columns back.
        DB::table('users')
            ->whereNotNull('two_factor_secret')
            ->chunk(100, function ($rows) {
                foreach ($rows as $row) {
                    try {
                        $plain = Crypt::decryptString($row->two_factor_secret);
                        DB::table('users')->where('id', $row->id)
                            ->update(['two_factor_secret' => $plain]);
                    } catch (\Throwable) {}
                }
            });

        DB::statement('ALTER TABLE users MODIFY two_factor_secret VARCHAR(128) NULL');
        DB::statement('ALTER TABLE users MODIFY two_factor_recovery_codes JSON NULL');
    }

    private function fixUserRow(object $row): void
    {
        $secretRaw  = $row->two_factor_secret;
        $codesRaw   = $row->two_factor_recovery_codes;

        $secretOk  = $this->verifyOrEncrypt($secretRaw, $encryptedSecret);
        $codesOk   = $this->verifyOrEncrypt($codesRaw,  $encryptedCodes);

        if (!$secretOk || !$codesOk) {
            // Corrupted or truncated — cannot recover; reset so user can re-configure.
            DB::table('users')->where('id', $row->id)->update([
                'two_factor_secret'         => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at'   => null,
            ]);
            return;
        }

        $updates = [];
        if ($encryptedSecret !== null) {
            $updates['two_factor_secret'] = $encryptedSecret;
        }
        if ($encryptedCodes !== null) {
            $updates['two_factor_recovery_codes'] = $encryptedCodes;
        }
        if (!empty($updates)) {
            DB::table('users')->where('id', $row->id)->update($updates);
        }
    }

    /**
     * Returns true when $value is either null, already properly encrypted, or was
     * successfully encrypted into $out.  Returns false when the value is corrupted
     * (e.g. a truncated ciphertext that cannot be decrypted).
     *
     * @param  string|null $value  Raw DB value.
     * @param  string|null &$out   Set to the encrypted string when encryption was needed.
     */
    private function verifyOrEncrypt(?string $value, ?string &$out): bool
    {
        $out = null;

        if ($value === null || $value === '') {
            return true; // Nothing to do.
        }

        if (str_starts_with($value, 'eyJ')) {
            // Looks like an encrypted string — verify it decrypts successfully.
            try {
                Crypt::decryptString($value);
                return true; // Already good — no update needed.
            } catch (\Throwable) {
                return false; // Truncated / corrupted — signal caller to clear row.
            }
        }

        // Plaintext — encrypt it now.
        $out = Crypt::encryptString($value);
        return true;
    }
};
