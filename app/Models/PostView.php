<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PostView extends Model
{
    public $timestamps = false;

    protected $fillable = ['post_id', 'ip_address', 'user_agent', 'referer'];

    public static function record(Post $post, Request $request): void
    {
        try {
            $ip = $request->ip();

            $alreadyCounted = static::where('post_id', $post->id)
                ->where('ip_address', $ip)
                ->where('created_at', '>=', now()->startOfDay())
                ->exists();

            if (!$alreadyCounted) {
                static::create([
                    'post_id'    => $post->id,
                    'ip_address' => $ip,
                    'user_agent' => mb_substr($request->userAgent() ?? '', 0, 255),
                    'referer'    => mb_substr($request->headers->get('referer') ?? '', 0, 500),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('PostView tracking failed', ['post_id' => $post->id, 'error' => $e->getMessage()]);
        }
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
