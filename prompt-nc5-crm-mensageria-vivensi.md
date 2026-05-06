# PROMPT — Módulo NC5 CRM: Cadastro de Clientes + Melhorias de Mensageria

## Contexto do Projeto

Você está trabalhando no **VivensiApp 2.0**, um SaaS multi-tenant brasileiro. O repositório já existe e está em produção. Você deve seguir rigorosamente os padrões, convenções e estruturas já estabelecidas no projeto.

---

## Stack Exato do Projeto

- **Framework:** Laravel 9 (PHP ^8.0) — NÃO é Laravel 11
- **Frontend:** Blade + Bootstrap 5 + jQuery (NÃO usa Livewire nem FilamentPHP)
- **Real-time:** Pusher/Soketi (`pusher/pusher-php-server ^7.2`)
- **Filas:** Laravel Horizon (`laravel/horizon ^5.0`)
- **Auth:** Laravel Sanctum (`laravel/sanctum ^2.14`)
- **PDF:** barryvdh/laravel-dompdf
- **Banco:** MySQL 8 (RDS AWS)
- **Cache/Filas:** Redis (ElastiCache AWS)
- **WhatsApp:** Evolution API (Baileys) via `EvolutionApiService`
- **IA:** Gemini (`GeminiService`) e DeepSeek (`DeepSeekService`) — ambos já existem
- **Email:** Brevo via `BrevoService`

---

## Padrões Obrigatórios do Projeto (não invente nada diferente)

### Multi-Tenancy
- Coluna `tenant_id` em todas as tabelas multi-tenant
- Trait `App\Traits\BelongsToTenant` aplicada nos Models — ela injeta `tenant_id` automaticamente no create e aplica global scope nos selects (exceto `super_admin` e console)
- Nunca usar pacote Stancl/Tenancy — o projeto tem implementação própria

### Estrutura de Controllers
- Controllers em `app/Http/Controllers/` (blade/web) ou `app/Http/Controllers/Api/` (JSON/webhook)
- Controllers do painel Manager em `app/Http/Controllers/Manager/` ou inline conforme o padrão atual
- Sem Filament Resources — o projeto usa controllers + views Blade customizadas

### Views
- Localização: `resources/views/`
- Layouts existentes em `resources/views/layouts/`
- Estrutura de pastas por módulo: ex. `resources/views/whatsapp/chat.blade.php`
- Fonte padrão: Outfit + Inter (já carregadas no chat view)
- UI: Bootstrap 5.3, FontAwesome 6.4, CSS customizado em `<style>` inline nas views

