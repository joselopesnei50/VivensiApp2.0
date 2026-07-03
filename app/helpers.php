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
     * HTMLPurifier via mews/purifier — parser real de HTML, imune aos bypasses
     * de regex (tags malformadas, encoding tricks) que o strip_tags não cobre.
     *
     * $allowedTags é mantido na assinatura por compatibilidade, mas ignorado:
     * a allowlist vem de config/purifier.php (perfil 'default').
     * Mudar tags permitidas = mudar o config, não o caller.
     */
    function sanitize_user_html(?string $html, ?string $allowedTags = null): string
    {
        if (empty($html)) {
            return '';
        }

        return clean($html, 'default');
    }
}
