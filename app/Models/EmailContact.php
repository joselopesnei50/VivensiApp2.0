<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailContact extends Model
{
    use HasFactory, BelongsToTenant;

    public const STATUS_ACTIVE       = 'active';
    public const STATUS_BOUNCED      = 'bounced';
    public const STATUS_UNSUBSCRIBED = 'unsubscribed';
    public const STATUS_INVALID      = 'invalid';

    protected $fillable = [
        'email_contact_list_id',
        'tenant_id',
        'email',
        'name',
        'status',
        'source',
        'added_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'added_at'        => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function list(): BelongsTo
    {
        return $this->belongsTo(EmailContactList::class, 'email_contact_list_id');
    }
}
