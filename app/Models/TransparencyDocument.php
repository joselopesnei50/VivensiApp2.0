<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransparencyDocument extends Model
{
    use HasFactory;
    protected $fillable = ['tenant_id', 'title', 'type', 'file_path', 'year', 'document_date'];
}
