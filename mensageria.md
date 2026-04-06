Como Engenheiro Chefe do sistema Vivensi, projetei a estrutura do novo Módulo de Mensageria e Automação focado em escalabilidade multi-tenant e segurança operacional (anti-ban).

Abaixo está a implementação técnica utilizando Laravel 11, Reverb e integração com Evolution API v2.

1. Banco de Dados: Migration whatsapp_instances
Esta tabela é o coração do multi-tenancy, vinculando cada instância da Evolution API a um usuário do Vivensi.
+2

PHP
Schema::create('whatsapp_instances', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Multi-tenant [cite: 163]
    $table->string('instance_name')->unique(); // Nome na Evolution API [cite: 163]
    $table->string('instance_token')->nullable(); // Token gerado pela API [cite: 206]
    $table->enum('status', ['open', 'connecting', 'close', 'paused'])->default('close'); [cite: 303, 306]
    $table->boolean('is_anti_ban_active')->default(true);
    $table->integer('daily_limit')->default(500);
    $table->json('settings')->nullable(); // Janelas de horário e delays [cite: 45]
    $table->timestamps();
});
2. Core: EvolutionServiceProvider.php
Este provider isola a lógica de comunicação, garantindo que o HttpClient do Laravel esteja configurado com os headers globais da Evolution API.

PHP
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;

class EvolutionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('evolution.client', function () {
            return Http::withHeaders([
                'apikey' => config('services.evolution.apikey'), // Global API Key [cite: 292]
                'Content-Type' => 'application/json',
            ])->baseUrl(config('services.evolution.base_url')); [cite: 205]
        });
    }
}
3. Inteligência Anti-Ban: AntiBanManager.php
Esta classe simula o comportamento humano orgânico, gerenciando estados de presença e cadência.

PHP
namespace App\Services\Messaging;

use App\Models\WhatsappInstance;
use Carbon\Carbon;

class AntiBanManager
{
    public function canSendMessage(WhatsappInstance $instance): bool
    {
        $now = Carbon::now();
        $settings = $instance->settings;

        // 1. Validação de Janela de Horário (Ex: 22h às 07h)
        $start = Carbon::createFromTimeString($settings['window_start'] ?? '07:00');
        $end = Carbon::createFromTimeString($settings['window_end'] ?? '22:00');

        if (!$now->between($start, $end)) {
            return false;
        }

        // 2. Verificação de limite diário [cite: 128]
        $sentToday = $instance->messages()->whereDate('created_at', Carbon::today())->count();
        return $sentToday < $instance->daily_limit;
    }

    public function getRandomDelay(int $baseDelay = 15): int
    {
        // Adiciona variação aleatória para simular comportamento humano
        return rand($baseDelay, $baseDelay + 45); [cite: 504]
    }
}
4. Automação: BroadcastMessageJob.php
Este Job processa o envio com throughput limitado, utilizando o Laravel Horizon para gerenciar as filas de forma eficiente.
+2

PHP
namespace App\Jobs;

use App\Models\WhatsappInstance;
use App\Services\Messaging\AntiBanManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class BroadcastMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3; // Tentativas em caso de falha [cite: 492]
    public $backoff = [60, 300]; // Delay entre tentativas [cite: 504]

    public function __construct(
        protected WhatsappInstance $instance,
        protected string $to,
        protected string $message
    ) {}

    public function handle(AntiBanManager $antiBan)
    {
        if (!$antiBan->canSendMessage($this->instance)) {
            $this->release(now()->addMinutes(30)); // Reagendar se fora da janela [cite: 503]
            return;
        }

        $client = app('evolution.client');

        // Etapa 1: Simular "Digitando..." 
        $client->post("/chat/sendPresence/{$this->instance->instance_name}", [
            'number' => $this->to,
            'presence' => 'composing'
        ]);

        sleep(rand(2, 5)); // Delay humano de digitação

        // Etapa 2: Envio Real da Mensagem [cite: 205, 308]
        $response = $client->post("/message/sendText/{$this->instance->instance_name}", [
            'number' => $this->to,
            'text' => $this->message,
            'delay' => $antiBan->getRandomDelay()
        ]);

        if ($response->successful()) {
            $this->logSuccess($response->json());
        } else {
            $this->fail($response->body());
        }
    }

    protected function logSuccess($data)
    {
        // Registro de log para o dashboard do cliente Vivensi [cite: 308, 457]
    }
}
Próximos Passos Sugeridos:

