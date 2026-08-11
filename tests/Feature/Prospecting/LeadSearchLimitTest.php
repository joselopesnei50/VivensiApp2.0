<?php

use App\Models\Prospect;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LeadSearchService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * Cobre a mudança de 2026-08-10:
 * — LeadSearchService::search paga Serper Maps em N páginas até bater $limit
 * — searchWeb passa num=$limit direto (Serper /search aceita até 100)
 * — dedup por título+endereço (Serper às vezes repete places entre páginas)
 */

beforeEach(function () {
    SystemSetting::create(['key' => 'serper_api_key', 'value' => 'fake-key-test']);
    Queue::fake(); // isola ProcessProspect + ScrapeProspectEmailJob (não gasta Gemini)
});

function fakeMapsPage(int $page, int $count, string $prefix = 'Loja'): array
{
    return array_map(fn ($i) => [
        'title'       => "$prefix $prefix P{$page}-{$i}",
        'address'     => "Rua {$i}, Sao Paulo",
        'phoneNumber' => '1199999' . str_pad((string)($page * 100 + $i), 4, '0', STR_PAD_LEFT),
        'rating'      => 4.5,
        'ratingCount' => 100,
    ], range(1, $count));
}

it('search com limit=20 faz 1 chamada Serper Maps', function () {
    Http::fake([
        'google.serper.dev/maps' => Http::response(['places' => fakeMapsPage(1, 20)], 200),
    ]);

    $tenant = Tenant::factory()->create();
    $service = app(LeadSearchService::class);
    $r = $service->search('restaurante', 'Sao Paulo', $tenant->id, 20);

    Http::assertSentCount(1);
    expect($r['new'])->toBe(20);
    expect(Prospect::where('tenant_id', $tenant->id)->count())->toBe(20);
});

it('search com limit=60 faz 3 chamadas paginadas e cria 60 leads', function () {
    Http::fake([
        'google.serper.dev/maps' => Http::sequence()
            ->push(['places' => fakeMapsPage(1, 20)], 200)
            ->push(['places' => fakeMapsPage(2, 20)], 200)
            ->push(['places' => fakeMapsPage(3, 20)], 200),
    ]);

    $tenant = Tenant::factory()->create();
    $service = app(LeadSearchService::class);
    $r = $service->search('restaurante', 'Sao Paulo', $tenant->id, 60);

    Http::assertSentCount(3);
    expect($r['new'])->toBe(60);
    expect(Prospect::where('tenant_id', $tenant->id)->count())->toBe(60);
});

it('search para de paginar se Serper devolver batch vazio antes do limit', function () {
    Http::fake([
        'google.serper.dev/maps' => Http::sequence()
            ->push(['places' => fakeMapsPage(1, 20)], 200)
            ->push(['places' => []], 200),
    ]);

    $tenant = Tenant::factory()->create();
    $service = app(LeadSearchService::class);
    $r = $service->search('restaurante', 'Sao Paulo', $tenant->id, 100);

    Http::assertSentCount(2);
    expect($r['new'])->toBe(20);
});

it('search deduplica places repetidos entre paginas por titulo+endereco', function () {
    $duplicate = [
        'title'       => 'Restaurante X',
        'address'     => 'Av Paulista 1000, Sao Paulo',
        'phoneNumber' => '11999999999',
    ];

    Http::fake([
        'google.serper.dev/maps' => Http::sequence()
            ->push(['places' => [$duplicate, ...fakeMapsPage(1, 19)]], 200)
            ->push(['places' => [$duplicate, ...fakeMapsPage(2, 19)]], 200),
    ]);

    $tenant = Tenant::factory()->create();
    $service = app(LeadSearchService::class);
    $r = $service->search('restaurante', 'Sao Paulo', $tenant->id, 40);

    // 19 + 1 duplicado + 19 = 39 unicos (o duplicado nao repete)
    expect($r['new'])->toBe(39);
});

it('searchWeb passa num=limit direto e cria N leads', function () {
    Http::fake([
        'google.serper.dev/search' => Http::response([
            'organic' => array_map(fn ($i) => [
                'title'   => "Empresa Web {$i}",
                'link'    => "https://exemplo{$i}.com.br",
                'snippet' => 'Snippet ' . $i,
            ], range(1, 50)),
        ], 200),
    ]);

    $tenant = Tenant::factory()->create();
    $service = app(LeadSearchService::class);
    $r = $service->searchWeb('construtora', $tenant->id, 50);

    Http::assertSent(function ($req) {
        return $req->url() === 'https://google.serper.dev/search'
            && $req->data()['num'] === 50;
    });
    expect($r['new'])->toBe(50);
});

it('endpoint /prospecting/search aceita limit e repassa pro service', function () {
    Http::fake([
        'google.serper.dev/maps' => Http::sequence()
            ->push(['places' => fakeMapsPage(1, 20)], 200)
            ->push(['places' => fakeMapsPage(2, 20)], 200),
    ]);

    $tenant = Tenant::factory()->create(['type' => 'manager']);
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);

    $resp = $this->actingAs($user)->post('/prospecting/search', [
        'mode'     => 'maps',
        'term'     => 'restaurante',
        'location' => 'Sao Paulo',
        'limit'    => 40,
    ]);

    $resp->assertRedirect();
    expect(Prospect::where('tenant_id', $tenant->id)->count())->toBe(40);
});

it('endpoint /prospecting/search rejeita limit fora da whitelist', function () {
    $tenant = Tenant::factory()->create(['type' => 'manager']);
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);

    $resp = $this->actingAs($user)->post('/prospecting/search', [
        'mode'     => 'maps',
        'term'     => 'restaurante',
        'location' => 'Sao Paulo',
        'limit'    => 999,
    ]);

    $resp->assertSessionHasErrors('limit');
});
