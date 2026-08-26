<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Trava upload de imagem pra HTML de campanha de email:
 * POST /admin/email-campaigns/upload-image devolve URL publica absoluta
 * que Brevo consegue baixar. Aceita jpg/png/webp/gif, max 5MB.
 */

beforeEach(function () {
    Storage::fake('public');
});

it('upload valido devolve url publica absoluta', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => 'super_admin',
        'two_factor_confirmed_at' => now(),
    ]);
    session(['2fa_verified' => true]);

    // NAO usar ->image() aqui — depende de ext GD do PHP.
    // ->create() com mime + extensao correta valida a regra 'mimes:png'.
    $file = UploadedFile::fake()->create('logo.png', 100, 'image/png');

    $r = $this->actingAs($user)->post('/admin/email-campaigns/upload-image', [
        'image' => $file,
    ]);

    $r->assertOk();
    $r->assertJsonStructure(['url']);
    $url = $r->json('url');
    expect($url)->toContain('/storage/email_campaigns_uploads/');
    expect($url)->toContain((string) $tenant->id . '_'); // tenant prefixado no filename
});

it('rejeita arquivo maior que 5MB', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id, 'role' => 'super_admin', 'two_factor_confirmed_at' => now(),
    ]);
    session(['2fa_verified' => true]);

    $big = UploadedFile::fake()->create('big.png', 6000, 'image/png'); // 6MB

    $this->actingAs($user)->post('/admin/email-campaigns/upload-image', ['image' => $big])
        ->assertSessionHasErrors('image');
});

it('rejeita extensao nao-imagem', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id, 'role' => 'super_admin', 'two_factor_confirmed_at' => now(),
    ]);
    session(['2fa_verified' => true]);

    $pdf = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

    $this->actingAs($user)->post('/admin/email-campaigns/upload-image', ['image' => $pdf])
        ->assertSessionHasErrors('image');
});
