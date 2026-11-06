<?php

use App\Models\Beneficiary;
use App\Models\Tenant;
use App\Models\User;

/**
 * Análise dos módulos da ONG (06/11/2026) — pacote 2: textos em português.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function p2Org(): User
{
    $t = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
    return User::factory()->create(['tenant_id' => $t->id, 'role' => 'ngo']);
}

it('mensagens de validação saem em português', function () {
    app()->setLocale('pt_BR');
    $v = validator(['name' => ''], ['name' => 'required']);
    expect($v->errors()->first('name'))->toBe('O campo nome é obrigatório.');
    expect(__('pagination.next'))->toBe('Próxima &raquo;')
        ->and(__('Showing'))->toBe('Mostrando');
});

it('ficha do beneficiário mostra o status em português', function () {
    $u = p2Org();
    $b = Beneficiary::create(['tenant_id' => $u->tenant_id, 'name' => 'Ana', 'status' => 'graduated']);

    $this->actingAs($u)->withHeaders(['Accept-Language' => 'pt-BR'])
        ->get('/ngo/beneficiaries/' . $b->id)
        ->assertOk()
        ->assertSee('Graduado')
        ->assertDontSee('Graduated')
        ->assertDontSee('GRADUATED');
});

it('telas dos módulos marcam o container para o tema do Bootstrap', function () {
    foreach (['ngo/beneficiaries/index', 'ngo/conformidade/dashboard', 'ngo/transparency/index', 'projects/classes/show', 'class-sessions/show'] as $v) {
        expect(file_get_contents(resource_path("views/{$v}.blade.php")))->toContain('data-theme-scope');
    }
    expect(file_get_contents(resource_path('views/layouts/app.blade.php')))->toContain("el.setAttribute('data-bs-theme', 'dark')");
});