### Models
- Namespace: `App\Models\`
- Usar `HasFactory`, `BelongsToTenant` (quando multi-tenant), `SoftDeletes` quando necessário
- `$fillable` sempre explícito
- `$casts` para datas e JSON

### Services
- Namespace: `App\Services\`
- Sub-namespace para grupos: ex. `App\Services\Messaging\`
- Injeção via constructor

### Jobs
- Namespace: `App\Jobs\`
- Sempre `implements ShouldQueue`
- Dispatched via `JobName::dispatch()`

### Rotas
- Arquivo: `routes/web.php`
- Rotas autenticadas dentro do grupo com middleware `auth`
- Nomear todas as rotas: `.name('modulo.acao')`

### Migrations
- Padrão de data: `2026_MM_DD_HHMMSS_descricao.php`
- Foreign keys sempre com `->constrained()->onDelete('cascade')` ou explícito
- Índices nos campos de busca e filtro frequente

---

## O que Já Existe (NÃO recriar)

### Módulo WhatsApp (completo — apenas estender)
- `whatsapp_configs` — configuração por tenant (instância Evolution, token, treinamento IA)
- `whatsapp_chats` — conversas (wa_id, contact_name, contact_phone, status, assigned_to)
- `whatsapp_messages` — mensagens (chat_id, message_id, content, direction, type, status)
- `whatsapp_notes` — notas internas por chat
- `whatsapp_bot_sessions` — sessões do bot (tenant_id, contact_phone, current_step, session_data)
- `whatsapp_automations` — automações por gatilho
- `whatsapp_blacklists` — lista de bloqueio
- `whatsapp_audit_log` — log de auditoria
- `canned_responses` — respostas rápidas por tenant
- `WhatsappController` com: chatIndex, chatList, getChatMessages, sendMessage, startChat, updateCompliance, addNote, getCannedResponses, updateTraining
- `EvolutionApiService`, `GeminiService`, `DeepSeekService`
- `AntiBanManager`, `ChatbotIntegrationService`, `MetaCloudApiService`
- View: `resources/views/whatsapp/chat.blade.php` (layout 3 colunas: lista chats | conversa | painel IA)

### Agenda Executiva (completo — usar como está)
- `meeting_bookings` — (name, email, phone, meeting_date, meeting_time, status, confirmation_token)
- `MeetingBooking::availableSlotsFor(string $date): array` — retorna slots livres
- Configurações via `SystemSetting::getValue('booking_days')`, `booking_start_time`, `booking_end_time`, `booking_slot_duration`

### Prospects (existente — inspiração, não recriar)
- Model `Prospect` com: company_name, category, phone, lead_score, ai_analysis, status, source
- Usado no módulo de prospecção B2B via Google Maps/web

---

## O que Deve Ser Desenvolvido

---

### PARTE 1 — Módulo de Clientes (NC5 CRM)

Base para o produto NC5 CRM vendido pela agência NC5. Mesmo codebase Vivensi, painel separado.

#### 1.1 Migration: `create_nc5_clients_table`

```php
Schema::create('nc5_clients', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

    // Identificação
    $table->string('name');
    $table->string('email')->nullable();
    $table->string('phone')->nullable();       // formato: 5511999999999
    $table->string('whatsapp')->nullable();    // pode diferir do phone
    $table->string('document')->nullable();    // CPF ou CNPJ
    $table->enum('document_type', ['cpf', 'cnpj'])->nullable();

    // Classificação
    $table->enum('type', ['lead', 'client', 'partner'])->default('lead');
    $table->enum('origin', ['whatsapp', 'manual', 'import', 'form'])->default('manual');
    $table->string('stage')->default('new'); // new, contacted, qualified, proposal, closed, lost
    $table->json('tags')->nullable();

    // Dados comerciais
    $table->string('company_name')->nullable();
    $table->string('job_title')->nullable();
    $table->string('city')->nullable();
    $table->string('state', 2)->nullable();
    $table->text('notes')->nullable();

    // Relacionamentos
    $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('last_interaction_at')->nullable();
    $table->timestamp('converted_at')->nullable();

    $table->softDeletes();
    $table->timestamps();

    $table->index(['tenant_id', 'type']);
    $table->index(['tenant_id', 'stage']);
    $table->unique(['tenant_id', 'phone'], 'nc5_clients_tenant_phone_unique');
    $table->unique(['tenant_id', 'whatsapp'], 'nc5_clients_tenant_whatsapp_unique');
});
```

#### 1.2 Migration: `create_nc5_client_interactions_table`

```php
Schema::create('nc5_client_interactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('client_id')->constrained('nc5_clients')->cascadeOnDelete();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->enum('type', [
        'whatsapp_message', 'email', 'call',
        'meeting', 'note', 'appointment', 'status_change'
    ]);
    $table->text('content')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamp('occurred_at');
    $table->timestamps();

    $table->index(['client_id', 'type']);
    $table->index(['tenant_id', 'occurred_at']);
});
```

#### 1.3 Model: `App\Models\Nc5Client`

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;

class Nc5Client extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $table = 'nc5_clients';

    protected $fillable = [
        'tenant_id', 'name', 'email', 'phone', 'whatsapp',
        'document', 'document_type', 'type', 'origin', 'stage',
        'tags', 'company_name', 'job_title', 'city', 'state',
        'notes', 'assigned_to', 'last_interaction_at', 'converted_at',
    ];

    protected $casts = [
        'tags'                => 'array',
        'last_interaction_at' => 'datetime',
        'converted_at'        => 'datetime',
    ];

    public function agent()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function interactions()
    {
        return $this->hasMany(Nc5ClientInteraction::class, 'client_id')->orderByDesc('occurred_at');
    }

    public function lastAppointment()
    {
        return $this->hasOne(MeetingBooking::class, 'phone', 'phone')
                    ->where('status', 'confirmed')
                    ->orderByDesc('meeting_date');
    }

    // Buscar ou criar por WhatsApp (usado pelo bot)
    public static function firstOrCreateByWhatsapp(string $phone, int $tenantId, string $name = null): self
    {
        $clean = preg_replace('/\D/', '', $phone);

        return static::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($clean) {
                $q->where('whatsapp', $clean)->orWhere('phone', $clean);
            })
            ->first()
            ?? static::create([
                'tenant_id' => $tenantId,
                'name'      => $name ?? 'Contato WhatsApp',
                'whatsapp'  => $clean,
                'phone'     => $clean,
                'origin'    => 'whatsapp',
                'type'      => 'lead',
            ]);
    }
}
```

