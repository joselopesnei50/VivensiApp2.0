<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use App\Traits\BelongsToTenant;
use App\Traits\HasAttachments;

class Beneficiary extends Model
{
    use \App\Traits\Auditable;
    use HasFactory, BelongsToTenant, HasAttachments;

    protected $fillable = [
        'tenant_id',
        'name',
        'cpf',
        'nis',
        'birth_date',
        'gender',
        'race_color',
        'education',
        'phone',
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
        'status',
    ];

    protected $hidden = ['cpf', 'nis', 'birth_date', 'cpf_bidx', 'nis_bidx'];

    protected $casts = [
        'birth_date' => 'date',
    ];

    // ── Encryption accessors/mutators ────────────────────────────────────────

    public function getCpfAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') return $value;
        try { return Crypt::decryptString($value); } catch (DecryptException) { return $value; }
    }

    public function setCpfAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['cpf'] = $value;
            $this->attributes['cpf_bidx'] = null;
            return;
        }
        $this->attributes['cpf'] = Crypt::encryptString($value);
        $this->attributes['cpf_bidx'] = hash_hmac('sha256', $value, config('app.key'));
    }

    public function getNisAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') return $value;
        try { return Crypt::decryptString($value); } catch (DecryptException) { return $value; }
    }

    public function setNisAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['nis'] = $value;
            $this->attributes['nis_bidx'] = null;
            return;
        }
        $this->attributes['nis'] = Crypt::encryptString($value);
        $this->attributes['nis_bidx'] = hash_hmac('sha256', $value, config('app.key'));
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function familyMembers()
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // Vinculos ao projeto (opt-in via form de ProjectPerson). Podem existir
    // varios (um por projeto que o beneficiario participa).
    public function projectMemberships()
    {
        return $this->hasMany(ProjectPerson::class);
    }

    protected static function booted(): void
    {
        // Ao excluir o beneficiario, apenas quebra o link em cada ProjectPerson
        // — o cadastro do projeto e o historico de chamada da turma sao
        // preservados. Redundante com a FK nullOnDelete em MySQL, mas garante
        // o comportamento em drivers que nao propagam ON DELETE (ex: SQLite
        // ao alterar tabela) e em codigo que use ->forceDelete diretamente.
        static::deleting(function (self $beneficiary) {
            ProjectPerson::where('tenant_id', $beneficiary->tenant_id)
                ->where('beneficiary_id', $beneficiary->id)
                ->update(['beneficiary_id' => null]);
        });
    }
}
