<?php

use App\Models\LandingPage;
use App\Models\LandingPageSection;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * P2 melhorias builder — upload de logo pro header_nav (ou qualquer section
 * com campo logo_url).
 */

uses(RefreshDatabase::class);

function ulEnv(): array
{
    $t = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
    $u = User::factory()->create(['tenant_id' => $t->id, 'role' => 'ngo']);
    return [$t, $u];
}

function ulLpSection(Tenant $t): array
{
    $lp = LandingPage::create([
        'tenant_id' => $t->id,
        'title'     => 'X',
        'slug'      => 'x-' . uniqid(),
        'status'    => 'draft',
        'settings'  => [],
    ]);
    $section = LandingPageSection::create([
        'landing_page_id' => $lp->id,
        'type'            => 'header_nav',
        'sort_order'      => 1,
        'content'         => [],
    ]);
    return [$lp, $section];
}

it('upload logo grava arquivo e atualiza section content.logo_url', function () {
    Storage::fake('public');
    [$t, $u] = ulEnv();
    [$lp, $section] = ulLpSection($t);

    $file = UploadedFile::fake()->create('logo.png', 50, 'image/png');

    $this->actingAs($u)
        ->post("/ngo/landing-pages/{$lp->id}/sections/{$section->id}/logo", ['file' => $file])
        ->assertOk()
        ->assertJson(['success' => true]);

    $section->refresh();
    expect($section->content['logo_url'])->toContain('landing-pages');
    expect($section->content['logo_url'])->toContain((string) $t->id);
    expect($section->content['logo_storage_path'])->toContain('landing-pages');
    Storage::disk('public')->assertExists($section->content['logo_storage_path']);
});

it('upload novo logo remove o antigo do storage', function () {
    Storage::fake('public');
    [$t, $u] = ulEnv();
    [$lp, $section] = ulLpSection($t);

    // Primeiro upload
    $this->actingAs($u)->post("/ngo/landing-pages/{$lp->id}/sections/{$section->id}/logo", [
        'file' => UploadedFile::fake()->create('logo-old.png', 50, 'image/png'),
    ])->assertOk();
    $section->refresh();
    $oldPath = $section->content['logo_storage_path'];
    Storage::disk('public')->assertExists($oldPath);

    // Segundo upload — deve remover o velho
    $this->actingAs($u)->post("/ngo/landing-pages/{$lp->id}/sections/{$section->id}/logo", [
        'file' => UploadedFile::fake()->create('logo-new.png', 50, 'image/png'),
    ])->assertOk();
    $section->refresh();
    $newPath = $section->content['logo_storage_path'];

    expect($newPath)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($newPath);
});

it('rejeita section de outra LP (cross-lp check)', function () {
    Storage::fake('public');
    [$t, $u] = ulEnv();
    [$lpA, $sectionA] = ulLpSection($t);
    [$lpB, $sectionB] = ulLpSection($t);

    // URL usa lpA mas section eh de lpB — deve 404
    $this->actingAs($u)
        ->post("/ngo/landing-pages/{$lpA->id}/sections/{$sectionB->id}/logo", [
            'file' => UploadedFile::fake()->create('x.png', 50, 'image/png'),
        ])
        ->assertStatus(404);
});

it('rejeita LP de outro tenant (404)', function () {
    Storage::fake('public');
    [$tA, $uA] = ulEnv();
    $tB = Tenant::factory()->create(['subscription_status' => 'active']);
    [$lpB, $sectionB] = ulLpSection($tB);

    $this->actingAs($uA)
        ->post("/ngo/landing-pages/{$lpB->id}/sections/{$sectionB->id}/logo", [
            'file' => UploadedFile::fake()->create('x.png', 50, 'image/png'),
        ])
        ->assertStatus(404);
});

it('rejeita arquivo maior que 2MB', function () {
    Storage::fake('public');
    [$t, $u] = ulEnv();
    [$lp, $section] = ulLpSection($t);

    $this->actingAs($u)
        ->withHeaders(['Accept' => 'application/json'])
        ->post("/ngo/landing-pages/{$lp->id}/sections/{$section->id}/logo", [
            'file' => UploadedFile::fake()->create('big.png', 3000, 'image/png'), // 3MB
        ])
        ->assertStatus(422);
});

it('rejeita arquivo nao-imagem', function () {
    Storage::fake('public');
    [$t, $u] = ulEnv();
    [$lp, $section] = ulLpSection($t);

    $this->actingAs($u)
        ->withHeaders(['Accept' => 'application/json'])
        ->post("/ngo/landing-pages/{$lp->id}/sections/{$section->id}/logo", [
            'file' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ])
        ->assertStatus(422);
});
