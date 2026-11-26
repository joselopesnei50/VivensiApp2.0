<?php

namespace App\Support;

/**
 * fputcsv com proteção contra fórmula (CSV injection): célula de texto que
 * começa com = + - @ (ou tab/CR) ganha um apóstrofo na frente, para o Excel
 * e o LibreOffice não executarem como fórmula. Número (ex.: "-150,00") fica
 * como está. Auditoria do painel (26/11/2026), item 7 — leads de formulário
 * público, beneficiários, doadores e financeiro vão parar em planilha.
 */
final class Csv
{
    /** @param resource $handle */
    public static function put($handle, array $fields, string $separator = ',', string $enclosure = '"', string $escape = '\\', string $eol = "\n"): int|false
    {
        return fputcsv($handle, array_map([self::class, 'cell'], $fields), $separator, $enclosure, $escape, $eol);
    }

    public static function cell(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }
        if (preg_match('/^-?[\d.,]+$/', $value)) {
            return $value;
        }
        return in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'" . $value : $value;
    }
}
