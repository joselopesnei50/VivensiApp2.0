<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RaffleVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'raffle_id',
        'ip_address',
        'user_agent',
        'referer',
    ];

    /**
     * Record a visit for a given raffle, throttled by IP to avoid inflating counts.
     * One record per IP per hour per raffle.
     */
    public static function record(Raffle $raffle, \Illuminate\Http\Request $request): void
    {
        try {
            $ip = $request->ip();

            // Throttle: only count one hit per IP per raffle per hour
            $alreadyCounted = static::where('raffle_id', $raffle->id)
                ->where('ip_address', $ip)
                ->where('created_at', '>=', now()->subHour())
                ->exists();

            if (!$alreadyCounted) {
                static::create([
                    'raffle_id'  => $raffle->id,
                    'ip_address' => $ip,
                    'user_agent' => mb_substr($request->userAgent() ?? '', 0, 255),
                    'referer'    => mb_substr($request->headers->get('referer') ?? '', 0, 500),
                ]);
            }
        } catch (\Throwable $e) {
            // Fail silently; analytics should never break the public page.
            \Illuminate\Support\Facades\Log::warning('RaffleVisit tracking failed', [
                'raffle_id' => $raffle->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    public function raffle()
    {
        return $this->belongsTo(Raffle::class);
    }
}
