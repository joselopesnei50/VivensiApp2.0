<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

use App\Traits\BelongsToTenant;

class NgoDonor extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'ngo_donors';

    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'document',
        'type', // individual, company, government
        'portal_token',
        'address',
        'address_zip',
        'address_street',
        'address_number',
        'address_complement',
        'address_neighborhood',
        'address_city',
        'address_state',
        'latitude',
        'longitude',
        'email_marketing_opt_in',
    ];

    protected $hidden = ['document', 'document_bidx', 'portal_token_bidx'];

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->portal_token)) {
                $model->portal_token = (string) Str::uuid();
            }
        });
    }

    // ── Encryption accessors/mutators ────────────────────────────────────────

    public function getDocumentAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') return $value;
        try { return Crypt::decryptString($value); } catch (DecryptException) { return $value; }
    }

    public function setDocumentAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['document'] = $value;
            $this->attributes['document_bidx'] = null;
            return;
        }
        $this->attributes['document'] = Crypt::encryptString($value);
        $this->attributes['document_bidx'] = hash_hmac('sha256', $value, config('app.key'));
    }

    public function getPortalTokenAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    public function setPortalTokenAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['portal_token']      = $value;
            $this->attributes['portal_token_bidx'] = null;
            return;
        }
        $this->attributes['portal_token']      = Crypt::encryptString($value);
        $this->attributes['portal_token_bidx'] = hash_hmac('sha256', $value, config('app.key'));
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function transactions()
    {
        return $this->hasMany(\App\Models\Transaction::class, 'ngo_donor_id');
    }

    public function getPortalUrlAttribute(): string
    {
        return url('/portal-doador/' . $this->portal_token);
    }
}
