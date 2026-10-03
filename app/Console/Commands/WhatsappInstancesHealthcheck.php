<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Varre todas as WhatsappInstances marcadas status='open' no banco e testa
 * contra a Evolution API (/instance/connectionState). Instâncias que não
 * existem (404) ou retornam erro são classificadas como fantasmas.
 *
 * --mark-ghosts  : atualiza status='disconnected' nas fantasmas (default: dry-run)
 */
class WhatsappInstancesHealthcheck extends Command
{
    protected $signature = 'whatsapp:instances-healthcheck
        {--mark-ghosts : marca fantasmas como disconnected no banco (default: so relata)}';

    protected $description = 'Varre WhatsappInstances status=open e cruza com Evolution API — detecta fantasmas';

    public function handle(): int
    {
        $url    = rtrim((string) config('whatsapp.evolution_api_url', env('EVOLUTION_API_URL', 'https://evo.vivensi.app.br')), '/');
        $apikey = (string) config('whatsapp.evolution_api_key', env('EVOLUTION_API_KEY'));

        if ($apikey === '') {
            $this->error('EVOLUTION_API_KEY ausente.');
            return self::FAILURE;
        }

        $instances = DB::table('whatsapp_instances')
            ->where('status', 'open')
            ->orderBy('tenant_id')
            ->orderBy('id')
            ->get();

        $this->info("Testando {$instances->count()} instancias status=open contra Evolution ($url)");
        $this->newLine();

        $healthy = [];
        $ghosts  = [];
        $errors  = [];

        foreach ($instances as $inst) {
            $name = $inst->instance_name ?? '?';
            try {
                $resp = Http::timeout(8)
                    ->withHeaders(['apikey' => $apikey])
                    ->get("$url/instance/connectionState/$name");

                if ($resp->status() === 200) {
                    $state = data_get($resp->json(), 'instance.state', 'unknown');
                    if ($state === 'open') {
                        $healthy[] = [$inst->id, $inst->tenant_id, $name, "state=$state"];
                    } else {
                        $ghosts[] = [$inst->id, $inst->tenant_id, $name, "state=$state (DB=open)"];
                    }
                } elseif ($resp->status() === 404) {
                    $ghosts[] = [$inst->id, $inst->tenant_id, $name, 'nao existe na Evolution (404)'];
                } else {
                    $errors[] = [$inst->id, $inst->tenant_id, $name, 'HTTP ' . $resp->status()];
                }
            } catch (\Throwable $e) {
                $errors[] = [$inst->id, $inst->tenant_id, $name, 'EXC: ' . substr($e->getMessage(), 0, 60)];
            }
        }

        $this->line('<info>HEALTHY (' . count($healthy) . '):</info>');
        if ($healthy) {
            $this->table(['id', 'tenant', 'instance_name', 'nota'], $healthy);
        } else {
            $this->warn('  (nenhuma)');
        }

        $this->newLine();
        $this->line('<comment>GHOSTS (' . count($ghosts) . '):</comment>');
        if ($ghosts) {
            $this->table(['id', 'tenant', 'instance_name', 'nota'], $ghosts);
        } else {
            $this->line('  (nenhuma)');
        }

        if ($errors) {
            $this->newLine();
            $this->line('<error>ERROS DE REDE (' . count($errors) . '):</error>');
            $this->table(['id', 'tenant', 'instance_name', 'nota'], $errors);
        }

        if ($this->option('mark-ghosts') && $ghosts) {
            $this->newLine();
            if (!$this->confirm('Marcar ' . count($ghosts) . ' instancias fantasmas como status=disconnected?', false)) {
                $this->line('Abortado.');
                return self::SUCCESS;
            }
            $ids = array_column($ghosts, 0);
            $affected = DB::table('whatsapp_instances')
                ->whereIn('id', $ids)
                ->update(['status' => 'disconnected', 'updated_at' => now()]);
            $this->info("Atualizadas $affected linhas (status=disconnected).");
        } elseif ($ghosts) {
            $this->newLine();
            $this->comment('Pra marcar as fantasmas: --mark-ghosts');
        }

        return self::SUCCESS;
    }
}
