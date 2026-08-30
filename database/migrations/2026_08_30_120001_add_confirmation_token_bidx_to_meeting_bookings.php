<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoria 2026-08-29 P3.b.3 (IDOR baixa) — meeting_bookings ganha
 * confirmation_token_bidx (HMAC do token com app.key). Alinha com o
 * padrao ja usado em Contract/NgoDonor/WhatsappFormSession. Se um dump
 * do DB vazar, tokens plaintext nao servem mais pra cancelar reunioes.
 *
 * Backwards-compat: URLs de cancelamento ja enviadas por email continuam
 * funcionando — comparacao passa a ser bidx-based mas o token original
 * na URL nao muda.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('meeting_bookings', function (Blueprint $t) {
            $t->string('confirmation_token_bidx', 64)->nullable()->after('confirmation_token')->index();
        });

        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7)) ?: $key;
        }

        // Backfill: bidx = HMAC-SHA256(token, app.key) para cada registro
        // existente. Faz em PHP porque MySQL nao expone HMAC direto.
        DB::table('meeting_bookings')
            ->whereNotNull('confirmation_token')
            ->whereNull('confirmation_token_bidx')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($key) {
                foreach ($rows as $row) {
                    DB::table('meeting_bookings')
                        ->where('id', $row->id)
                        ->update([
                            'confirmation_token_bidx' => hash_hmac('sha256', $row->confirmation_token, $key),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('meeting_bookings', function (Blueprint $t) {
            $t->dropIndex(['confirmation_token_bidx']);
            $t->dropColumn('confirmation_token_bidx');
        });
    }
};
