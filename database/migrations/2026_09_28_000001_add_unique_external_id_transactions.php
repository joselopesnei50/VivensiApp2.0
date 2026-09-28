<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * P0.2 (2026-09-28) — Adiciona UNIQUE em transactions.external_id.
 *
 * Sem esse indice, dois POSTs /checkout/process no mesmo segundo geravam
 * VIVENSI_{tenant}_{time()} identico e criavam duas linhas em transactions
 * silenciosamente. O webhook depois pegava a primeira via ->first() e a
 * segunda ficava orfa em pending pra sempre.
 *
 * Migration idempotente:
 *  1. Detecta duplicatas nao-nulas e loga (nao aborta).
 *  2. Renomeia external_id das duplicatas (mantem a linha mais antiga PAGA,
 *     ou a mais antiga em geral) — prefixa DUPE_{id}_ pras outras.
 *  3. Cria UNIQUE(external_id). NULL continua permitido em multiplas linhas
 *     porque MySQL trata NULL como distinct em UNIQUE.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('transactions', 'external_id')) {
            Log::warning('Migration add_unique_external_id: coluna external_id nao existe, skip');
            return;
        }

        // 1. Detectar duplicatas
        $dupes = DB::table('transactions')
            ->select('external_id', DB::raw('COUNT(*) as c'))
            ->whereNotNull('external_id')
            ->where('external_id', '!=', '')
            ->groupBy('external_id')
            ->having('c', '>', 1)
            ->get();

        if ($dupes->isNotEmpty()) {
            Log::warning('Migration add_unique_external_id: encontradas ' . $dupes->count() . ' external_id duplicadas — deduplicando', [
                'dupes' => $dupes->pluck('external_id')->all(),
            ]);

            foreach ($dupes as $dupe) {
                $rows = DB::table('transactions')
                    ->where('external_id', $dupe->external_id)
                    ->orderBy('id')
                    ->get(['id', 'status']);

                // Prioridade pra manter: a mais antiga com status=paid; senao a mais antiga.
                $keeper = $rows->firstWhere('status', 'paid');
                if (!$keeper) {
                    $keeper = $rows->first();
                }

                foreach ($rows as $row) {
                    if ($row->id !== $keeper->id) {
                        $newExtId = 'DUPE_' . $row->id . '_' . substr((string) $dupe->external_id, 0, 60);
                        DB::table('transactions')
                            ->where('id', $row->id)
                            ->update(['external_id' => $newExtId]);
                        Log::warning('Migration add_unique_external_id: renomeada duplicata', [
                            'tx_id'       => $row->id,
                            'old_ext_id'  => $dupe->external_id,
                            'new_ext_id'  => $newExtId,
                            'keeper_id'   => $keeper->id,
                        ]);
                    }
                }
            }
        }

        // 2. Criar UNIQUE. Skip se ja existe (migration re-executavel) — usa
        // Doctrine pra funcionar em MySQL (prod) e SQLite (testes).
        if ($this->indexExists('transactions', 'transactions_external_id_unique')) {
            Log::info('Migration add_unique_external_id: indice ja existe, skip');
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->unique('external_id', 'transactions_external_id_unique');
        });

        Log::info('Migration add_unique_external_id: UNIQUE criado em transactions.external_id');
    }

    public function down(): void
    {
        if (!Schema::hasColumn('transactions', 'external_id')) {
            return;
        }

        if (!$this->indexExists('transactions', 'transactions_external_id_unique')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('transactions_external_id_unique');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            $rows = DB::select(
                'SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?',
                [$indexName]
            );
            return count($rows) > 0;
        }

        if ($driver === 'sqlite') {
            $rows = DB::select(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                [$table, $indexName]
            );
            return count($rows) > 0;
        }

        // Fallback: Doctrine schema manager (postgres etc). L9 ainda expoe.
        try {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            foreach ($sm->listTableIndexes($table) as $idx) {
                if ($idx->getName() === $indexName) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('indexExists fallback falhou', ['error' => $e->getMessage()]);
        }
        return false;
    }
};
