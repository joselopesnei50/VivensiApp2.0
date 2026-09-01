<?php

use App\Models\LandingPage;
use App\Models\LandingPageSection;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * P3 melhorias builder — endpoint generico de upload de asset por section.
 * Kinds aceitos: logo, background, image. Whitelist estreita evita escrita
 * em campos arbitrarios.
 */

uses(RefreshDatabase::class);

function uaEnv(): array
{
    $t = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
    $u = User::factory()->create(['tenant_id' => $t->id, 'role' => 'ngo']);
    $lp = LandingPage::create([
        'tenant_id' => $t->id, 'title' => 'X', 'slug' => 'x-' . uniqid(),
        'status' => 'draft', 'settings' => [],
    ]);
    $section = LandingPageSection::create([
        'landing_page_id' => $lp->id, 'type' => 'hero_image',
        'sort_order' => 1, 'content' => [],
    ]);
    return [$t, $u, $lp, $section];
}

it('background upload grava em content.background_url + storage_path', function () {
    Storage::fake('public');
    [$t, $u, $lp, $s] = uaEnv();

    $this->actingAs($u)
        ->post("/ngo/landing-pages/{$lp->id}/sections/{$s->id}/asset/background", [
            'file' => UploadedFile::fake()->create('bg.jpg', 100, 'image/jpeg'),
        ])
        ->assertOk()
        ->assertJson(['success' => true, 'kind' => 'background']);

    $s->refresh();
    expect($s->content['background_url'])->toContain('landing-pages');
    expect($s->content['background_storage_path'])->toContain('background');
    Storage::disk('public')->assertExists($s->content['background_storage_path']);
});

it('image upload grava em content.image_url', function () {
    Storage::fake('public');
    [$t, $u, $lp, $s] = uaEnv();

    $this->actingAs($u)
        ->post("/ngo/landing-pages/{$lp->id}/sections/{$s->id}/asset/image", [
            'file' => UploadedFile::fake()->create('x.png', 100, 'image/png'),
        ])->assertOk();

    $s->refresh();
    expect($s->content['image_url'])->toContain('landing-pages');
});

it('rota antiga /logo continua funcionando (backward compat)', function () {
    Storage::fake('public');
    [$t, $u, $lp, $s] = uaEnv();

    $this->actingAs($u)
        ->post("/ngo/landing-pages/{$lp->id}/sections/{$s->id}/logo", [
            'file' => UploadedFile::fake()->create('l.png', 50, 'image/png'),
        ])->assertOk();

    $s->refresh();
    expect($s->content['logo_url'])->toContain('landing-pages');
});

it('kind fora do whitelist retorna 404 (route constraint)', function () {
    Storage::fake('public');
    [$t, $u, $lp, $s] = uaEnv();

    $this->actingAs($u)
        ->withHeaders(['Accept' => 'application/json'])
        ->post("/ngo/landing-pages/{$lp->id}/sections/{$s->id}/asset/senha", [
            'file' => UploadedFile::fake()->create('x.png', 50, 'image/png'),
        ])
        ->assertStatus(404); // Route regex where('kind', 'logo|background|image') nao casa
});
