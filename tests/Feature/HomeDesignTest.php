<?php

/*
| Home pública: telas reais, perguntas frequentes e sem provas sociais
| que não dá para confirmar (nota inventada, escassez, depoimento).
*/

it('mostra as telas do sistema e as perguntas frequentes', function () {
    $r = $this->get('/')->assertOk();
    $r->assertSee('id="por-dentro"', false)
      ->assertSee('A rotina da sua ONG, dentro da Vivensi')
      ->assertSee('img/home/telas/turma-frequencia.webp', false)
      ->assertSee('Perguntas frequentes')
      ->assertSee('Quem são o Bruno e o Bruce?');

    foreach (['turma-frequencia', 'beneficiarios', 'relatorio-anual', 'portal-transparencia'] as $img) {
        expect(file_exists(public_path("img/home/telas/{$img}.webp")))->toBeTrue()
            ->and(file_exists(public_path("img/home/telas/{$img}-800.webp")))->toBeTrue();
    }
});

it('não tem pop-up na entrada nem números e selos sem confirmação', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->not->toContain('bpp-backdrop')          // pop-up do Bruce
        ->not->toContain('announceBar"')                     // faixa de anúncio duplicada
        ->not->toContain('4.9/5')
        ->not->toContain('99.9% Uptime')
        ->not->toContain('vagas restantes')
        ->not->toContain('Maria Rodrigues')
        ->not->toContain('expansão acelerada')
        ->not->toContain('Bruce AI')
        ->not->toContain('VIVENSIAPP')
        ->not->toContain('wa.me/551697618695');             // número sem o 9
});

it('seção de preço: plano pago com recursos, sem seletor anual e sem plano de cortesia', function () {
    \App\Models\SubscriptionPlan::forceCreate(['name' => 'Terceiro Setor - Avante', 'price' => 349.90, 'is_active' => true, 'is_courtesy' => false,
        'target_audience' => 'ngo', 'features' => ['Rifas online com PIX']]);
    \App\Models\SubscriptionPlan::forceCreate(['name' => 'Cortesia Parceiros', 'price' => 0, 'is_active' => true, 'is_courtesy' => true, 'target_audience' => 'ngo']);

    $this->get('/')->assertOk()
        ->assertSee('Um plano. Tudo incluso.')
        ->assertSee('349,90')
        ->assertSee('Rifas online com PIX')
        ->assertDontSee('Cortesia Parceiros')
        ->assertDontSee('id="billing-toggle"', false);
});
