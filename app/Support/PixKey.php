<?php

namespace App\Support;

/**
 * Chave Pix no formato que o DICT (Banco Central) registra.
 *
 *  - cpf_cnpj: só números (CPF 11, CNPJ 14). CNPJ alfanumérico (a partir de jul/2026):
 *              12 posições letra/número + 2 dígitos, em maiúsculas.
 *  - email:    minúsculas, até 77 caracteres.
 *  - phone:    +55 + DDD + celular de 9 dígitos (ex.: +5516988392853).
 *  - random:   UUID (chave aleatória) em minúsculas.
 *
 * Qualquer outro formato (com pontos, traços, parênteses, espaço) gera um QR Code
 * que o banco do pagador recusa ou não encontra.
 */
final class PixKey
{
    public const TYPES = ['cpf_cnpj', 'email', 'phone', 'random'];

    public const LABELS = [
        'cpf_cnpj' => 'CNPJ ou CPF',
        'email'    => 'E-mail',
        'phone'    => 'Celular',
        'random'   => 'Chave aleatória',
    ];

    private const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    /**
     * Devolve a chave normalizada ou null se ela não for válida para o tipo.
     */
    public static function normalize(?string $type, ?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '' || !in_array($type, self::TYPES, true)) {
            return null;
        }

        switch ($type) {
            case 'cpf_cnpj':
                $doc = strtoupper(preg_replace('/[^0-9A-Za-z]/', '', $raw));
                if (strlen($doc) === 11 && ctype_digit($doc)) {
                    return self::validCpf($doc) ? $doc : null;
                }
                if (strlen($doc) === 14) {
                    return self::validCnpj($doc) ? $doc : null;
                }
                return null;

            case 'email':
                $email = mb_strtolower($raw);
                return (strlen($email) <= 77 && filter_var($email, FILTER_VALIDATE_EMAIL)) ? $email : null;

            case 'phone':
                $digits = preg_replace('/\D/', '', $raw);
                if (strlen($digits) === 11) {
                    $digits = '55' . $digits;
                }
                // 55 + DDD (11 a 99, sem zero) + celular começando com 9
                return preg_match('/^55[1-9][1-9]9\d{8}$/', $digits) ? '+' . $digits : null;

            case 'random':
                $uuid = mb_strtolower(preg_replace('/\s/', '', $raw));
                return preg_match(self::UUID, $uuid) ? $uuid : null;
        }

        return null;
    }

    /**
     * Adivinha o tipo de uma chave antiga, gravada antes de o tipo ser obrigatório.
     */
    public static function detect(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        if (str_contains($raw, '@')) {
            return 'email';
        }
        if (preg_match(self::UUID, mb_strtolower($raw))) {
            return 'random';
        }
        if (str_starts_with($raw, '+')) {
            return 'phone';
        }

        $alnum = strtoupper(preg_replace('/[^0-9A-Za-z]/', '', $raw));
        if (strlen($alnum) === 14 && self::validCnpj($alnum)) {
            return 'cpf_cnpj';
        }
        if (strlen($alnum) === 11 && ctype_digit($alnum)) {
            // 11 dígitos: CPF válido ou celular com DDD
            return self::validCpf($alnum) ? 'cpf_cnpj' : 'phone';
        }
        if (strlen($alnum) === 13 && str_starts_with($alnum, '55')) {
            return 'phone';
        }

        return null;
    }

    /**
     * Chave pronta para o QR Code: usa o tipo gravado, ou adivinha.
     * Se nada servir, devolve a chave só sem espaços (comportamento antigo).
     */
    public static function forPayload(?string $raw, ?string $type = null): string
    {
        $type = in_array($type, self::TYPES, true) ? $type : self::detect($raw);

        return self::normalize($type, $raw) ?? trim((string) $raw);
    }

    /**
     * Como a chave aparece para as pessoas (com pontuação).
     */
    public static function display(?string $key, ?string $type = null): string
    {
        $key  = (string) $key;
        $type = in_array($type, self::TYPES, true) ? $type : self::detect($key);

        if ($type === 'cpf_cnpj' && strlen($key) === 11 && ctype_digit($key)) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $key);
        }
        if ($type === 'cpf_cnpj' && strlen($key) === 14) {
            return preg_replace('/(\w{2})(\w{3})(\w{3})(\w{4})(\d{2})/', '$1.$2.$3/$4-$5', $key);
        }
        if ($type === 'phone' && preg_match('/^\+55(\d{2})(\d{5})(\d{4})$/', $key, $m)) {
            return "+55 ({$m[1]}) {$m[2]}-{$m[3]}";
        }

        return $key;
    }

    public static function errorMessage(?string $type): string
    {
        return match ($type) {
            'cpf_cnpj' => 'CNPJ ou CPF inválido. Confira os números (os dígitos verificadores não batem).',
            'email'    => 'E-mail inválido. Use o mesmo e-mail cadastrado como chave no banco.',
            'phone'    => 'Celular inválido. Informe DDD + número de celular com 9 dígitos, ex.: (16) 98839-2853.',
            'random'   => 'Chave aleatória inválida. Ela tem 32 letras e números separados por traços, ex.: 123e4567-e89b-12d3-a456-426614174000.',
            default    => 'Escolha o tipo da chave Pix.',
        };
    }

    public static function validCpf(string $cpf): bool
    {
        if (!preg_match('/^\d{11}$/', $cpf) || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $cpf[$i] * (($t + 1) - $i);
            }
            $dv = ((10 * $sum) % 11) % 10;
            if ((int) $cpf[$t] !== $dv) {
                return false;
            }
        }
        return true;
    }

    /**
     * CNPJ numérico ou alfanumérico (IN RFB 2.229/2024): cada posição vale
     * ord(caractere) - 48; os dois últimos são dígitos verificadores numéricos.
     */
    public static function validCnpj(string $cnpj): bool
    {
        $cnpj = strtoupper($cnpj);
        if (!preg_match('/^[0-9A-Z]{12}\d{2}$/', $cnpj) || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }
        $calc = function (string $base): int {
            $weights = strlen($base) === 12
                ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]
                : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
            $sum = 0;
            foreach (str_split($base) as $i => $ch) {
                $sum += (ord($ch) - 48) * $weights[$i];
            }
            $r = $sum % 11;
            return $r < 2 ? 0 : 11 - $r;
        };
        $d1 = $calc(substr($cnpj, 0, 12));
        $d2 = $calc(substr($cnpj, 0, 12) . $d1);

        return $cnpj[12] === (string) $d1 && $cnpj[13] === (string) $d2;
    }
}
