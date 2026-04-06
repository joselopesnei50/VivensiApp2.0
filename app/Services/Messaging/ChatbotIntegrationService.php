<?php

namespace App\Services\Messaging;

use App\Models\WhatsappConfig;
use App\Services\EvolutionApiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotIntegrationService
{
    /**
     * @var EvolutionApiService
     */
    protected $apiService;

    public function __construct(EvolutionApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Ativa e configura a integração com o Typebot nativamente na Evolution API
     *
     * @param WhatsappConfig $config
     * @param array $typebotConfig ['url' => ..., 'bot_name' => ...]
     * @return array|null
     */
    public function connectTypebot(WhatsappConfig $config, array $typebotConfig): ?array
    {
        $baseUrl = config('whatsapp.evolution_api_url', env('EVOLUTION_API_URL', 'http://localhost:8080'));
        $apiKey = $config->evolution_instance_token ?? config('whatsapp.evolution_global_key', env('EVOLUTION_GLOBAL_KEY'));
        
        try {
            $response = Http::withHeaders([
                'apikey' => $apiKey
            ])->post("{$baseUrl}/typebot/set/{$config->instance_name}", [
                'enabled' => true,
                'url' => rtrim($typebotConfig['url'], '/'),
                'typebot' => $typebotConfig['bot_name'],
                'expire' => 0,
                'keywordFinish' => '#SAIR',
                'delayMessage' => 1000,
                'unknownMessage' => 'Não entendi, pode repetir?',
                'listeningFromMe' => false,
                'stopBotFromMe' => true, // Pausa o bot se o usuário humano intervir
                'keepOpen' => true,
                'debounceTime' => 10
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Erro ao configurar Typebot: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Ativa e configura a integração com Dify
     *
     * @param WhatsappConfig $config
     * @param array $difyConfig ['api_url' => ..., 'api_key' => ...]
     * @return array|null
     */
    public function connectDify(WhatsappConfig $config, array $difyConfig): ?array
    {
        $baseUrl = config('whatsapp.evolution_api_url', env('EVOLUTION_API_URL', 'http://localhost:8080'));
        $apiKey = $config->evolution_instance_token ?? config('whatsapp.evolution_global_key', env('EVOLUTION_GLOBAL_KEY'));
        
        try {
            $response = Http::withHeaders([
                'apikey' => $apiKey
            ])->post("{$baseUrl}/dify/set/{$config->instance_name}", [
                'enabled' => true,
                'botKey' => $difyConfig['api_key'],
                'apiUrl' => rtrim($difyConfig['api_url'], '/'),
                'triggerType' => 'all', // all ou keyword
                'generateAudio' => false
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Erro ao configurar Dify: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
