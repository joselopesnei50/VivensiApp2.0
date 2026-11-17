<?php

use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;

/*
| Termos e Privacidade: uma versão só, com a NC5, reembolso de 7 dias, foro de
| Araraquara, 90 dias de guarda e a lista de empresas que tratam os dados.
| Entrada do cliente novo: contrato fora do painel, sem janelas por cima.
*/

it('termos trazem empresa, reembolso, períodos, guarda e foro', function () {
    $this->get('/termos')->assertOk()
        ->assertSee('NC5 HUB DIGITAL LTDA')->assertSee('67.848.807/0001-50')
        ->assertSee('7 dias')->assertSee('devolução integral', false)->assertDontSee('proporcional aos dias')
        ->assertSee('mensal (30 dias)', false)->assertSee('anual')
        ->assertSee('90 dias')->assertSee('Araraquara/SP')
        ->assertDontSee('Vivensi Tecnologia')->assertDontSee('São Paulo/SP para')->assertDontSee('claude.ai');
});

it('privacidade lista quem trata os dados e não tem dados falsos', function () {
    $html = $this->get('/privacidade')->assertOk()->getContent();
    foreach (['NC5 HUB DIGITAL LTDA', '67.848.807/0001-50', 'AbacatePay', 'Together AI', 'Google (Gemini)', 'DeepSeek', 'Amazon Web Services', 'operadora', 'controladora', '90 dias', 'José da Silva', 'contato@vivensi.app.br'] as $t) {
        expect($html)->toContain($t);
    }
    expect($html)->not->toContain('12.345.678/0001-90')->not->toContain('Av. Paulista')->not->toContain('vivensi.com.br');
});

it('as cópias antigas de termos e privacidade redirecionam para a versão única', function () {
    $this->get('/pagina/termos')->assertRedirect(route('legal.terms'))->assertStatus(301);
    $this->get('/pagina/privacidade')->assertRedirect(route('legal.privacy'))->assertStatus(301);
});

it('home sem nota 5.0 e com a regra de reembolso de 7 dias', function () {
    $this->get('/')->assertOk()->assertDontSee('Avaliação média')->assertDontSee('em 30 dias')->assertSee('7 dias para decidir')->assertSee('Devolvemos 100% do valor em até 7 dias');
});

it('estatística e pixel só carregam depois de aceitar os cookies', function () {
    SystemSetting::setValue('ga4_measurement_id', 'G-TESTE123');
    $this->get('/termos')->assertDontSee('G-TESTE123')->assertSee('Só os necessários');
    $this->withCookie('vivensi_cookie_consent', 'accepted')->get('/termos')->assertSee('G-TESTE123');
});

it('contrato do cliente novo abre fora do painel, sem menu nem janela de novidades', function () {
    $t = Tenant::factory()->create(['type' => 'ngo', 'subscription_status' => 'awaiting_contract']);
    $u = User::factory()->create(['tenant_id' => $t->id, 'role' => 'ngo', 'welcome_dismissed_at' => null]);

    $this->actingAs($u)->get('/register/contrato')->assertOk()
        ->assertSee('Dados da entidade')
        ->assertDontSee('welcome-modal-backdrop', false)
        ->assertDontSee('id="mainSidebar"', false)
        ->assertDontSee('vivensi-tour.js', false)
        ->assertSee('NC5 HUB DIGITAL LTDA');
});

it('IP do servidor para domínio próprio vem do .env e aceita o antigo durante a migração', function () {
    config(['app.server_public_ip' => '18.230.1.2', 'app.server_accepted_ips' => '34.193.132.112']);
    expect(\App\Services\LandingDomainProvisioner::serverIp())->toBe('18.230.1.2')
        ->and(\App\Services\LandingDomainProvisioner::acceptedIps())->toBe(['18.230.1.2', '34.193.132.112']);
});
