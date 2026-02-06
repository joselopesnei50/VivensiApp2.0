<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class PublicPartnership extends Model
{
    use HasFactory, BelongsToTenant;
    protected $fillable = ['tenant_id', 'agency_name', 'project_name', 'value', 'gazette_link', 'status', 'start_date', 'end_date'];
}
