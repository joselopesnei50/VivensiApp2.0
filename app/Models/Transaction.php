<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class Transaction extends Model
{
    use \App\Traits\Auditable, BelongsToTenant;
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'tenant_id',
        'category_id',
        'project_id',
        'description',
        'amount',
        'type',
        'date',
        'status',
        'attachment_path',
        'external_id',
        'origem_verba_id',
        'receipt_path',
        'approval_status',
        'volunteer_id'
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function category() {
        return $this->belongsTo(\App\Models\FinancialCategory::class, 'category_id');
    }

    public function project() {
        return $this->belongsTo(\App\Models\Project::class);
    }

    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }
}
