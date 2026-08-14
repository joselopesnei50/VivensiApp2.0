<?php

use App\Models\Asset;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function attTenantUser(): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'ngo']);
}

function makeAsset(int $tenantId): Asset
{
    return Asset::create([
        'tenant_id'        => $tenantId,
        'name'             => 'Notebook Dell',
        'code'             => 'PAT-001',
        'acquisition_date' => '2026-01-15',
        'value'            => 4500.00,
        'status'           => 'active',
    ]);
}

function makeInvItem(int $tenantId): InventoryItem
{
    return InventoryItem::create([
        'tenant_id'      => $tenantId,
        'name'           => 'Papel A4',
        'unit'           => 'resma',
        'quantity'       => 50,
        'minimum_stock'  => 10,
    ]);
}

function makeInvMove(int $tenantId, int $itemId): InventoryMovement
{
    return InventoryMovement::create([
        'tenant_id'         => $tenantId,
        'inventory_item_id' => $itemId,
        'type'              => 'in',
        'quantity'          => 20,
        'date'              => '2026-07-17',
        'description'       => 'Compra fornecedor',
    ]);
}

beforeEach(function () {
    Storage::fake('local');
});

// ── Feliz caminho ─────────────────────────────────────────────────────────────

it('sobe anexo PDF para Asset via morphType asset', function () {
    $user  = attTenantUser();
    $asset = makeAsset($user->tenant_id);

    $file = UploadedFile::fake()->create('nota-fiscal.pdf', 500, 'application/pdf');

    $this->actingAs($user)
        ->post("/attachments/asset/{$asset->id}", ['file' => $file])
        ->assertRedirect();

    $att = Attachment::withoutGlobalScope('tenant')->first();
    expect($att)->not->toBeNull();
    expect($att->attachable_type)->toBe(Asset::class);
    expect((int) $att->attachable_id)->toBe($asset->id);
    expect((int) $att->tenant_id)->toBe($user->tenant_id);
    expect($att->original_name)->toBe('nota-fiscal.pdf');
    Storage::disk('local')->assertExists($att->path);
});

it('sobe anexo JPG para InventoryItem via morphType inv_item', function () {
    $user = attTenantUser();
    $item = makeInvItem($user->tenant_id);

    $file = UploadedFile::fake()->create('foto.jpg', 100, 'image/jpeg');

    $this->actingAs($user)
        ->post("/attachments/inv_item/{$item->id}", ['file' => $file])
        ->assertRedirect();

    $att = Attachment::withoutGlobalScope('tenant')->first();
    expect($att->attachable_type)->toBe(InventoryItem::class);
    expect((int) $att->attachable_id)->toBe($item->id);
});

it('sobe anexo PNG para InventoryMovement via morphType inv_move', function () {
    $user = attTenantUser();
    $item = makeInvItem($user->tenant_id);
    $mov  = makeInvMove($user->tenant_id, $item->id);

    $file = UploadedFile::fake()->create('nf-entrada.png', 100, 'image/png');

    $this->actingAs($user)
        ->post("/attachments/inv_move/{$mov->id}", ['file' => $file])
        ->assertRedirect();

    $att = Attachment::withoutGlobalScope('tenant')->first();
    expect($att->attachable_type)->toBe(InventoryMovement::class);
});

// ── Seguranca: whitelist morphType ────────────────────────────────────────────

it('morphType desconhecido retorna 404 (anti-IDOR)', function () {
    $user = attTenantUser();

    $file = UploadedFile::fake()->create('x.pdf', 100, 'application/pdf');

    $this->actingAs($user)
        ->post('/attachments/user/1', ['file' => $file])
        ->assertNotFound();

    expect(Attachment::withoutGlobalScope('tenant')->count())->toBe(0);
});

// ── Seguranca: cross-tenant ───────────────────────────────────────────────────

it('nao consegue anexar em Asset de outro tenant', function () {
    $mine   = attTenantUser();
    $others = attTenantUser();
    $othersAsset = makeAsset($others->tenant_id);

    $file = UploadedFile::fake()->create('nota.pdf', 100, 'application/pdf');

    $this->actingAs($mine)
        ->post("/attachments/asset/{$othersAsset->id}", ['file' => $file])
        ->assertNotFound();

    expect(Attachment::withoutGlobalScope('tenant')->count())->toBe(0);
});