#### 1.4 Model: `App\Models\Nc5ClientInteraction`

Criar model completo com fillable, casts e relacionamentos com `Nc5Client` e `User`.

#### 1.5 Service: `App\Services\Nc5ClientService`

Implementar os seguintes métodos:

```php
class Nc5ClientService
{
    // Enriquecer cadastro progressivamente (bot chama isso)
    public function enrich(Nc5Client $client, array $data): Nc5Client;

    // Mudar estágio e registrar no histórico
    public function moveToStage(Nc5Client $client, string $stage, ?User $by = null): Nc5Client;

    // Registrar interação
    public function logInteraction(Nc5Client $client, string $type, string $content, array $metadata = []): Nc5ClientInteraction;

    // Importação CSV (colunas: name, phone, email, company_name, stage)
    public function importFromCsv(string $filePath, int $tenantId): array; // retorna ['created'=>N, 'updated'=>N, 'errors'=>[]]

    // Busca inteligente por nome, telefone, email, empresa
    public function search(string $query, int $tenantId, int $limit = 20): \Illuminate\Support\Collection;
}
```

#### 1.6 Controller: `App\Http\Controllers\Nc5\ClientController`

CRUD completo com:
- `index()` — listagem com filtros (type, stage, assigned_to, search, date range) + paginação 20/página
- `create()` / `store()` — formulário de criação
- `show($id)` — ficha do cliente com timeline de interações e próximo agendamento
- `edit($id)` / `update($id)` — edição
- `destroy($id)` — soft delete
- `import(Request $request)` — upload CSV via `Nc5ClientService::importFromCsv()`
- `export()` — exportar CSV/PDF da listagem atual

#### 1.7 Views Blade

Criar as seguintes views em `resources/views/nc5/clients/`:

**`index.blade.php`** — Tabela de clientes com:
- Filtros laterais ou em bar: tipo, estágio, responsável, período
- Campo de busca ao vivo (fetch JS)
- Badge colorido por estágio: `new`=cinza, `contacted`=azul, `qualified`=amarelo, `proposal`=laranja, `closed`=verde, `lost`=vermelho
- Botão "Importar CSV" e "Exportar"
- Link para WhatsApp rápido se `whatsapp` preenchido

**`show.blade.php`** — Ficha completa:
- Header com nome, empresa, badge de estágio, botão "Mover estágio" (dropdown)
- Grid 2 colunas: dados do cliente | timeline de interações (scroll infinito ou paginada)
- Card "Próximo Agendamento" se existir em `meeting_bookings`
- Botão "Iniciar conversa no WhatsApp" → redireciona para `/whatsapp/chat?phone={phone}`
- Formulário inline para adicionar nota/interação manual

**`_form.blade.php`** — Partial reutilizável para create/edit

#### 1.8 Rotas

Adicionar em `routes/web.php` dentro do grupo `auth`:

