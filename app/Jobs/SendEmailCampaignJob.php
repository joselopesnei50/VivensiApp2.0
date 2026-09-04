<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use App\Models\Tenant;
use App\Services\BrevoService;
use App\Services\EmailQuotaService;
use App\Services\EmailUnsubscribeTokenService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Dispara campanha de email via Brevo em background.
 *
 * Motivo: importContacts do BrevoService faz 1 chamada HTTP por contato
 * (proposital, evita race condition da API assincrona /contacts/batch).
 * Isso pode passar de dezenas de segundos em campanhas grandes e fazer
 * o nginx dar 504 no `send()` sincrono.
 *
 * Agora o controller so valida + enfileira. Job faz:
 *   1. Cria lista no Brevo
 *   2. Importa contatos (loop lento)
 *   3. Cria campanha no Brevo
 *   4. Dispara
 *   5. Atualiza EmailCampaign.status ('sent' | 'error')
 *
 * Sem retry automatico: se cair no meio, deixa em 'error' com
 * error_message pra usuario decidir reenviar. Retry cego poderia
 * disparar 2x (idempotencia fraca no Brevo).
 */
class SendEmailCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 1800; // 30min: bulk /contacts/import (~2min pra 20K) + polling + margem
                                // antigo 15min quebrava com 20K contatos porque o import era 1-a-1

    /**
     * @param int      $campaignId Id da EmailCampaign a disparar.
     * @param array    $contacts   Array de ['email', 'name'] ja resolvidos e validados no controller.
     * @param int|null $quotaTenantId Se veio, o job vai refundar cota em cada branch de erro
     *                                (Manager e Ngo consomem cota no controller antes do dispatch).
     *                                Admin passa null porque nao usa cota.
     */
    public function __construct(
        public int $campaignId,
        public array $contacts,
        public ?int $quotaTenantId = null,
    ) {
    }

    public function handle(BrevoService $brevo, EmailQuotaService $quota, EmailUnsubscribeTokenService $unsub): void
    {
        $campaign = EmailCampaign::withoutGlobalScopes()->find($this->campaignId);
        if (!$campaign) {
            Log::warning('SendEmailCampaignJob: campanha nao encontrada', ['id' => $this->campaignId]);
            $this->refundQuota($quota);
            return;
        }

        // Se ja saiu do estado sending (paralelo, cancelamento), aborta.
        if ($campaign->status !== 'sending') {
            Log::info('SendEmailCampaignJob: campanha nao esta mais em sending, abortando', [
                'id'     => $campaign->id,
                'status' => $campaign->status,
            ]);
            $this->refundQuota($quota);
            return;
        }

        try {
            // 1. Cria lista no Brevo
            $listName = 'Vivensi — ' . $campaign->name . ' — ' . now()->format('d/m/Y H:i');
            $listId   = $brevo->createContactList($listName);

            if (!$listId) {
                $this->refundQuota($quota);
                $campaign->update([
                    'status'        => 'error',
                    'error_message' => 'Falha ao criar lista de contatos no Brevo.',
                ]);
                return;
            }

            // Gera UNSUB_TOKEN per-contato (HMAC do email+tenant) pra virar merge tag
            // {{contact.UNSUB_TOKEN}} na landing de descadastro. Sem tenant_id nao
            // temos como descadastrar cruzando listas — pula token.
            $tenantIdForToken = $campaign->tenant_id ?? $this->quotaTenantId;
            $contactsWithToken = $tenantIdForToken
                ? array_map(function ($c) use ($unsub, $tenantIdForToken) {
                    $c['unsub_token'] = $unsub->generate($c['email'], $tenantIdForToken);
                    return $c;
                }, $this->contacts)
                : $this->contacts;

            // 2. Importa contatos (loop HTTP lento)
            $imported = $brevo->importContacts($listId, $contactsWithToken);

            if ($imported === 0) {
                $this->refundQuota($quota);
                $campaign->update([
                    'status'        => 'error',
                    'brevo_list_id' => $listId,
                    'error_message' => 'Nenhum contato foi adicionado a lista no Brevo. Verifique se os e-mails sao validos e nao estao bloqueados.',
                ]);
                return;
            }

            Log::info('SendEmailCampaignJob: contatos importados', [
                'campaign_id' => $campaign->id,
                'imported'    => $imported,
                'total'       => count($this->contacts),
            ]);

            // 3. Cria campanha no Brevo — HTML enriquecido com link de descadastro
            $htmlWithUnsub = $this->injectUnsubscribeLink($campaign->html_content, $tenantIdForToken);

            $brevoCampaignId = $brevo->createBrevoEmailCampaign([
                'name'           => $campaign->name,
                'subject'        => $campaign->subject,
                'html_content'   => $htmlWithUnsub,
                'sender_name'    => $campaign->sender_name,
                'sender_email'   => $campaign->sender_email,
                'reply_to_email' => $campaign->reply_to_email,
                'brevo_list_id'  => $listId,
            ]);

            if (!$brevoCampaignId) {
                $this->refundQuota($quota);
                $brevoMsg = $brevo->lastBrevoError ?? 'Erro desconhecido';
                $campaign->update([
                    'status'        => 'error',
                    'brevo_list_id' => $listId,
                    'error_message' => 'Falha ao criar campanha no Brevo. Brevo respondeu: ' . $brevoMsg,
                ]);
                return;
            }

            // 4. Dispara
            $sent = $brevo->sendBrevoEmailCampaign($brevoCampaignId);
            if (!$sent) {
                $this->refundQuota($quota);
            }

            $campaign->update([
                'status'            => $sent ? 'sent' : 'error',
                'brevo_list_id'     => $listId,
                'brevo_campaign_id' => $brevoCampaignId,
                'recipient_count'   => count($this->contacts),
                'sent_at'           => $sent ? now() : null,
                'error_message'     => $sent ? null : 'A campanha foi criada no Brevo mas nao foi possivel disparar.',
            ]);

            Log::info('SendEmailCampaignJob: concluido', [
                'campaign_id' => $campaign->id,
                'brevo_id'    => $brevoCampaignId,
                'recipients'  => count($this->contacts),
                'sent'        => $sent,
            ]);
        } catch (\Throwable $e) {
            $this->refundQuota($quota);
            Log::error('SendEmailCampaignJob: erro inesperado', [
                'campaign_id' => $campaign->id,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);
            $campaign->update([
                'status'        => 'error',
                'error_message' => 'Erro inesperado no job: ' . $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendEmailCampaignJob: falhou definitivamente', [
            'campaign_id' => $this->campaignId,
            'error'       => $exception->getMessage(),
        ]);
        try {
            $this->refundQuota(app(EmailQuotaService::class));
        } catch (\Throwable $e) {
            Log::warning('SendEmailCampaignJob::failed refund falhou: ' . $e->getMessage());
        }
        EmailCampaign::withoutGlobalScopes()
            ->where('id', $this->campaignId)
            ->where('status', 'sending')
            ->update([
                'status'        => 'error',
                'error_message' => 'Job falhou: ' . $exception->getMessage(),
            ]);
    }

    private function refundQuota(EmailQuotaService $quota): void
    {
        if (!$this->quotaTenantId) return;
        $tenant = Tenant::withoutGlobalScopes()->find($this->quotaTenantId);
        if (!$tenant) return;
        $quota->refund($tenant, count($this->contacts));
    }

    /**
     * Substitui {unsubscribe_url} pelo link real. Se ausente, adiciona footer
     * automatico antes do </body> — LGPD art. 18 exige canal explicito.
     * URL usa merge tag {{contact.UNSUB_TOKEN}} — Brevo substitui per-recipient.
     */
    private function injectUnsubscribeLink(string $html, ?int $tenantId): string
    {
        if (!$tenantId) return $html; // sem tenant, nao da pra descadastrar
        $unsubUrl = url('/email/descadastro/{{contact.UNSUB_TOKEN}}');

        if (str_contains($html, '{unsubscribe_url}')) {
            return str_replace('{unsubscribe_url}', $unsubUrl, $html);
        }

        $footer = '<div style="margin-top:32px;padding:20px 0;border-top:1px solid #e2e8f0;text-align:center;font-family:Arial,sans-serif;font-size:12px;color:#94a3b8;line-height:1.6;">'
                . 'Você recebeu este e-mail porque está cadastrado em nossa lista de contatos.<br>'
                . '<a href="' . $unsubUrl . '" style="color:#059669;text-decoration:underline;font-weight:600;">Cancelar inscrição</a>'
                . '</div>';

        if (stripos($html, '</body>') !== false) {
            return str_ireplace('</body>', $footer . '</body>', $html);
        }
        return $html . $footer;
    }
}
