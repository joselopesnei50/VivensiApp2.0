<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EvolutionApiService;
use Illuminate\Support\Facades\Http;

class EvolutionSetupAgent extends Command
{
    protected $signature = 'evolution:setup {--test-instance=vivensi_master}';
    protected $description = 'O Agente de Configuração Evolution 2.0: Guia passo-a-passo para o sucesso da integração.';

    public function handle(EvolutionApiService $evo)
    {
        $this->info("🤖 Iniciando Agente de Configuração Evolution 2.0...");
        $this->line("---");

        // 1. Validar Variáveis de Ambiente
        $baseUrl = config('whatsapp.evolution_api_url');
        $globalKey = config('whatsapp.evolution_global_key');

        $this->comment("🔍 Verificando Configurações Locais:");
        $this->line("URL: " . ($baseUrl ?: 'NÃO DEFINIDA (Usando fallback http://localhost:8080)'));
        $this->line("Key: " . ($globalKey ? '********' . substr($globalKey, -4) : 'NÃO DEFINIDA'));

        // 2. Testar Conectividade Bruta
        $target = $baseUrl ?: 'https://evo.vivensi.app.br';
        $this->info("\n📡 Testando conexão com: $target");

        try {
            $response = Http::timeout(5)->withoutVerifying()->withHeaders([
                'apikey' => $globalKey
            ])->get("$target/instance/connectionState/ping");

            if ($response->successful() || $response->status() === 404) {
                $this->info("✅ API ESTÁ VIVA! O Laravel conseguiu falar com a Evolution.");
            } else {
                $this->error("❌ FALHA DE AUTENTICAÇÃO (HTTP {$response->status()})");
                $this->line("Dica: Verifique se a GLOBAL_KEY no .env é igual à do Docker.");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ FALHA DE REDE: " . $e->getMessage());
            $this->line("Dica: Se estiver usando portas diferentes de 8080, ajuste no .env.");
            return 1;
        }

        // 3. Testar Provisionamento (Opcional - Criar instância de teste)
        $testName = $this->option('test-instance');
        if ($this->confirm("\n🚀 Deseja testar a criação de uma instância mestre ('{$testName}')?", true)) {
            $this->info("🔄 Provisionando {$testName}...");
            $res = $evo->createInstance($testName, "master_token_" . uniqid());

            if (isset($res['error'])) {
                $this->error("❌ FALHA NO PROVISIONAMENTO!");
                $this->line("Motivo: " . ($res['error'] ?? 'Erro desconhecido'));
                $this->line("Resposta da API: " . json_encode($res['details'] ?? 'SEM RESPOSTA'));
                $this->comment("\nDica: Se a resposta for 'Unauthorized', sua GLOBAL_KEY na AWS não é a mesma do Laravel.");
            } else {
                $this->info("✅ INSTÂNCIA CRIADA COM SUCESSO!");
                $this->line("Agora você pode ir ao painel e conectar seu WhatsApp.");
            }
        }

        $this->line("\n---");
        $this->info("🎯 Diagnóstico concluído. O sistema está pronto para operar!");
        return 0;
    }
}
