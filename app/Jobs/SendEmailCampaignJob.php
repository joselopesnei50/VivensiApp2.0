<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use App\Models\Tenant;
use App\Services\BrevoService;
use App\Services\EmailQuotaService;
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
    public int $timeout = 900; // 15min pra caber campanhas com milhares de contatos

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

    public function handle(BrevoService $brevo, EmailQuotaService $quota): void
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

            // 2. Importa contatos (loop HTTP lento)
            $imported = $brevo->importContacts($listId, $this->contacts);

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

            // 3. Cria campanha no Brevo
            $brevoCampaignId = $brevo->createBrevoEmailCampaign([
                'name'           => $campaign->name,
                'subject'        => $campaign->subject,
                'html_content'   => $campaign->html_content,
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
}