Deseja que eu crie a Controller de Webhooks para processar as respostas do Typebot e Chatwoot integradas ao Vivensi? 
+2


Gostaria de ver a interface do Dashboard em React para monitorar as instâncias em tempo real via Laravel Reverb? 
+1

Como Engenheiro Chefe do Vivensi, projetei a solução completa integrando o ecossistema Laravel com a Evolution API v2. Esta arquitetura garante isolamento de dados por usuário (multi-tenancy), segurança operacional com blindagem inteligente e processamento em tempo real.
+4

Abaixo estão os componentes estruturais do sistema:

1. Arquitetura de Instâncias (Multi-tenant)
Cada cliente do Vivensi gerencia suas próprias instâncias. O sistema utiliza UUIDs e nomes de instância únicos para garantir o isolamento na Evolution API.
+1

Migration: whatsapp_instances

PHP
Schema::create('whatsapp_instances', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->index(); // Vínculo com o cliente Vivensi
    $table->string('instance_name')->unique(); // Ex: vivensi_prod_01
    $table->string('instance_token')->nullable(); // Apikey da instância na API
    $table->enum('status', ['open', 'connecting', 'close', 'paused'])->default('close');
    $table->boolean('anti_ban_active')->default(true);
    $table->json('settings')->nullable(); // Janelas de horário e limites diários [cite: 45]
    $table->timestamps();
});
2. Service Provider: EvolutionServiceProvider.php
Este provedor encapsula a comunicação HTTP com a API, tratando a autenticação global via headers.

PHP
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;

class EvolutionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('evolution.api', function () {
            return Http::withHeaders([
                'apikey' => config('services.evolution.key'), // Global API Key [cite: 292]
                'Content-Type' => 'application/json',
            ])->baseUrl(config('services.evolution.url'));
        });
    }
}
3. Módulo de Blindagem Inteligente: AntiBanManager.php
Gerencia o "aquecimento" do número e simula comportamento humano orgânico através de estados de presença.
+2

PHP
namespace App\Services\Messaging;

use App\Models\WhatsappInstance;
use Carbon\Carbon;

class AntiBanManager
{
    public function canSend(WhatsappInstance $instance): bool
    {
        $settings = $instance->settings;
        $now = Carbon::now();

        // 1. Janela de Horário (Anti-Denúncia)
        $start = Carbon::parse($settings['safe_start'] ?? '08:00');
        $end = Carbon::parse($settings['safe_end'] ?? '21:00');
        if (!$now->between($start, $end)) return false;

        // 2. Limite de Aquecimento (Throughput Progressivo)
        $sentToday = $instance->messages()->whereDate('created_at', Carbon::today())->count();
        return $sentToday < ($settings['daily_limit'] ?? 100);
    }

    public function simulateHumanInteraction(string $instanceName, string $jid): void
    {
        $api = app('evolution.api');
        
        // Simula "Digitando..." [cite: 310, 312]
        $api->post("/chat/sendPresence/{$instanceName}", [
            'number' => $jid,
            'presence' => 'composing'
        ]);
        
        sleep(rand(3, 8)); // Delay randômico humano
    }
}
4. Engine de Disparo e Filas: BroadcastMessageJob.php
Utiliza Laravel Jobs para garantir que o envio em massa respeite os limites de processamento e cadência.
+1

PHP
namespace App\Jobs;

use App\Models\WhatsappInstance;
use App\Services\Messaging\AntiBanManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class BroadcastMessageJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        protected WhatsappInstance $instance,
        protected string $to,
        protected string $text
    ) {}

    public function handle(AntiBanManager $antiBan): void
    {
        if (!$antiBan->canSend($this->instance)) {
            $this->release(now()->addMinutes(30));
            return;
        }

        $antiBan->simulateHumanInteraction($this->instance->instance_name, $this->to);

        $response = app('evolution.api')->post("/message/sendText/{$this->instance->instance_name}", [
            'number' => $this->to,
            'text' => $this->text,
            'delay' => rand(1500, 4000) // Delay adicional na API [cite: 295]
        ]);

        if ($response->successful()) {
            // Log de sucesso para o dashboard do cliente
            broadcast(new \App\Events\MessageStatusUpdated($this->instance->user_id, $this->to, 'sent')); // Real-time [cite: 331]
        }
    }
}
5. Engine de Chatbot e Webhooks
O sistema recebe eventos da Evolution API e os processa via filas para automação de respostas.
+2


