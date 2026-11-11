<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Acrescenta ao plano Avante: hub de marketing, API oficial da Meta e Vivensi Academy.
 * Só inclui o que ainda não está na lista e mantém o resto como está (inclusive edições do painel).
 */
return new class extends Migration
{
    private const NEW_ITEMS = [
        'marketing' => 'Hub de marketing: gestão do Instagram, gerador de posts com IA e calendário editorial',
        'meta'      => 'WhatsApp pela API oficial da Meta',
        'academy'   => 'Vivensi Academy: trilhas de capacitação para a equipe',
    ];

    public function up(): void
    {
        $plans = DB::table('subscription_plans')
            ->where('target_audience', 'ngo')
            ->where(function ($q) {
                $q->where('abacatepay_product_id', 'prod_qDSXkday2ZMPAugkYBHKcJJf')
                  ->orWhere('name', 'like', '%Avante%');
            })
            ->get(['id', 'features']);

        foreach ($plans as $plan) {
            $list = array_values(array_filter((array) json_decode((string) $plan->features, true)));
            $text = mb_strtolower(implode(' | ', $list));

            $add = [];
            if (!str_contains($text, 'hub de marketing') && !str_contains($text, 'calendário editorial')) $add[] = self::NEW_ITEMS['marketing'];
            if (!str_contains($text, 'api oficial'))                                                     $add[] = self::NEW_ITEMS['meta'];
            if (!str_contains($text, 'academy'))                                                         $add[] = self::NEW_ITEMS['academy'];
            if (!$add) continue;

            // Entra logo depois de "Campanhas de e-mail..." quando existir; senão, no fim.
            $pos = null;
            foreach ($list as $i => $f) {
                if (str_contains(mb_strtolower($f), 'campanhas de e-mail')) { $pos = $i + 1; break; }
            }
            $pos === null ? array_push($list, ...$add) : array_splice($list, $pos, 0, $add);

            DB::table('subscription_plans')->where('id', $plan->id)->update([
                'features'   => json_encode($list, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
    }
};
