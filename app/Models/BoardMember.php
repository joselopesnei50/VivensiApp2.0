<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoardMember extends Model
{
    use HasFactory;
    protected $table = 'transparency_board';
    protected $fillable = ['tenant_id', 'name', 'position', 'tenure_start', 'tenure_end', 'bio', 'photo_url'];
}
