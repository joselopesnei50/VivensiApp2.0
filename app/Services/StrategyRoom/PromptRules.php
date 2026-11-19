<?php

namespace App\Services\StrategyRoom;

/**
 * Regras comuns a todos os agentes da Sala de Estratégia.
 *
 * As instruções internas estão sem acento (legado) e o modelo copiava isso
 * nas falas mostradas ao usuário ("acao", "nao"). Esta regra corrige a saída.
 */
final class PromptRules
{
    public const LANGUAGE = 'IDIOMA: escreva todas as falas e textos do JSON em português do Brasil, com acentuação e ortografia corretas (ex.: ação, não, você, análise, saúde, captação), mesmo que estas instruções estejam escritas sem acento.';

    public static function withLanguage(string $systemPrompt): string
    {
        return rtrim($systemPrompt) . "\n\n" . self::LANGUAGE;
    }
}
