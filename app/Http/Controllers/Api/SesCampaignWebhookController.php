<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\EmailSuppression;
use App\Services\CampaignMail\OwnCampaignDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * POST /api/webhooks/ses-campaigns — eventos do SES publicados num tópico SNS.
 *
 * Aceita tanto "event publishing" (Configuration Set → eventType) quanto
 * "notifications" da identidade (notificationType). Trata:
 *   Bounce Permanent → destinatário bounced + bloqueio global
 *   Complaint        → destinatário complained + bloqueio global
 *   Delivery         → delivered_at
 *
 * Segurança: assinatura SNS verificada com o certificado da AWS (host
 * sns.<região>.amazonaws.com), tópico opcionalmente fixado por env.
 */
class SesCampaignWebhookController extends Controller
{
    public function handle(Request $request, OwnCampaignDispatcher $dispatcher)
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload) || empty($payload['Type'])) {
            return response()->json(['error' => 'payload inválido'], 400);
        }

        $allowedTopic = config('campaign_mail.ses_sns_topic_arn');
        if ($allowedTopic && ($payload['TopicArn'] ?? null) !== $allowedTopic) {
            Log::warning('SES webhook: tópico não autorizado', ['topic' => $payload['TopicArn'] ?? null]);
            return response()->json(['error' => 'tópico não autorizado'], 403);
        }

        if (!$this->verifySignature($payload)) {
            Log::warning('SES webhook: assinatura SNS inválida');
            return response()->json(['error' => 'assinatura inválida'], 403);
        }

        if ($payload['Type'] === 'SubscriptionConfirmation') {
            $url = (string) ($payload['SubscribeURL'] ?? '');
            if ($this->isAwsSnsUrl($url)) {
                Http::timeout(10)->get($url);
                Log::info('SES webhook: inscrição SNS confirmada', ['topic' => $payload['TopicArn'] ?? null]);
            }
            return response()->json(['ok' => true]);
        }

        if ($payload['Type'] !== 'Notification') {
            return response()->json(['ok' => true]);
        }

        $event = json_decode((string) ($payload['Message'] ?? ''), true);
        if (!is_array($event)) {
            return response()->json(['ok' => true]);
        }

        $touched = $this->process($event);

        foreach ($touched as $campaignId) {
            if ($campaign = EmailCampaign::withoutGlobalScopes()->find($campaignId)) {
                $dispatcher->refreshStats($campaign);
            }
        }

        return response()->json(['ok' => true]);
    }

    /** @return int[] ids de campanhas afetadas */
    private function process(array $event): array
    {
        $type      = $event['eventType'] ?? $event['notificationType'] ?? null;
        $mail      = $event['mail'] ?? [];
        $messageId = $mail['messageId'] ?? null;
        $recipient = $messageId ? EmailCampaignRecipient::where('provider_message_id', $messageId)->first() : null;

        // Fallback: header X-Vivensi-Recipient (quando o SES inclui os headers)
        if (!$recipient && !empty($mail['headers']) && is_array($mail['headers'])) {
            foreach ($mail['headers'] as $h) {
                if (strcasecmp($h['name'] ?? '', 'X-Vivensi-Recipient') === 0) {
                    $recipient = EmailCampaignRecipient::where('token', $h['value'] ?? '')->first();
                    break;
                }
            }
        }

        $campaignId = $recipient?->email_campaign_id;

        switch ($type) {
            case 'Bounce':
                $bounce    = $event['bounce'] ?? [];
                $permanent = ($bounce['bounceType'] ?? '') === 'Permanent';
                foreach ($bounce['bouncedRecipients'] ?? [] as $r) {
                    $email = mb_strtolower((string) ($r['emailAddress'] ?? ''));
                    if ($permanent) {
                        EmailSuppression::suppress($email, EmailSuppression::REASON_HARD_BOUNCE, $r['diagnosticCode'] ?? ($bounce['bounceSubType'] ?? null), $campaignId);
                    }
                    if ($recipient && $permanent && $recipient->email === $email) {
                        $recipient->update([
                            'status'     => EmailCampaignRecipient::STATUS_BOUNCED,
                            'bounced_at' => now(),
                            'error'      => mb_substr((string) ($r['diagnosticCode'] ?? 'Hard bounce'), 0, 500),
                        ]);
                    }
                }
                break;

            case 'Complaint':
                foreach ($event['complaint']['complainedRecipients'] ?? [] as $r) {
                    $email = mb_strtolower((string) ($r['emailAddress'] ?? ''));
                    EmailSuppression::suppress($email, EmailSuppression::REASON_COMPLAINT, $event['complaint']['complaintFeedbackType'] ?? null, $campaignId);
                    if ($recipient && $recipient->email === $email) {
                        $recipient->update([
                            'status'        => EmailCampaignRecipient::STATUS_COMPLAINED,
                            'complained_at' => now(),
                        ]);
                    }
                }
                break;

            case 'Delivery':
                if ($recipient && !$recipient->delivered_at) {
                    $recipient->update(['delivered_at' => now()]);
                }
                break;
        }

        return $campaignId ? [$campaignId] : [];
    }

    private function verifySignature(array $p): bool
    {
        $certUrl = (string) ($p['SigningCertURL'] ?? '');
        if (!$this->isAwsSnsUrl($certUrl) || empty($p['Signature'])) {
            return false;
        }

        $keys = $p['Type'] === 'Notification'
            ? ['Message', 'MessageId', 'Subject', 'Timestamp', 'TopicArn', 'Type']
            : ['Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'];

        $stringToSign = '';
        foreach ($keys as $k) {
            if (!array_key_exists($k, $p)) continue; // Subject é opcional
            $stringToSign .= $k . "\n" . $p[$k] . "\n";
        }

        $pem = Cache::remember('sns_cert:' . sha1($certUrl), now()->addDay(), function () use ($certUrl) {
            $res = Http::timeout(10)->get($certUrl);
            return $res->successful() ? $res->body() : null;
        });
        if (!$pem) return false;

        $pubKey = openssl_pkey_get_public($pem);
        if (!$pubKey) return false;

        $algo = ($p['SignatureVersion'] ?? '1') === '2' ? OPENSSL_ALGO_SHA256 : OPENSSL_ALGO_SHA1;

        return openssl_verify($stringToSign, base64_decode((string) $p['Signature']), $pubKey, $algo) === 1;
    }

    private function isAwsSnsUrl(string $url): bool
    {
        $parts = parse_url($url);
        return ($parts['scheme'] ?? '') === 'https'
            && (bool) preg_match('~^sns\.[a-z0-9-]+\.amazonaws\.com(\.cn)?$~', $parts['host'] ?? '');
    }
}
