<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratedReport extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'user_id', 'type', 'params',
        'status', 'file_path', 'filename', 'error_message',
    ];

    protected $casts = [
        'params'     => 'array',
        'created_at' => 'datetime',
    ];

    public static function cleanup(int $hoursOld = 24): void
    {
        self::where('created_at', '<', now()->subHours($hoursOld))
            ->each(function ($report) {
                if ($report->file_path) {
                    \Illuminate\Support\Facades\Storage::disk('local')->delete($report->file_path);
                }
                $report->delete();
            });
    }
}
