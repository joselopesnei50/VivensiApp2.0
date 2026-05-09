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