```php
Route::prefix('nc5/clients')->name('nc5.clients.')->group(function () {
    Route::get('/',                  [Nc5\ClientController::class, 'index'])->name('index');
    Route::get('/create',            [Nc5\ClientController::class, 'create'])->name('create');
    Route::post('/',                 [Nc5\ClientController::class, 'store'])->name('store');
    Route::get('/{id}',              [Nc5\ClientController::class, 'show'])->name('show');
    Route::get('/{id}/edit',         [Nc5\ClientController::class, 'edit'])->name('edit');
    Route::put('/{id}',              [Nc5\ClientController::class, 'update'])->name('update');
    Route::delete('/{id}',           [Nc5\ClientController::class, 'destroy'])->name('destroy');
    Route::post('/import',           [Nc5\ClientController::class, 'import'])->name('import');
    Route::get('/export',            [Nc5\ClientController::class, 'export'])->name('export');
    // AJAX
    Route::get('/search',            [Nc5\ClientController::class, 'searchAjax'])->name('search');
    Route::post('/{id}/stage',       [Nc5\ClientController::class, 'moveStage'])->name('stage');
    Route::post('/{id}/interaction', [Nc5\ClientController::class, 'addInteraction'])->name('interaction');
});
```

---

### PARTE 2 — Melhorias no Módulo de Mensageria

#### 2.1 Handoff Bot → Atendente Humano

**Migration: adicionar campos em `whatsapp_chats`**

```php
Schema::table('whatsapp_chats', function (Blueprint $table) {
    $table->enum('mode', ['bot', 'queue', 'human', 'resolved'])
          ->default('bot')->after('status');
    $table->timestamp('queue_entered_at')->nullable()->after('mode');
    $table->timestamp('human_started_at')->nullable()->after('queue_entered_at');
    $table->timestamp('resolved_at')->nullable()->after('human_started_at');
});
```

**Lógica de handoff em `WhatsappController` (novos métodos):**

```php
// POST /whatsapp/chat/{id}/handoff
// Transfere para fila humana. Pode ser chamado pelo bot ou pelo próprio atendente.
public function handoff(Request $request, $chatId): JsonResponse
{
    $chat = WhatsappChat::where('tenant_id', auth()->user()->tenant_id)->findOrFail($chatId);
    $chat->update([
        'mode'             => 'queue',
        'queue_entered_at' => now(),
    ]);

    // Notifica atendentes disponíveis via Pusher
    broadcast(new \App\Events\ChatTransferredToQueue($chat))->toOthers();

    // Mensagem automática para o contato
    // (usar EvolutionApiService já existente)

    return response()->json(['success' => true, 'chat' => $chat]);
}

// POST /whatsapp/chat/{id}/claim
// Atendente reivindica a conversa da fila
public function claimChat(Request $request, $chatId): JsonResponse;

// POST /whatsapp/chat/{id}/return-to-bot
// Atendente devolve para o bot
public function returnToBot(Request $request, $chatId): JsonResponse;

// POST /whatsapp/chat/{id}/resolve
// Marcar como resolvido
public function resolveChat(Request $request, $chatId): JsonResponse;
```

**Events a criar:**
- `App\Events\ChatTransferredToQueue` — broadcast no canal `tenant.{tenantId}.queue`
- `App\Events\ChatClaimedByAgent` — broadcast quando atendente reivindica

**Atualizar `resources/views/whatsapp/chat.blade.php`:**
- Adicionar badge de `mode` no header de cada chat na sidebar
- Adicionar aba "Fila de Espera" na sidebar com chats em `mode = queue`
- Adicionar botão "Assumir atendimento" nas conversas em fila
- Botão "Devolver ao Bot" e "Resolver" no header da conversa ativa
- Indicador de tempo na fila (calculado de `queue_entered_at`)
- Escutar evento Pusher `ChatTransferredToQueue` para atualizar a fila em tempo real

#### 2.2 Agendamento via Bot

**Criar `App\Jobs\ProcessWhatsAppBotMessage`** (se ainda não existir) ou **estender o existente**:

Dentro da lógica de processamento de mensagem do bot (atualmente em `ProcessWhatsAppBotMessage` ou similar), adicionar detecção de intenção de agendamento:

```php
// No job de processamento do bot, adicionar função:
private function detectSchedulingIntent(string $text): bool
{
    $keywords = ['agendar', 'agendamento', 'marcar', 'horário', 'disponível', 'reunião', 'consulta', 'atendimento'];
    $text = mb_strtolower($text);
    foreach ($keywords as $kw) {
        if (str_contains($text, $kw)) return true;
    }
    return false;
}
```

