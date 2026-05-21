<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class LgpdDataRequest extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'user_id', 'tenant_id', 'type', 'status', 'ip_address', 'notes', 'processed_at', 'processed_by',
    ];

    protected $casts = ['processed_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
