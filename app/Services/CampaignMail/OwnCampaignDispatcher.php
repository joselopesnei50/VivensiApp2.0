<?php

namespace App\Services\CampaignMail;

use App\Jobs\SendOwnCampaignBatchJob;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\EmailSuppression;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Coloca uma campanha de envio próprio na fila e mantém as métricas.
 *
 * Idempotente: a tabela de destinatários tem UNIQUE(campanha, email), então
 * reenviar uma campanha com erro só reprocessa quem ainda não recebeu.
 */
class OwnCampaignDispatcher
{
    /**
     * @param array<int, array{email: string, name?: string}> $contacts
     * @return int destinatários na fila
     */
    public function start(EmailCampaign $campaign, array $contacts): int
    {
        $suppressed = EmailSuppression::lookup(array_column($contacts, 'email'));
        $now        = now();

        $rows = [];
        foreach ($contacts as $c) {
            $email = mb_strtolower(trim((string) ($c['email'] ?? '')));
            if ($email === '' || isset($suppressed[$email]) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $rows[$email] = [
                'email_campaign_id' => $campaign->id,
                'email'             => $email,
                'name'              => isset($c['name']) ? mb_substr((string) $c['name'], 0, 190) : null,
                'token'             => Str::random(40),
                'status'            => EmailCampaignRecipient::STATUS_QUEUED,
                'created_at'        => $now,
                'updated_at'        => $now,
            ];
        }

        DB::transaction(function () use ($campaign, $rows) {
            foreach (array_chunk(array_values($rows), 500) as $chunk) {
                DB::table('email_campaign_recipients')->insertOrIgnore($chunk);
            }

            // Retentativa: quem falhou volta pra fila. Quem ficou travado em
            // 'sending' (worker morreu no meio do lote) NÃO volta — pode ter
            // saído; melhor perder 1 envio do que mandar 2x numa prospecção.
            EmailCampaignRecipient::where('email_campaign_id', $campaign->id)
                ->where('status', EmailCampaignRecipient::STATUS_FAILED)
                ->update(['status' => EmailCampaignRecipient::STATUS_QUEUED, 'error' => null]);
            EmailCampaignRecipient::where('email_campaign_id', $campaign->id)
                ->where('status', EmailCampaignRecipient::STATUS_SENDING)
                ->update(['status' => EmailCampaignRecipient::STATUS_FAILED, 'error' => 'Envio interrompido (worker reiniciado). Não reenviado para evitar duplicidade.']);
        });

        $queued = EmailCampaignRecipient::where('email_campaign_id', $campaign->id)
            ->where('status', EmailCampaignRecipient::STATUS_QUEUED)
            ->count();

        $campaign->update([
            'status'          => 'sending',
            'error_message'   => null,
            'recipient_count' => EmailCampaignRecipient::where('email_campaign_id', $campaign->id)->count(),
        ]);
        $this->refreshStats($campaign);

        if ($queued > 0) {
            SendOwnCampaignBatchJob::dispatch($campaign->id)->onQueue('emails');
        }

        return $queued;
    }

    /** Recalcula stat_* a partir da tabela de destinatários. */
    public function refreshStats(EmailCampaign $campaign): void
    {
        $base = fn () => EmailCampaignRecipient::where('email_campaign_id', $campaign->id);

        $sentLike = [
            EmailCampaignRecipient::STATUS_SENT,
            EmailCampaignRecipient::STATUS_BOUNCED,
            EmailCampaignRecipient::STATUS_COMPLAINED,
        ];

        $sent        = $base()->whereIn('status', $sentLike)->count();
        $bounces     = $base()->whereNotNull('bounced_at')->count();
        $complaints  = $base()->whereNotNull('complained_at')->count();

        $campaign->forceFill([
            'stat_sent'         => $sent,
            'stat_failed'       => $base()->where('status', EmailCampaignRecipient::STATUS_FAILED)->count(),
            // Sem webhook de entrega configurado, "entregue" = enviado menos bounces.
            'stat_delivered'    => max(0, $sent - $bounces),
            'stat_opens'        => $base()->whereNotNull('opened_at')->count(),
            'stat_clicks'       => $base()->whereNotNull('clicked_at')->count(),
            'stat_bounces'      => $bounces,
            'stat_spam'         => $complaints,
            'stat_unsubscribes' => $base()->whereNotNull('unsubscribed_at')->count(),
            'stats_fetched_at'  => now(),
        ])->save();
    }
}
