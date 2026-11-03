<?php

namespace App\Services\CampaignMail;

use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Services\EmailUnsubscribeTokenService;

/**
 * Monta o HTML final de UM destinatário de campanha própria:
 *   1. Personaliza {nome}, {primeiro_nome}, {email} (e {name}, legado)
 *   2. Link de descadastro real (LGPD art. 18) — {unsubscribe_url} ou rodapé automático
 *   3. Reescreve links para rastrear cliques (URL assinada, sem open redirect)
 *   4. Pixel de abertura
 */
class CampaignRenderer
{
    public function __construct(private EmailUnsubscribeTokenService $unsub)
    {
    }

    /**
     * @return array{html: string, unsubscribe_url: ?string, one_click_url: ?string}
     */
    public function render(EmailCampaign $campaign, EmailCampaignRecipient $recipient): array
    {
        $html = (string) $campaign->html_content;

        $name      = trim((string) $recipient->name);
        $firstName = $name !== '' ? explode(' ', $name)[0] : '';
        $vars = [
            '{nome}'          => e($name !== '' ? $name : 'Olá'),
            '{name}'          => e($name !== '' ? $name : 'Olá'),
            '{primeiro_nome}' => e($firstName),
            '{email}'         => e($recipient->email),
        ];
        $html = strtr($html, $vars);

        $unsubUrl = null;
        $oneClick = null;
        if ($campaign->tenant_id) {
            $token    = $this->unsub->generate($recipient->email, (int) $campaign->tenant_id);
            $unsubUrl = route('public.email.unsubscribe', ['token' => $token, 'r' => $recipient->token]);
            $oneClick = route('public.email.unsubscribe.one_click', ['token' => $token, 'r' => $recipient->token]);
            $html     = $this->injectUnsubscribe($html, $unsubUrl);
        }

        if (config('campaign_mail.tracking.clicks')) {
            $html = $this->rewriteLinks($html, $recipient->token, $unsubUrl);
        }

        if (config('campaign_mail.tracking.opens')) {
            $pixel = '<img src="' . e(route('campaign.track.open', ['token' => $recipient->token])) . '" width="1" height="1" alt="" style="display:block;width:1px;height:1px;border:0;" />';
            $html  = stripos($html, '</body>') !== false
                ? preg_replace('~</body>~i', $pixel . '</body>', $html, 1)
                : $html . $pixel;
        }

        return ['html' => $html, 'unsubscribe_url' => $unsubUrl, 'one_click_url' => $oneClick];
    }

    /** Assinatura curta (HMAC) do par token+URL — impede usar o rastreador como open redirect. */
    public static function sign(string $recipientToken, string $url): string
    {
        $key = (string) config('app.key');
        return substr(hash_hmac('sha256', $recipientToken . '|' . $url, $key), 0, 24);
    }

    public static function encodeUrl(string $url): string
    {
        return rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
    }

    public static function decodeUrl(string $encoded): ?string
    {
        $padded  = strtr($encoded, '-_', '+/');
        $padded .= str_repeat('=', (4 - strlen($padded) % 4) % 4);
        $decoded = base64_decode($padded, true);
        return $decoded === false ? null : $decoded;
    }

    private function injectUnsubscribe(string $html, string $unsubUrl): string
    {
        if (str_contains($html, '{unsubscribe_url}')) {
            return str_replace('{unsubscribe_url}', e($unsubUrl), $html);
        }

        $footer = '<div style="margin-top:32px;padding:20px 0;border-top:1px solid #e2e8f0;text-align:center;font-family:Arial,sans-serif;font-size:12px;color:#94a3b8;line-height:1.6;">'
                . 'Você recebeu este e-mail porque seu contato profissional consta em nossa base.<br>'
                . '<a href="' . e($unsubUrl) . '" style="color:#059669;text-decoration:underline;font-weight:600;">Não quero mais receber</a>'
                . '</div>';

        return stripos($html, '</body>') !== false
            ? preg_replace('~</body>~i', $footer . '</body>', $html, 1)
            : $html . $footer;
    }

    private function rewriteLinks(string $html, string $recipientToken, ?string $unsubUrl): string
    {
        return preg_replace_callback(
            '~(<a\b[^>]*?\bhref\s*=\s*)(["\'])(.*?)\2~is',
            function (array $m) use ($recipientToken, $unsubUrl) {
                $raw = html_entity_decode($m[3], ENT_QUOTES | ENT_HTML5);
                if (!preg_match('~^https?://~i', $raw)) {
                    return $m[0]; // mailto:, tel:, âncoras, merge tags
                }
                if ($unsubUrl && $raw === $unsubUrl) {
                    return $m[0]; // descadastro não passa pelo rastreador
                }
                $tracked = route('campaign.track.click', [
                    'token' => $recipientToken,
                    'u'     => self::encodeUrl($raw),
                    's'     => self::sign($recipientToken, $raw),
                ]);
                return $m[1] . $m[2] . e($tracked) . $m[2];
            },
            $html
        ) ?? $html;
    }
}
