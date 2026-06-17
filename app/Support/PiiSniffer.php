<?php

namespace App\Support;

/**
 * Detecta padrões comuns de PII em texto livre (CPF, telefone BR, e-mail).
 * Usado no FormRequest do Perfil Operacional (Fase 1 — Etapa B) pra
 * impedir que o operador embuta dado pessoal de eleitor/lead em instruções
 * que vão parar no system prompt do Bruce.
 *
 * Heurística — não é catch-all. Cobre o uso típico (dump de base, copy-paste
 * de planilha) e é barato o suficiente pra rodar em toda submissão.
 */
class PiiSniffer
{
    /** CPF com ou sem máscara: 123.456.789-00 ou 12345678900. */
    public const PATTERN_CPF = '/\b\d{3}\.?\d{3}\.?\d{3}-?\d{2}\b/';

    /**
     * Telefone BR: com/sem +55, DDD com/sem parênteses, 9º dígito opcional.
     * 8 a 11 dígitos consecutivos no número (descontando separadores).
     */
    public const PATTERN_PHONE_BR = '/(?<!\d)(?:\+?55\s?)?(?:\(?\d{2}\)?[\s-]?)?(?:9?\d{4})[\s-]?\d{4}(?!\d)/';

    /** E-mail simples — basta um @ com domínio. */
    public const PATTERN_EMAIL = '/[\w.+-]+@[\w.-]+\.\w{2,}/';

    public static function hasCpf(string $text): bool
    {
        return $text !== '' && preg_match(self::PATTERN_CPF, $text) === 1;
    }

    public static function hasPhoneBR(string $text): bool
    {
        return $text !== '' && preg_match(self::PATTERN_PHONE_BR, $text) === 1;
    }

    public static function hasEmail(string $text): bool
    {
        return $text !== '' && preg_match(self::PATTERN_EMAIL, $text) === 1;
    }

    /**
     * Retorna o tipo do primeiro PII detectado ('cpf', 'phone', 'email') ou
     * null se nada bater. A ordem importa: CPF é o mais nítido, depois
     * telefone, depois e-mail (que pode dar falso positivo em URL).
     */
    public static function detect(string $text): ?string
    {
        if ($text === '') {
            return null;
        }
        if (self::hasCpf($text))     return 'cpf';
        if (self::hasPhoneBR($text)) return 'phone';
        if (self::hasEmail($text))   return 'email';
        return null;
    }
}
