<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Models\BroadcastCampaign;
use App\Models\Tenant;
use App\Services\EvolutionApiService;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhatsappBroadcastController extends Controller
{
    public function index()
    {
        try {
        Gate::authorize('access-whatsapp');
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$tenantId && $user->role !== 'super_admin') {
            return redirect()->route('whatsapp.settings')
                ->with('error', 'Configure a instância WhatsApp primeiro.');
        }

        $contactsCount = WhatsappChat::where('tenant_id', $tenantId)->count();
        $config        = WhatsappConfig::where('tenant_id', $tenantId)->first();
        $activeInstance = WhatsappInstance::where('tenant_id', $tenantId)
            ->where('status', 'open')->first();

        try {
            $campaigns = BroadcastCampaign::where('tenant_id', $tenantId)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();
        } catch (\Exception $e) {
            $campaigns = collect();
        }

        return view('admin.whatsapp.broadcast.index',
            compact('contactsCount', 'config', 'activeInstance', 'campaigns'));

        } catch (\Throwable $e) {
            Log::error('BroadcastController@index: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response('Erro: ' . $e->getMessage() . ' em ' . basename($e->getFile()) . ':' . $e->getLine(), 500);
        }
    }

    public function importContacts(Request $request)
    {
        Gate::authorize('access-whatsapp');

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $data = array_map('str_getcsv', file($request->file('csv_file')->getRealPath()));

        if (count($data) > 0 && strtolower($data[0][0]) === 'nome') {
            array_shift($data);
        }

        $imported = 0;
        foreach ($data as $row) {
            if (count($row) >= 2) {
                $name  = trim($row[0]);
                $phone = preg_replace('/\D+/', '', $row[1]);
                if (strlen($phone) >= 10) {
                    WhatsappChat::firstOrCreate(
                        ['tenant_id' => $tenantId, 'wa_id' => $phone],
                        ['contact_name' => $name, 'contact_phone' => $phone,
                         'status' => 'open', 'opt_in_at' => now()]
                    );
                    $imported++;
                }
            }
        }

        return redirect()->back()->with('success', "{$imported} contatos importados com sucesso!");
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

        $evo    = new EvolutionApiService($instance);
        $groups = $evo->getGroups();

        $mapped = array_map(fn($g) => [
            'id'   => $g['id'] ?? '',
            'name' => $g['subject'] ?? $g['name'] ?? 'Grupo sem nome',
            'size' => $g['size'] ?? ($g['participants'] ? count($g['participants']) : 0),
        ], $groups);

        usort($mapped, fn($a, $b) => strcmp($a['name'], $b['name']));

        return response()->json($mapped);
    }

    public function sendBroadcast(Request $request)
    {
        Gate::authorize('access-whatsapp');

        $request->validate([
            'message'         => 'nullable|string|max:4000',
            'audience'        => 'required|in:all,selected,groups',
            'cadence'         => 'nullable|integer|in:1,3,5,10,30',
            'broadcast_image' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ]);

        if (!$request->filled('message') && !$request->hasFile('broadcast_image')) {
            return redirect()->back()->with('error', 'Digite uma mensagem ou anexe uma imagem.');
        }

        $tenantId        = auth()->user()->tenant_id;
        $message         = $request->input('message', '');
        $audience        = $request->input('audience');
        $cadenceSeconds  = (int) $request->input('cadence', 3);

        $instance = WhatsappInstance::where('tenant_id', $tenantId)
            ->where('status', 'open')->first();

        if (!$instance) {
            return redirect()->back()->with('error', 'Nenhuma instância WhatsApp conectada.');
        }

        // Image
        $imageBase64 = null;
        $imageMime   = null;
        if ($request->hasFile('broadcast_image')) {
            $file        = $request->file('broadcast_image');
            $imageMime   = $file->getMimeType() ?: 'image/jpeg';
            $path        = $file->store('broadcasts', 'public');
            $imageBase64 = base64_encode(Storage::disk('public')->get($path));
        }

        $evo = new EvolutionApiService($instance);

        // Build recipients list
        $recipients = collect();

        if ($audience === 'groups') {
            $groupIds = $request->input('group_ids', []);
            if (empty($groupIds)) {
                return redirect()->back()->with('error', 'Selecione ao menos um grupo.');
            }
            foreach ($groupIds as $gid) {
                $recipients->push((object)['wa_id' => $gid, 'id' => null]);
            }
        } else {
            $query = WhatsappChat::where('tenant_id', $tenantId)
                ->whereNull('opt_out_at')
                ->whereNull('blocked_at');

            if ($audience === 'selected' && $request->has('phones')) {
                $phones = array_map(
                    fn($p) => preg_replace('/\D+/', '', $p),
                    explode(',', $request->input('phones'))
                );
                $query->whereIn('wa_id', $phones);
            }
            $recipients = $query->get();
        }

        if ($recipients->isEmpty()) {
            return redirect()->back()->with('error', 'Nenhum destinatário selecionado.');
        }

        $sentCount   = 0;
        $failedCount = 0;

        foreach ($recipients as $recipient) {
            try {
                $res = $imageBase64
                    ? $evo->sendMedia($recipient->wa_id, $imageBase64, $message, $imageMime)
                    : $evo->sendMessage($recipient->wa_id, $message, null, rand(1, 3));

                $success = !isset($res['error']) && !empty($res);

                if ($success) {
                    if ($recipient->id) {
                        WhatsappMessage::create([
                            'chat_id'    => $recipient->id,
                            'message_id' => $res['key']['id'] ?? ('BROADCAST_' . uniqid()),
                            'content'    => $imageBase64 ? ('[imagem] ' . $message) : $message,
                            'direction'  => 'outbound',
                            'type'       => $imageBase64 ? 'image' : 'text',
                        ]);
                    }
                    $sentCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Exception $e) {
                Log::error("Broadcast failed for {$recipient->wa_id}: " . $e->getMessage());
                $failedCount++;
            }

            usleep($cadenceSeconds * 1_000_000);
        }

        try {
            BroadcastCampaign::create([
                'tenant_id'     => $tenantId,
                'message'       => $message ?: null,
                'has_image'     => (bool) $imageBase64,
                'audience_type' => $audience,
                'total_sent'    => $sentCount,
                'total_failed'  => $failedCount,
            ]);
        } catch (\Exception $e) {
            Log::warning('BroadcastCampaign log failed: ' . $e->getMessage());
        }

        return redirect()->back()->with('success',
            "Campanha concluída: {$sentCount} enviados" . ($failedCount ? ", {$failedCount} falhas." : "."));
    }
}
