<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Evolution API Service v2
 * 
 * Substitui o ZApiService com suporte a Multi-Tenant (ONG e Gestores) e 
 * recursos nativos Anti-Ban (Spintax, delay, presença de digitação).
 */
class EvolutionApiService
{
    protected $instanceName;
    protected $apiKey; // Token/ApiKey da instância
    protected $globalApiKey;
    protected $baseUrl;
    protected $contextModel;

    /**
     * @param \Illuminate\Database\Eloquent\Model|null $contextModel (App\Models\Tenant ou App\Models\User)
     */
    public function __construct($contextModel = null)
    {
        $this->baseUrl = config('whatsapp.evolution_api_url', env('EVOLUTION_API_URL', 'http://localhost:8080'));
        $this->globalApiKey = config('whatsapp.evolution_global_key', env('EVOLUTION_GLOBAL_KEY', 'global-api-key-here'));
        $this->contextModel = $contextModel;

        if ($contextModel) {
            $this->instanceName = $contextModel->evolution_instance_name ?? '';
            // Usa o token da instância se disponível, senão usa a globalApiKey como fallback
            $this->apiKey = !empty($contextModel->evolution_instance_token)
                ? $contextModel->evolution_instance_token
                : $this->globalApiKey;
        }
    }

    /**
     * Cria uma instância de forma 'Atômica' (Criação + Webhook + Configurações).
     * Isso garante que a Evolution já saiba para onde enviar o QR Code no primeiro segundo.
     */
    public function createInstance(string $name, string $clientToken = null, ?string $number = null): array
    {
        $webhookUrl  = config('app.url') . "/api/whatsapp/webhook?token=" . $clientToken;

        $payload = [
            'instanceName' => $name,
            'qrcode'       => true,
            'integration'  => 'WHATSAPP-BAILEYS',
        ];

        if ($number) {
            $payload['number'] = preg_replace('/\D/', '', $number);
        }

        // Configurações LocalSettings e Webhook Integrado
        $payload = array_merge($payload, [
            'rejectCall'   => false,
            'groupsIgnore' => true,
            'alwaysOnline' => true,
            'readMessages' => true,
            'readStatus'   => true,
            'webhook' => [
                'enabled' => true,
                'url'     => $webhookUrl,
                'byEvents' => false,
                'base64'   => true,
                'events'   => [
                    'qrcode.updated',
                    'connection.update',
                    'messages.upsert',
                    'messages.update',
                    'send.message'
                ]
            ]
        ]);

        try {
            $response = Http::timeout(120)->withoutVerifying()->withHeaders([ // Timeout estendido para 120s e Bypass SSL
                'apikey' => $this->globalApiKey
            ])->post("{$this->baseUrl}/instance/create", $payload);

            if ($response->successful()) {
                Log::info('Evolution API: Instance created successfully', ['name' => $name, 'resp' => $response->json()]);
                return array_merge($response->json(), ['generated_token' => $this->globalApiKey]);
            }

            Log::error('Evolution API: Atomic create failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'url'    => "{$this->baseUrl}/instance/create"
            ]);

            return ['error' => 'Falha na criação da instância', 'details' => $response->body()];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            try {
                Log::error('Evolution API: ConnectionException', [
                    'msg' => $e->getMessage(),
                    'url' => "{$this->baseUrl}/instance/create"
                ]);
            } catch (\Exception $logEx) {}
            return ['error' => 'Falha de conexão com o servidor Evolution (Timeout/Rede)', 'details' => $e->getMessage()];

        } catch (\Exception $e) {
            try {
                Log::error('Evolution API: Exception', [
                    'msg' => $e->getMessage(),
                    'class' => get_class($e)
                ]);
            } catch (\Exception $logEx) {}
            return ['error' => 'Erro inesperado na conexão com API', 'details' => $e->getMessage()];
        }
    }

    /**
     * @deprecated Configuração agora é atômica na criação.
     */
    public function setWebhook(string $instanceName, string $clientToken = null)
    {
        return true;
    }

    /**
     * Busca o código de conexão (QR ou Pairing) diretamente da API da Evolution.
     * Útil quando o Webhook ainda não populou o cache local.
     */
    public function fetchConnectionCode(string $instanceName): array
    {
        try {
            // 1. Verificamos o estado atual (v2)
            $stateRes = Http::timeout(10)->withoutVerifying()->withHeaders([
                'apikey' => $this->globalApiKey
            ])->get("{$this->baseUrl}/instance/connectionState/{$instanceName}");
            
            $state = $stateRes->json()['instance']['state'] ?? 'close';

            // 2. Se estiver 'close', dispara o comando de conexão (wake up)
            if ($state === 'close') {
                Http::timeout(10)->withoutVerifying()->withHeaders([
                    'apikey' => $this->globalApiKey
                ])->get("{$this->baseUrl}/instance/connect/{$instanceName}");
                
                // Pequena pausa para a Evolution inicializar
                usleep(500000); // 500ms
            }

            // 3. Busca o código de conexão
            $response = Http::timeout(20)->withoutVerifying()->withHeaders([
                'apikey' => $this->globalApiKey
            ])->get("{$this->baseUrl}/instance/connect/{$instanceName}");
 
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'qrcode'      => $data['base64'] ?? ($data['qrcode']['base64'] ?? null),
                    'pairingCode' => $data['pairingCode'] ?? ($data['qrcode']['pairingCode'] ?? null),
                    'status'      => $data['instance']['status'] ?? ($data['status'] ?? 'unknown'),
                    'state'       => $data['instance']['state'] ?? ($data['state'] ?? 'unknown'),
                ];
            }
 
            return ['error' => 'Instância inicializando... tente novamente em instantes.'];
        } catch (\Exception $e) {
            try {
                Log::error('Evolution API: Exception in fetchConnectionCode', ['msg' => $e->getMessage()]);
            } catch (\Exception $logEx) {}
            return ['error' => 'Falha de comunicação: ' . $e->getMessage()];
        }
    }

    /**
     * Solicita o Pairing Code para conectar a instância ao número de telefone.
     */
    public function getPairingCode(string $phoneNumber): array
    {
        if (!$this->instanceName || !$this->apiKey) {
            return ['error' => 'Instance not configured.'];
        }

        // Garante somente dígitos (DDI+DDD+número, ex: 5511999999999)
        $number = preg_replace('/\D/', '', $phoneNumber);

        // Adiciona DDI 55 (Brasil) automaticamente se o número tiver 10 ou 11 dígitos
        if (strlen($number) <= 11 && !str_starts_with($number, '55')) {
            $number = '55' . $number;
        }

        try {
            // Verifica estado atual da instância (Usa Global Key)
            $stateRes = Http::timeout(10)->withoutVerifying()->withHeaders([
                'apikey' => $this->globalApiKey,
            ])->get("{$this->baseUrl}/instance/connectionState/{$this->instanceName}");

            $state = $stateRes->json()['instance']['state'] ?? 'close';

            // Se a instância estiver fechada, inicia o processo de conexão (Usa Global Key para gestão)
            if ($state === 'close') {
                Http::timeout(10)->withoutVerifying()->withHeaders([
                    'apikey' => $this->globalApiKey,
                ])->get("{$this->baseUrl}/instance/connect/{$this->instanceName}");
                sleep(1);
            }

            // Solicita o pairing code via POST com o número no corpo (padrão v2)
            $response = Http::timeout(30)->withoutVerifying()->withHeaders([
                'apikey' => $this->globalApiKey,
            ])->post("{$this->baseUrl}/instance/connect/{$this->instanceName}", [
                'number' => $number,
            ]);

            Log::info('Evolution API pairingCode response', [
                'instance' => $this->instanceName,
                'number'   => $number,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('Evolution API pairingCode exception', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Obtém o QR Code da instância em base64 (para instâncias em modo QR Code).
     */
    public function getConnectionQr(): array
    {
        if (!$this->instanceName) {
            return ['error' => 'Instance not configured.'];
        }

        try {
            $response = Http::timeout(15)->withoutVerifying()->withHeaders([
                'apikey' => $this->globalApiKey,
            ])->get("{$this->baseUrl}/instance/connect/{$this->instanceName}");

            return $response->json() ?? [];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }


    /**
     * Obtém status de conexão da instância (open, connecting, close).
     */
    public function getConnectionState(): array
    {
        if (!$this->instanceName) {
            return ['error' => 'Instance not configured.'];
        }

        try {
            $response = Http::timeout(10)->withHeaders([
                'apikey' => $this->globalApiKey
            ])->get("{$this->baseUrl}/instance/connectionState/{$this->instanceName}");

            return $response->json();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Desloga o WhatsApp do dispositivo.
     */
    public function logout(): array
    {
        if (!$this->instanceName) {
            return ['error' => 'Instance not configured.'];
        }

        $response = Http::withoutVerifying()->withHeaders([
            'apikey' => $this->globalApiKey
        ])->delete("{$this->baseUrl}/instance/logout/{$this->instanceName}");

        return $response->json();
    }

    /**
     * Envia mensagem de texto com recursos Anti-Ban.
     * 
     * @param string $to Número com código do país (DDI + DDD + Num)
     * @param string $message Mensagem bruta (pode conter Spintax {Olá|Oi})
     * @param string|null $idempotencyKey Chave opcional de prevenção de duplicação
     * @param int $delaySeconds Segundos que ficará "Digitando" antes de enviar
     */
    public function sendMessage(string $to, string $message, ?string $idempotencyKey = null, int $delaySeconds = 0): array
    {
        if (config('whatsapp.sandbox_enabled', false) && app()->environment('local')) {
            return [
                'messageId' => 'SANDBOX_' . uniqid(),
                'status' => 'PENDING',
                'message' => 'Message sent (Sandbox)',
            ];
        }

        if (!$this->instanceName || !$this->apiKey) {
            return ['error' => 'Evolution API Configuration Missing'];
        }

        // REGRA DEFINITIVA: NÃO limpe o JID. 
        // A Evolution API (createJid.ts) precisa do sufixo (@s.whatsapp.net ou @lid)
        // para encontrar o contato correto. Limpar o número causa erro 404 ou mensagem fantasma.
        $number = (string) $to;

        // Processa o Spintax transformando {A|B} em apenas um dos itens
        $renderedMessage = $this->applySpintax($message);

        // SOLUÇÃO DEFINITIVA (Baseada no Código-Fonte da Evolution API):
        // O código da sua Evolution espera um payload PLANO para sendText.
        $payload = [
            'number' => $number,
            'text'   => $renderedMessage,
            'delay'  => $delaySeconds > 0 ? $delaySeconds * 1000 : 1500,
            'linkPreview' => false,
        ];

        try {
            // Log detalhado do envio para diagnóstico
            Log::info('Evolution API Outbound Request', [
                'instance' => $this->instanceName,
                'target'   => $number,
                'url'      => "{$this->baseUrl}/message/sendText/{$this->instanceName}",
                'payload'  => $payload
            ]);

            $response = Http::retry(3, 200, function ($exception, $request) {
                if (!$exception instanceof \Illuminate\Http\Client\RequestException) return false;
                $status = $exception->response->status();
                return in_array($status, [429, 500, 502, 503, 504]);
            }, throw: false)
            ->timeout(20)
            ->withoutVerifying()
            ->withHeaders([
                'apikey' => $this->apiKey 
            ])
            ->post("{$this->baseUrl}/message/sendText/{$this->instanceName}", $payload);

            if ($response->failed()) {
                // REGRA 3: Forçar a Exposição do Erro
                Log::error('EVOLUTION API REJEITOU O ENVIO', [
                    'status_code'   => $response->status(),
                    'response_body' => $response->json() ?? $response->body(),
                    'payload_enviado' => $payload,
                    'instance' => $this->instanceName
                ]);

                return [
                    'error' => 'Failed to send message',
                    'status' => $response->status(),
                    'details' => $response->body(),
                ];
            }

            // Log de sucesso conforme o padrão solicitado
            Log::info('EVOLUTION API ACEITOU O ENVIO', ['response' => $response->json()]);
            return $response->json();

        } catch (\Exception $e) {
            Log::error('Evolution API exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ['error' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Aplica a lógica de Spintax numa string.
     * Exemplo: "{Olá|Oi|Eae} tudo {bom|bem}?"
     */
    public function applySpintax(string $text): string
    {
        // Encontra tudo dentro de colchetes {...} e extrai aleatoriamente
        return preg_replace_callback('/\{(((?>[^\{\}]+)|(?R))*)\}/x', function ($match) {
            $options = explode('|', $match[1]);
            return $options[array_rand($options)];
        }, $text);
    }

    /**
     * Garante que o ID seja um JID válido.
     * Se não tiver @, adiciona @s.whatsapp.net. 
     * Se tiver, remove espaços/caracteres inválidos mantendo o sufixo.
     */
    public function formatJid(string $id): string
    {
        // Conforme a Regra 3 do usuário, para envio na Evolution v2, 
        // o campo 'number' deve conter apenas os dígitos puros.
        return preg_replace('/[^0-9]/', '', $id);
    }

    /**
     * Resolve um ID (@lid ou número) para um JID padrão via API.
     */
    public function fetchProfile(string $id): array
    {
        if (!$this->instanceName || !$this->apiKey) return [];

        try {
            // No Evolution V2, usamos o checkNumbers para validar e obter o JID real
            $response = Http::timeout(10)->withHeaders([
                'apikey' => $this->apiKey
            ])->post("{$this->baseUrl}/chat/checkNumbers/{$this->instanceName}", [
                'numbers' => [$id]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data[0] ?? []; // Retorna o primeiro resultado (jid, exists, etc)
            }
        } catch (\Exception $e) {
            Log::error('Evolution API fetchProfile error', ['err' => $e->getMessage()]);
        }
        return [];
    }

    /**
     * Obtém o JID da própria instância (bot).
     */
    public function getBotJid(): ?string
    {
        try {
            $res = $this->getConnectionState();
            return $res['instance']['ownerJid'] ?? $res['instance']['owner'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Envia o status de presença (ex: composing) para simular digitação
     */
    public function sendPresence(string $instanceName, string $number, string $presence = 'composing'): void
    {
        if (!$this->apiKey) return;

        try {
            Http::timeout(5)->withoutVerifying()->withHeaders([
                'apikey' => $this->apiKey
            ])->post("{$this->baseUrl}/chat/sendPresence/{$instanceName}", [
                'number' => $number,
                'presence' => $presence
            ]);
        } catch (\Exception $e) {
            Log::warning("Erro ao enviar presença para {$number} na instância {$instanceName}");
        }
    }
}
