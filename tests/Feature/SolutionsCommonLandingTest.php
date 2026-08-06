<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('/solucoes/pessoa-comum renderiza os dois destaques novos', function () {
    $resp = $this->get('/solucoes/pessoa-comum')->assertStatus(200);

    // Destaque Bruce IA — tag, headline e CTA
    $resp->assertSee('Bruce IA · aplicada ao seu negócio');
    $resp->assertSee('Não é chatbot');
    $resp->assertSee('bruceia-icone-fundo-escuro.svg');
    $resp->assertSee('Ativar o Bruce');

    // Destaque Marketing — cada um dos 8 servicos precisa aparecer
    $resp->assertSee('Marketing');
    $resp->assertSee('Criação de post para rede social');
    $resp->assertSee('Calendário editorial');
    $resp->assertSee('Agendamento de post');
    $resp->assertSee('Plano de marketing');
    $resp->assertSee('Sala de Estratégia');
    $resp->assertSee('E-mail marketing');
    $resp->assertSee('API Oficial Meta WhatsApp');
    $resp->assertSee('API nativa Vivensi');
});

it('CTAs de conversao apontam para register', function () {
    $resp = $this->get('/solucoes/pessoa-comum')->assertStatus(200);
    $resp->assertSee(route('register'));
});
