<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginActivity extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'ip_address', 'user_agent', 'device',
        'browser', 'platform', 'country', 'success', 'logged_in_at',
    ];

    protected $casts = [
        'success'      => 'boolean',
        'logged_in_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record(User $user, bool $success = true): void
    {
        $ua       = request()->userAgent() ?? '';
        $device   = self::detectDevice($ua);
        $browser  = self::detectBrowser($ua);
        $platform = self::detectPlatform($ua);

        static::create([
            'user_id'      => $user->id,
            'ip_address'   => request()->ip(),
            'user_agent'   => substr($ua, 0, 500),
            'device'       => $device,
            'browser'      => $browser,
            'platform'     => $platform,
            'success'      => $success,
            'logged_in_at' => now(),
        ]);
    }

    private static function detectDevice(string $ua): string
    {
        if (preg_match('/tablet|ipad/i', $ua))  return 'Tablet';
        if (preg_match('/mobile|android|iphone/i', $ua)) return 'Mobile';
        return 'Desktop';
    }

    private static function detectBrowser(string $ua): string
    {
        if (str_contains($ua, 'Edg'))     return 'Edge';
        if (str_contains($ua, 'OPR'))     return 'Opera';
        if (str_contains($ua, 'Chrome'))  return 'Chrome';
        if (str_contains($ua, 'Firefox')) return 'Firefox';
        if (str_contains($ua, 'Safari'))  return 'Safari';
        return 'Outro';
    }

    private static function detectPlatform(string $ua): string
    {
        if (str_contains($ua, 'Windows'))   return 'Windows';
        if (str_contains($ua, 'Macintosh')) return 'macOS';
        if (str_contains($ua, 'iPhone'))    return 'iOS';
        if (str_contains($ua, 'Android'))   return 'Android';
        if (str_contains($ua, 'Linux'))     return 'Linux';
        return 'Outro';
    }
}