Webhook Listener: Recebe o evento messages.upsert.
+1


Controller: Valida a assinatura do webhook (segurança) e despacha para a fila de atendimento.
+1


Processamento: O sistema consulta as "Respostas Rápidas" cadastradas pelo usuário do Vivensi no banco de dados e responde automaticamente se houver match de palavras-chave.
+1

6. Infraestrutura de Monitoramento: Laravel Horizon
Utilizamos o Horizon para gerenciar as filas do Redis, garantindo o balanceamento de carga entre as centenas de instâncias dos usuários.
+1

PHP
// config/horizon.php
'production' => [
    'supervisor-vivensi' => [
        'connection' => 'redis',
        'queue' => ['broadcasts', 'chatbots'],
        'balance' => 'auto', // Balanceamento dinâmico por carga [cite: 511]
        'maxProcesses' => 15,
    ],
],

Próximo Passo: Gostaria que eu configurasse os Webhooks específicos para o Typebot ou Dify integrados ao Vivensi para fluxos de IA mais complexos?
+1

Como Engenheiro Chefe do sistema Vivensi, projetei a integração avançada de automação e monitoramento em tempo real. Esta etapa final conecta o motor de IA (Typebot/Dify) ao seu backend e entrega a interface de acompanhamento para os usuários do sistema.1. Integração de IA: Typebot & DifyO Vivensi permite que o usuário vincule fluxos complexos de IA às suas instâncias de WhatsApp. A Evolution API v2 facilita essa conexão através de endpoints dedicados.Configuração via Service (Backend)Esta lógica ativa o bot na instância do cliente, permitindo que a IA assuma o atendimento automaticamente.PHP// app/Services/Messaging/ChatbotIntegrationService.php
public function connectTypebot(WhatsappInstance $instance, array $config)
{
    // Ativa a integração do Typebot na Evolution API 
    return app('evolution')->post("/typebot/set/{$instance->instance_name}", [
        'enabled' => true,
        'url' => $config['url'], // URL do seu servidor Typebot [cite: 45]
        'bot' => $config['bot_name'],
        'delayMessage' => 1000,
        'unknownMessage' => 'Não entendi, pode repetir?',
        'listeningFromMe' => false, // Evita responder às próprias mensagens [cite: 176]
        'stopBotFromMe' => true // Pausa o bot se o usuário humano do Vivensi intervir [cite: 176]
    ]);
}
2. Monitoramento Real-Time: Laravel ReverbPara que o usuário do Vivensi veja as mensagens e o status das instâncias sem atualizar a página, utilizamos o Laravel Reverb, um servidor WebSocket nativo de alta performance.Evento de TransmissãoPHP// app/Events/InstanceStatusChanged.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class InstanceStatusChanged implements ShouldBroadcast
{
    use InteractsWithSockets;

    public function __construct(
        public int $userId, 
        public string $instanceName, 
        public string $status
    ) {}

    public function broadcastOn(): Channel
    {
        // Canal privado para garantir o Multi-tenancy (segurança de dados) [cite: 364]
        return new PrivateChannel("user.{$this->userId}");
    }
}
3. Frontend: Dashboard em React (Vite)Abaixo, o componente React que consome os dados do backend e escuta o Reverb para atualizações dinâmicas.JavaScript// resources/js/components/InstanceMonitor.jsx
import React, { useEffect, useState } from 'react';
import Echo from 'laravel-echo'; // Laravel Echo para escuta de eventos [cite: 395]