it('nao consegue baixar anexo de outro tenant', function () {
    $mine   = attTenantUser();
    $others = attTenantUser();
    $othersAsset = makeAsset($others->tenant_id);

    $file = UploadedFile::fake()->create('secret.pdf', 100, 'application/pdf');
    $this->actingAs($others)->post("/attachments/asset/{$othersAsset->id}", ['file' => $file]);

    $att = Attachment::withoutGlobalScope('tenant')->first();

    $this->actingAs($mine)
        ->get("/attachments/{$att->id}/download")
        ->assertNotFound();
});

it('nao consegue deletar anexo de outro tenant', function () {
    $mine   = attTenantUser();
    $others = attTenantUser();
    $othersAsset = makeAsset($others->tenant_id);

    $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
    $this->actingAs($others)->post("/attachments/asset/{$othersAsset->id}", ['file' => $file]);

    $att = Attachment::withoutGlobalScope('tenant')->first();

    $this->actingAs($mine)
        ->delete("/attachments/{$att->id}")
        ->assertNotFound();

    // Ainda existe (soft delete nao rolou)
    expect(Attachment::withoutGlobalScope('tenant')->whereNull('deleted_at')->count())->toBe(1);
});

// ── Seguranca: validacao mime + tamanho ───────────────────────────────────────

it('rejeita arquivo com mime nao permitido (zip)', function () {
    $user  = attTenantUser();
    $asset = makeAsset($user->tenant_id);

    $file = UploadedFile::fake()->create('malicioso.zip', 100, 'application/zip');

    $this->actingAs($user)
        ->post("/attachments/asset/{$asset->id}", ['file' => $file])
        ->assertSessionHasErrors('file');

    expect(Attachment::withoutGlobalScope('tenant')->count())->toBe(0);
});

it('rejeita arquivo maior que 10 MB', function () {
    $user  = attTenantUser();
    $asset = makeAsset($user->tenant_id);

    // 11 MB (10240 KB e o limite)
    $file = UploadedFile::fake()->create('grande.pdf', 11 * 1024, 'application/pdf');

    $this->actingAs($user)
        ->post("/attachments/asset/{$asset->id}", ['file' => $file])
        ->assertSessionHasErrors('file');

    expect(Attachment::withoutGlobalScope('tenant')->count())->toBe(0);
});

// ── Feliz caminho: index (lista) ──────────────────────────────────────────────

it('index lista anexos do owner', function () {
    $user  = attTenantUser();
    $asset = makeAsset($user->tenant_id);

    $this->actingAs($user)
        ->post("/attachments/asset/{$asset->id}", ['file' => UploadedFile::fake()->create('a.pdf', 100, 'application/pdf')]);
    $this->actingAs($user)
        ->post("/attachments/asset/{$asset->id}", ['file' => UploadedFile::fake()->create('b.jpg', 100, 'image/jpeg')]);

    $this->actingAs($user)
        ->get("/attachments/asset/{$asset->id}")
        ->assertOk()
        ->assertSee('a.pdf')
        ->assertSee('b.jpg');
});

it('destroy soft-deleta anexo proprio', function () {
    $user  = attTenantUser();
    $asset = makeAsset($user->tenant_id);

    $this->actingAs($user)
        ->post("/attachments/asset/{$asset->id}", ['file' => UploadedFile::fake()->create('x.pdf', 100, 'application/pdf')]);

    $att = Attachment::withoutGlobalScope('tenant')->first();

    $this->actingAs($user)
        ->delete("/attachments/{$att->id}")
        ->assertRedirect();

    // Soft delete: linha existe mas deleted_at preenchido
    expect(Attachment::withoutGlobalScope('tenant')->whereNull('deleted_at')->count())->toBe(0);
    expect(Attachment::withoutGlobalScope('tenant')->withTrashed()->count())->toBe(1);
});

// ── Beneficiary: PII / LGPD ───────────────────────────────────────────────────

function makeBeneficiary(int $tenantId): Beneficiary
{
    return Beneficiary::create([
        'tenant_id' => $tenantId,
        'name'      => 'Maria Silva',
        'status'    => 'active',
    ]);
}

it('exige tipo_documento no upload de anexo de beneficiario', function () {
    $user  = attTenantUser();
    $benef = makeBeneficiary($user->tenant_id);

    $this->actingAs($user)
        ->post("/attachments/beneficiary/{$benef->id}", [
            'file' => UploadedFile::fake()->create('rg.pdf', 100, 'application/pdf'),
            // tipo_documento ausente de proposito
        ])
        ->assertSessionHasErrors('tipo_documento');

    expect(Attachment::withoutGlobalScope('tenant')->count())->toBe(0);
});

