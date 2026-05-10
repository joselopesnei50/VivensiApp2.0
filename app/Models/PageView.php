<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PageView extends Model
{
    public $timestamps = false;

    protected $fillable = ['page_id', 'ip_address', 'user_agent', 'referer'];

    public static function record(Page $page, Request $request): void
    {
        try {
            $ip = $request->ip();

            $alreadyCounted = static::where('page_id', $page->id)
                ->where('ip_address', $ip)
                ->where('created_at', '>=', now()->startOfDay())
                ->exists();

            if (!$alreadyCounted) {
                static::create([
                    'page_id'    => $page->id,
                    'ip_address' => $ip,
                    'user_agent' => mb_substr($request->userAgent() ?? '', 0, 255),
                    'referer'    => mb_substr($request->headers->get('referer') ?? '', 0, 500),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('PageView tracking failed', ['page_id' => $page->id, 'error' => $e->getMessage()]);
        }
    }

    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
