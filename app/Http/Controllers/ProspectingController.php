<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessProspect;
use App\Models\EmailCampaign;
use App\Models\Prospect;
use App\Models\SponsorshipDeal;
use App\Models\BroadcastCampaign;
use App\Services\LeadSearchService;
use App\Services\EvolutionApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProspectingController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $status   = $request->query('status');

        // Whitelist per_page (2026-08-07) — antes ficava capado em 20 fixo.
        $perPage = (int) $request->query('per_page', 20);
        if (!in_array($perPage, [20, 50, 100, 200], true)) {
            $perPage = 20;
        }

        $base = Prospect::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId);

        $prospects = (clone $base)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByRaw("CASE status WHEN 'analyzed' THEN 0 WHEN 'raw' THEN 1 ELSE 2 END")
            ->orderBy('lead_score', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $stats = [
            'total'     => (clone $base)->count(),
            'raw'       => (clone $base)->where('status', 'raw')->count(),
            'analyzed'  => (clone $base)->where('status', 'analyzed')->count(),
            'contacted' => (clone $base)->where('status', 'contacted')->count(),
            'hot'       => (clone $base)->where('lead_score', '>=', 80)->count(),
        ];

        return view('prospecting.index', compact('prospects', 'stats', 'status', 'perPage'));
    }

    public function search(Request $request, LeadSearchService $searchService)
    {
        $mode = $request->input('mode', 'maps');

        $request->validate([
            'term'     => 'required|string|max:100',
            'location' => ($mode === 'maps') ? 'required|string|max:100' : 'nullable|string|max:100',
            'limit'    => 'nullable|integer|in:20,40,60,80,100',
        ]);

        $limit = (int) $request->input('limit', 20);

        try {
            if ($mode === 'web') {
                $result    = $searchService->searchWeb($request->term, Auth::user()->tenant_id, $limit);
                $modeLabel = 'Busca Web';
            } else {
                $result    = $searchService->search($request->term, $request->location, Auth::user()->tenant_id, $limit);
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
        $request->validate([
            'prospect_ids_raw' => 'required|string|max:5000',
        ]);

        $ids = array_filter(array_map('intval', explode(',', $request->input('prospect_ids_raw'))));

        if (empty($ids)) {
            return back()->with('error', 'Nenhum lead selecionado.');
        }

        if (count($ids) > 500) {
            return back()->with('error', 'Máximo de 500 leads por exclusão em massa.');
        }

        $tenantId = Auth::user()->tenant_id;

        $deleted = Prospect::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->delete();

        if ($deleted > 0) {
            \App\Models\AuditLog::create([
                'tenant_id'      => $tenantId,
                'user_id'        => Auth::id(),
                'event'          => 'bulk_deleted',
                'auditable_type' => Prospect::class,
                'auditable_id'   => 0,
                'old_values'     => ['ids' => array_values($ids), 'count' => $deleted],
                'new_values'     => null,
                'url'            => $request->fullUrl(),
                'ip_address'     => $request->ip(),
                'user_agent'     => (string) $request->userAgent(),
            ]);
        }

        return back()->with('success', "{$deleted} lead(s) deletado(s) com sucesso.");
    }

    /**
     * Antes disparava SendProspectWhatsapp DIRETO (sem cota, sem anti-ban,
     * e gravando opt_in_at em número frio que nunca consentiu). Desde
     * 2026-07-31 cria um rascunho no módulo formal de Disparo em Massa com
     * a mensagem já preenchida — o envio passa por revisão, cota e anti-ban.
     */
    public function broadcastWhatsapp(Request $request)
    {
        $request->validate([
            'prospect_ids_raw' => 'required|string|max:5000',
            'message'          => 'required|string|max:4000',
        ]);

        return $this->createBroadcastDraft($request, $request->input('message'));
    }

    /**
     * Envia prospects selecionados pro módulo formal de Disparo em Massa.
     * Cria uma BroadcastCampaign rascunho (status='draft') com os telefones
     * pré-populados — o cliente conclui a campanha em /whatsapp/broadcast
     * usando a infra completa (templates, agendamento, anti-ban, cota).
     *
     * Diferença vs broadcastWhatsapp: esse aqui cria o rascunho sem mensagem;
     * aquele pré-preenche a mensagem digitada no modal. Ambos passam pelo
     * fluxo formal — não existe mais disparo direto na Prospecção.
     */
    public function sendToBroadcast(Request $request)
    {
        $request->validate([
            'prospect_ids_raw' => 'required|string|max:5000',
        ]);

        return $this->createBroadcastDraft($request, '');
    }

    private function createBroadcastDraft(Request $request, string $message)
    {
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
            'message'           => $message,
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

    /**
     * Salva/edita manualmente o e-mail de um prospect + registra opt-in LGPD.
     *
     * Chamado pelo modal/input da UI quando o scraping falhou ou o user quer
     * substituir. O opt_in só vai para true se o user marcar explicitamente
     * o checkbox de consentimento (LGPD art. 7º).
     */
    public function updateEmail(Request $request, int $id)
    {
        $validated = $request->validate([
            'email'        => ['required', 'email', 'max:190'],
            'email_opt_in' => ['nullable', 'boolean'],
        ]);

        $prospect = $this->findForTenant($id);

        $prospect->update([
            'email'          => strtolower(trim($validated['email'])),
            'email_opt_in'   => (bool) $request->boolean('email_opt_in'),
            'email_source'   => 'manual',
            'email_found_at' => now(),
        ]);

        return back()->with('success', 'E-mail atualizado.' .
            ($prospect->email_opt_in ? ' Consentimento LGPD registrado.' : ''));
    }

    /**
     * Cria um rascunho de EmailCampaign com os prospects selecionados como
     * destinatários manuais (apenas os que têm e-mail + opt_in_email = true).
     *
     * NÃO dispara — o user precisa abrir a campanha rascunho, editar subject
     * e html_content, e clicar "enviar". Pipeline segue via SendEmailCampaignJob
     * na queue 'emails' (Brevo).
     */
    public function sendToEmailCampaign(Request $request)
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
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where('email_opt_in', true)
            ->get();

        if ($prospects->isEmpty()) {
            return back()->with(
                'error',
                'Nenhum dos leads selecionados tem e-mail com consentimento LGPD marcado. '
                . 'Marque o checkbox "declaro consentimento" no e-mail antes de enviar.'
            );
        }

        // Dedup + normaliza pra minúsculo
        $emails = $prospects
            ->pluck('email')
            ->map(fn ($e) => strtolower(trim((string) $e)))
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        if (empty($emails)) {
            return back()->with('error', 'Nenhum e-mail válido entre os prospects selecionados.');
        }

        $campaign = EmailCampaign::create([
            'tenant_id'     => $tenantId,
            'created_by'    => Auth::id(),
            'name'          => 'Prospecção IA — ' . now()->format('d/m/Y H:i'),
            'subject'       => '[EDITE ANTES DE ENVIAR] Assunto da campanha',
            'html_content'  => '<p>Edite este conteúdo antes de enviar a campanha.</p>'
                             . '<p>Destinatários: ' . count($emails) . ' prospects com consentimento LGPD.</p>',
            'audience_type' => 'manual',
            'manual_emails' => json_encode($emails),
            'status'        => 'draft',
        ]);

        // Marca os prospects como contatados pra não reenviar sem querer.
        Prospect::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $prospects->pluck('id')->all())
            ->update(['status' => 'contacted']);

        // Escolhe a rota de destino baseada no perfil (NGO / Manager / Admin)
        $user   = Auth::user();
        $tenant = $user->tenant;
        $isNgo  = in_array($tenant?->type ?? '', ['ngo']) || $user->isNgo();

        if ($isNgo) {
            $showRoute = 'ngo.email_campaigns.show';
        } elseif (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            $showRoute = 'admin.email_campaigns.show';
        } else {
            $showRoute = 'manager.email_campaigns.show';
        }

        return redirect()->route($showRoute, $campaign)->with(
            'success',
            sprintf(
                'Rascunho criado com %d destinatário(s) da Prospecção IA. Edite assunto e conteúdo, e clique em enviar.',
                count($emails)
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