**Criar `App\Services\BotSchedulingService`:**

```php
class BotSchedulingService
{
    // Retorna mensagem formatada com slots disponíveis para os próximos N dias
    public function getAvailableSlotsMessage(int $daysAhead = 3): string
    {
        $message = "📅 *Horários disponíveis:*\n\n";
        $count = 1;

        for ($i = 1; $i <= $daysAhead; $i++) {
            $date = now()->addDays($i)->format('Y-m-d');
            $slots = MeetingBooking::availableSlotsFor($date);
            if (empty($slots)) continue;

            $dateFormatted = now()->addDays($i)->locale('pt_BR')->isoFormat('dddd, D/MM');
            $message .= "*{$dateFormatted}*\n";

            foreach (array_slice($slots, 0, 3) as $slot) {
                $message .= "  {$count}. {$slot}\n";
                $count++;
            }
            $message .= "\n";
        }

        $message .= "Digite o número do horário desejado para confirmar.";
        return $message;
    }

    // Salva agendamento a partir de seleção do usuário no bot
    // Usa whatsapp_bot_sessions para rastrear estado da conversa de agendamento
    public function processSlotSelection(
        string $contactPhone,
        int    $tenantId,
        string $selectedOption,
        string $contactName
    ): array; // retorna ['booked' => bool, 'message' => string, 'booking' => ?MeetingBooking]

    // Vincula o agendamento ao cliente NC5 se existir
    public function linkToNc5Client(MeetingBooking $booking, int $tenantId): void
    {
        $client = Nc5Client::firstOrCreateByWhatsapp($booking->phone, $tenantId, $booking->name);
        $this->nc5ClientService->logInteraction(
            $client, 'appointment',
            "Agendamento confirmado: {$booking->meeting_date->format('d/m/Y')} às {$booking->meeting_time}",
            ['booking_id' => $booking->id]
        );
    }
}
```

**Integrar `BotSchedulingService` no fluxo do bot:**

No job de processamento (que já usa Gemini/DeepSeek), adicionar:
1. Antes de chamar a IA: verificar se a sessão do contato está em estado `awaiting_slot_selection`
2. Se sim: processar seleção via `BotSchedulingService::processSlotSelection()`
3. Se não: verificar intenção de agendamento; se detectada, chamar `getAvailableSlotsMessage()` e salvar estado `awaiting_slot_selection` na sessão (`whatsapp_bot_sessions`)

#### 2.3 Relatórios de Atendimento

**Criar `App\Http\Controllers\WhatsappReportController`:**

```php
class WhatsappReportController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId  = auth()->user()->tenant_id;
        $from      = $request->get('from', now()->startOfMonth()->toDateString());
        $to        = $request->get('to', now()->toDateString());
        $agentId   = $request->get('agent_id');

        // Métricas
        $data = [
            'total_conversations'     => WhatsappChat::whereBetween('created_at', [$from, $to])->count(),
            'resolved_by_bot'         => WhatsappChat::whereBetween('created_at', [$from, $to])->where('mode', 'resolved')->whereNull('human_started_at')->count(),
            'transferred_to_human'    => WhatsappChat::whereBetween('created_at', [$from, $to])->whereNotNull('human_started_at')->count(),
            'avg_queue_wait_seconds'  => WhatsappChat::whereBetween('created_at', [$from, $to])->whereNotNull('queue_entered_at')->whereNotNull('human_started_at')->selectRaw('AVG(TIMESTAMPDIFF(SECOND, queue_entered_at, human_started_at)) as avg_wait')->value('avg_wait'),
            'conversations_by_day'    => WhatsappChat::whereBetween('created_at', [$from, $to])->selectRaw('DATE(created_at) as date, COUNT(*) as total')->groupBy('date')->orderBy('date')->get(),
            'conversations_by_agent'  => WhatsappChat::whereBetween('created_at', [$from, $to])->whereNotNull('assigned_to')->with('agent:id,name')->selectRaw('assigned_to, COUNT(*) as total')->groupBy('assigned_to')->get(),
            'top_handoff_notes'       => WhatsappNote::whereBetween('created_at', [$from, $to])->where('type', 'system_log')->selectRaw('content, COUNT(*) as total')->groupBy('content')->orderByDesc('total')->limit(5)->get(),
            'agents'                  => User::where('tenant_id', $tenantId)->where('status', 'active')->get(['id', 'name']),
        ];

        return view('whatsapp.reports', compact('data', 'from', 'to', 'agentId'));
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Exportar CSV com listagem de conversas do período
    }
}
```

