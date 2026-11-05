<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PostView extends Model
{
    public $timestamps = false;

    protected $fillable = ['post_id', 'ip_address', 'user_agent', 'referer', 'is_bot', 'source', 'device'];

    protected $casts = ['is_bot' => 'boolean'];

    public static function record(Post $post, Request $request): void
    {
        try {
            $ip = $request->ip();

            $alreadyCounted = static::where('post_id', $post->id)
                ->where('ip_address', $ip)
                ->where('created_at', '>=', now()->startOfDay())
                ->exists();

            if (!$alreadyCounted) {
                $ua      = $request->userAgent() ?? '';
                $referer = $request->headers->get('referer') ?? '';
                static::create([
                    'post_id'    => $post->id,
                    'ip_address' => $ip,
                    'user_agent' => mb_substr($ua, 0, 255),
                    'referer'    => mb_substr($referer, 0, 500),
                    // Robô continua registrado (útil para saber que o Google passou),
                    // mas fica fora das métricas de leitores
                    'is_bot'     => \App\Support\TrafficClassifier::isBot($ua),
                    'source'     => \App\Support\TrafficClassifier::source($referer),
                    'device'     => \App\Support\TrafficClassifier::device($ua),
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
