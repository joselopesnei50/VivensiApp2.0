<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class TransparencyDocument extends Model
{
    use HasFactory, BelongsToTenant;
    protected $fillable = ['tenant_id', 'title', 'type', 'file_path', 'year', 'document_date'];
}
