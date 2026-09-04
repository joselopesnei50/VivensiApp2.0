<?php

namespace App\Http\Controllers\Ngo;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Services\BrevoService;
use App\Services\EmailQuotaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NgoEmailCampaignController extends Controller
{
    public function index()
    {
        $campaigns = EmailCampaign::with('creator')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->latest()
            ->paginate(20);
        return view('ngo.email_campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        $lists = \App\Models\EmailContactList::where('tenant_id', auth()->user()->tenant_id)
            ->withCount('activeContacts')
            ->orderByDesc('updated_at')
            ->get();
        return view('ngo.email_campaigns.create', compact('lists'));
    }

    /**
     * Upload de imagem pra usar no HTML da campanha.
     * Devolve URL publica absoluta (Brevo busca por HTTP).
     * Aceita jpg/png/webp/gif, max 5MB. Isolado por tenant no filename.
     */
    public function uploadImage(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->tenant_id) {
            return response()->json(['error' => 'Sessão sem tenant.'], 401);
        }

        $request->validate([
            'image' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,gif'],
        ]);

        $file = $request->file('image');
        $ext  = $file->getClientOriginalExtension();
        $name = $user->tenant_id . '_' . uniqid() . '.' . $ext;
        $path = $file->storeAs('email_campaigns_uploads', $name, 'public');

        return response()->json([
            'url' => asset('storage/' . $path),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'subject'               => ['required', 'string', 'max:255', 'not_regex:/[\r\n]/'],
            'html_content'          => ['required', 'string'],
            'sender_name'           => ['nullable', 'string', 'max:100', 'not_regex:/[\r\n]/'],
            'sender_email'          => ['nullable', 'email', 'max:150'],
            'reply_to_email'        => ['nullable', 'email', 'max:150'],
            'audience_type'         => ['required', 'in:donors,donors_optins,leads,manual,contact_list'],
            'manual_emails_raw'     => ['nullable', 'string'],
            'email_contact_list_id' => ['nullable', 'integer', 'required_if:audience_type,contact_list'],
        ]);

        // Guard: lista precisa ser do mesmo tenant + ter contatos ativos
        $contactListId = null;
        if ($validated['audience_type'] === 'contact_list') {
            $list = \App\Models\EmailContactList::where('tenant_id', auth()->user()->tenant_id)
                ->where('id', $validated['email_contact_list_id'])
                ->first();
            if (!$list) {
                return back()->withInput()->withErrors(['email_contact_list_id' => 'Lista não encontrada.']);
            }
            if ($list->activeContacts()->count() === 0) {
                return back()->withInput()->withErrors(['email_contact_list_id' => 'Esta lista não tem contatos ativos.']);
            }
            $contactListId = $list->id;
        }

        // Cast defensivo: ConvertEmptyStringsToNull middleware transforma campo vazio
        // em null, e $request->input(key, default) so usa default se a chave AUSENTE.
        $manualEmails = $this->parseManualEmailsInput((string) $request->input('manual_emails_raw', ''));

        $campaign = EmailCampaign::create([
            'tenant_id'             => auth()->user()->tenant_id,
            'created_by'            => auth()->id(),
            'name'                  => $validated['name'],
            'subject'               => $validated['subject'],
            'html_content'          => $validated['html_content'],
            'sender_name'           => $validated['sender_name'] ?? null,
            'sender_email'          => $validated['sender_email'] ?? null,
            'reply_to_email'        => $validated['reply_to_email'] ?? null,
            'audience_type'         => $validated['audience_type'],
            'manual_emails'         => !empty($manualEmails) ? json_encode($manualEmails) : null,
            'email_contact_list_id' => $contactListId,
            'status'                => 'draft',
        ]);

        return redirect()->route('ngo.email_campaigns.show', $campaign)
            ->with('success', "Campanha \"{$campaign->name}\" criada como rascunho.");
    }

    public function show(EmailCampaign $emailCampaign)
    {
        $this->authorizeForTenant($emailCampaign);

        if ($emailCampaign->status === 'sent' && $emailCampaign->brevo_campaign_id) {
            $stale = $emailCampaign->stats_fetched_at === null
                || $emailCampaign->stats_fetched_at->lt(now()->subMinutes(30));

            if ($stale) {
                $stats = app(BrevoService::class)->getBrevoEmailCampaignStats($emailCampaign->brevo_campaign_id);

                if (!empty($stats) && !isset($stats['_no_data'])) {
                    $emailCampaign->update([
                        'stat_delivered'    => $stats['delivered'],
                        'stat_opens'        => $stats['opens'],
                        'stat_clicks'       => $stats['clicks'],
                        'stat_bounces'      => $stats['bounces'],
                        'stat_unsubscribes' => $stats['unsubscribes'],
                        'stat_spam'         => $stats['spam'],
                        'stats_fetched_at'  => now(),
                    ]);
                    $emailCampaign->refresh();
                } elseif (isset($stats['_no_data'])) {
                    $emailCampaign->update(['stats_fetched_at' => now()]);
                }
            }
        }

        return view('ngo.email_campaigns.show', ['campaign' => $emailCampaign]);
    }

    public function destroy(EmailCampaign $emailCampaign)
    {
        $this->authorizeForTenant($emailCampaign);

        if ($emailCampaign->status === 'sent') {
            return back()->with('error', 'Não é possível excluir uma campanha já enviada.');
        }
        $emailCampaign->delete();
        return back()->with('success', 'Campanha excluída.');
    }

    public function send(EmailCampaign $emailCampaign)
    {
        $this->authorizeForTenant($emailCampaign);

        if ($emailCampaign->status === 'sent') {
            return back()->with('error', 'Esta campanha já foi enviada.');
        }
        if ($emailCampaign->status === 'sending') {
            return back()->with('error', 'Esta campanha já está sendo enviada.');
        }

        // Etapa rapida: resolve destinatarios + valida cota (sincrono).
        // Parte lenta (Brevo importContacts loop) vai pro worker de emails.
        $contacts = $this->resolveRecipients($emailCampaign);

        if (empty($contacts)) {
            $emailCampaign->update([
                'status'        => 'error',
                'error_message' => 'Nenhum destinatário encontrado para o público selecionado.',
            ]);
            return back()->with('error', 'Nenhum destinatário encontrado para o público selecionado.');
        }

        $tenant = auth()->user()->tenant;
        $quota  = app(EmailQuotaService::class);
        $count  = count($contacts);

        if (!$quota->tryConsume($tenant, $count)) {
            $remaining = $quota->getRemainingToday($tenant);
            $cap       = $quota->getQuota($tenant);
            $msg = "Cota diária de e-mails excedida ({$count} destinatários, restam {$remaining}/{$cap} hoje). "
                 . "Solicite ao Super Admin para aumentar a capacidade ou aguarde o reset diário.";
            $emailCampaign->update(['status' => 'error', 'error_message' => $msg]);
            Log::info('NGO EmailCampaign: cota diária excedida — disparo bloqueado.', [
                'campaign_id' => $emailCampaign->id,
                'tenant_id'   => $tenant->id,
                'requested'   => $count,
                'remaining'   => $remaining,
                'daily_quota' => $cap,
            ]);
            return back()->with('error', $msg);
        }

        $emailCampaign->update(['status' => 'sending', 'error_message' => null]);

        \App\Jobs\SendEmailCampaignJob::dispatch($emailCampaign->id, $contacts, $tenant->id)->onQueue('emails');

        Log::info('NGO EmailCampaign: enfileirado', [
            'campaign_id' => $emailCampaign->id,
            'tenant_id'   => $tenant->id,
            'recipients'  => $count,
        ]);

        return back()->with('success', "Campanha enfileirada para {$count} destinatário(s). O envio roda em segundo plano — acompanhe o status na lista de campanhas.");
    }

    public function refreshStats(EmailCampaign $emailCampaign)
    {
        $this->authorizeForTenant($emailCampaign);

        if (!$emailCampaign->brevo_campaign_id) {
            return back()->with('error', 'Campanha ainda não enviada pelo Brevo.');
        }

        $stats = app(BrevoService::class)->getBrevoEmailCampaignStats($emailCampaign->brevo_campaign_id);

        if (empty($stats)) {
            return back()->with('error', 'Métricas ainda não disponíveis. O Brevo pode levar alguns minutos após o envio. Tente novamente em instantes.');
        }

        $emailCampaign->update([
            'stat_delivered'    => $stats['delivered'],
            'stat_opens'        => $stats['opens'],
            'stat_clicks'       => $stats['clicks'],
            'stat_bounces'      => $stats['bounces'],
            'stat_unsubscribes' => $stats['unsubscribes'],
            'stat_spam'         => $stats['spam'],
            'stats_fetched_at'  => now(),
        ]);

        return back()->with('success', 'Métricas atualizadas com sucesso!');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolveRecipients(EmailCampaign $campaign): array
    {
        $type = $campaign->audience_type;
        $manualEmailsJson = $campaign->manual_emails;
        $tenantId = auth()->user()->tenant_id;
        $contacts = collect();

        if ($type === 'contact_list' && $campaign->email_contact_list_id) {
            $list = \App\Models\EmailContactList::where('tenant_id', $tenantId)
                ->where('id', $campaign->email_contact_list_id)
                ->first();
            if ($list) {
                $list->activeContacts()
                    ->select(['email', 'name'])
                    ->chunk(500, function ($chunk) use (&$contacts) {
                        $contacts = $contacts->merge(
                            $chunk->map(fn($c) => ['email' => $c->email, 'name' => $c->name ?? ''])
                        );
                    });
            }
        }

        if (in_array($type, ['donors', 'donors_optins'])) {
            // LGPD art. 8: consentimento sempre obrigatorio para marketing.
            // Ambos os tipos (legado 'donors' e 'donors_optins') exigem opt-in.
            // Doadores sem opt-in devem receber transacionais via BrevoService::sendEmail,
            // nunca via modulo de campanha.
            $donors = DB::table('ngo_donors')
                ->where('tenant_id', $tenantId)
                ->where('email_marketing_opt_in', true)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->get(['email', 'name']);

            $contacts = $contacts->merge(
                $donors->map(fn($d) => ['email' => $d->email, 'name' => $d->name ?? ''])
            );
        }

        if ($type === 'leads') {
            try {
                $leads = DB::table('landing_page_leads')
                    ->join('landing_pages', 'landing_pages.id', '=', 'landing_page_leads.landing_page_id')
                    ->where('landing_pages.tenant_id', $tenantId)
                    ->whereNotNull('landing_page_leads.email')
                    ->where('landing_page_leads.email', '!=', '')
                    ->get(['landing_page_leads.email', 'landing_page_leads.name']);

                $contacts = $contacts->merge(
                    $leads->map(fn($l) => ['email' => $l->email, 'name' => $l->name ?? 'Visitante'])
                );
            } catch (\Throwable $e) {
                Log::warning('NGO EmailCampaign: falha ao buscar leads', ['error' => $e->getMessage()]);
            }
        }

        // E-mails manuais — sempre incluídos se informados
        if ($manualEmailsJson) {
            $manual   = json_decode($manualEmailsJson, true) ?? [];
            $contacts = $contacts->merge(collect($manual));
        }

        return $contacts
            ->unique('email')
            ->filter(fn($c) => filter_var($c['email'], FILTER_VALIDATE_EMAIL))
            ->values()
            ->toArray();
    }

    private function authorizeForTenant(EmailCampaign $campaign): void
    {
        if ($campaign->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
    }

    private function parseManualEmailsInput(string $raw): array
    {
        $lines    = preg_split('/[\n,;]+/', $raw);
        $contacts = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;

            if (preg_match('/^(.+?)\s*<([^>]+)>\s*$/', $line, $m)) {
                $email = trim($m[2]);
                $name  = trim($m[1]);
            } else {
                $email = $line;
                $name  = '';
            }

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $contacts[] = ['email' => strtolower($email), 'name' => $name ?: $email];
            }
        }

        return $contacts;
    }
}
