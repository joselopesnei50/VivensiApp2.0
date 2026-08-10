<?php

use App\Models\SubscriptionPlan;

/**
 * Entrega 2 (2026-08-07) — seção de planos nas 3 landings dedicadas.
 * Cada landing renderiza os planos do target_audience correspondente via
 * parcial public._plans_section, com fallback WhatsApp quando vazio.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function spsPlan(string $audience, string $name, float $price, array $features = []): SubscriptionPlan
{
    return SubscriptionPlan::create([
        'name'            => $name,
        'target_audience' => $audience,
        'price'           => $price,
        'interval'        => 'monthly',
        'features'        => $features,
        'is_active'       => true,
        'is_courtesy'     => false,
    ]);
}

it('/solucoes/terceiro-setor renderiza cards com planos NGO', function () {
    spsPlan('ngo', 'ONG Essencial', 79.90, ['CRM Doadores', 'Bruce IA', 'WhatsApp Cloud']);

    $resp = $this->get('/solucoes/terceiro-setor')->assertStatus(200);
    $resp->assertSee('Escolha o plano da sua operação');
    $resp->assertSee('ONG Essencial');
    $resp->assertSee('79,90');
    $resp->assertSee('CRM Doadores');
    $resp->assertSee('Bruce IA');
});

it('/solucoes/gestor-projetos renderiza cards com planos Manager', function () {
    spsPlan('manager', 'Gestor Pro', 149.00, ['Kanban', 'Time tracking']);

    $resp = $this->get('/solucoes/gestor-projetos')->assertStatus(200);
    $resp->assertSee('Gestor Pro');
    $resp->assertSee('149,00');
    $resp->assertSee('Kanban');
});

it('/solucoes/pessoa-comum renderiza cards com planos Common', function () {
    spsPlan('common', 'TopE Basico', 89.00, ['CRM Clientes', 'WhatsApp comercial']);

    $resp = $this->get('/solucoes/pessoa-comum')->assertStatus(200);
    $resp->assertSee('TopE Basico');
    $resp->assertSee('89,00');
    $resp->assertSee('CRM Clientes');
});

it('cada landing SO mostra os planos do audience correspondente', function () {
    spsPlan('ngo',     'ONG X',    79.90);
    spsPlan('manager', 'GestorY',  149.00);
    spsPlan('common',  'TopEZ',    89.00);

    $ngo    = $this->get('/solucoes/terceiro-setor')->assertStatus(200);
    $ngo->assertSee('ONG X');
    $ngo->assertDontSee('GestorY');
    $ngo->assertDontSee('TopEZ');

    $mgr    = $this->get('/solucoes/gestor-projetos')->assertStatus(200);
    $mgr->assertSee('GestorY');
    $mgr->assertDontSee('ONG X');
    $mgr->assertDontSee('TopEZ');

    $com    = $this->get('/solucoes/pessoa-comum')->assertStatus(200);
    $com->assertSee('TopEZ');
    $com->assertDontSee('ONG X');
    $com->assertDontSee('GestorY');
});

it('landing sem plano cadastrado no audience mostra fallback WhatsApp', function () {
    $resp = $this->get('/solucoes/terceiro-setor')->assertStatus(200);
    $resp->assertSee('Planos para ONGs de todos os portes');
    $resp->assertSee('wa.me/551697618695');
    $resp->assertSee('Falar no WhatsApp');
});

it('com 3 planos, card do meio ganha destaque Mais popular', function () {
    spsPlan('ngo', 'ONG Basico', 79.90);
    spsPlan('ngo', 'ONG Pro',    149.00);
    spsPlan('ngo', 'ONG Full',   299.00);

    $resp = $this->get('/solucoes/terceiro-setor')->assertStatus(200);
    $resp->assertSee('Mais popular');
});

it('CTA de cada card aponta pra /register com plan_id', function () {
    $plan = spsPlan('ngo', 'ONG Unico', 99.00);

    $resp = $this->get('/solucoes/terceiro-setor')->assertStatus(200);
    $resp->assertSee('/register?plan_id=' . $plan->id, false);
});
