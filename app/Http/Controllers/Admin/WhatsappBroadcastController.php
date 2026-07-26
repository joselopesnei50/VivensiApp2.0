<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Models\Tenant;
use App\Services\EvolutionApiService;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class WhatsappBroadcastController extends Controller
{
    public function index()
    {
        Gate::authorize('access-whatsapp');
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$tenantId && !$user->isSuperAdmin()) {
            return redirect()->route('whatsapp.settings')
                ->with('error', 'Configure a instância WhatsApp primeiro.');
        }

        $contactsCount  = WhatsappChat::where('tenant_id', $tenantId)->count();
        $config         = WhatsappConfig::where('tenant_id', $tenantId)->first();
        $activeInstance = WhatsappInstance::where('tenant_id', $tenantId)
                            ->where('status', 'open')->first();

        $campaigns  = collect();
        $scheduled  = collect();
        if (Schema::hasTable('broadcast_campaigns')) {
            $campaigns = \App\Models\BroadcastCampaign::where('tenant_id', $tenantId)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

            $scheduled = \App\Models\BroadcastCampaign::where('tenant_id', $tenantId)
                ->where('status', 'scheduled')
                ->orderBy('scheduled_at')
                ->get();
        }

        $preMessage = session('ai_broadcast_message');

        // Etiquetas disponíveis para disparo segmentado (Fase 2)
        $labels = \App\Models\WhatsappLabel::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        // Importações recentes (últimas 5 etiquetas criadas via upload de CSV).
        // Nome começa com "Importação" (padrão gerado por importContacts()).
        $recentImports = \App\Models\WhatsappLabel::where('tenant_id', $tenantId)
            ->where('name', 'like', 'Importação%')
            ->withCount('chats')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('admin.whatsapp.broadcast.index',
            compact('contactsCount', 'config', 'activeInstance', 'campaigns', 'scheduled', 'preMessage', 'labels', 'recentImports'));
    }

    public function importContacts(Request $request)
    {
        Gate::authorize('access-whatsapp');

        $request->validate([
            // mimes:csv é frágil (Excel envia application/vnd.ms-excel). Valida por extensão.
            'csv_file'      => 'required|file|max:2048',
            // LGPD art. 7º inciso IX (legítimo interesse) exige que o controlador
            // declare posse do consentimento antes do disparo. Checkbox obrigatório.
            'lgpd_consent'  => 'required|accepted',
            // Nome opcional pra facilitar identificar a importação depois.
            'import_name'   => 'nullable|string|max:80',
        ], [
            'lgpd_consent.required' => 'Você precisa confirmar que possui consentimento dos contatos (LGPD).',
            'lgpd_consent.accepted' => 'Você precisa confirmar que possui consentimento dos contatos (LGPD).',
        ]);

        $tenantId    = auth()->user()->tenant_id;
        $userId      = auth()->id();
        $maxLines    = 5000;
        $imported    = 0;
        $lineCount   = 0;
        $handle      = false;
        $importedIds = []; // ids dos WhatsappChat criados/encontrados nesta importação

        // Cria a etiqueta dedicada dessa importação ANTES do parsing.
        // Nome padrão: "Importação DD/MM HH:MM" (ou o custom informado pelo user).
        $labelName = trim((string) $request->input('import_name', ''));
        if ($labelName === '') {
            $labelName = 'Importação ' . now()->format('d/m H:i');
        } else {
            $labelName = 'Importação: ' . mb_substr($labelName, 0, 60);
        }

        $importLabel = \App\Models\WhatsappLabel::create([
            'tenant_id'  => $tenantId,
            'name'       => $labelName,
            'slug'       => \Illuminate\Support\Str::slug($labelName . '-' . now()->timestamp),
            'color'      => '#7c3aed',
            'background' => '#f5f3ff',
            'created_by' => $userId,
        ]);

        try {
            $path = $request->file('csv_file')->getRealPath();
            $handle = $path ? fopen($path, 'r') : false;

            if ($handle === false) {
                $importLabel->delete(); // rollback: sem contatos, etiqueta vazia é lixo
                return redirect()->back()->with('error', 'Não foi possível ler o arquivo enviado. Tente novamente.');
            }

            // Detecta separador na primeira linha (vírgula ou ponto-e-vírgula do Excel-BR).
            $firstLine = fgets($handle);
            $firstLine = $firstLine === false ? '' : ltrim($firstLine, "\xEF\xBB\xBF"); // remove BOM
            $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
            rewind($handle);

            $isHeader = true;
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false && $lineCount < $maxLines) {
                // Remove BOM do primeiro campo da primeira linha
                if ($lineCount === 0 && isset($row[0])) {
                    $row[0] = ltrim($row[0], "\xEF\xBB\xBF");
                }

                // Pula o cabeçalho se a primeira coluna for "nome"
                if ($isHeader) {
                    $isHeader = false;
                    if (isset($row[0]) && strtolower(trim($row[0])) === 'nome') {
                        continue;
                    }
                }

                $lineCount++;

                if (count($row) < 2) {
                    continue;
                }

                // CONSERTA O 1366: Excel-BR salva em Windows-1252. Converte cada
                // campo para UTF-8 quando não for UTF-8 válido — sem isso, qualquer
                // nome com acento (Angélica, José, Conceição) estoura SQL erro 1366
                // e derruba a importação inteira em 500.
                $row = array_map([$this, 'toUtf8'], $row);

                $name  = trim($row[0] ?? '');
                $phone = \App\Services\EvolutionApiService::normalizeBrazilianPhone((string) ($row[1] ?? ''));

                if (!$phone || strlen($phone) < 12) {
                    continue;
                }

                // Data de consentimento: se coluna 3 do CSV tem data válida, usa ela;
                // senão usa NOW() porque o user confirmou consentimento LGPD na UI.
                [$optInAt, $optInSource] = $this->parseConsentDate($row[2] ?? null);
                if ($optInAt === null) {
                    $optInAt     = now();
                    $optInSource = 'csv_import_with_consent';
                }

                $attributes = [
                    'contact_name'  => $name,
                    'contact_phone' => $phone,
                    'status'        => 'open',
                    'opt_in_at'     => $optInAt,
                ];
                // Só grava opt_in_source se a coluna existir (evita 500 se migration não rodou).
                if (Schema::hasColumn('whatsapp_chats', 'opt_in_source')) {
                    $attributes['opt_in_source'] = $optInSource;
                }

                $chat = WhatsappChat::firstOrCreate(
                    ['tenant_id' => $tenantId, 'wa_id' => $phone],
                    $attributes
                );

                // Se o contato já existia SEM opt-in, atualiza pra opt-in agora
                // (user acabou de declarar consentimento pra esse número).
                if (!$chat->wasRecentlyCreated && $chat->opt_in_at === null) {
                    $chat->update(['opt_in_at' => $optInAt] + (
                        Schema::hasColumn('whatsapp_chats', 'opt_in_source')
                            ? ['opt_in_source' => $optInSource]
                            : []
                    ));
                }

                $importedIds[] = $chat->id;
                $imported++;
            }

            fclose($handle);

            // Vincula todos os contatos importados à etiqueta desta importação.
            // syncWithoutDetaching mantém etiquetas antigas caso o contato já existisse.
            if (!empty($importedIds)) {
                $importLabel->chats()->syncWithoutDetaching($importedIds);
            } else {
                // Nenhum contato válido no CSV — apaga etiqueta vazia
                $importLabel->delete();
                $importLabel = null;
            }
        } catch (\Throwable $e) {
            if ($handle !== false) {
                @fclose($handle);
            }
            // Rollback da etiqueta em caso de erro fatal no meio da importação
            if ($importLabel && $importLabel->exists) {
                try { $importLabel->delete(); } catch (\Throwable $ex) { /* ignore */ }
            }
            Log::error('Falha ao importar contatos no Broadcast', [
                'tenant_id' => $tenantId,
                'line'      => $lineCount,
                'error'     => $e->getMessage(),
                'file'      => basename($e->getFile()) . ':' . $e->getLine(),
            ]);
            return redirect()->back()->with(
                'error',
                'Erro ao importar contatos. Verifique se o arquivo está no formato "Nome,Telefone". Detalhe: ' . $e->getMessage()
            );
        }

        // Registro de auditoria (LGPD art. 37 — operações de tratamento).
        try {
            if (class_exists(\App\Models\AuditLog::class) && $importLabel) {
                \App\Models\AuditLog::create([
                    'tenant_id'      => $tenantId,
                    'user_id'        => $userId,
                    'event'          => 'created',
                    'auditable_type' => \App\Models\WhatsappLabel::class,
                    'auditable_id'   => $importLabel->id,
                    'url'            => $request->fullUrl(),
                    'ip_address'     => $request->ip(),
                    'user_agent'     => (string) $request->userAgent(),
                    'new_values'     => [
                        'source'           => 'csv_import_with_consent',
                        'label_name'       => $importLabel->name,
                        'imported_count'   => $imported,
                        'lines_processed'  => $lineCount,
                        'consent_declared' => true,
                    ],
                ]);
            }
        } catch (\Throwable $auditEx) {
            Log::warning('AuditLog do broadcast import falhou (não bloqueia)', ['error' => $auditEx->getMessage()]);
        }

        if ($imported === 0 || !$importLabel) {
            return redirect()->back()->with(
                'error',
                'Nenhum contato válido encontrado no arquivo. Verifique o formato: Nome,Telefone.'
            );
        }

        $msg = "{$imported} contatos importados e vinculados à etiqueta \"{$importLabel->name}\". Para disparar apenas para eles, escolha Público Alvo → Etiquetas → \"{$importLabel->name}\".";
        if ($lineCount >= $maxLines) {
            $msg .= " (limite de {$maxLines} linhas por importação atingido)";
        }

        return redirect()->back()
            ->with('success', $msg)
            ->with('import_label_id', $importLabel->id)
            ->with('import_label_name', $importLabel->name);
    }

    /**
     * Normaliza para UTF-8. Excel no Windows (PT-BR) salva CSV em Windows-1252,
     * cujos bytes acentuados (É = \xC9, ç = \xE7…) não são UTF-8 válido e fazem
     * o MySQL estourar erro 1366 ao inserir. Converte só quando necessário.
     */
    private function toUtf8($value)
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }
        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }
        return mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
    }

    /**
     * Interpreta a coluna opcional de data de consentimento do CSV.
     * Retorna [opt_in_at, opt_in_source] ou [null, null] se ausente/inválida.
     * Padrão seguro: sem data = sem opt-in (LGPD).
     */
    private function parseConsentDate(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [null, null];
        }

        $ts = strtotime(trim($raw));
        if ($ts === false || $ts > time()) {
            return [null, null]; // data futura ou inválida — descarta
        }

        return [\Carbon\Carbon::createFromTimestamp($ts)->startOfDay(), 'csv_import'];
    }

    public function getGroups()
    {
        Gate::authorize('access-whatsapp');
        $tenantId = auth()->user()->tenant_id;

        $instance = WhatsappInstance::where('tenant_id', $tenantId)
            ->where('status', 'open')->first();

        if (!$instance) {
            return response()->json(['error' => 'Nenhuma instância conectada.'], 422);
        }

        try {
            $evo    = new EvolutionApiService($instance);
            $groups = $evo->getGroups();

            if (!is_array($groups)) {
                return response()->json(['error' => 'O formato de grupos retornado pela API é inválido.'], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
            }

            $mapped = array_map(fn($g) => [
                'id'   => $g['id'] ?? (is_string($g) ? $g : ''),
                'name' => $g['subject'] ?? $g['name'] ?? (is_string($g) ? $g : 'Grupo sem nome'),
                'size' => isset($g['participants']) && is_array($g['participants']) ? count($g['participants']) : ($g['size'] ?? 0),
            ], $groups);

            usort($mapped, fn($a, $b) => strcmp((string)$a['name'], (string)$b['name']));

            return response()->json($mapped, 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\Throwable $e) {
            try {
                Log::error("Erro ao buscar grupos no Broadcast: " . $e->getMessage());
            } catch (\Throwable $logEx) {
                // Ignore log errors (e.g. permission denied)
            }
            return response()->json([
                'error' => 'Erro fatal interno: ' . $e->getMessage() . ' no arquivo ' . basename($e->getFile()) . ':' . $e->getLine()
            ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
        }
    }

    public function campaigns(\Illuminate\Http\Request $request)
    {
        Gate::authorize('access-whatsapp');
        $tenantId = auth()->user()->tenant_id;

        $campaigns   = collect();
        $totalSent   = 0;
        $totalFailed = 0;
        $completed   = 0;

        if (Schema::hasTable('broadcast_campaigns')) {
            $query = \App\Models\BroadcastCampaign::where('tenant_id', $tenantId);

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }
            if ($request->filled('audience')) {
                $query->where('audience_type', $request->input('audience'));
            }
            if ($request->filled('from')) {
                $query->whereDate('created_at', '>=', $request->input('from'));
            }
            if ($request->filled('to')) {
                $query->whereDate('created_at', '<=', $request->input('to'));
            }

            $campaigns   = $query->orderByDesc('created_at')->paginate(25)->withQueryString();
            $totalSent   = \App\Models\BroadcastCampaign::where('tenant_id', $tenantId)->sum('total_sent');
            $totalFailed = \App\Models\BroadcastCampaign::where('tenant_id', $tenantId)->sum('total_failed');
            $completed   = \App\Models\BroadcastCampaign::where('tenant_id', $tenantId)->where('status', 'completed')->count();
        }

        $totalMessages = $totalSent + $totalFailed;
        $successRate   = $totalMessages > 0 ? round(($totalSent / $totalMessages) * 100) : 0;

        return view('admin.whatsapp.broadcast.campaigns',
            compact('campaigns', 'totalSent', 'totalFailed', 'completed', 'successRate'));
    }

    /**
     * Cancela campanha em estados nao-terminais (scheduled, queued, paused, processing).
     *
     * Para 'processing': muda o status para 'cancelled'. O ProcessBroadcastCampaignJob
     * verifica em cada iteracao `if (\$campaign->status !== 'processing') break;`,
     * entao o loop interno encerra naturalmente na proxima iteracao.
     *
     * Status 'cancelled' e tratado visualmente como neutro (nao "failed", que e erro
     * de execucao).
     */
    // Carrega um rascunho no form principal (padrao prefilled_phones + preMessage
    // que a index() ja consome). Guarda o draft_campaign_id na sessao pra o
    // sendBroadcast() saber que deve atualizar o rascunho ao inves de criar novo.
    public function editDraft(int $id)
    {
        Gate::authorize('access-whatsapp');
        $tenantId = auth()->user()->tenant_id;

        $draft = \App\Models\BroadcastCampaign::where('tenant_id', $tenantId)
            ->where('id', $id)
            ->where('status', 'draft')
            ->firstOrFail();

        // phones no schema atual e string CSV. Fallback pra JSON legado (rascunhos
        // criados antes do fix do implode).
        $phonesRaw = (string) ($draft->phones ?? '');
        if ($phonesRaw !== '' && str_starts_with(trim($phonesRaw), '[')) {
            $decoded = json_decode($phonesRaw, true);
            if (is_array($decoded)) {
                $phonesRaw = implode(',', $decoded);
            }
        }

        session()->flash('prefilled_phones', $phonesRaw);
        session()->flash('ai_broadcast_message', (string) ($draft->message ?? ''));
        session()->put('draft_campaign_id', $draft->id); // put, nao flash — sobrevive o redirect ate o submit.

        return redirect()->route('whatsapp.broadcast.index')
            ->with('success', 'Rascunho carregado. Complete a mensagem e dispare quando quiser.');
    }

    public function discardDraft(int $id)
    {
        Gate::authorize('access-whatsapp');
        $tenantId = auth()->user()->tenant_id;

        $draft = \App\Models\BroadcastCampaign::where('tenant_id', $tenantId)
            ->where('id', $id)
            ->where('status', 'draft')
            ->firstOrFail();

        $draft->delete();

        return redirect()->route('whatsapp.broadcast.campaigns')
            ->with('success', 'Rascunho descartado.');
    }

    public function cancelScheduled(int $id)
    {
        Gate::authorize('access-whatsapp');
        $tenantId = auth()->user()->tenant_id;

        $campaign = \App\Models\BroadcastCampaign::where('tenant_id', $tenantId)
            ->where('id', $id)
            ->whereIn('status', ['scheduled', 'queued', 'paused', 'processing'])
            ->firstOrFail();

        $campaign->update([
            'status'       => 'cancelled',
            'completed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Campanha cancelada.');
    }

    public function resumeCampaign(int $id)
    {
        Gate::authorize('access-whatsapp');
        $tenantId = auth()->user()->tenant_id;

        $campaign = \App\Models\BroadcastCampaign::where('tenant_id', $tenantId)
            ->where('id', $id)
            ->where('status', 'paused')
            ->firstOrFail();

        $instance = WhatsappInstance::where('tenant_id', $tenantId)
            ->where('status', 'open')->first();

        if (!$instance) {
            return redirect()->back()->with('error', 'Nenhuma instância WhatsApp conectada. Conecte o WhatsApp antes de retomar.');
        }

        $campaign->update([
            'status'       => 'queued',
            'completed_at' => null,
        ]);

        \App\Jobs\ProcessBroadcastCampaignJob::dispatch($campaign->id, $campaign->tenant_id);

        return redirect()->back()->with('success', 'Campanha retomada! O disparo continuará em segundo plano dentro da janela permitida.');
    }

    public function sendBroadcast(Request $request)
    {
        Gate::authorize('access-whatsapp');

        // Detecta falha de upload no nível do PHP (upload_max_filesize / post_max_size)
        if ($request->hasFile('broadcast_image') && !$request->file('broadcast_image')->isValid()) {
            $maxMb = min(
                (int) ini_get('upload_max_filesize'),
                (int) ini_get('post_max_size')
            );
            return redirect()->back()->withErrors([
                'broadcast_image' => "A imagem não pôde ser enviada. Verifique se o arquivo é menor que {$maxMb}MB e tente novamente.",
            ])->withInput();
        }

        $request->validate([
            'message'         => 'nullable|string|max:4000',
            'audience'        => 'required|in:all,selected,groups,labels',
            'cadence'         => 'nullable|integer|in:1,3,5,10,30',
            'broadcast_image' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'scheduled_at'    => 'nullable|date|after:now',
            'group_send_mode' => 'nullable|in:group,members',
            'group_ids'       => 'required_if:audience,groups|array|min:1',
            'group_ids.*'     => 'string',
            'label_ids'       => 'required_if:audience,labels|array|min:1',
            'label_ids.*'     => 'integer',
        ]);

        if (!$request->filled('message') && !$request->hasFile('broadcast_image')) {
            return redirect()->back()->with('error', 'Digite uma mensagem ou anexe uma imagem.');
        }

        if ($request->input('audience') === 'groups' && empty($request->input('group_ids'))) {
            return redirect()->back()->with('error', 'Selecione pelo menos um grupo para disparar.');
        }

        $tenantId       = auth()->user()->tenant_id;
        $message        = $request->input('message', '');
        $audience       = $request->input('audience');
        $cadenceSeconds = (int) $request->input('cadence', 3);

        // datetime-local envia horário do browser sem timezone.
        // Interpreta como America/Sao_Paulo (Brasil UTC-3) e converte para UTC para armazenar.
        $scheduledAt = null;
        if ($request->filled('scheduled_at')) {
            $scheduledAt = \Carbon\Carbon::parse(
                $request->input('scheduled_at'),
                'America/Sao_Paulo'
            )->utc();
        }

        $instance = WhatsappInstance::where('tenant_id', $tenantId)
            ->where('status', 'open')->first();

        if (!$instance) {
            return redirect()->back()->with('error', 'Nenhuma instância WhatsApp conectada.');
        }

        $imagePath = null;
        $hasImage  = false;
        if ($request->hasFile('broadcast_image')) {
            $file     = $request->file('broadcast_image');
            $realMime = $file->getMimeType(); // finfo — lê bytes reais, não header do cliente
            $mimeMap  = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
            ];
            if (!isset($mimeMap[$realMime])) {
                return redirect()->back()->with('error', 'Tipo de arquivo inválido. Use apenas imagens JPG, PNG, GIF ou WebP.');
            }
            $ext       = $mimeMap[$realMime];
            $imagePath = $file->storeAs('broadcasts', 'broadcast_' . uniqid() . '.' . $ext, 'public');
            $hasImage  = true;
        }

        $groupSendMode = ($audience === 'groups')
            ? $request->input('group_send_mode', 'group')
            : 'group';

        // Filtra os label_ids enviados para garantir que pertencem ao tenant
        // (proteção contra envio cross-tenant via id forjado no form).
        $labelIds = null;
        if ($audience === 'labels') {
            $labelIds = \App\Models\WhatsappLabel::where('tenant_id', $tenantId)
                ->whereIn('id', $request->input('label_ids', []))
                ->pluck('id')
                ->all();
            if (empty($labelIds)) {
                return redirect()->back()->with('error', 'Selecione ao menos uma etiqueta válida.');
            }
        }

        $payload = [
            'tenant_id'       => $tenantId,
            'created_by'      => auth()->id(),
            'message'         => $message ?: null,
            'has_image'       => $hasImage,
            'image_path'      => $imagePath,
            'audience_type'   => $audience,
            'cadence'         => $cadenceSeconds,
            'scheduled_at'    => $scheduledAt,
            'status'          => $scheduledAt ? 'scheduled' : 'queued',
            'group_ids'       => $audience === 'groups' ? $request->input('group_ids', []) : null,
            'group_send_mode' => $groupSendMode,
            'phones'          => $audience === 'selected' ? $request->input('phones') : null,
            'label_ids'       => $labelIds,
        ];

        // Se o usuario veio da fila "Continuar edicao" de um rascunho, atualiza
        // o mesmo campaign ao inves de criar novo (evita rascunhos orfaos no banco).
        $draftId = (int) session('draft_campaign_id');
        if ($draftId > 0) {
            $campaign = \App\Models\BroadcastCampaign::where('tenant_id', $tenantId)
                ->where('id', $draftId)
                ->where('status', 'draft')
                ->first();
        } else {
            $campaign = null;
        }

        if ($campaign) {
            $campaign->update($payload);
            session()->forget('draft_campaign_id');
        } else {
            $campaign = \App\Models\BroadcastCampaign::create($payload);
        }

        \Illuminate\Support\Facades\Log::info('Broadcast campaign created', [
            'campaign_id'     => $campaign->id,
            'tenant_id'       => $tenantId,
            'audience_type'   => $audience,
            'group_send_mode' => $groupSendMode,
            'group_count'     => is_array($request->input('group_ids')) ? count($request->input('group_ids')) : 0,
            'has_image'       => $hasImage,
            'scheduled_at'    => $scheduledAt?->toDateTimeString(),
            'status'          => $campaign->status,
        ]);

        // ── Alerta anti-ban (Fase 1 2026): mensagem sem spintax em campanha grande ─
        // A Meta detecta como spam mensagens IDÊNTICAS disparadas em massa mesmo
        // dentro dos limites de warming/horário. Se a campanha tem >40 destinatários
        // estimados e o texto não tem variação {a|b}, avisa o gestor. Não bloqueia
        // — o job já bloqueia acima do limite de fingerprint.
        if (!empty($message) && !\App\Services\Messaging\AntiBanManager::hasSpintax($message)) {
            $estimatedCount = $this->estimateAudienceSize($campaign, $tenantId);
            if ($estimatedCount > \App\Services\Messaging\AntiBanManager::MAX_SAME_CONTENT_PER_DAY) {
                session()->flash(
                    'warning_antiban',
                    "⚠️ Campanha estimada em {$estimatedCount} destinatários com mensagem sem variação. " .
                    "Risco de detecção elevado pela Meta em 2026. " .
                    "Considere usar spintax nas mensagens (exemplo: {Olá|Oi|E aí}) para variar o conteúdo entre envios."
                );
            }
        }

        if (!$scheduledAt) {
            \App\Jobs\ProcessBroadcastCampaignJob::dispatch($campaign->id, $campaign->tenant_id);
            return redirect()->back()->with('success', 'Disparo iniciado em segundo plano!');
        }

        return redirect()->back()->with('success', 'Disparo agendado para ' . $campaign->scheduled_at->format('d/m/Y H:i') . '!');
    }

    /**
     * Estimativa barata do tamanho da audiência de uma campanha, só para
     * fins de alerta anti-ban antes do dispatch. O count real quem calcula
     * é o job — este método só precisa saber "provavelmente > 40 ou não".
     * Retorna 0 se não conseguir estimar (é aceitável — o alerta simplesmente
     * não dispara nesse caso).
     */
    private function estimateAudienceSize(\App\Models\BroadcastCampaign $campaign, int $tenantId): int
    {
        try {
            switch ($campaign->audience_type) {
                case 'selected':
                    $phones = array_filter(
                        array_map('trim', explode(',', (string) $campaign->phones)),
                        fn ($p) => strlen(preg_replace('/\D+/', '', $p)) >= 10
                    );
                    return count($phones);

                case 'labels':
                    if (empty($campaign->label_ids)) {
                        return 0;
                    }
                    return \App\Models\WhatsappChat::where('tenant_id', $tenantId)
                        ->whereNotNull('opt_in_at')
                        ->whereNull('opt_out_at')
                        ->whereNull('blocked_at')
                        ->whereHas('labelTags', fn ($q) => $q->whereIn('whatsapp_labels.id', $campaign->label_ids))
                        ->count();

                case 'groups':
                    return count($campaign->group_ids ?? []);

                case 'all':
                default:
                    return \App\Models\WhatsappChat::where('tenant_id', $tenantId)
                        ->whereNotNull('opt_in_at')
                        ->whereNull('opt_out_at')
                        ->whereNull('blocked_at')
                        ->count();
            }
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Endpoint AJAX que retorna a contagem estimada de destinatários quando
     * o usuário seleciona etiquetas no formulário de disparo. Aplica os
     * mesmos filtros de compliance (opt-in/opt-out/blocked) que o job de
     * envio usa, então a contagem reflete o número real que será disparado.
     */
    public function labelRecipientsCount(\Illuminate\Http\Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $ids = (array) $request->input('ids', []);
        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return response()->json(['count' => 0]);
        }

        // Filtra IDs para garantir que pertencem ao tenant (segurança)
        $validIds = \App\Models\WhatsappLabel::where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();

        if (empty($validIds)) {
            return response()->json(['count' => 0]);
        }

        $count = \App\Models\WhatsappChat::where('tenant_id', $tenantId)
            ->whereNotNull('opt_in_at')
            ->whereNull('opt_out_at')
            ->whereNull('blocked_at')
            ->whereHas('labelTags', fn ($q) => $q->whereIn('whatsapp_labels.id', $validIds))
            ->count();

        return response()->json([
            'count' => $count,
            'max'   => \App\Jobs\ProcessBroadcastCampaignJob::MAX_RECIPIENTS,
        ]);
    }
}
