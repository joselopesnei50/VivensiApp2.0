<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Services\BrevoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManagerEmailCampaignController extends Controller
{
    public function index()
    {
        $campaigns = EmailCampaign::with('creator')->latest()->paginate(20);
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
            'subject'           => ['required', 'string', 'max:255'],
            'html_content'      => ['required', 'string'],
            'sender_name'       => ['nullable', 'string', 'max:100'],
            'sender_email'      => ['nullable', 'email', 'max:150'],
            'reply_to_email'    => ['nullable', 'email', 'max:150'],
            'audience_type'     => ['required', 'in:leads,manual'],
            'manual_emails_raw' => ['nullable', 'string'],
        ]);

        $manualEmails = $this->parseManualEmailsInput($request->input('manual_emails_raw', ''));

        $campaign = EmailCampaign::create([
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
        if ($emailCampaign->status === 'sent') {
            return back()->with('error', 'Não é possível excluir uma campanha já enviada.');
        }
        $emailCampaign->delete();
        return back()->with('success', 'Campanha excluída.');
    }

    public function send(EmailCampaign $emailCampaign)
    {
        if ($emailCampaign->status === 'sent') {
            return back()->with('error', 'Esta campanha já foi enviada.');
        }
        if ($emailCampaign->status === 'sending') {
            return back()->with('error', 'Esta campanha já está sendo enviada.');
        }

        $emailCampaign->update(['status' => 'sending']);
        $brevo = app(BrevoService::class);

        try {
            $contacts = $this->resolveRecipients($emailCampaign->audience_type, $emailCampaign->manual_emails);

            if (empty($contacts)) {
                $emailCampaign->update([
                    'status'        => 'error',
                    'error_message' => 'Nenhum destinatário encontrado para o público selecionado.',
                ]);
                return back()->with('error', 'Nenhum destinatário encontrado para o público selecionado.');
            }

            $orgName  = auth()->user()->tenant->name ?? 'Organização';
            $listName = "MGR — {$orgName} — {$emailCampaign->name} — " . now()->format('d/m/Y H:i');
            $listId   = $brevo->createContactList($listName);

            if (!$listId) {
                $emailCampaign->update([
                    'status'        => 'error',
                    'error_message' => 'Falha ao criar lista de contatos no Brevo.',
                ]);
                return back()->with('error', 'Falha ao criar lista no Brevo. Verifique as configurações do sistema.');
            }

            $imported = $brevo->importContacts($listId, $contacts);

            if ($imported === 0) {
                $emailCampaign->update([
                    'status'        => 'error',
                    'error_message' => 'Nenhum contato foi adicionado à lista no Brevo.',
                ]);
                return back()->with('error', 'Falha ao importar contatos. Verifique se os e-mails são válidos.');
            }

            Log::info('Manager EmailCampaign: contatos importados', [
                'campaign_id' => $emailCampaign->id,
                'tenant_id'   => auth()->user()->tenant_id,
                'imported'    => $imported,
                'total'       => count($contacts),
            ]);

            $campaignId = $brevo->createBrevoEmailCampaign([
                'name'           => $emailCampaign->name,
                'subject'        => $emailCampaign->subject,
                'html_content'   => $emailCampaign->html_content,
                'sender_name'    => $emailCampaign->sender_name,
                'sender_email'   => $emailCampaign->sender_email,
                'reply_to_email' => $emailCampaign->reply_to_email,
                'brevo_list_id'  => $listId,
            ]);

            if (!$campaignId) {
                $brevoMsg = $brevo->lastBrevoError ?? 'Erro desconhecido';
                $emailCampaign->update([
                    'status'        => 'error',
                    'brevo_list_id' => $listId,
                    'error_message' => 'Falha ao criar campanha no Brevo. ' . $brevoMsg,
                ]);
                return back()->with('error', 'Falha ao criar campanha no Brevo. ' . $brevoMsg);
            }

            $sent = $brevo->sendBrevoEmailCampaign($campaignId);

            $emailCampaign->update([
                'status'            => $sent ? 'sent' : 'error',
                'brevo_list_id'     => $listId,
                'brevo_campaign_id' => $campaignId,
                'recipient_count'   => count($contacts),
                'sent_at'           => $sent ? now() : null,
                'error_message'     => $sent ? null : 'Falha ao disparar a campanha no Brevo.',
            ]);

            if ($sent) {
                Log::info('Manager EmailCampaign sent', [
                    'id'         => $emailCampaign->id,
                    'tenant_id'  => auth()->user()->tenant_id,
                    'recipients' => count($contacts),
                    'imported'   => $imported,
                ]);
                return back()->with('success', "Campanha enfileirada para {$imported} destinatário(s). O Brevo processa e entrega em alguns minutos — atualize as métricas em instantes.");
            }

            return back()->with('error', 'A campanha foi criada no Brevo mas não foi possível disparar. Tente novamente.');

        } catch (\Throwable $e) {
            Log::error('Manager EmailCampaign send error', [
                'id'        => $emailCampaign->id,
                'tenant_id' => auth()->user()->tenant_id,
                'error'     => $e->getMessage(),
            ]);
            $emailCampaign->update(['status' => 'error', 'error_message' => $e->getMessage()]);
            return back()->with('error', 'Erro inesperado: ' . $e->getMessage());
        }
    }

    public function refreshStats(EmailCampaign $emailCampaign)
    {
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
