<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiImageUsageLog extends Model
{
    protected $fillable = [
        'user_id', 'month', 'year', 'generation_count'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Incrementa a cota do usuário para o mês atual.
     */
    public static function incrementUsage(int $userId)
    {
        $now = now();
        return self::updateOrCreate(
            ['user_id' => $userId, 'month' => $now->month, 'year' => $now->year],
            ['generation_count' => \DB::raw('generation_count + 1')]
        );
    }

    /**
     * Retorna a contagem atual do usuário para o mês.
     */
    public static function getCurrentUsage(int $userId): int
    {
        $now = now();
        return self::where('user_id', $userId)
            ->where('month', $now->month)
            ->where('year', $now->year)
            ->value('generation_count') ?? 0;
    }
}
