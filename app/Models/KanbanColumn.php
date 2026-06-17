<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KanbanColumn extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'board_id',
        'name',
        'color',
        'position',
        'wip_limit',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(KanbanBoard::class, 'board_id');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(KanbanCard::class, 'column_id')
            ->whereNull('archived_at')
            ->orderBy('position');
    }
}
