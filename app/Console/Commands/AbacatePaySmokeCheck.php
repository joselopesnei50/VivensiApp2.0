<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AbacatePaySmokeCheck extends Command
{
    protected $signature = 'abacatepay:smoke-check
        {--limit=5 : quantas transactions recentes inspecionar}
        {--tenant= : filtra por tenant_id especifico}';

    protected $description = 'Verifica integridade do fluxo AbacatePay pos-deploy (external_id novo, UNIQUE, duplicatas, tenant status)';

    public function handle(): int
    {
        $this->info('=== AbacatePay Smoke Check ===');
        $this->newLine();

        $this->checkUniqueIndex();
        $this->newLine();

        $this->checkDuplicates();
        $this->newLine();

        $this->checkRecentTransactions();
        $this->newLine();

        return self::SUCCESS;
    }

    private function checkUniqueIndex(): void
    {
        $this->line('<comment>[1] UNIQUE index em transactions.external_id</comment>');

        $driver = DB::connection()->getDriverName();
        if ($driver !== 'mysql') {
            $this->warn("  Driver=$driver, pulando (so testa em MySQL).");
            return;
        }

        $rows = DB::select("SHOW INDEX FROM transactions WHERE Key_name = 'transactions_external_id_unique'");
        if (empty($rows)) {
            $this->error('  AUSENTE — migration 2026_09_28_000001 nao rodou.');
            return;
        }

        $idx = $rows[0];
        $ok = (int) $idx->Non_unique === 0 && $idx->Column_name === 'external_id';
        $this->line($ok
            ? '  <info>OK</info> — UNIQUE em external_id confirmada'
            : "  <error>INCONSISTENTE</error> — Non_unique={$idx->Non_unique} Column={$idx->Column_name}");
    }

    private function checkDuplicates(): void
    {
        $this->line('<comment>[2] Duplicatas em external_id (fora do padrao DUPE_*)</comment>');

        $dupes = DB::table('transactions')
            ->select('external_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('external_id')
            ->where('external_id', 'not like', 'DUPE\\_%')
            ->groupBy('external_id')
            ->having('total', '>', 1)
            ->get();

        if ($dupes->isEmpty()) {
            $this->line('  <info>OK</info> — nenhuma duplicata ativa');
            return;
        }

        $this->error("  {$dupes->count()} external_id com duplicata:");
        foreach ($dupes as $d) {
            $this->line("    - {$d->external_id} ({$d->total}x)");
        }
    }

    private function checkRecentTransactions(): void
    {
        $limit = (int) $this->option('limit');
        $tenantFilter = $this->option('tenant');

        $this->line("<comment>[3] Ultimas $limit transactions AbacatePay</comment>");

        $q = Transaction::query()
            ->where('external_id', 'like', 'VIVENSI\\_%')
            ->latest('id')
            ->limit($limit);

        if ($tenantFilter) {
            $q->where('tenant_id', (int) $tenantFilter);
        }

        $txs = $q->get();

        if ($txs->isEmpty()) {
            $this->warn('  Nenhuma transaction encontrada.');
            return;
        }

        $patternNew = '/^VIVENSI_\d+_\d+_[A-Za-z0-9]{6}$/';
        $patternOld = '/^VIVENSI_\d+_\d+$/';

        $rows = [];
        foreach ($txs as $tx) {
            $extFormat = 'desconhecido';
            if (preg_match($patternNew, (string) $tx->external_id)) {
                $extFormat = 'NOVO (c/ sufixo)';
            } elseif (preg_match($patternOld, (string) $tx->external_id)) {
                $extFormat = 'ANTIGO (sem sufixo)';
            }

            $tenant = Tenant::find($tx->tenant_id);
            $rows[] = [
                $tx->id,
                $tx->tenant_id,
                $tenant?->status ?? '-',
                $tx->status,
                $tx->external_id,
                $extFormat,
                $tx->created_at?->format('d/m H:i'),
            ];
        }

        $this->table(
            ['tx_id', 'tenant', 'tenant.status', 'tx.status', 'external_id', 'formato', 'criado'],
            $rows
        );
    }
}
