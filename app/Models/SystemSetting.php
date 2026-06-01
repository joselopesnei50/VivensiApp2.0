<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory;

    protected $table = 'system_settings';

    protected $fillable = [
        'key',
        'value',
        'group'
    ];

    public static function getValue($key, $default = null)
    {
        // '__NULL__' sentinel distingue "chave inexistente cacheada" de null real
        $cached = Cache::remember("system_setting.{$key}", 3600, function () use ($key) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : '__NULL__';
        });

        $value = $cached === '__NULL__' ? null : $cached;
        return $value ?? $default;
    }

    public static function setValue($key, $value, $group = 'general')
    {
        Cache::forget("system_setting.{$key}");
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );
    }
}
