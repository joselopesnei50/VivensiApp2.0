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
     * @return array{html: string, text: string, unsubscribe_url: ?string, one_click_url: ?string}
     */
    public function render(EmailCampaign $campaign, EmailCampaignRecipient $recipient): array
    {
        $html = $this->personalize((string) $campaign->html_content, (string) $recipient->name, $recipient->email);

        $unsubUrl = null;
        $oneClick = null;
        if ($campaign->tenant_id) {
            $token    = $this->unsub->generate($recipient->email, (int) $campaign->tenant_id);
            $unsubUrl = route('public.email.unsubscribe', ['token' => $token, 'r' => $recipient->token]);
            $oneClick = route('public.email.unsubscribe.one_click', ['token' => $token, 'r' => $recipient->token]);
            $html     = $this->injectUnsubscribe($html, $unsubUrl);
        }

        $htmlForText = $html; // links reais (sem rastreio) e com rodapé de descadastro

        if (config('campaign_mail.tracking.clicks')) {
            $html = $this->rewriteLinks($html, $recipient->token, $unsubUrl);
        }

        if (config('campaign_mail.tracking.opens')) {
            $pixel = '<img src="' . e(route('campaign.track.open', ['token' => $recipient->token])) . '" width="1" height="1" alt="" style="display:block;width:1px;height:1px;border:0;" />';
            $html  = stripos($html, '</body>') !== false
                ? preg_replace('~</body>~i', $pixel . '</body>', $html, 1)
                : $html . $pixel;
        }

        // Versão em texto simples (multipart/alternative): melhora a entrega e
        // atende leitores de e-mail sem HTML. Gerada antes do pixel/rastreio.
        $text = self::htmlToText($htmlForText ?? $html);

        return ['html' => $html, 'text' => $text, 'unsubscribe_url' => $unsubUrl, 'one_click_url' => $oneClick];
    }

    /**
     * Personaliza {nome}, {primeiro_nome}, {email} (e {name}, legado).
     *
     * Contato sem nome (ou com o próprio e-mail como nome): a variável some
     * junto com a vírgula vizinha — "Olá, {primeiro_nome}!" vira "Olá!".
     * Também aceita um texto padrão: {primeiro_nome|amigo} → "amigo".
     */
    public function personalize(string $html, ?string $rawName, string $email): string
    {
        $name = trim((string) $rawName);
        if ($name === '' || str_contains($name, '@')) {
            $name = '';
        }
        $firstName = $name !== '' ? preg_split('/\s+/', $name)[0] : '';

        $values = ['nome' => $name, 'name' => $name, 'primeiro_nome' => $firstName];

        // {var|padrão}
        $html = preg_replace_callback('/\{(nome|name|primeiro_nome)\|([^{}]*)\}/u', function ($m) use ($values) {
            return e($values[$m[1]] !== '' ? $values[$m[1]] : $m[2]);
        }, $html);

        foreach ($values as $key => $value) {
            if ($value === '') {
                // "Olá, {primeiro_nome}!" → "Olá!" ; "{primeiro_nome}, veja" → "Veja" não é tentado: só remove
                $html = preg_replace('/[ \t]*,[ \t]*\{' . $key . '\}/u', '', $html);
                // "Olá {primeiro_nome}!" → "Olá!" (espaço antes de pontuação)
                $html = preg_replace('/[ \t]+\{' . $key . '\}(?=[ \t]*[!?.;:])/u', '', $html);
                $html = preg_replace('/\{' . $key . '\}[ \t]*,[ \t]*/u', '', $html);
                $html = str_replace('{' . $key . '}', '', $html);
            } else {
                $html = str_replace('{' . $key . '}', e($value), $html);
            }
        }

        return str_replace('{email}', e($email), $html);
    }

    /** Converte o HTML do e-mail em texto simples legível. */
    public static function htmlToText(string $html): string
    {
        $t = preg_replace('~<(head|style|script|title)\b[^>]*>.*?</\1>~is', '', $html);
        // Elementos ocultos (pré-cabeçalho, pixels)
        $t = preg_replace('~<(div|span|p)\b[^>]*display\s*:\s*none[^>]*>.*?</\1>~is', '', $t);
        $t = preg_replace('~<img\b[^>]*>~i', '', $t);
        // Links: "texto (url)"
        $t = preg_replace_callback('~<a\b[^>]*href\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)</a>~is', function ($m) {
            $label = trim(html_entity_decode(strip_tags($m[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $url   = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (!preg_match('~^https?://~i', $url) || $label === '' || $label === $url) {
                return $label !== '' ? $label : $url;
            }
            return $label . ' (' . $url . ')';
        }, $t);
        $t = preg_replace('~<br\s*/?>~i', "\n", $t);
        $t = preg_replace('~</(p|div|tr|h[1-6]|li|table)>~i', "\n\n", $t);
        $t = preg_replace('~<li\b[^>]*>~i', '- ', $t);
        $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = str_replace("\u{00A0}", ' ', $t);
        $t = preg_replace('/[ \t]+/u', ' ', $t);
        $t = preg_replace('/ *\n */', "\n", $t);
        $t = preg_replace('/\n{3,}/', "\n\n", $t);
        return trim($t);
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