**Criar view `resources/views/whatsapp/reports.blade.php`:**
- Cards de métricas no topo (total conversas, % resolvido pelo bot, transferências humanas, tempo médio de espera)
- Gráfico de barras por dia (usar Chart.js ou ApexCharts, conforme já usado no projeto)
- Tabela de desempenho por agente
- Date range picker (Bootstrap Datepicker ou flatpickr)
- Botão exportar CSV

**Adicionar rotas:**

```php
Route::get('/whatsapp/reports', [WhatsappReportController::class, 'index'])->name('whatsapp.reports');
Route::get('/whatsapp/reports/export', [WhatsappReportController::class, 'export'])->name('whatsapp.reports.export');
```

#### 2.4 Auto-criação de Cliente NC5 via WhatsApp

No webhook da Evolution API (já em `EvolutionWebhookController` ou `WhatsappController::webhook`), ao receber mensagem de contato desconhecido e criar `WhatsappChat`, também chamar:

```php
// Se tenant tiver módulo NC5 ativo, criar/atualizar cliente
if ($tenant->hasModule('nc5')) { // ou verificar flag na tabela tenants
    Nc5Client::firstOrCreateByWhatsapp($contactPhone, $tenantId, $contactName);
}
```

---

## Checklist de Entrega

### Migrations (em ordem)
- [ ] `2026_05_XX_000001_create_nc5_clients_table.php`
- [ ] `2026_05_XX_000002_create_nc5_client_interactions_table.php`
- [ ] `2026_05_XX_000003_add_mode_and_handoff_fields_to_whatsapp_chats.php`

### Models
- [ ] `App\Models\Nc5Client`
- [ ] `App\Models\Nc5ClientInteraction`

### Services
- [ ] `App\Services\Nc5ClientService`
- [ ] `App\Services\BotSchedulingService`

### Controllers
- [ ] `App\Http\Controllers\Nc5\ClientController`
- [ ] `App\Http\Controllers\WhatsappReportController`
- [ ] Novos métodos em `WhatsappController`: `handoff`, `claimChat`, `returnToBot`, `resolveChat`

### Events
- [ ] `App\Events\ChatTransferredToQueue`
- [ ] `App\Events\ChatClaimedByAgent`

### Views
- [ ] `resources/views/nc5/clients/index.blade.php`
- [ ] `resources/views/nc5/clients/show.blade.php`
- [ ] `resources/views/nc5/clients/_form.blade.php`
- [ ] `resources/views/whatsapp/reports.blade.php`
- [ ] Atualizar `resources/views/whatsapp/chat.blade.php` (handoff UI)

### Rotas
- [ ] Bloco `nc5/clients` em `routes/web.php`
- [ ] Rotas de handoff em `routes/web.php`
- [ ] Rotas de relatórios em `routes/web.php`

### Jobs
- [ ] Integrar `BotSchedulingService` no job de processamento do bot existente

---

## Restrições Absolutas

1. **NÃO instalar novos pacotes** sem justificativa explícita e aprovação
2. **NÃO usar Livewire, Filament ou Inertia** — o projeto usa Blade puro + JavaScript vanilla/jQuery
3. **NÃO alterar** a estrutura de WhatsappChat, WhatsappMessage, WhatsappConfig existentes — apenas adicionar colunas via nova migration
4. **NÃO reescrever** EvolutionApiService, GeminiService, DeepSeekService
5. Todos os comentários e strings de interface em **português brasileiro**
6. Seguir o padrão visual do `whatsapp/chat.blade.php` já existente (Outfit + Inter, variáveis CSS, Bootstrap 5)
7. O campo `mode` em `whatsapp_chats` é **adicional** — o campo `status` existente (`open`, `closed`, `pending`) continua funcionando normalmente
