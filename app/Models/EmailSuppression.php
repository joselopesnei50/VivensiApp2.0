<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bloqueio global de envio das campanhas próprias. Um hard bounce ou uma
 * reclamação de spam nunca mais recebe campanha — protege a reputação do
 * domínio e a conta no SES (que suspende acima de ~10% bounce / 0,5% spam).
 */
class EmailSuppression extends Model
{
    public const REASON_HARD_BOUNCE = 'hard_bounce';
    public const REASON_COMPLAINT   = 'complaint';
    public const REASON_MANUAL      = 'manual';
    public const REASON_UNSUBSCRIBE = 'unsubscribe';

    protected $fillable = ['email', 'reason', 'detail', 'email_campaign_id'];

    public static function suppress(string $email, string $reason, ?string $detail = null, ?int $campaignId = null): void
    {
        $email = mb_strtolower(trim($email));
        if ($email === '') return;

        $attrs = ['reason' => $reason, 'detail' => $detail ? mb_substr($detail, 0, 500) : null, 'email_campaign_id' => $campaignId];
        $row   = static::firstOrCreate(['email' => $email], $attrs);

        // Já bloqueado por descadastro/manual e agora veio bounce ou reclamação:
        // sobe para o motivo permanente. Senão, reativar a inscrição apagaria o
        // bloqueio e um endereço inválido/reclamante voltaria a receber.
        if (!$row->wasRecentlyCreated && self::isPermanent($reason) && !self::isPermanent($row->reason)) {
            $row->update($attrs);
        }
    }

    /** Bounce e reclamação nunca são desfeitos pela reativação de inscrição. */
    public static function isPermanent(?string $reason): bool
    {
        return in_array($reason, [self::REASON_HARD_BOUNCE, self::REASON_COMPLAINT], true);
    }

    /** @return array<string,true> e-mails (minúsculos) bloqueados */
    public static function lookup(array $emails): array
    {
        $normalized = array_values(array_unique(array_map(fn ($e) => mb_strtolower(trim((string) $e)), $emails)));
        if (empty($normalized)) return [];

        $found = [];
        foreach (array_chunk($normalized, 1000) as $chunk) {
            foreach (static::whereIn('email', $chunk)->pluck('email') as $e) {
                $found[mb_strtolower($e)] = true;
            }
        }
        return $found;
    }
}
