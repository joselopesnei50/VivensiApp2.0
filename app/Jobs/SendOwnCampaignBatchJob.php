<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\EmailSuppression;
use App\Services\CampaignMail\CampaignRenderer;
use App\Services\CampaignMail\OwnCampaignDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Envia UM lote de uma campanha própria e se re-agenda até esvaziar a fila.
 *
 *   lote = send_rate_per_minute (ou config) destinatários → envia → espera 60s → próximo lote
 *
 * Seguro contra duplicidade: cada lote "reivindica" linhas queued → sending
 * com um claim_token; um job duplicado não encontra nada pra enviar.
 * Freios: campanha pausada/cancelada para na hora; teto diário (aquecimento);
 * pausa automática se bounce/reclamação passarem do limite.
 */
class SendOwnCampaignBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;      // o encadeamento é a retentativa; o comando emails:unstuck-campaigns retoma se a corrente quebrar
    public int $timeout = 600;

    public function __construct(public int $campaignId)
    {
    }

    public function handle(CampaignRenderer $renderer, OwnCampaignDispatcher $dispatcher): void
    {
        $campaign = EmailCampaign::withoutGlobalScopes()->find($this->campaignId);
        if (!$campaign || $campaign->status !== 'sending' || !$campaign->usesOwnDelivery()) {
            return;
        }

        $batchSize = max(1, (int) ($campaign->send_rate_per_minute ?: config('campaign_mail.rate_per_minute', 60)));

        // Teto diário + reivindicação do lote sob um lock global: com vários
        // workers (ou várias campanhas) ao mesmo tempo, cada job enxerga o que
        // os outros já reservaram e o teto não é ultrapassado.
        $claim    = Str::random(40);
        $deferred = false;
        try {
            Cache::lock('own-campaign:claim', 30)->block(15, function () use ($campaign, &$batchSize, $claim, &$deferred) {
                $dailyLimit = (int) config('campaign_mail.daily_limit', 0);
                if ($dailyLimit > 0) {
                    // Conta o que saiu nas últimas 24h + o que já está reservado em outros lotes
                    $used = EmailCampaignRecipient::where(function ($q) {
                        $q->where('sent_at', '>=', now()->subDay())
                          ->orWhere('status', EmailCampaignRecipient::STATUS_SENDING);
                    })->count();
                    $room = $dailyLimit - $used;
                    if ($room <= 0) {
                        $deferred = true;
                        return;
                    }
                    $batchSize = min($batchSize, $room);
                }

                $ids = EmailCampaignRecipient::where('email_campaign_id', $campaign->id)
                    ->where('status', EmailCampaignRecipient::STATUS_QUEUED)
                    ->orderBy('id')
                    ->limit($batchSize)
                    ->pluck('id');

                if ($ids->isNotEmpty()) {
                    EmailCampaignRecipient::whereIn('id', $ids)
                        ->where('status', EmailCampaignRecipient::STATUS_QUEUED)
                        ->update(['status' => EmailCampaignRecipient::STATUS_SENDING, 'claim_token' => $claim]);
                }
            });
        } catch (LockTimeoutException) {
            // Outro worker segurou o lock por muito tempo: tenta de novo em 1 min
            $campaign->touch();
            self::dispatch($campaign->id)->onQueue('emails')->delay(now()->addMinute());
            return;
        }

        if ($deferred) {
            Log::info('OwnCampaign: teto diário atingido, adiando 30min', ['campaign_id' => $campaign->id]);
            $campaign->touch(); // mantém o watchdog de travamento quieto
            self::dispatch($campaign->id)->onQueue('emails')->delay(now()->addMinutes(30));
            return;
        }

        $batch      = EmailCampaignRecipient::where('claim_token', $claim)->where('status', EmailCampaignRecipient::STATUS_SENDING)->get();
        $suppressed = EmailSuppression::lookup($batch->pluck('email')->all());
        $mailer     = config('campaign_mail.mailer', 'campaign_ses');
        $perSecond  = max(1, (int) config('campaign_mail.max_per_second', 10));
        $fromEmail  = $campaign->sender_email ?: config('campaign_mail.from.address');
        $fromName   = $campaign->sender_name ?: config('campaign_mail.from.name');

        foreach ($batch as $recipient) {
            // Campanha pausada no meio do lote → devolve o resto pra fila
            if (EmailCampaign::withoutGlobalScopes()->whereKey($campaign->id)->value('status') !== 'sending') {
                EmailCampaignRecipient::where('claim_token', $claim)
                    ->where('status', EmailCampaignRecipient::STATUS_SENDING)
                    ->update(['status' => EmailCampaignRecipient::STATUS_QUEUED, 'claim_token' => null]);
                break;
            }

            if (isset($suppressed[$recipient->email])) {
                $recipient->update(['status' => EmailCampaignRecipient::STATUS_SKIPPED, 'error' => 'Endereço bloqueado (bounce/reclamação anterior).']);
                continue;
            }

            $started = microtime(true);
            try {
                $parts = $renderer->render($campaign, $recipient);

                $sent = Mail::mailer($mailer)->send([
                    'html' => new HtmlString($parts['html']),
                    'text' => new HtmlString($parts['text']),
                ], [], function (Message $m) use ($campaign, $recipient, $parts, $fromEmail, $fromName) {
                    $m->to($recipient->email, $recipient->name ?: null)
                      ->from($fromEmail, $fromName)
                      ->subject($campaign->subject);
                    $replyTo = $campaign->reply_to_email ?: config('campaign_mail.reply_to');
                    if ($replyTo) {
                        $m->replyTo($replyTo);
                    }
                    $headers = $m->getHeaders();
                    $headers->addTextHeader('X-Vivensi-Campaign', (string) $campaign->id);
                    $headers->addTextHeader('X-Vivensi-Recipient', $recipient->token);
                    if ($parts['one_click_url']) {
                        // Gmail/Yahoo exigem descadastro em 1 clique (RFC 8058) para remetentes em volume
                        $headers->addTextHeader('List-Unsubscribe', '<' . $parts['one_click_url'] . '>');
                        $headers->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
                    }
                });

                $recipient->update([
                    'status'              => EmailCampaignRecipient::STATUS_SENT,
                    'sent_at'             => now(),
                    'provider_message_id' => $sent ? $this->messageId($sent) : null,
                    'error'               => null,
                ]);
            } catch (\Throwable $e) {
                $recipient->update([
                    'status' => EmailCampaignRecipient::STATUS_FAILED,
                    'error'  => mb_substr($e->getMessage(), 0, 500),
                ]);
                Log::warning('OwnCampaign: falha ao enviar', ['campaign_id' => $campaign->id, 'recipient_id' => $recipient->id, 'error' => $e->getMessage()]);

                // Erro de conta/credencial (ex.: SES em sandbox, identidade não verificada):
                // não adianta continuar queimando a fila.
                if ($this->isFatal($e)) {
                    $this->pause($campaign, 'Envio pausado: o provedor recusou o envio — ' . mb_substr($e->getMessage(), 0, 300));
                    EmailCampaignRecipient::where('claim_token', $claim)
                        ->where('status', EmailCampaignRecipient::STATUS_SENDING)
                        ->update(['status' => EmailCampaignRecipient::STATUS_QUEUED, 'claim_token' => null]);
                    $dispatcher->refreshStats($campaign);
                    return;
                }
            }

            // Respeita o teto por segundo do provedor
            $elapsed = microtime(true) - $started;
            $minGap  = 1 / $perSecond;
            if ($elapsed < $minGap) {
                usleep((int) (($minGap - $elapsed) * 1_000_000));
            }
        }

        $dispatcher->refreshStats($campaign);
        $campaign->refresh();

        if ($campaign->status !== 'sending') {
            return; // pausada durante o lote
        }

        if ($reason = $this->safetyBrake($campaign)) {
            $this->pause($campaign, $reason);
            return;
        }

        $remaining = EmailCampaignRecipient::where('email_campaign_id', $campaign->id)
            ->where('status', EmailCampaignRecipient::STATUS_QUEUED)
            ->exists();

        if ($remaining) {
            self::dispatch($campaign->id)->onQueue('emails')->delay(now()->addMinute());
            return;
        }

        $failedAll = $campaign->stat_sent === 0 && $campaign->stat_failed > 0;
        $campaign->update([
            'status'        => $failedAll ? 'error' : 'sent',
            'sent_at'       => $failedAll ? null : ($campaign->sent_at ?? now()),
            'error_message' => $failedAll
                ? 'Nenhum e-mail saiu. Veja o erro dos destinatários e a configuração do provedor.'
                : ($campaign->stat_failed ? "{$campaign->stat_failed} envio(s) falharam — use 'Tentar novamente' para reprocessar só esses." : null),
        ]);

        Log::info('OwnCampaign: concluída', ['campaign_id' => $campaign->id, 'sent' => $campaign->stat_sent, 'failed' => $campaign->stat_failed]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendOwnCampaignBatchJob falhou', ['campaign_id' => $this->campaignId, 'error' => $e->getMessage()]);
        // Não marca a campanha como erro: o watchdog (emails:unstuck-campaigns) retoma a fila.
    }

    /**
     * O SES troca o Message-ID; o id que chega nos eventos SNS (mail.messageId)
     * é o que o transporte grava no header X-SES-Message-ID.
     */
    private function messageId($sent): ?string
    {
        try {
            $headers = $sent->getOriginalMessage()->getHeaders();
            if ($headers->has('X-SES-Message-ID')) {
                return trim($headers->get('X-SES-Message-ID')->getBodyAsString());
            }
            $id = $sent->getMessageId();
            return $id ? trim($id, '<>') : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function isFatal(\Throwable $e): bool
    {
        $msg = mb_strtolower($e->getMessage());
        foreach (['not verified', 'messagerejected', 'accountsendingpaused', 'sending paused', 'invalidclienttokenid',
                  'signaturedoesnotmatch', 'accessdenied', 'not authorized', 'authentication', 'daily message quota',
                  'credentials', 'configuration set does not exist', 'configurationsetdoesnotexist',
                  'mailer [', 'connection could not be established'] as $needle) {
            if (str_contains($msg, $needle)) return true;
        }
        return false;
    }

    private function safetyBrake(EmailCampaign $campaign): ?string
    {
        $min  = (int) config('campaign_mail.safety.min_sample', 100);
        $sent = (int) $campaign->stat_sent;
        if ($sent < $min) return null;

        $bounce    = $campaign->stat_bounces / $sent * 100;
        $complaint = $campaign->stat_spam / $sent * 100;

        if ($bounce > (float) config('campaign_mail.safety.max_bounce_rate', 5)) {
            return sprintf('Pausa automática: bounce em %.1f%% (limite %.1f%%). Limpe a lista antes de continuar — bounce alto pode suspender a conta no SES.', $bounce, config('campaign_mail.safety.max_bounce_rate'));
        }
        if ($complaint > (float) config('campaign_mail.safety.max_complaint_rate', 0.3)) {
            return sprintf('Pausa automática: reclamações de spam em %.2f%% (limite %.2f%%). Revise público e conteúdo.', $complaint, config('campaign_mail.safety.max_complaint_rate'));
        }
        return null;
    }

    private function pause(EmailCampaign $campaign, string $reason): void
    {
        $campaign->update(['status' => 'paused', 'error_message' => $reason]);
        Log::warning('OwnCampaign: pausada', ['campaign_id' => $campaign->id, 'reason' => $reason]);
    }
}
