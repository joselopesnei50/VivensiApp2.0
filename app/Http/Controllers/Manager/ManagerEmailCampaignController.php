<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Services\BrevoService;
use App\Services\EmailQuotaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManagerEmailCampaignController extends Controller
{
    public function index()
    {
        $campaigns = EmailCampaign::with('creator')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->latest()
            ->paginate(20);
        return view('manager.email_campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        return view('manager.email_campaigns.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            // not_regex bloqueia CRLF injection em headers SMTP (Bcc:, From: forjados).
            'subject'           => ['required', 'string', 'max:255', 'not_regex:/[\r\n]/'],
            'html_content'      => ['required', 'string'],
            'sender_name'       => ['nullable', 'string', 'max:100', 'not_regex:/[\r\n]/'],
            'sender_email'      => ['nullable', 'email', 'max:150'],
            'reply_to_email'    => ['nullable', 'email', 'max:150'],
            'audience_type'     => ['required', 'in:leads,manual'],
            'manual_emails_raw' => ['nullable', 'string'],
        ]);

        $manualEmails = $this->parseManualEmailsInput($request->input('manual_emails_raw', ''));

        $campaign = EmailCampaign::create([
            'tenant_id'      => auth()->user()->tenant_id,
            'created_by'     => auth()->id(),
            'name'           => $validated['name'],
            'subject'        => $validated['subject'],
            'html_content'   => $validated['html_content'],
            'sender_name'    => $validated['sender_name'] ?? null,
            'sender_email'   => $validated['sender_email'] ?? null,
            'reply_to_email' => $validated['reply_to_email'] ?? null,
            'audience_type'  => $validated['audience_type'],
            'manual_emails'  => !empty($manualEmails) ? json_encode($manualEmails) : null,
            'status'         => 'draft',
        ]);

        return redirect()->route('manager.email_campaigns.show', $campaign)
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

        return view('manager.email_campaigns.show', ['campaign' => $emailCampaign]);
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
        $contacts = $this->resolveRecipients($emailCampaign->audience_type, $emailCampaign->manual_emails);

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
            Log::info('Manager EmailCampaign: cota diária excedida — disparo bloqueado.', [
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

        Log::info('Manager EmailCampaign: enfileirado', [
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
            return back()->with('error', 'Métricas ainda não disponíveis. Tente novamente em instantes.');
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

    private function resolveRecipients(string $type, ?string $manualEmailsJson = null): array
    {
        $tenantId = auth()->user()->tenant_id;
        $contacts = collect();

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
                Log::warning('Manager EmailCampaign: falha ao buscar leads', ['error' => $e->getMessage()]);
            }
        }

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
