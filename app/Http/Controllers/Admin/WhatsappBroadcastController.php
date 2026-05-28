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

        return view('admin.whatsapp.broadcast.index',
            compact('contactsCount', 'config', 'activeInstance', 'campaigns', 'scheduled', 'preMessage'));
    }

    public function importContacts(Request $request)
    {
        Gate::authorize('access-whatsapp');

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $tenantId  = auth()->user()->tenant_id;
        $maxLines  = 5000;
        $imported  = 0;
        $lineCount = 0;
        $handle    = fopen($request->file('csv_file')->getRealPath(), 'r');

        // Pular cabeçalho se existir
        $firstRow = fgetcsv($handle);
        if ($firstRow && strtolower(trim($firstRow[0] ?? '')) !== 'nome') {
            // Não é cabeçalho — processa como dado
            if (count($firstRow) >= 2) {
                $phone = \App\Services\EvolutionApiService::normalizeBrazilianPhone($firstRow[1]);
                if ($phone && strlen($phone) >= 12) {
                    WhatsappChat::firstOrCreate(
                        ['tenant_id' => $tenantId, 'wa_id' => $phone],
                        ['contact_name' => trim($firstRow[0]), 'contact_phone' => $phone, 'status' => 'open', 'opt_in_at' => now()]
                    );
                    $imported++;
                }
            }
            $lineCount++;
        }

        while (($row = fgetcsv($handle)) !== false && $lineCount < $maxLines) {
            $lineCount++;
            if (count($row) >= 2) {
                $name  = trim($row[0]);
                $phone = \App\Services\EvolutionApiService::normalizeBrazilianPhone($row[1]);
                if ($phone && strlen($phone) >= 12) {
                    WhatsappChat::firstOrCreate(
                        ['tenant_id' => $tenantId, 'wa_id' => $phone],
                        ['contact_name' => $name, 'contact_phone' => $phone, 'status' => 'open', 'opt_in_at' => now()]
                    );
                    $imported++;
                }
            }
        }

        fclose($handle);

        $msg = "{$imported} contatos importados com sucesso!";
        if ($lineCount >= $maxLines) {
            $msg .= " (limite de {$maxLines} linhas por importação atingido)";
        }

        return redirect()->back()->with('success', $msg);
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

    public function cancelScheduled(int $id)
    {
        Gate::authorize('access-whatsapp');
        $tenantId = auth()->user()->tenant_id;

        $campaign = \App\Models\BroadcastCampaign::where('tenant_id', $tenantId)
            ->where('id', $id)
            ->where('status', 'scheduled')
            ->firstOrFail();

        $campaign->update(['status' => 'failed']);

        return redirect()->back()->with('success', 'Campanha agendada cancelada com sucesso.');
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
            'audience'        => 'required|in:all,selected,groups',
            'cadence'         => 'nullable|integer|in:1,3,5,10,30',
            'broadcast_image' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'scheduled_at'    => 'nullable|date|after:now',
            'group_send_mode' => 'nullable|in:group,members',
            'group_ids'       => 'required_if:audience,groups|array|min:1',
            'group_ids.*'     => 'string',
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

        $campaign = \App\Models\BroadcastCampaign::create([
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
        ]);

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

        if (!$scheduledAt) {
            \App\Jobs\ProcessBroadcastCampaignJob::dispatch($campaign->id, $campaign->tenant_id);
            return redirect()->back()->with('success', 'Disparo iniciado em segundo plano!');
        }

        return redirect()->back()->with('success', 'Disparo agendado para ' . $campaign->scheduled_at->format('d/m/Y H:i') . '!');
    }
}
