<?php

namespace App\Support;

/**
 * Número para links do WhatsApp (https://wa.me/<número>).
 *
 * O wa.me exige o código do país: telefone salvo só com DDD (10 ou 11
 * dígitos) abria uma conversa com um número inexistente.
 */
class WhatsappLink
{
    public static function number(?string $phone): string
    {
        $d = ltrim((string) preg_replace('/\D+/', '', (string) $phone), '0');
        return (strlen($d) === 10 || strlen($d) === 11) ? '55' . $d : $d;
    }
}
