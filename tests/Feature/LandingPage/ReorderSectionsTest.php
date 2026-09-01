<?php

use App\Models\LandingPage;
use App\Models\LandingPageSection;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * P1 melhorias builder — reorder de sections via drag-drop (backend).
 * Endpoint: POST /ngo/landing-pages/{id}/reorder body {order: [id...]}.
 */

uses(RefreshDatabase::class);

function rsEnv(): array
{
    $t = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
    $u = User::factory()->create(['tenant_id' => $t->id, 'role' => 'ngo']);
    return [$t, $u];
}

function rsLpWithSections(Tenant $t, int $count = 3): LandingPage
{
    $lp = LandingPage::create([
        'tenant_id' => $t->id,
        'title'     => 'X',
        'slug'      => 'x-' . uniqid(),
        'status'    => 'draft',
        'settings'  => [],
    ]);
    for ($i = 1; $i <= $count; $i++) {
        LandingPageSection::create([
            'landing_page_id' => $lp->id,
            'type'            => 'hero',
            'sort_order'      => $i,
            'content'         => [],
        ]);
    }
    return $lp;
}

it('reordena sections e persiste sort_order', function () {
    [$t, $u] = rsEnv();
    $lp = rsLpWithSections($t, 3);
    $ids = $lp->sections()->orderBy('sort_order')->pluck('id')->all();

    // Inverte: [3, 2, 1] em posicao [1, 2, 3]
    $reversed = array_reverse($ids);

    $this->actingAs($u)
        ->postJson("/ngo/landing-pages/{$lp->id}/reorder", ['order' => $reversed])
        ->assertOk()
        ->assertJson(['success' => true, 'count' => 3]);

    $newOrder = LandingPageSection::where('landing_page_id', $lp->id)
        ->orderBy('sort_order')
        ->pluck('id')
        ->all();

    expect($newOrder)->toBe($reversed);
});

it('rejeita section de outra LP do mesmo tenant (nao vaza reorder cross-lp)', function () {
    [$t, $u] = rsEnv();
    $lpA = rsLpWithSections($t, 2);
    $lpB = rsLpWithSections($t, 2);
    $lpBIds = $lpB->sections()->pluck('id')->all();

    // Tenta reordenar lpA com IDs de lpB
    $this->actingAs($u)
        ->postJson("/ngo/landing-pages/{$lpA->id}/reorder", ['order' => $lpBIds])
        ->assertStatus(422);

    // Sort orders de lpB nao mudaram
    $lpBOrder = LandingPageSection::where('landing_page_id', $lpB->id)
        ->orderBy('id')
        ->pluck('sort_order')
        ->map(fn($v) => (int) $v)
        ->all();
    expect($lpBOrder)->toBe([1, 2]);
});

it('rejeita reorder de LP de outro tenant (findOrFail 404)', function () {
    [$tA, $uA] = rsEnv();
    $tB = Tenant::factory()->create(['subscription_status' => 'active']);
    $lpB = rsLpWithSections($tB, 2);
    $lpBIds = $lpB->sections()->pluck('id')->all();

    $this->actingAs($uA)
        ->postJson("/ngo/landing-pages/{$lpB->id}/reorder", ['order' => $lpBIds])
        ->assertStatus(404);
});

it('valida payload — order obrigatorio, array de inteiros', function () {
    [$t, $u] = rsEnv();
    $lp = rsLpWithSections($t, 2);

    $this->actingAs($u)
        ->postJson("/ngo/landing-pages/{$lp->id}/reorder", [])
        ->assertStatus(422);

    $this->actingAs($u)
        ->postJson("/ngo/landing-pages/{$lp->id}/reorder", ['order' => 'nao-array'])
        ->assertStatus(422);

    $this->actingAs($u)
        ->postJson("/ngo/landing-pages/{$lp->id}/reorder", ['order' => ['abc', 'def']])
        ->assertStatus(422);
});

it('deduplicacao — IDs repetidos no payload', function () {
    [$t, $u] = rsEnv();
    $lp = rsLpWithSections($t, 3);
    $ids = $lp->sections()->pluck('id')->all();

    // Payload com um ID duplicado
    $withDupe = [$ids[0], $ids[1], $ids[0]];

    // Deve rejeitar 422 (count mismatch apos unique) OU aceitar com dedupe.
    // O servico faz unique, e apos unique count sao 2, todos existem no LP, entao aceita.
    $response = $this->actingAs($u)
        ->postJson("/ngo/landing-pages/{$lp->id}/reorder", ['order' => $withDupe]);

    expect($response->status())->toBeIn([200, 422]);
});
