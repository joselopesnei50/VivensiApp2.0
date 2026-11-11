<?php

use App\Models\SubscriptionPlan;

it('preenche os recursos do plano Avante só quando estão vazios', function () {
    $avante  = SubscriptionPlan::forceCreate(['name' => 'Terceiro Setor - Avante', 'price' => 349.90, 'is_active' => true, 'target_audience' => 'ngo', 'features' => []]);
    $porId   = SubscriptionPlan::forceCreate(['name' => 'Outro nome', 'price' => 349.90, 'is_active' => true, 'target_audience' => 'ngo', 'abacatepay_product_id' => 'prod_qDSXkday2ZMPAugkYBHKcJJf']);
    $editado = SubscriptionPlan::forceCreate(['name' => 'Avante Plus', 'price' => 1, 'is_active' => true, 'target_audience' => 'ngo', 'features' => ['Meu texto']]);
    $antigo  = SubscriptionPlan::forceCreate(['name' => 'Terceiro Setor', 'price' => 397.90, 'is_active' => true, 'target_audience' => 'ngo']);

    (require database_path('migrations/2026_11_11_020000_fill_features_plano_avante.php'))->up();

    expect($avante->fresh()->features)->toHaveCount(13)->toContain('Rifas online com PIX')
        ->and($porId->fresh()->features)->toHaveCount(13)
        ->and($editado->fresh()->features)->toBe(['Meu texto'])
        ->and($antigo->fresh()->features)->toBeNull();
});
