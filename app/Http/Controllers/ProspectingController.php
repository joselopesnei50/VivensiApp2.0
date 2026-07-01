<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessProspect;
use App\Models\Prospect;
use App\Models\SponsorshipDeal;
use App\Models\BroadcastCampaign;
use App\Models\WhatsappInstance;
use App\Services\LeadSearchService;
use App\Services\EvolutionApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ProspectingController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $status   = $request->query('status');

        $base = Prospect::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId);

        $prospects = (clone $base)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByRaw("CASE status WHEN 'analyzed' THEN 0 WHEN 'raw' THEN 1 ELSE 2 END")
            ->orderBy('lead_score', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total'     => (clone $base)->count(),
            'raw'       => (clone $base)->where('status', 'raw')->count(),
            'analyzed'  => (clone $base)->where('status', 'analyzed')->count(),
            'contacted' => (clone $base)->where('status', 'contacted')->count(),
            'hot'       => (clone $base)->where('lead_score', '>=', 80)->count(),
        ];

        return view('prospecting.index', compact('prospects', 'stats', 'status'));
    }

    public function search(Request $request, LeadSearchService $searchService)
    {
        $mode = $request->input('mode', 'maps');

        $request->validate([
            'term'     => 'required|string|max:100',
            'location' => ($mode === 'maps') ? 'required|string|max:100' : 'nullable|string|max:100',
        ]);

        try {
            if ($mode === 'web') {
                $result    = $searchService->searchWeb($request->term, Auth::user()->tenant_id);
                $modeLabel = 'Busca Web';
            } else {
                $result    = $searchService->search($request->term, $request->location, Auth::user()->tenant_id);
                $modeLabel = 'Google Maps';
            }

            $msg = "[{$modeLabel}] {$result['new']} novo(s) lead(s) encontrado(s).";
            if ($result['updated'] > 0) {
                $msg .= " {$result['updated']} já existia(m) e foram atualizados.";
            }
            $msg .= ' A Bruce AI está analisando em segundo plano.';

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', 'Erro na prospecção: ' . $e->getMessage());
        }
    }

    public function analyze(int $id)
    {
        $prospect = $this->findForTenant($id);
        ProcessProspect::dispatch($prospect)->onQueue('default');

        return back()->with('success', 'Análise da Bruce AI reiniciada para este lead.');
    }

    public function analyzeAll()
    {
        $tenantId = Auth::user()->tenant_id;

        $rawLeads = Prospect::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('status', 'raw')
            ->get();

        foreach ($rawLeads as $prospect) {
            ProcessProspect::dispatch($prospect)->onQueue('default');
        }

        return back()->with('success', "Bruce AI iniciou a análise de {$rawLeads->count()} lead(s) em fila.");
    }

    public function convertToDeal(int $id)
    {
        $prospect = $this->findForTenant($id);
        $tenantId = Auth::user()->tenant_id;

        return DB::transaction(function () use ($prospect, $tenantId) {
            SponsorshipDeal::create([
                'tenant_id'      => $tenantId,
                'company_name'   => $prospect->company_name,
                'contact_person' => 'Lead de Prospecção Automática',
                'phone'          => $prospect->phone,
                'email'          => null,
                'expected_value' => 0,
                'stage'          => 'prospecting',
                'notes'          => implode("\n\n", array_filter([
                    "Lead convertido da Prospecção Automática.",
                    $prospect->address    ? "Endereço: {$prospect->address}"   : null,
                    $prospect->website    ? "Site: {$prospect->website}"       : null,
                    $prospect->ai_analysis ? "Análise Bruce AI:\n{$prospect->ai_analysis}" : null,
                ])),
            ]);

            $prospect->update(['status' => 'contacted']);

            $user   = Auth::user();
            $tenant = $user->tenant;
            $isNgo  = in_array($tenant?->type ?? '', ['ngo']) || $user->isNgo();

            $redirect = $isNgo ? '/ngo/sponsorships' : '/prospecting';

            return redirect($redirect)->with('success', 'Lead enviado para o Funil com sucesso!');
        });
    }

    public function destroy(int $id)
    {
        $this->findForTenant($id)->delete();

        return back()->with('success', 'Lead removido da lista.');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = array_filter(array_map('intval', explode(',', $request->input('prospect_ids_raw', ''))));

        if (empty($ids)) {
            return back()->with('error', 'Nenhum lead selecionado.');
        }

        $tenantId = Auth::user()->tenant_id;

        $deleted = Prospect::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->delete();

        return back()->with('success', "{$deleted} lead(s) deletado(s) com sucesso.");
    }

    public function broadcastWhatsapp(Request $request)
    {
        $request->validate([
            'prospect_ids_raw' => 'required|string',
            'message'          => 'required|string|max:4000',
        ]);

        $ids = array_filter(array_map('intval', explode(',', $request->input('prospect_ids_raw'))));

        if (empty($ids)) {
            return back()->with('error', 'Nenhum lead selecionado.');
        }

        $tenantId = Auth::user()->tenant_id;

        $instance = WhatsappInstance::where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->first();

        if (!$instance) {
            return back()->with('error', 'Nenhuma instância WhatsApp conectada. Configure em Aparelhos Conectados.');
        }

        $prospects = Prospect::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get();

        if ($prospects->isEmpty()) {
            return back()->with('error', 'Nenhum lead selecionado possui número de telefone.');
        }

        $message    = $request->input('message');
        $instanceId = $instance->id;
        $total      = $prospects->count();

        foreach ($prospects as $prospect) {
            \App\Jobs\SendProspectWhatsapp::dispatch(
                $prospect->id,
                $tenantId,
                $instanceId,
                $message
            )->onQueue('default')->delay(now()->addSeconds($prospects->search($prospect) * 3));

            $prospect->update(['status' => 'contacted']);
        }

        if (Schema::hasTable('broadcast_campaigns')) {
            try {
                BroadcastCampaign::create([
                    'tenant_id'     => $tenantId,
                    'message'       => $message,
                    'has_image'     => false,
                    'audience_type' => 'selected',
                    'total_sent'    => $total,
                    'total_failed'  => 0,
                ]);
            } catch (\Exception $e) {
                Log::warning('BroadcastCampaign log failed: ' . $e->getMessage());
            }
        }

        return back()->with('success', "{$total} mensagem(ns) agendada(s) para envio via WhatsApp. Processando em segundo plano.");
    }

    /**
     * Envia prospects selecionados pro módulo formal de Disparo em Massa.
     * Cria uma BroadcastCampaign rascunho (status='draft') com os telefones
     * pré-populados — o cliente conclui a campanha em /whatsapp/broadcast
     * usando a infra completa (templates, agendamento, anti-ban, cota).
     *
     * Diferença vs broadcastWhatsapp: aquele dispara DIRETO (sem revisão,
     * cota, agendamento). Esse aqui prepara o terreno pro fluxo formal.
     */
    public function sendToBroadcast(Request $request)
    {
        $request->validate([
            'prospect_ids_raw' => 'required|string',
        ]);

        $ids = array_filter(array_map('intval', explode(',', $request->input('prospect_ids_raw'))));
        if (empty($ids)) {
            return back()->with('error', 'Nenhum lead selecionado.');
        }

        $tenantId = Auth::user()->tenant_id;

        $prospects = Prospect::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get();

        if ($prospects->isEmpty()) {
            return back()->with('error', 'Nenhum lead selecionado possui número de telefone.');
        }

        // Normaliza e desduplica os telefones BR antes de gravar.
        $phones = $prospects
            ->map(fn ($p) => EvolutionApiService::normalizeBrazilianPhone((string) $p->phone))
            ->filter(fn ($p) => $p && strlen($p) >= 12)
            ->unique()
            ->values()
            ->all();

        if (empty($phones)) {
            return back()->with('error', 'Nenhum telefone válido entre os leads selecionados.');
        }

        if (!Schema::hasTable('broadcast_campaigns')) {
            return back()->with('error', 'O módulo de Disparo em Massa não está disponível no momento.');
        }

        $campaign = BroadcastCampaign::create([
            'tenant_id'         => $tenantId,
            'created_by'        => Auth::id(),
            'name'              => 'Prospecção IA — ' . now()->format('d/m/Y H:i'),
            'message'           => '',
            'has_image'         => false,
            'audience_type'     => 'selected',
            'status'            => 'draft',
            // Formato alinhado com ProcessBroadcastCampaignJob:451 (explode por virgula).
            'phones'            => implode(',', $phones),
            'actual_recipients' => count($phones),
        ]);

        // Marca os prospects como contatados pra não disparar duas vezes.
        Prospect::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $prospects->pluck('id')->all())
            ->update(['status' => 'contacted']);

        return redirect()->route('whatsapp.broadcast.campaigns')->with(
            'success',
            sprintf(
                'Rascunho criado com %d destinatário(s) da Prospecção IA. Edite a mensagem e dispare quando quiser.',
                count($phones)
            )
        );
    }

    private function findForTenant(int $id): Prospect
    {
        $tenantId = Auth::user()->tenant_id;

        return Prospect::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);
    }
}
