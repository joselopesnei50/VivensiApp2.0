<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EvolutionApiService;
use Illuminate\Support\Facades\Log;

class TestEvolutionConnection extends Command
{
    protected $signature = 'evolution:test {--instance=teste} {--number=}';
    protected $description = 'Testa criação de instância e conexão com Evolution API';

    public function handle(EvolutionApiService $evolutionService)
    {
        $instanceName = $this->option('instance') . '_' . uniqid();
        $number = $this->option('number');

        $this->info("🔄 Criando instância: {$instanceName}");
        
        $result = $evolutionService->createInstance($instanceName, 'test_token_' . uniqid(), $number);

        if (isset($result['error'])) {
            $this->error("❌ Erro: " . $result['error']);
            $this->line("Detalhes: " . ($result['details'] ?? 'Nenhum'));
            Log::error('EvolutionTestCommand', $result);
            return 1;
        }

        $this->info("✅ Instância criada com sucesso!");
        $this->line("Resposta: " . json_encode($result, JSON_PRETTY_PRINT));

        // Se número fornecido, tenta obter pairing code
        if ($number) {
            $this->info("🔄 Solicitando Pairing Code para {$number}...");
            // Recria o service com o contexto (se necessário, ajuste)
            $pairingResult = $evolutionService->getPairingCode($number);
            if (isset($pairingResult['pairingCode']) || isset($pairingResult['code'])) {
                $code = $pairingResult['pairingCode'] ?? ($pairingResult['code'] ?? 'N/A');
                $this->info("✅ Pairing Code: {$code}");
            } else {
                $this->error("❌ Falha ao obter Pairing Code: " . json_encode($pairingResult));
            }
        }

        return 0;
    }
}
