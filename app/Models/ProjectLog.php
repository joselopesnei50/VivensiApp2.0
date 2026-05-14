<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ProjectLog extends Model
{
    use BelongsToTenant;
    public $timestamps = false;

    protected $fillable = ['project_id', 'tenant_id', 'user_id', 'body', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
