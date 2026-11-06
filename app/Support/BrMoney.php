<?php

namespace App\Support;

/**
 * Lê valor em reais vindo de formulário, aceitando os dois jeitos que chegam:
 *  - texto no padrão brasileiro: "1.500,50", "1500,50", "1.500"
 *  - campo <input type=number> ou valor do banco: "1500.50", "1500.5", "1500"
 *
 * O jeito antigo (apagar todo ponto e trocar vírgula por ponto) transformava
 * "15000.00" em "1500000" — orçamento/repasse ×100.
 */
class BrMoney
{
    public static function parse(mixed $value): mixed
    {
        if ($value === null || is_int($value) || is_float($value)) {
            return $value;
        }

        $s = trim(str_replace(['R$', ' ', "\u{00A0}"], '', (string) $value));
        if ($s === '') {
            return $s;
        }

        if (str_contains($s, ',')) {
            // Padrão brasileiro: ponto = milhar, vírgula = decimal.
            return str_replace(',', '.', str_replace('.', '', $s));
        }

        // Só pontos: um ponto seguido de 1 ou 2 dígitos é decimal ("1500.50");
        // qualquer outro caso é separador de milhar ("1.500", "1.500.000").
        if (preg_match('/^-?\d+\.\d{1,2}$/', $s)) {
            return $s;
        }

        return str_replace('.', '', $s);
    }
}
