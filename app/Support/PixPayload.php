<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Str;

/**
 * BR Code do Pix estático (EMV QRCPS, Merchant Presented Mode) com CRC16-CCITT,
 * como pede o manual do Banco Central.
 */
final class PixPayload
{
    /** Nome que aparece para quem paga (campo 59: até 25 caracteres ASCII). */
    public static function merchantName(Tenant $tenant): string
    {
        $name = Str::ascii((string) ($tenant->razao_social ?: ($tenant->name ?? 'EMPRESA')));
        $name = strtoupper(preg_replace('/[^A-Za-z0-9 ]/', '', $name));

        return substr(trim(preg_replace('/\s+/', ' ', $name)), 0, 25) ?: 'EMPRESA';
    }

    /** Cidade (campo 60: até 15 caracteres ASCII). Sem cidade cadastrada, SAO PAULO. */
    public static function merchantCity(Tenant $tenant): string
    {
        $city = strtoupper(preg_replace('/[^A-Za-z ]/', '', Str::ascii((string) ($tenant->cidade ?? ''))));

        return substr(trim(preg_replace('/\s+/', ' ', $city)), 0, 15) ?: 'SAO PAULO';
    }

    public static function forTenant(Tenant $tenant, ?float $amount, string $txid = '***'): ?string
    {
        if (empty($tenant->pix_key)) {
            return null;
        }

        return self::build(
            PixKey::forPayload($tenant->pix_key, $tenant->pix_key_type),
            self::merchantName($tenant),
            self::merchantCity($tenant),
            $amount,
            $txid
        );
    }

    public static function build(string $key, string $name, string $city, ?float $amount, string $txid = '***'): string
    {
        $tlv = fn (string $id, string $value): string =>
            $id . str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT) . $value;

        // txid: '***' (sem identificador) ou até 25 letras/números
        if ($txid !== '***') {
            $txid = substr(preg_replace('/[^A-Za-z0-9]/', '', $txid), 0, 25) ?: '***';
        }

        $payload = '000201'
            . $tlv('26', $tlv('00', 'br.gov.bcb.pix') . $tlv('01', $key))
            . '52040000'
            . '5303986'
            . ($amount !== null && $amount > 0 ? $tlv('54', number_format($amount, 2, '.', '')) : '')
            . '5802BR'
            . $tlv('59', $name)
            . $tlv('60', $city)
            . $tlv('62', $tlv('05', $txid))
            . '6304';

        return $payload . self::crc16($payload);
    }

    public static function crc16(string $data): string
    {
        $crc = 0xFFFF;
        for ($i = 0, $n = strlen($data); $i < $n; $i++) {
            $crc ^= ord($data[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
                $crc &= 0xFFFF;
            }
        }

        return strtoupper(sprintf('%04X', $crc));
    }
}
