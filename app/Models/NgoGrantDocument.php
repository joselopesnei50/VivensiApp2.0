<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class NgoGrantDocument extends Model
{
    use HasFactory, BelongsToTenant, Auditable;

    protected $table = 'ngo_grant_documents';

    protected $fillable = [
        'tenant_id',
        'ngo_grant_id',
        'title',
        'type',
        'file_path',
        'original_name',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    public function grant()
    {
        return $this->belongsTo(NgoGrant::class, 'ngo_grant_id');
    }

    protected static function booted()
    {
        static::deleting(function (NgoGrantDocument $doc) {
            $disk = 'public';
            try {
                if ($doc->file_path) {
                    Storage::disk($disk)->delete($doc->file_path);
                }
            } catch (\Throwable $e) {
                // Keep flow stable; file may already be missing.
            }
        });
    }
}