it('rejeita tipo_documento fora da whitelist', function () {
    $user  = attTenantUser();
    $benef = makeBeneficiary($user->tenant_id);

    $this->actingAs($user)
        ->post("/attachments/beneficiary/{$benef->id}", [
            'file'           => UploadedFile::fake()->create('rg.pdf', 100, 'application/pdf'),
            'tipo_documento' => 'foto_pet',
        ])
        ->assertSessionHasErrors('tipo_documento');

    expect(Attachment::withoutGlobalScope('tenant')->count())->toBe(0);
});

it('sobe anexo de beneficiario e grava AuditLog', function () {
    $user  = attTenantUser();
    $benef = makeBeneficiary($user->tenant_id);

    $this->actingAs($user)
        ->post("/attachments/beneficiary/{$benef->id}", [
            'file'           => UploadedFile::fake()->create('rg.pdf', 100, 'application/pdf'),
            'tipo_documento' => 'rg',
        ])
        ->assertRedirect();

    $att = Attachment::withoutGlobalScope('tenant')->first();
    expect($att->attachable_type)->toBe(Beneficiary::class);
    expect($att->tipo_documento)->toBe('rg');

    $audit = AuditLog::withoutGlobalScope('tenant')
        ->where('event', 'BENEFICIARY_ATTACHMENT_UPLOADED')->first();
    expect($audit)->not->toBeNull();
    expect((int) $audit->tenant_id)->toBe($user->tenant_id);
    expect((int) $audit->user_id)->toBe($user->id);
    expect($audit->auditable_type)->toBe(Attachment::class);
    expect((int) $audit->auditable_id)->toBe($att->id);
});

it('download de anexo de beneficiario grava AuditLog', function () {
    $user  = attTenantUser();
    $benef = makeBeneficiary($user->tenant_id);

    $this->actingAs($user)->post("/attachments/beneficiary/{$benef->id}", [
        'file'           => UploadedFile::fake()->create('rg.pdf', 100, 'application/pdf'),
        'tipo_documento' => 'rg',
    ]);
    $att = Attachment::withoutGlobalScope('tenant')->first();

    // Zera log de upload pra isolar o evento de download
    AuditLog::withoutGlobalScope('tenant')->delete();

    $this->actingAs($user)->get("/attachments/{$att->id}/download")->assertOk();

    $audit = AuditLog::withoutGlobalScope('tenant')
        ->where('event', 'BENEFICIARY_ATTACHMENT_DOWNLOADED')->first();
    expect($audit)->not->toBeNull();
    expect((int) $audit->auditable_id)->toBe($att->id);
});

it('employee nao consegue deletar anexo de beneficiario (gate)', function () {
    $owner = attTenantUser(); // role=ngo sobe
    $benef = makeBeneficiary($owner->tenant_id);

    $this->actingAs($owner)->post("/attachments/beneficiary/{$benef->id}", [
        'file'           => UploadedFile::fake()->create('rg.pdf', 100, 'application/pdf'),
        'tipo_documento' => 'rg',
    ]);
    $att = Attachment::withoutGlobalScope('tenant')->first();

    $employee = User::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'role'      => 'employee',
    ]);

    $this->actingAs($employee)
        ->delete("/attachments/{$att->id}")
        ->assertForbidden();

    // Anexo permanece
    expect(Attachment::withoutGlobalScope('tenant')->whereNull('deleted_at')->count())->toBe(1);
});

it('destroy de anexo de beneficiario grava AuditLog', function () {
    $user  = attTenantUser();
    $benef = makeBeneficiary($user->tenant_id);

    $this->actingAs($user)->post("/attachments/beneficiary/{$benef->id}", [
        'file'           => UploadedFile::fake()->create('rg.pdf', 100, 'application/pdf'),
        'tipo_documento' => 'rg',
    ]);
    $att = Attachment::withoutGlobalScope('tenant')->first();

    AuditLog::withoutGlobalScope('tenant')->delete();

    $this->actingAs($user)->delete("/attachments/{$att->id}")->assertRedirect();

    $audit = AuditLog::withoutGlobalScope('tenant')
        ->where('event', 'BENEFICIARY_ATTACHMENT_DELETED')->first();
    expect($audit)->not->toBeNull();
    expect((int) $audit->auditable_id)->toBe($att->id);
});
