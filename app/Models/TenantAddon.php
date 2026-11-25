<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Quanto uma organização contratou de um adicional (0 = cancelado). */
class TenantAddon extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'billing_addon_id', 'quantity', 'updated_by'];

    protected $casts = ['quantity' => 'integer'];

    public function addon()
    {
        return $this->belongsTo(BillingAddon::class, 'billing_addon_id');
    }
}
