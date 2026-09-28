<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgendaEvent extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    public const STATUSES = [
        'pending'   => 'Pendente',
        'done'      => 'Concluído',
        'cancelled' => 'Cancelado',
    ];

    public const KINDS = [
        'meeting'  => 'Reunião',
        'visit'    => 'Visita',
        'call'     => 'Ligação',
        'deadline' => 'Prazo',
        'renewal'  => 'Renovação',
        'other'    => 'Outro',
    ];

    public const KIND_COLORS = [
        'meeting'  => '#4f46e5',
        'visit'    => '#0ea5e9',
        'call'     => '#10b981',
        'deadline' => '#f59e0b',
        'renewal'  => '#8b5cf6',
        'other'    => '#64748b',
    ];

    protected $fillable = [
        'tenant_id',
        'client_id',
        'created_by',
        'title',
        'description',
        'location',
        'starts_on',
        'starts_at',
        'ends_at',
        'all_day',
        'status',
        'kind',
        'color',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'client_id' => 'integer',
        'created_by' => 'integer',
        'starts_on' => 'date',
        'all_day'   => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'pending')
            ->where('starts_on', '>=', now()->toDateString())
            ->orderBy('starts_on')->orderBy('starts_at');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('starts_on', '<', now()->toDateString());
    }

    public function scopeBetween($query, string $from, string $to)
    {
        return $query->whereBetween('starts_on', [$from, $to]);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getKindLabelAttribute(): string
    {
        return self::KINDS[$this->kind] ?? ucfirst((string) $this->kind);
    }

    public function getDisplayColorAttribute(): string
    {
        if ($this->color) {
            return $this->color;
        }
        return self::KIND_COLORS[$this->kind] ?? '#4f46e5';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending'
            && $this->starts_on instanceof Carbon
            && $this->starts_on->lt(now()->startOfDay());
    }

    public function isToday(): bool
    {
        return $this->starts_on instanceof Carbon
            && $this->starts_on->isSameDay(now());
    }

    public function getWhenLabelAttribute(): string
    {
        if (!$this->starts_on instanceof Carbon) {
            return '—';
        }
        $date = $this->starts_on->format('d/m/Y');
        if ($this->all_day || !$this->starts_at) {
            return $date;
        }
        $time = $this->starts_at;
        if ($this->ends_at) {
            $time .= ' — ' . $this->ends_at;
        }
        return $date . ' · ' . $time;
    }
}
