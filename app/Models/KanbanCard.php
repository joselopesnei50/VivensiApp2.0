<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KanbanCard extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'column_id',
        'created_by',
        'assigned_to',
        'whatsapp_chat_id',
        'title',
        'description',
        'position',
        'due_date',
        'meta',
        'archived_at',
    ];

    protected $casts = [
        'due_date'    => 'date',
        'meta'        => 'array',
        'archived_at' => 'datetime',
    ];

    public function column(): BelongsTo
    {
        return $this->belongsTo(KanbanColumn::class, 'column_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function whatsappChat(): BelongsTo
    {
        return $this->belongsTo(WhatsappChat::class, 'whatsapp_chat_id');
    }
}
