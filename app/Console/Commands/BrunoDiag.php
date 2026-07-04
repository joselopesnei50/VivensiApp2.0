<?php

namespace App\Console\Commands;

use App\Models\SubscriptionPlan;
use App\Services\Bruno\BrunoTools;
use Illuminate\Console\Command;

class BrunoDiag extends Command
{
    protected $signature = 'bruno:diag';

    protected $description = 'Diagnóstico do Bot Vendedor Bruno: planos no banco, retorno da tool consultar_planos e últimos tool calls no log';

    public function handle(): int
    {
        $this->info('=== 1. subscription_plans (banco) ===');
        // select * de propósito: tolera drift de schema (ex: price_yearly ausente em ambiente local)
        $plans = SubscriptionPlan::all();
        if ($plans->isEmpty()) {
            $this->warn('Nenhum plano cadastrado.');
        } else {
            $this->table(
                ['ID', 'Nome', 'Público', 'Mensal', 'Anual', 'Ativo'],
                $plans->map(fn ($p) => [
                    $p->id,
                    $p->name,
                    $p->target_audience,
                    $p->price,
                    $p->price_yearly ?? '-',
                    $p->is_active ? 'SIM' : 'NAO',
                ])->all()
            );
        }

        $this->info('=== 2. Tool consultar_planos (o que o Bruno recebe) ===');
        $result = BrunoTools::execute('consultar_planos', []);
        $this->line('total: ' . ($result['total'] ?? '?'));
        foreach (($result['planos'] ?? []) as $p) {
            $this->line("- {$p['nome']} ({$p['painel']}): {$p['preco_mensal']}/mês"
                . ($p['preco_anual'] ? " ou {$p['preco_anual']}/ano" : ''));
        }
        if (($result['total'] ?? 0) === 0) {
            $this->warn('Tool retorna vazio — Bruno responde "sob consulta" até haver plano ativo.');
        }

        $this->info('=== 3. Últimos tool calls do Bruno no log ===');
        $found = 0;
        $files = glob(storage_path('logs/*.log')) ?: [];
        rsort($files);
        foreach (array_slice($files, 0, 3) as $file) {
            foreach (array_reverse(file($file) ?: []) as $line) {
                if (str_contains($line, 'BrunoTools: execute')) {
                    $this->line(trim($line));
                    if (++$found >= 5) {
                        break 2;
                    }
                }
            }
        }
        if ($found === 0) {
            $this->warn('Nenhum "BrunoTools: execute" nos 3 logs mais recentes.');
            $this->warn('Ou o Bruno não chamou tools, ou LOG_LEVEL está acima de "info" (confira LOG_LEVEL no .env).');
        }

        return self::SUCCESS;
    }
}
