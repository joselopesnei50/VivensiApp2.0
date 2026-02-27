<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use App\Models\Tenant;
use App\Services\EvolutionApiService;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class WhatsappBroadcastController extends Controller
{
    /**
     * Display the broadcast dashboard.
     */
    public function index()
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        if (!$tenantId) {
            return redirect()->route('whatsapp.settings')
                ->with('error', 'Configure a instância WhatsApp primeiro.');
        }

        // Count contacts (chats)
        $contactsCount = WhatsappChat::where('tenant_id', $tenantId)->count();

        // Config info (never inserting with firstOrCreate here to avoid constraint errors)
        $config = WhatsappConfig::where('tenant_id', $tenantId)->first();

        return view('admin.whatsapp.broadcast.index', compact('contactsCount', 'config'));
    }

    /**
     * Handle CSV upload and import contacts.
     */
    public function importContacts(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $file = $request->file('csv_file');
        
        $path = $file->getRealPath();
        $data = array_map('str_getcsv', file($path));
        
        // Remove header if exists (assumes Name, Phone structure)
        if (count($data) > 0 && strtolower($data[0][0]) === 'nome') {
            array_shift($data);
        }

        $imported = 0;
        foreach ($data as $row) {
            if (count($row) >= 2) {
                $name = trim($row[0]);
                $phone = preg_replace('/\D+/', '', $row[1]);
                
                if (strlen($phone) >= 10) {
                    WhatsappChat::firstOrCreate(
                        ['tenant_id' => $tenantId, 'wa_id' => $phone],
                        [
                            'contact_name' => $name,
                            'contact_phone' => $phone,
                            'status' => 'open',
                            'opt_in_at' => now(), // Assumes they opted in since admin uploaded
                        ]
                    );
                    $imported++;
                }
            }
        }

        return redirect()->back()->with('success', "{$imported} contatos importados com sucesso!");
    }

    /**
     * Dispatch broadcast messages.
     * Due to server issues, we will process this synchronously for now (limited batches)
     * or queue them if the queue worker was running.
     */
    public function sendBroadcast(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:4000',
            'audience' => 'required|in:all,selected',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $message = $request->input('message');
        
        $query = WhatsappChat::where('tenant_id', $tenantId)->whereNull('opt_out_at')->whereNull('blocked_at');
        
        if ($request->input('audience') === 'selected' && $request->has('phones')) {
            $phones = explode(',', $request->input('phones'));
            $phones = array_map(function($p) { return preg_replace('/\D+/', '', $p); }, $phones);
            $query->whereIn('wa_id', $phones);
        }

        $contacts = $query->get();
        if ($contacts->isEmpty()) {
            return redirect()->back()->with('error', 'Nenhum contato selecionado ou disponível para envio.');
        }

        $sentCount = 0;
        $contextModel = $this->getContextModel();
        
        if (!$contextModel || empty($contextModel->evolution_instance_name)) {
            return redirect()->back()->with('error', 'Sua instância Evolution API não está configurada.');
        }

        $evo = new EvolutionApiService($contextModel);

        foreach ($contacts as $contact) {
            try {
                // Send synchronously to ensure delivery testing vs timeout
                // A better approach is queuing, but we are working around a stuck queue worker.
                $res = $evo->sendMessage($contact->wa_id, $message, null, rand(1, 3));
                
                if (isset($res['key']['id']) || isset($res['messageId'])) {
                    \App\Models\WhatsappMessage::create([
                        'chat_id' => $contact->id,
                        'message_id' => $res['key']['id'] ?? ($res['messageId'] ?? 'BROADCAST_' . uniqid()),
                        'content' => $message,
                        'direction' => 'outbound',
                        'type' => 'text'
                    ]);
                    $sentCount++;
                }

            } catch (\Exception $e) {
                Log::error("Broadcast failed for {$contact->wa_id}: " . $e->getMessage());
            }
            
            // Artificial delay to prevent API blocking
            usleep(500000); // 0.5 sec
        }

        return redirect()->back()->with('success', "Disparo iniciado: {$sentCount} mensagens enviadas.");
    }

    private function getContextModel()
    {
        $user = auth()->user();
        if ($user->role === 'manager') {
            return $user;
        }
        if ($user->tenant_id) {
            return Tenant::find($user->tenant_id);
        }
        return null;
    }
}