const InstanceMonitor = ({ userId }) => {
    const [instances, setInstances] = useState([]);

    useEffect(() => {
        // Configuração do canal via Laravel Echo e Reverb [cite: 396, 414]
        window.Echo.private(`user.${userId}`)
            .listen('InstanceStatusChanged', (e) => {
                setInstances(prev => prev.map(inst => 
                    inst.name === e.instanceName ? { ...inst, status: e.status } : inst
                ));
            });

        return () => window.Echo.leave(`user.${userId}`); // Limpeza para evitar vazamento de memória [cite: 416]
    }, [userId]);

    return (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {instances.map(instance => (
                <div key={instance.name} className="p-4 border rounded shadow">
                    <h3 className="font-bold">{instance.name}</h3>
                    <span className={`badge ${instance.status === 'open' ? 'bg-green-500' : 'bg-red-500'}`}>
                        {instance.status.toUpperCase()}
                    </span>
                    <p className="text-sm">Status em tempo real via Reverb </p>
                </div>
            ))}
        </div>
    );
};
4. Gestão de Filas: Horizon & Anti-BanPara garantir que as automações do Typebot e os disparos em massa não resultem em bloqueios, o Laravel Horizon deve ser configurado com prioridades.Fila high: Mensagens de chatbot (IA) que precisam de resposta imediata.Fila low: Disparos em massa (Bulk), processados com cadência lenta para respeitar a blindagem.PHP// config/horizon.php
'environments' => [
    'production' => [
        'supervisor-vivensi' => [
            'connection' => 'redis',
            'queue' => ['high', 'default', 'low'], // Ordem de prioridade [cite: 534]
            'balance' => 'auto', // Balanceamento automático de processos [cite: 511]
            'maxProcesses' => 20,
        ],
    ],
],
Resumo da Entrega CompletaArquitetura Multi-tenant: Banco de dados isolado por user_id.Integração API: EvolutionServiceProvider centralizando a Evolution API v2.Blindagem: AntiBanManager com simulação humana ("digitando...").IA: Conexão nativa com Typebot e Dify para fluxos automáticos.Real-time: Laravel Reverb para dashboards dinâmicos sem refresh.Escalabilidade: Laravel Horizon monitorando filas de Redis.
Como Engenheiro Chefe do sistema Vivensi, preparei o arquivo .env definitivo e a estrutura final de configuração para o ambiente de produção. Este arquivo integra o backend Laravel com a Evolution API v2, o servidor de WebSockets Laravel Reverb, o banco de dados e o sistema de filas Redis/Horizon.1. Configuração do Ambiente (.env)Este arquivo contém as credenciais críticas para o funcionamento do Módulo de Mensageria e Automação.Bash# --- VIVENSI: CONFIGURAÇÕES GERAIS ---
APP_NAME=Vivensi
APP_ENV=production
APP_KEY=base64:$(php artisan key:generate --show)
APP_DEBUG=false
APP_URL=https://vivensi.com.br

# --- BANCO DE DADOS (MYSQL) ---
# Compatível com os esquemas de mensagens e instâncias do sistema [cite: 162, 163, 167]
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vivensi_prod
DB_USERNAME=admin_vivensi
DB_PASSWORD=sua_senha_segura

# --- EVOLUTION API V2 ---
# Integração principal para WhatsApp, Typebot e Dify [cite: 1, 5, 6]
EVOLUTION_API_URL=https://api.vivensi.com.br
EVOLUTION_API_KEY=sua_global_apikey_da_evolution_aqui
EVOLUTION_WEBHOOK_ENABLED=true
EVOLUTION_WEBHOOK_URL=${APP_URL}/api/v1/webhooks/evolution

# --- LARAVEL REVERB (REAL-TIME) ---
# Servidor de WebSockets nativo para dashboards dinâmicos [cite: 329, 330, 382]
REVERB_APP_ID=vivensi-app
REVERB_APP_KEY=vivensi-key
REVERB_APP_SECRET=vivensi-secret
REVERB_HOST="0.0.0.0"
REVERB_PORT=8080
REVERB_SCHEME=https

# --- REDIS & HORIZON (FILAS) ---
# Essencial para o throughput limitado e blindagem anti-ban [cite: 459, 460, 510]
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
QUEUE_CONNECTION=redis

# --- BROADCASTING ---
BROADCAST_DRIVER=reverb
2. Estrutura de Atendimento (Quick Links)Abaixo estão os recursos essenciais para a manutenção da infraestrutura de mensageria:Instalação com Docker: Para implantar a Evolution API v2 em containers.Evolution Manager: Interface para monitorar as instâncias administrativas.Documentação Baileys: Referência técnica para o motor de conexão WebSocket.Laravel Horizon: Painel para monitorar métricas de jobs e falhas de envio.3. Resumo Técnico do Sistema VivensiA arquitetura foi projetada para suportar alta demanda com segurança:Multi-tenancy: Cada usuário do Vivensi possui isolamento total de suas instâncias de WhatsApp e tokens.Blindagem Inteligente: Implementada via AntiBanManager (simulação humana e janelas de horário).IA Conectiva: Integração nativa com Typebot e Dify para fluxos de atendimento automáticos.Real-time Dashboard: Atualizações instantâneas de status de mensagens via Laravel Reverb e Echo.
