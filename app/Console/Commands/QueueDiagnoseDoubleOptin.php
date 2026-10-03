<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QueueDiagnoseDoubleOptin extends Command
{
    protected $signature = 'queue:diagnose-double-optin
        {--sample=1 : quantas exceptions recentes mostrar}';

    protected $description = 'Diagnostica falhas em SendDoubleOptInWhatsapp (distribuicao temporal + exception real)';

    public function handle(): int
    {
        $this->info('=== Diagnostico SendDoubleOptInWhatsapp ===');
        $this->newLine();

        $base = DB::table('failed_jobs')
            ->where('payload', 'like', '%SendDoubleOptInWhatsapp%');

        $agg = (clone $base)
            ->selectRaw('MIN(failed_at) as primeiro, MAX(failed_at) as ultimo, COUNT(*) as total')
            ->first();

        if (!$agg || $agg->total === 0) {
            $this->info('Nenhuma falha encontrada.');
            return self::SUCCESS;
        }

        $this->line("Total:    <info>{$agg->total}</info>");
        $this->line("Primeiro: <info>{$agg->primeiro}</info>");
        $this->line("Ultimo:   <info>{$agg->ultimo}</info>");

        $uniqueExceptions = (clone $base)
            ->selectRaw('SUBSTRING(exception, 1, 120) as head, COUNT(*) as c')
            ->groupBy('head')
            ->orderByDesc('c')
            ->limit(5)
            ->get();

        $this->newLine();
        $this->line('<comment>Top exceptions (prefixo 120 chars):</comment>');
        foreach ($uniqueExceptions as $ex) {
            $this->line("  [{$ex->c}x] " . trim(preg_replace('/\s+/', ' ', $ex->head)));
        }

        // tokens unicos nos payloads
        $tokens = [];
        foreach ((clone $base)->pluck('payload') as $p) {
            if (preg_match('/"tokenId";i:(\d+)/', (string) $p, $m)) {
                $tokens[(int) $m[1]] = ($tokens[(int) $m[1]] ?? 0) + 1;
            }
        }
        $this->newLine();
        $this->line('<comment>Tokens unicos afetados:</comment> ' . count($tokens) . ' (total falhas: ' . array_sum($tokens) . ')');
        if (!empty($tokens)) {
            arsort($tokens);
            foreach (array_slice($tokens, 0, 5, true) as $tid => $c) {
                $exists = DB::table('lead_double_opt_in_tokens')->where('id', $tid)->exists();
                $this->line("  token_id=$tid falhas=$c" . ($exists ? '' : ' <error>(token nao existe mais)</error>'));
            }
        }

        // config horizon vs retry_after
        $this->newLine();
        $this->line('<comment>Config queue.redis:</comment>');
        $cfg = config('queue.connections.redis');
        $this->line('  retry_after=' . ($cfg['retry_after'] ?? '?') . 's');
        $this->line('  block_for=' . ($cfg['block_for'] ?? 'null'));

        // instancias Evolution ativas
        $this->newLine();
        $this->line('<comment>WhatsappInstances ativas por tenant:</comment>');
        $instCount = DB::table('whatsapp_instances')->where('status', 'open')->count();
        $this->line("  total open=$instCount");

        // conectividade Evolution
        $this->newLine();
        $this->line('<comment>Evolution API:</comment>');
        $base = (string) config('services.evolution.base_url', '');
        if ($base === '') {
            $this->error('  services.evolution.base_url NAO CONFIGURADA');
        } else {
            try {
                $resp = \Illuminate\Support\Facades\Http::timeout(5)->get($base);
                $this->line("  GET $base -> HTTP " . $resp->status());
            } catch (\Throwable $e) {
                $this->error('  ERRO: ' . $e->getMessage());
            }
        }

        $sample = (int) $this->option('sample');
        if ($sample > 0) {
            $recent = (clone $base_q = DB::table('failed_jobs')->where('payload', 'like', '%SendDoubleOptInWhatsapp%'))
                ->orderByDesc('id')
                ->limit($sample)
                ->get(['id', 'queue', 'failed_at', 'exception']);

            $this->newLine();
            $this->line('<comment>Ultimas ' . $sample . ' falhas (exception completa 2000 chars):</comment>');
            foreach ($recent as $r) {
                $this->newLine();
                $this->line("--- failed_jobs.id={$r->id} queue={$r->queue} failed_at={$r->failed_at} ---");
                $this->line(substr((string) $r->exception, 0, 2000));
            }
        }

        return self::SUCCESS;
    }
}
