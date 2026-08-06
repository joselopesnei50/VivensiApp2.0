<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('/solucoes/terceiro-setor renderiza destaque Bruce IA contextualizado', function () {
    $resp = $this->get('/solucoes/terceiro-setor')->assertStatus(200);

    $resp->assertSee('Bruce IA · treinado para o terceiro setor');
    $resp->assertSee('bruceia-icone-fundo-escuro.svg');
    $resp->assertSee('doador, edital e beneficiário');
    $resp->assertSee('Ativar o Bruce na minha ONG');
});

it('/solucoes/terceiro-setor lista os 6 blocos tematicos com modulos reais', function () {
    $resp = $this->get('/solucoes/terceiro-setor')->assertStatus(200);

    $resp->assertSee('Captação, Doadores');
    $resp->assertSee('Editais &amp; Convênios com IA', false);
    $resp->assertSee('Conformidade Contínua CEBAS/MROSC/SUAS');
    $resp->assertSee('Beneficiários');
    $resp->assertSee('WhatsApp');
    $resp->assertSee('Prestação de Contas');
});

it('/solucoes/terceiro-setor cita modulos exclusivos ONG', function () {
    $resp = $this->get('/solucoes/terceiro-setor')->assertStatus(200);

    $resp->assertSee('Radar automático');
    $resp->assertSee('Querido Diário');
    $resp->assertSee('CEBAS');
    $resp->assertSee('MROSC');
    $resp->assertSee('SUAS');
    $resp->assertSee('Voluntariado');
    $resp->assertSee('Vivensi Academy');
});

it('/solucoes/terceiro-setor NAO usa copy de trial/gratuito (nao existe)', function () {
    $resp = $this->get('/solucoes/terceiro-setor')->assertStatus(200);

    $resp->assertDontSee('Entrar na lista de espera');
    $resp->assertDontSee('Sem custar nada');
    $resp->assertDontSee('Acesso 100% gratuito');
    $resp->assertDontSee('Plano Lançamento');
});

it('CTAs de conversao apontam para register', function () {
    $resp = $this->get('/solucoes/terceiro-setor')->assertStatus(200);
    $resp->assertSee(route('register'));
});
