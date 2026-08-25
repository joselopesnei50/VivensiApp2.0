<?php

use App\Models\BrunoLesson;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Trava Fase 3 do Bruno (RAG lite via lessons taggeadas): retrieve por
 * match de tag, isolamento tenant vs global, admin CRUD, injecao no prompt.
 */

it('retrieveRelevant devolve licoes com maior match de tag primeiro', function () {
    BrunoLesson::create([
        'title' => 'Preco alto',
        'tags' => ['objecao-preco'],
        'situation' => 'objecao preco pura',
        'bruno_replied' => 'resposta A',
    ]);
    BrunoLesson::create([
        'title' => 'ONG pequena preco',
        'tags' => ['ong-pequena', 'objecao-preco', 'objecao-verba'],
        'situation' => 'ong pequena obj preco+verba',
        'bruno_replied' => 'resposta B',
    ]);
    BrunoLesson::create([
        'title' => 'Radar editais',
        'tags' => ['radar-editais'],
        'situation' => 'radar editais',
        'bruno_replied' => 'resposta C',
    ]);

    $lessons = BrunoLesson::retrieveRelevant(null, ['objecao-preco', 'ong-pequena'], 3);

    expect($lessons)->toHaveCount(2);
    expect($lessons->first()->title)->toBe('ONG pequena preco'); // match 2
    expect($lessons->last()->title)->toBe('Preco alto');           // match 1
});

it('retrieveRelevant sem tags devolve top N recentes', function () {
    for ($i = 1; $i <= 5; $i++) {
        BrunoLesson::create([
            'title' => "Lesson {$i}",
            'tags' => [],
            'situation' => 's',
            'bruno_replied' => 'r',
        ]);
    }

    $lessons = BrunoLesson::retrieveRelevant(null, [], 3);
    expect($lessons)->toHaveCount(3);
});

it('retrieveRelevant considera licoes globais + do tenant', function () {
    $t1 = Tenant::factory()->create();
    $t2 = Tenant::factory()->create();

    BrunoLesson::create(['title' => 'Global', 'tenant_id' => null, 'tags' => ['x'], 'situation' => 's', 'bruno_replied' => 'r']);
    BrunoLesson::create(['title' => 'T1', 'tenant_id' => $t1->id, 'tags' => ['x'], 'situation' => 's', 'bruno_replied' => 'r']);
    BrunoLesson::create(['title' => 'T2', 'tenant_id' => $t2->id, 'tags' => ['x'], 'situation' => 's', 'bruno_replied' => 'r']);

    $lessons = BrunoLesson::retrieveRelevant($t1->id, ['x'], 5);

    $titles = $lessons->pluck('title')->toArray();
    expect($titles)->toContain('Global');
    expect($titles)->toContain('T1');
    expect($titles)->not->toContain('T2');
});

it('retrieveRelevant filtra inativas', function () {
    BrunoLesson::create(['title' => 'A', 'tags' => ['x'], 'situation' => 's', 'bruno_replied' => 'r', 'active' => true]);
    BrunoLesson::create(['title' => 'B', 'tags' => ['x'], 'situation' => 's', 'bruno_replied' => 'r', 'active' => false]);

    $lessons = BrunoLesson::retrieveRelevant(null, ['x'], 5);
    expect($lessons->pluck('title')->toArray())->toBe(['A']);
});

it('POST /admin/bruno/lessons cria licao com tags normalizadas', function () {
    // super_admin passa por RequireTwoFactor middleware — precisa flag na sessao
    $admin = User::factory()->create([
        'role'                    => 'super_admin',
        'two_factor_confirmed_at' => now(),
    ]);
    session(['2fa_verified' => true]);

    $this->actingAs($admin)->post('/admin/bruno/lessons', [
        'title' => 'Teste',
        'tags' => 'ObjeCao-Preco, ONG-Pequena , objecao-preco', // dedup + lowercase
        'situation' => 'situ',
        'bruno_replied' => 'resp',
    ])->assertRedirect(route('admin.bruno.lessons.index'));

    $l = BrunoLesson::where('title', 'Teste')->first();
    expect($l)->not->toBeNull();
    expect($l->tags)->toBe(['objecao-preco', 'ong-pequena']); // dedup + trim + lowercase
    expect($l->active)->toBeTrue();
});

it('non-admin bloqueado no CRUD de lessons', function () {
    $user = User::factory()->create(['role' => 'common']);
    // qualquer redirect serve — 2fa OU super_admin bloqueia; ambos = redirect
    $r = $this->actingAs($user)->get('/admin/bruno/lessons');
    expect(in_array($r->status(), [302, 403]))->toBeTrue();
});
