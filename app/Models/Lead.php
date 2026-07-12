<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Lead extends Model
{
    use HasFactory, BelongsToTenant;

    public const STATUS_PENDING      = 'pending';
    public const STATUS_CONFIRMED    = 'confirmed';
    public const STATUS_UNSUBSCRIBED = 'unsubscribed';
    public const STATUS_BLOCKED      = 'blocked';

    protected $fillable = [
        'tenant_id',
        'whatsapp_chat_id',
        'name',
        'phone',
        'phone_normalized',
        'email',
        'city',
        'tags',
        'meta',
        'status',
        'consent_origin',
        'consent_at',
        'consent_ip',
        'consent_user_agent',
        'double_opt_in_at',
        'unsubscribed_at',
        'last_interaction_at',
    ];

    protected $casts = [
        'tags'                => 'array',
        'meta'                => 'array',
        'consent_at'          => 'datetime',
        'double_opt_in_at'    => 'datetime',
        'unsubscribed_at'     => 'datetime',
        'last_interaction_at' => 'datetime',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(WhatsappChat::class, 'whatsapp_chat_id');
    }

    public function consents(): HasMany
    {
        return $this->hasMany(LeadConsent::class)->orderByDesc('recorded_at');
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(LeadTimelineItem::class)->orderByDesc('created_at');
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isOptedOut(): bool
    {
        return $this->status === self::STATUS_UNSUBSCRIBED || $this->unsubscribed_at !== null;
    }

    // ── Encryption at-rest (LGPD C4) ─────────────────────────────────────────
    //
    // Padrão identico ao Beneficiary.cpf/nis. Crypt::encryptString cifra com
    // AES-256-CBC + envelope; blind index HMAC-SHA256 permite busca sem
    // decriptar. Plaintext legado (antes do backfill) e aceito no getter via
    // fallback do try/catch.

    public function getPhoneAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') return $value;
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value; // Plaintext legacy — retorna como veio
        }
    }

    public function setPhoneAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['phone']      = $value;
            $this->attributes['phone_bidx'] = null;
            return;
        }
        $this->attributes['phone']      = Crypt::encryptString($value);
        $this->attributes['phone_bidx'] = self::hashForBidx($value);
    }

    public function getEmailAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') return $value;
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value; // Plaintext legacy
        }
    }

    public function setEmailAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['email']      = $value;
            $this->attributes['email_bidx'] = null;
            return;
        }
        // Normaliza: sempre lowercase + trim antes de cifrar/hashear.
        // Garante que busca por "FOO@bar.com" bate com armazenado "foo@bar.com".
        $normalized = mb_strtolower(trim($value));
        $this->attributes['email']      = Crypt::encryptString($normalized);
        $this->attributes['email_bidx'] = self::hashForBidx($normalized);
    }

    /**
     * Helper publico p/ services buscarem por email/phone cifrado.
     * Ex: Lead::where('email_bidx', Lead::hashForBidx('foo@bar.com'))->first()
     */
    public static function hashForBidx(string $plaintext): string
    {
        return hash_hmac('sha256', $plaintext, config('app.key'));
    }
}
