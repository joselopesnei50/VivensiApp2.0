<?php

use App\Models\Prospect;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Fixes /prospecting (2026-08-07):
 *  1. per_page whitelist [20,50,100,200] — antes capado em 20 fixo.
 *  2. Checkbox visivel pra TODOS os prospects (era so analyzed+contato)
 *     pra permitir bulk delete de raw/sem contato.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function ppdUser(): User
{
    $t = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create(['tenant_id' => $t->id, 'role' => 'ngo']);
}

function ppdProspect(int $tenantId, array $attrs = []): Prospect
{
    return Prospect::create(array_merge([
        'tenant_id'    => $tenantId,
        'company_name' => 'Empresa X',
        'status'       => 'raw',
        'lead_score'   => 50,
    ], $attrs));
}

it('per_page default e 20', function () {
    $u = ppdUser();
    for ($i = 0; $i < 25; $i++) ppdProspect($u->tenant_id, ['company_name' => "Emp {$i}"]);

    $resp = $this->actingAs($u)->get('/prospecting')->assertStatus(200);
    // 20 na primeira pagina + link pra pagina 2
    expect(substr_count($resp->getContent(), 'class="prospect-cb"'))->toBe(20);
});

it('per_page=100 mostra ate 100 resultados', function () {
    $u = ppdUser();
    for ($i = 0; $i < 75; $i++) ppdProspect($u->tenant_id, ['company_name' => "Emp {$i}"]);

    $resp = $this->actingAs($u)->get('/prospecting?per_page=100')->assertStatus(200);
    expect(substr_count($resp->getContent(), 'class="prospect-cb"'))->toBe(75);
});

it('per_page fora do whitelist cai no default 20', function () {
    $u = ppdUser();
    for ($i = 0; $i < 30; $i++) ppdProspect($u->tenant_id, ['company_name' => "Emp {$i}"]);

    $resp = $this->actingAs($u)->get('/prospecting?per_page=9999')->assertStatus(200);
    expect(substr_count($resp->getContent(), 'class="prospect-cb"'))->toBe(20);
});

it('checkbox aparece pra prospect raw sem telefone (antes ficava sem)', function () {
    $u = ppdUser();
    $p = ppdProspect($u->tenant_id, ['status' => 'raw', 'phone' => null, 'email' => null]);

    $resp = $this->actingAs($u)->get('/prospecting')->assertStatus(200);
    $resp->assertSee('class="prospect-cb"', false);
    $resp->assertSee('value="' . $p->id . '"', false);
});

it('bulk-delete funciona pra prospects raw sem contato', function () {
    $u = ppdUser();
    $p1 = ppdProspect($u->tenant_id, ['status' => 'raw']);
    $p2 = ppdProspect($u->tenant_id, ['status' => 'analyzed', 'phone' => '11999998888']);

    $this->actingAs($u)
        ->from('/prospecting')
        ->delete('/prospecting/bulk-delete', [
            'prospect_ids_raw' => "{$p1->id},{$p2->id}",
        ])
        ->assertRedirect();

    expect(Prospect::withoutGlobalScope('tenant')->count())->toBe(0);
});

it('selector de per_page aparece no HTML com valor atual', function () {
    $u = ppdUser();
    ppdProspect($u->tenant_id);

    $resp = $this->actingAs($u)->get('/prospecting?per_page=50')->assertStatus(200);
    $resp->assertSee('Itens por página');
    $resp->assertSee('changePerPage');
    $resp->assertSee('value="50" selected', false);
});
