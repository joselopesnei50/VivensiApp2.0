<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Models\User;
use App\Services\BrevoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmailCampaignController extends Controller
{
    public function index()
    {
        $campaigns = EmailCampaign::with('creator')->latest()->paginate(20);
        return view('admin.email_campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        return view('admin.email_campaigns.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'subject'        => ['required', 'string', 'max:255'],
            'html_content'   => ['required', 'string'],
            'sender_name'    => ['nullable', 'string', 'max:100'],
            'sender_email'   => ['nullable', 'email', 'max:150'],
            'reply_to_email' => ['nullable', 'email', 'max:150'],
            'audience_type'  => ['required', 'in:tenant_admins,all_users,leads,all,manual,none'],
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

        return redirect()->route('admin.email_campaigns.index')
            ->with('success', "Campanha \"{$campaign->name}\" criada como rascunho.");
    }

    public function show(EmailCampaign $emailCampaign)
    {
        // Auto-busca métricas se enviada e ainda não foram carregadas (ou faz +30min)
        if ($emailCampaign->status === 'sent' && $emailCampaign->brevo_campaign_id) {
            $stale = $emailCampaign->stats_fetched_at === null
                || $emailCampaign->stats_fetched_at->lt(now()->subMinutes(30));

            if ($stale) {
                $stats = app(BrevoService::class)->getBrevoEmailCampaignStats($emailCampaign->brevo_campaign_id);
                if (!empty($stats)) {
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
                }
            }
        }

        return view('admin.email_campaigns.show', ['campaign' => $emailCampaign]);
    }

    public function destroy(EmailCampaign $emailCampaign)
    {
        if ($emailCampaign->status === 'sent') {
            return back()->with('error', 'Não é possível excluir uma campanha já enviada.');
        }
        $emailCampaign->delete();
        return back()->with('success', 'Campanha excluída.');
    }

    /**
     * Dispara a campanha imediatamente — pipeline completo:
     * 1. Coleta destinatários conforme audience_type
     * 2. Cria lista no Brevo
     * 3. Importa contatos
     * 4. Cria campanha no Brevo
     * 5. Envia agora
     */
    public function send(EmailCampaign $emailCampaign)
    {
        if ($emailCampaign->status === 'sent') {
            return back()->with('error', 'Esta campanha já foi enviada.');
        }

        $emailCampaign->update(['status' => 'sending']);
        $brevo = app(BrevoService::class);

        try {
            // 1. Coleta destinatários
            $contacts = $this->resolveRecipients($emailCampaign->audience_type, $emailCampaign->manual_emails);

            if (empty($contacts)) {
                $emailCampaign->update(['status' => 'error', 'error_message' => 'Nenhum destinatário encontrado para o público selecionado.']);
                return back()->with('error', 'Nenhum destinatário encontrado.');
            }

            // 2. Cria lista no Brevo
            $listName = 'Vivensi — ' . $emailCampaign->name . ' — ' . now()->format('d/m/Y H:i');
            $listId   = $brevo->createContactList($listName);

            if (!$listId) {
                $emailCampaign->update(['status' => 'error', 'error_message' => 'Falha ao criar lista de contatos no Brevo.']);
                return back()->with('error', 'Falha ao criar lista no Brevo. Verifique a chave API nas configurações.');
            }

            // 3. Importa contatos
            $brevo->importContacts($listId, $contacts);

            // 4. Cria campanha no Brevo
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
                // Busca o erro real do log para exibir ao usuário
                $errorDetail = 'Verifique se o remetente está verificado no Brevo e se a chave API tem permissão para campanhas.';
                $emailCampaign->update([
                    'status'        => 'error',
                    'brevo_list_id' => $listId,
                    'error_message' => 'Falha ao criar campanha no Brevo. ' . $errorDetail,
                ]);
                return back()->with('error', 'Falha ao criar campanha no Brevo. ' . $errorDetail);
            }

            // 5. Dispara
            $sent = $brevo->sendBrevoEmailCampaign($campaignId);

            $emailCampaign->update([
                'status'           => $sent ? 'sent' : 'error',
                'brevo_list_id'    => $listId,
                'brevo_campaign_id'=> $campaignId,
                'recipient_count'  => count($contacts),
                'sent_at'          => $sent ? now() : null,
                'error_message'    => $sent ? null : 'Falha ao disparar a campanha no Brevo.',
            ]);

            if ($sent) {
                Log::info('EmailCampaign sent', ['id' => $emailCampaign->id, 'recipients' => count($contacts)]);
                return back()->with('success', "Campanha disparada para " . count($contacts) . " destinatários!");
            }

            return back()->with('error', 'A campanha foi criada no Brevo mas não foi possível disparar. Tente novamente.');

        } catch (\Throwable $e) {
            Log::error('EmailCampaign send error', ['id' => $emailCampaign->id, 'error' => $e->getMessage()]);
            $emailCampaign->update(['status' => 'error', 'error_message' => $e->getMessage()]);
            return back()->with('error', 'Erro inesperado: ' . $e->getMessage());
        }
    }

    /**
     * Busca métricas atualizadas da campanha no Brevo.
     */
    public function refreshStats(EmailCampaign $emailCampaign)
    {
        if (!$emailCampaign->brevo_campaign_id) {
            return back()->with('error', 'Campanha ainda não enviada pelo Brevo.');
        }

        $stats = app(BrevoService::class)->getBrevoEmailCampaignStats($emailCampaign->brevo_campaign_id);

        if (empty($stats)) {
            return back()->with('error', 'Métricas ainda não disponíveis. O Brevo pode levar alguns minutos para processar após o envio. Tente novamente em instantes.');
        }

        $emailCampaign->update([
            'stat_delivered'   => $stats['delivered'],
            'stat_opens'       => $stats['opens'],
            'stat_clicks'      => $stats['clicks'],
            'stat_bounces'     => $stats['bounces'],
            'stat_unsubscribes'=> $stats['unsubscribes'],
            'stat_spam'        => $stats['spam'],
            'stats_fetched_at' => now(),
        ]);

        return back()->with('success', 'Métricas atualizadas com sucesso!');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolveRecipients(string $type, ?string $manualEmailsJson = null): array
    {
        $contacts = collect();

        // Público baseado em tipo
        if (in_array($type, ['tenant_admins', 'all_users', 'all', 'manual'])) {
            if ($type !== 'manual') {
                $query = User::where('status', 'active')->whereNotNull('email');

                if ($type === 'tenant_admins') {
                    $query->whereIn('id', function ($q) {
                        $q->selectRaw('MIN(id)')->from('users')
                          ->whereNotNull('tenant_id')->groupBy('tenant_id');
                    });
                }

                $contacts = $contacts->merge(
                    $query->get(['email', 'name'])->map(fn($u) => ['email' => $u->email, 'name' => $u->name])
                );
            }
        }

        if (in_array($type, ['leads', 'all'])) {
            $leads = DB::table('landing_page_leads')
                ->whereNotNull('email')->where('email', '!=', '')
                ->get(['email', 'name']);

            $contacts = $contacts->merge(
                $leads->map(fn($l) => ['email' => $l->email, 'name' => $l->name ?? 'Visitante'])
            );
        }

        // E-mails manuais (avulsos) — sempre incluídos se informados
        if ($manualEmailsJson) {
            $manual = json_decode($manualEmailsJson, true) ?? [];
            $contacts = $contacts->merge(collect($manual));
        }

        return $contacts->unique('email')->filter(fn($c) => filter_var($c['email'], FILTER_VALIDATE_EMAIL))->values()->toArray();
    }

    private function parseManualEmailsInput(string $raw): array
    {
        $lines    = preg_split('/[\n,;]+/', $raw);
        $contacts = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;

            // Formato "Nome <email@dominio.com>"
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
