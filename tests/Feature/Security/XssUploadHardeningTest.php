<?php

use App\Models\LandingPage;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Auditoria do painel (26/11/2026), itens 1–4:
 *  - SVG com script não entra mais na pasta pública (rifa, landing, linha do tempo);
 *  - nome de campo com HTML vindo de landing pública é descartado;
 *  - sino de notificações, busca global e detalhes do inscrito mostram texto, não HTML.
 */
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function xuSvg(): UploadedFile
{
    $p = tempnam(sys_get_temp_dir(), 'svg');
    file_put_contents($p, '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg"><script>alert(document.domain)</script></svg>');
    return new UploadedFile($p, 'logo.svg', 'image/svg+xml', null, true);
}

function xuAdmin(): array
{
    $t = Tenant::factory()->create(['type' => 'ngo', 'subscription_status' => 'active']);
    $u = User::factory()->create(['tenant_id' => $t->id, 'role' => 'ngo', 'status' => 'active']);
    return [$t, $u];
}

function xuPublicFiles(string $ext): array
{
    return collect(Storage::disk('public')->allFiles())->filter(fn ($f) => str_ends_with($f, '.' . $ext))->values()->all();
}

it('rifa recusa SVG e grava PNG com a extensão do conteúdo', function () {
    Storage::fake('public');
    [, $u] = xuAdmin();
    $base = ['title' => 'Rifa', 'description' => 'd', 'ticket_price' => 5, 'total_tickets' => 10];

    $this->actingAs($u)->post('/raffles', $base + ['image' => xuSvg()])->assertSessionHasErrors('image');
    expect(xuPublicFiles('svg'))->toBe([]);

    $this->actingAs($u)->post('/raffles', $base + ['image' => UploadedFile::fake()->image('foto.png')]);
    expect(xuPublicFiles('png'))->toHaveCount(1);
});

it('landing recusa SVG na imagem de compartilhamento e no favicon', function () {
    Storage::fake('public');
    [$t, $u] = xuAdmin();
    $lp = LandingPage::create(['tenant_id' => $t->id, 'title' => 'L', 'slug' => 'l-' . uniqid(), 'status' => 'published']);

    $this->actingAs($u)->postJson("/ngo/landing-pages/{$lp->id}/upload-og-image", ['file' => xuSvg()])->assertStatus(422);
    $this->actingAs($u)->postJson("/ngo/landing-pages/{$lp->id}/upload-favicon", ['file' => xuSvg()])->assertStatus(422);
    expect(xuPublicFiles('svg'))->toBe([]);

    $this->actingAs($u)->postJson("/ngo/landing-pages/{$lp->id}/upload-og-image", ['file' => UploadedFile::fake()->image('og.png')])->assertOk();
    expect(xuPublicFiles('png'))->toHaveCount(1);
});

it('linha do tempo do projeto recusa SVG', function () {
    Storage::fake('public');
    [$t, $u] = xuAdmin();
    $p = Project::factory()->create(['tenant_id' => $t->id]);

    $this->actingAs($u)->post("/projects/{$p->id}/timeline", ['title' => 'Marco', 'type' => 'photo', 'media' => xuSvg()])
        ->assertSessionHasErrors('media');
    expect(xuPublicFiles('svg'))->toBe([]);
});

it('lead público descarta nome de campo com HTML e mantém os normais', function () {
    $t = Tenant::factory()->create(['subscription_status' => 'active']);
    $lp = LandingPage::create(['tenant_id' => $t->id, 'title' => 'L', 'slug' => 'lx-' . uniqid(), 'status' => 'published']);

    $this->post("/lp/{$lp->slug}/lead", [
        'name' => 'Maria', 'email' => 'maria@exemplo.com', 'consent_given' => 1,
        '<img src=x onerror=alert(1)>' => '1',
        'como_conheceu' => 'Instagram',
    ]);

    $extra = json_decode((string) DB::table('landing_page_leads')->value('extra_data'), true);
    expect($extra)->toHaveKey('como_conheceu');
    expect(array_keys($extra))->not->toContain('<img src=x onerror=alert(1)>');
});

it('sino, busca e detalhes do inscrito montam o HTML escapando o texto', function () {
    [, $u] = xuAdmin();
    $html = $this->actingAs($u)->get('/dashboard')->getContent();

    expect($html)->not->toContain('${n.title}')->not->toContain('${n.message}');
    expect($html)->toContain('t.textContent = String(n.title');
    expect($html)->toContain('${gsEsc(item.label)}')->not->toContain('${item.label}</div>');

    $show = file_get_contents(resource_path('views/projects/show.blade.php'));
    expect($show)->toContain('lead_extra_top_keys.map(pdmEsc)')->toContain('pdmRow(pdmEsc(pretty)');
});

it('comando lista SVG/HTML já gravados na pasta pública', function () {
    Storage::fake('public');
    Storage::disk('public')->put('raffles/velho.svg', '<svg/>');
    Storage::disk('public')->put('raffles/ok.png', 'x');

    $this->artisan('security:uploads-publicos')
        ->expectsOutputToContain('1 arquivo(s) que o navegador pode executar')
        ->expectsOutputToContain('Nada foi apagado')
        ->assertExitCode(0);
    expect(Storage::disk('public')->exists('raffles/velho.svg'))->toBeTrue();

    $this->artisan('security:uploads-publicos', ['--apagar' => true])->expectsOutputToContain('1 arquivo(s) apagado(s)');
    expect(Storage::disk('public')->exists('raffles/velho.svg'))->toBeFalse();
    expect(Storage::disk('public')->exists('raffles/ok.png'))->toBeTrue();
});
