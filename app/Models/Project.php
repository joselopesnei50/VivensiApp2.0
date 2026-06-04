<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class Project extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'budget',
        'start_date',
        'end_date',
        'status',
        'ngo_grant_id',
        'address',
        'latitude',
        'longitude',
        'ai_summary',
        'ai_summary_at',
        'ai_summary_status',
        'archived_at',
        'archived_by',
    ];

    protected $casts = [
        'start_date'    => 'date',
        'end_date'      => 'date',
        'budget'        => 'decimal:2',
        'ai_summary_at' => 'datetime',
        'archived_at'   => 'datetime',
    ];

    // Desativa a coluna updated_at que não existe no banco legado
    const UPDATED_AT = null;

    // ── Arquivamento ─────────────────────────────────────────────────────────
    // archived_at preserva o histórico (sem delete). Listagens padrão usam
    // ->active(); a tela /projects/archived usa ->archived().

    public function scopeActive($q)   { return $q->whereNull('projects.archived_at'); }
    public function scopeArchived($q) { return $q->whereNotNull('projects.archived_at'); }

    public function isArchived(): bool { return ! is_null($this->archived_at); }

    public function archive(?int $userId = null): bool
    {
        $this->archived_at = now();
        $this->archived_by = $userId ?? auth()->id();
        return $this->save();
    }

    public function unarchive(): bool
    {
        $this->archived_at = null;
        $this->archived_by = null;
        return $this->save();
    }
    
    // Relacionamento com Logs (Opicional por enquanto, mas bom ter)
    // Relacionamento com Transações (Opicional)

    public function ngo_grant()
    {
        return $this->belongsTo(NgoGrant::class, 'ngo_grant_id');
    }

    public function members()
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function people()
    {
        return $this->hasMany(ProjectPerson::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function timelineRecords()
    {
        return $this->hasMany(ProjectTimelineRecord::class)->orderBy('date', 'desc')->orderBy('created_at', 'desc');
    }

    public function logs()
    {
        return $this->hasMany(ProjectLog::class)->orderBy('created_at', 'desc');
    }
}
