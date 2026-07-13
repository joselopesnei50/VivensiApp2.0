<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ClassSession extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'project_id',
        'project_class_id',
        'title',
        'date',
        'start_time',
        'end_time',
        'teacher_user_id',
        'mode',
        'public_token',
        'public_enabled_until',
        'notes',
    ];

    protected $casts = [
        'date'                 => 'date',
        'public_enabled_until' => 'datetime',
    ];

    protected $hidden = ['public_token_bidx'];

    // ── Token publico cifrado at-rest + bidx pesquisavel ─────────────────────
    // Mesmo padrao de Contract::token e NgoDonor::portal_token.

    public function getPublicTokenAttribute(?string $value): ?string
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

    public function setPublicTokenAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['public_token']      = $value;
            $this->attributes['public_token_bidx'] = null;
            return;
        }
        $this->attributes['public_token']      = Crypt::encryptString($value);
        $this->attributes['public_token_bidx'] = hash_hmac('sha256', $value, config('app.key'));
    }

    // Lookup publico por token — centralizado aqui (diverge do padrao inline
    // de ContractController::showPublic de proposito, pra reuso).
    public static function findByPublicToken(string $token): ?self
    {
        $bidx = hash_hmac('sha256', $token, config('app.key'));

        return static::withoutGlobalScopes()
            ->where('public_token_bidx', $bidx)
            ->first();
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function attendances()
    {
        return $this->hasMany(ClassAttendance::class);
    }
}
