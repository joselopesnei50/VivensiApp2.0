<?php

namespace App\Support;

/**
 * Classifica uma visita pelo User-Agent e pelo Referer:
 * robô ou pessoa, origem do tráfego e tipo de dispositivo.
 * Sem dependências externas — roda no registro da visita e na migração
 * que reclassifica o histórico.
 */
class TrafficClassifier
{
    public const SOURCES = [
        'google'    => 'Google',
        'search'    => 'Outros buscadores',
        'instagram' => 'Instagram',
        'facebook'  => 'Facebook',
        'linkedin'  => 'LinkedIn',
        'whatsapp'  => 'WhatsApp',
        'x'         => 'X (Twitter)',
        'youtube'   => 'YouTube',
        'internal'  => 'O próprio site',
        'email'     => 'E-mail',
        'ai'        => 'Assistentes de IA',
        'direct'    => 'Direto / sem origem',
        'other'     => 'Outros sites',
    ];

    public const DEVICES = [
        'mobile'  => 'Celular',
        'tablet'  => 'Tablet',
        'desktop' => 'Computador',
    ];

    private const BOT_PATTERN = '~(?<!cu)bot\b|bot/|crawl|spider|slurp|scrap|fetcher|preview|facebookexternalhit|facebot|meta-externalagent|whatsapp|telegrambot|twitterbot|linkedinbot|slackbot|skypeuripreview|googlebot|google-inspectiontool|googleother|adsbot|mediapartners|bingbot|bingpreview|yandex|baidu|duckduckbot|ahrefs|semrush|mj12|dotbot|petalbot|bytespider|gptbot|chatgpt-user|oai-searchbot|claudebot|claude-web|anthropic|ccbot|perplexity|applebot|amazonbot|headlesschrome|phantomjs|lighthouse|pagespeed|gtmetrix|pingdom|uptime|monitor|statuscake|python-requests|python-urllib|aiohttp|httpx|curl/|wget|go-http-client|okhttp|java/|libwww|axios|node-fetch|guzzle|postman|insomnia~i';

    public static function isBot(?string $userAgent): bool
    {
        $ua = trim((string) $userAgent);
        if ($ua === '' || mb_strlen($ua) < 15) {
            return true; // navegador de verdade sempre manda um User-Agent completo
        }
        return (bool) preg_match(self::BOT_PATTERN, $ua);
    }

    public static function device(?string $userAgent): string
    {
        $ua = (string) $userAgent;
        if (preg_match('~ipad|tablet|kindle|silk|playbook|(android(?!.*mobile))~i', $ua)) {
            return 'tablet';
        }
        if (preg_match('~mobi|iphone|ipod|android|windows phone|blackberry|opera mini~i', $ua)) {
            return 'mobile';
        }
        return 'desktop';
    }

    public static function source(?string $referer, ?string $ownHost = null): string
    {
        $referer = trim((string) $referer);
        if ($referer === '') {
            return 'direct';
        }

        $host = mb_strtolower((string) parse_url($referer, PHP_URL_HOST));
        if ($host === '') {
            return preg_match('~^android-app://com\.(google\.android\.gm|microsoft\.office\.outlook)~i', $referer) ? 'email' : 'other';
        }
        $host = preg_replace('~^(www\.|m\.|l\.|lm\.|mobile\.)~', '', $host);

        $ownHost = $ownHost ?? mb_strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $ownHost = preg_replace('~^www\.~', '', $ownHost);
        if ($ownHost && ($host === $ownHost || str_ends_with($host, '.' . $ownHost))) {
            return 'internal';
        }

        $rules = [
            'google'    => '~(^|\.)google\.[a-z.]+$|^googleusercontent\.com$|^news\.google~',
            'search'    => '~(^|\.)(bing\.com|duckduckgo\.com|yahoo\.com|yandex\.[a-z]+|ecosia\.org|brave\.com|baidu\.com)$~',
            'instagram' => '~(^|\.)instagram\.com$~',
            'facebook'  => '~(^|\.)(facebook\.com|fb\.com|fb\.me|messenger\.com)$~',
            'linkedin'  => '~(^|\.)(linkedin\.com|lnkd\.in)$~',
            'whatsapp'  => '~(^|\.)(whatsapp\.com|wa\.me)$~',
            'x'         => '~(^|\.)(t\.co|twitter\.com|x\.com)$~',
            'youtube'   => '~(^|\.)(youtube\.com|youtu\.be)$~',
            'email'     => '~(^|\.)(mail\.google\.com|outlook\.live\.com|outlook\.office\.com|mail\.yahoo\.com|webmail\.|roundcube)~',
            'ai'        => '~(^|\.)(chatgpt\.com|openai\.com|perplexity\.ai|claude\.ai|gemini\.google\.com|copilot\.microsoft\.com)$~',
        ];

        // e-mail antes do Google (mail.google.com)
        if (preg_match($rules['email'], $host)) return 'email';
        if (preg_match($rules['ai'], $host)) return 'ai';
        foreach ($rules as $key => $re) {
            if (preg_match($re, $host)) {
                return $key;
            }
        }
        return 'other';
    }

    public static function sourceLabel(?string $key): string
    {
        return self::SOURCES[$key] ?? self::SOURCES['other'];
    }

    public static function deviceLabel(?string $key): string
    {
        return self::DEVICES[$key] ?? self::DEVICES['desktop'];
    }
}
