<?php

if (!function_exists('sanitize_br_currency')) {
    /**
     * Converte valor monetário brasileiro para float.
     * Aceita: "1.234,56" → 1234.56 | "1234,56" → 1234.56 | "1234.56" → 1234.56
     */
    function sanitize_br_currency($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        $value = (string) $value;
        // Remove separador de milhar (ponto), troca vírgula decimal por ponto
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        return (float) $value;
    }
}

if (!function_exists('format_br_currency')) {
    /**
     * Formata float para moeda brasileira: 1234.56 → "1.234,56"
     */
    function format_br_currency($value, int $decimals = 2): string
    {
        return number_format((float) $value, $decimals, ',', '.');
    }
}

if (!function_exists('whatsapp_bidx_key')) {
    /**
     * Retorna a chave HMAC usada para gerar o blind index do token de instância
     * WhatsApp (campo whatsapp_instances.instance_token_bidx).
     *
     * Preferência:
     *  1) config('whatsapp.bidx_key') — chave dedicada, rotacionável sem
     *     impactar APP_KEY (Tarefa 1.5 da auditoria).
     *  2) fallback para config('app.key') — comportamento legado, mantém
     *     dev e produções ainda não migradas funcionando.
     *
     * Para migrar de fallback para chave dedicada:
     *  1) Defina WHATSAPP_BIDX_KEY no .env de produção
     *  2) Rode: php artisan whatsapp:rebuild-bidx --apply
     *  (a ordem importa — sem o rebuild, lookups via webhook param de funcionar)
     */
    function whatsapp_bidx_key(): string
    {
        $dedicated = config('whatsapp.bidx_key');
        if (is_string($dedicated) && $dedicated !== '') {
            return $dedicated;
        }
        return (string) config('app.key');
    }
}

if (!function_exists('sanitize_user_html')) {
    /**
     * Sanitiza HTML autorado (páginas/blog) antes de exibir com {!! !!}.
     * Defesa em profundidade SEM dependência externa: mantém uma allowlist de
     * tags, mas remove os vetores de XSS que o strip_tags sozinho NÃO remove
     * (atributos on*, protocolos javascript:/data:/vbscript: e style inline).
     *
     * Observação: para conteúdo de terceiros não confiáveis, prefira HTMLPurifier.
     * Aqui o conteúdo é autorado por admin, então isto cobre o risco residual.
     */
    function sanitize_user_html(?string $html, ?string $allowedTags = null): string
    {
        $allowedTags ??= '<h1><h2><h3><h4><h5><h6><p><br><strong><b><em><i><u>'
            . '<ul><ol><li><a><blockquote><table><thead><tbody><tr><th><td><img><span><div>';

        $clean = strip_tags((string) $html, $allowedTags);

        // 1. Remove handlers de evento inline: onclick, onerror, onmouseover, ...
        $clean = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);

        // 2. Neutraliza protocolos perigosos em href/src.
        $clean = preg_replace(
            '/(href|src)\s*=\s*(["\']?)\s*(?:javascript|data|vbscript)\s*:/i',
            '$1=$2#',
            $clean
        );

        // 3. Remove style inline (vetor de url()/expression em CSS).
        $clean = preg_replace('/\sstyle\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);

        return $clean;
    }
}
