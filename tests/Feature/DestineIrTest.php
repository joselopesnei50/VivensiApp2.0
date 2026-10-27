<?php

use App\Models\DestineIrPartner;
use App\Models\DestineIrSetting;
use App\Models\Tenant;
use App\Models\User;

/**
 * Página pública "Destine seu IR" + CRUD admin + rastreio por entidade.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function dirSuperAdmin(): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create([
        'tenant_id'               => $tenant->id,
        'role'                    => 'super_admin',
        'two_factor_confirmed_at' => now(),
    ]);
}

function dirRegularUser(): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'ngo']);
}

function dirPublish(): DestineIrSetting
{
    $s = DestineIrSetting::current();
    $s->update(['is_published' => true]);
    return $s;
}

// ── Página pública ────────────────────────────────────────────────────────────

it('rascunho (nao publicada) retorna 404', function () {
    DestineIrSetting::current(); // seed defaults, is_published=false
    $this->get('/destine-ir')->assertStatus(404);
});

it('publicada abre com 200 e meta OG presentes', function () {
    dirPublish();
    $this->get('/destine-ir')
        ->assertOk()
        ->assertSee('og:title', false)
        ->assertSee('og:description', false)
        ->assertSee('og:url', false)
        ->assertSee('Deixe seu imposto em Araraquara', false);
});

it('CNPJ do fundo aparece com botao de copiar', function () {
    dirPublish();
    $this->get('/destine-ir')
        ->assertOk()
        ->assertSee('17.837.230/0001-02')
        ->assertSee('Copiar CNPJ');
});

it('slug de entidade inexistente retorna 404', function () {
    dirPublish();
    $this->get('/destine-ir/entidade-que-nao-existe')->assertStatus(404);
});

it('slug de entidade inativa retorna 404', function () {
    dirPublish();
    DestineIrPartner::create([
        'name' => 'Inativa', 'slug' => 'inativa', 'is_active' => false,
    ]);
    $this->get('/destine-ir/inativa')->assertStatus(404);
});

it('acesso por slug incrementa visits_count da entidade', function () {
    dirPublish();
    $partner = DestineIrPartner::create([
        'name' => 'Casa Teste', 'slug' => 'casa-teste', 'is_active' => true,
    ]);

    $this->get('/destine-ir/casa-teste')->assertOk()->assertSee('Casa Teste');

    $partner->refresh();
    expect($partner->visits_count)->toBe(1);
    expect($partner->last_visit_at)->not->toBeNull();
});

it('mesma sessao nao conta visita duplicada', function () {
    dirPublish();
    $partner = DestineIrPartner::create([
        'name' => 'Casa X', 'slug' => 'casa-x', 'is_active' => true,
    ]);

    $this->get('/destine-ir/casa-x')->assertOk();
    $this->get('/destine-ir/casa-x')->assertOk();

    expect($partner->fresh()->visits_count)->toBe(1);
});

it('seção de incentivo com palavra de destaque aparece', function () {
    dirPublish();
    $this->get('/destine-ir')
        ->assertOk()
        ->assertSee('DEIXE AQUI', false)
        ->assertSee('Destinar agora', false);
});

// ── Admin — gate ──────────────────────────────────────────────────────────────

it('nao-super_admin nao acessa editor', function () {
    $this->actingAs(dirRegularUser())
        ->get('/admin/destine-ir')
        ->assertStatus(403);
});

it('super_admin abre editor', function () {
    $this->actingAs(dirSuperAdmin())->withSession(['2fa_verified' => true])
        ->get('/admin/destine-ir')
        ->assertOk()
        ->assertSee('Destine seu IR');
});

it('super_admin abre dashboard de metricas', function () {
    $this->actingAs(dirSuperAdmin())->withSession(['2fa_verified' => true])
        ->get('/admin/destine-ir/metrics')
        ->assertOk()
        ->assertSee('Métricas por entidade parceira', false);
});

// ── Admin — CRUD partner ──────────────────────────────────────────────────────

it('cria entidade com slug auto-gerado', function () {
    $this->actingAs(dirSuperAdmin())->withSession(['2fa_verified' => true])
        ->post('/admin/destine-ir/partners', [
            'name' => 'Casa dos Pequeninos',
        ])
        ->assertRedirect();

    $p = DestineIrPartner::first();
    expect($p->name)->toBe('Casa dos Pequeninos');
    expect($p->slug)->toBe('casa-dos-pequeninos');
    expect($p->is_active)->toBeTrue();
});

// ── Vídeo YouTube ─────────────────────────────────────────────────────────────

it('embute iframe do YouTube quando video_youtube_url preenchido', function () {
    $s = dirPublish();
    $s->update(['video_youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);

    $this->get('/destine-ir')
        ->assertOk()
        ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false);
});

it('aceita link curto youtu.be', function () {
    $s = dirPublish();
    $s->update(['video_youtube_url' => 'https://youtu.be/dQw4w9WgXcQ']);

    $this->get('/destine-ir')
        ->assertOk()
        ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false);
});

it('youtube_id null retorna null (link invalido)', function () {
    $s = DestineIrSetting::current();
    $s->update(['video_youtube_url' => 'https://vimeo.com/123']);
    expect($s->fresh()->youtube_id)->toBeNull();
});

// ── Callout de indicação da entidade ──────────────────────────────────────────

it('callout da entidade aparece quando vem via slug', function () {
    dirPublish();
    DestineIrPartner::create(['name' => 'Lar Esperanca', 'slug' => 'lar-esperanca', 'is_active' => true]);

    $resp = $this->get('/destine-ir/lar-esperanca');
    $resp->assertOk()
        ->assertSee('Indicação de entidade', false)
        ->assertSee('Preparar e-mail', false)
        ->assertSee('mailto:', false)
        ->assertSee('Lar Esperanca', false);
});

it('callout nao aparece na rota raiz sem partner', function () {
    dirPublish();
    $this->get('/destine-ir')
        ->assertOk()
        ->assertDontSee('Indicação de entidade', false)
        ->assertDontSee('Preparar e-mail', false);
});

it('publica a pagina via update', function () {
    $this->actingAs(dirSuperAdmin())->withSession(['2fa_verified' => true])
        ->put('/admin/destine-ir', [
            'is_published'   => '1',
            'hero_title'     => 'Novo titulo de teste',
            'hero_subtitle'  => 'Novo subtitulo de teste',
            'fund_name'      => 'Fundo Teste',
            'fund_cnpj'      => '00.000.000/0001-00',
            'fund_email'     => 'teste@teste.com',
            'council_name'   => 'Conselho Teste',
        ])
        ->assertRedirect();

    expect(DestineIrSetting::current()->is_published)->toBeTrue();
    expect(DestineIrSetting::current()->hero_title)->toBe('Novo titulo de teste');
});
