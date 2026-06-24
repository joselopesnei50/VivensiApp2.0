<?php

use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\MeiPanelService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Vivensi — MEI / NFS-e (Opção C: anexar nota emitida no portal nfse.gov.br).
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function nfseTenantUser(): array
{
    $tenant = Tenant::factory()->create(['type' => 'common']);
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'client']);
    return [$tenant, $user];
}

function nfseReceipt(int $tenantId, float $valor = 100.0): Transaction
{
    return Transaction::create([
        'tenant_id'   => $tenantId,
        'description' => 'Recibo teste',
        'amount'      => $valor,
        'type'        => 'income',
        'status'      => 'paid',
        'date'        => now()->toDateString(),
    ]);
}

it('attachNfse exige nfse_numero e data', function () {
    [$tenant, $user] = nfseTenantUser();
    $tx = nfseReceipt($tenant->id);

    $resp = $this->actingAs($user)
        ->from('/personal/receipts')
        ->post("/personal/receipts/{$tx->id}/nfse", []);

    $resp->assertSessionHasErrors(['nfse_numero', 'nfse_emitida_em']);
});

it('attachNfse salva número, data e PDF', function () {
    Storage::fake('local');
    [$tenant, $user] = nfseTenantUser();
    $tx = nfseReceipt($tenant->id);
    $pdf = UploadedFile::fake()->create('nota.pdf', 200, 'application/pdf');

    $this->actingAs($user)->post("/personal/receipts/{$tx->id}/nfse", [
        'nfse_numero'     => 'NFS-00123',
        'nfse_emitida_em' => '2026-06-24',
        'pdf'             => $pdf,
    ]);

    $tx->refresh();
    expect($tx->nfse_numero)->toBe('NFS-00123');
    expect((string) $tx->nfse_emitida_em->toDateString())->toBe('2026-06-24');
    expect($tx->nfse_url_pdf)->toStartWith("private/nfse/{$tenant->id}/");
    Storage::disk('local')->assertExists($tx->nfse_url_pdf);
});

it('attachNfse sem PDF mantém receita válida (PDF é opcional)', function () {
    [$tenant, $user] = nfseTenantUser();
    $tx = nfseReceipt($tenant->id);

    $this->actingAs($user)->post("/personal/receipts/{$tx->id}/nfse", [
        'nfse_numero'     => '0042',
        'nfse_emitida_em' => '2026-06-20',
    ]);

    $tx->refresh();
    expect($tx->nfse_numero)->toBe('0042');
    expect($tx->nfse_url_pdf)->toBeNull();
});

it('attachNfse rejeita arquivo não-PDF', function () {
    [$tenant, $user] = nfseTenantUser();
    $tx = nfseReceipt($tenant->id);
    $jpg = UploadedFile::fake()->image('foto.jpg');

    $resp = $this->actingAs($user)
        ->from('/personal/receipts')
        ->post("/personal/receipts/{$tx->id}/nfse", [
            'nfse_numero'     => '0001',
            'nfse_emitida_em' => '2026-06-24',
            'pdf'             => $jpg,
        ]);

    $resp->assertSessionHasErrors('pdf');
});

it('attachNfse substitui PDF antigo ao anexar novo', function () {
    Storage::fake('local');
    [$tenant, $user] = nfseTenantUser();
    $tx = nfseReceipt($tenant->id);

    $pdf1 = UploadedFile::fake()->create('a.pdf', 100, 'application/pdf');
    $this->actingAs($user)->post("/personal/receipts/{$tx->id}/nfse", [
        'nfse_numero'     => '1',
        'nfse_emitida_em' => '2026-06-24',
        'pdf'             => $pdf1,
    ]);
    $pathAntigo = $tx->fresh()->nfse_url_pdf;

    $pdf2 = UploadedFile::fake()->create('b.pdf', 100, 'application/pdf');
    $this->actingAs($user)->post("/personal/receipts/{$tx->id}/nfse", [
        'nfse_numero'     => '2',
        'nfse_emitida_em' => '2026-06-24',
        'pdf'             => $pdf2,
    ]);
    $pathNovo = $tx->fresh()->nfse_url_pdf;

    expect($pathAntigo)->not->toBe($pathNovo);
    Storage::disk('local')->assertMissing($pathAntigo);
    Storage::disk('local')->assertExists($pathNovo);
});

it('detachNfse limpa campos e apaga PDF', function () {
    Storage::fake('local');
    [$tenant, $user] = nfseTenantUser();
    $tx = nfseReceipt($tenant->id);
    $this->actingAs($user)->post("/personal/receipts/{$tx->id}/nfse", [
        'nfse_numero'     => '99',
        'nfse_emitida_em' => '2026-06-24',
        'pdf'             => UploadedFile::fake()->create('n.pdf', 50, 'application/pdf'),
    ]);
    $path = $tx->fresh()->nfse_url_pdf;

    $this->actingAs($user)->delete("/personal/receipts/{$tx->id}/nfse");

    $tx->refresh();
    expect($tx->nfse_numero)->toBeNull();
    expect($tx->nfse_url_pdf)->toBeNull();
    expect($tx->nfse_emitida_em)->toBeNull();
    Storage::disk('local')->assertMissing($path);
});

it('downloadNfse retorna 404 quando não há PDF', function () {
    [$tenant, $user] = nfseTenantUser();
    $tx = nfseReceipt($tenant->id);

    $this->actingAs($user)
        ->get("/personal/receipts/{$tx->id}/nfse/download")
        ->assertNotFound();
});

it('coberturaNfse conta corretamente', function () {
    [$tenant, $user] = nfseTenantUser();
    $svc = app(MeiPanelService::class);

    $r0 = $svc->coberturaNfse($tenant->id);
    expect($r0['total_receitas'])->toBe(0);
    expect($r0['percentual'])->toBe(0.0);

    nfseReceipt($tenant->id, 100);
    nfseReceipt($tenant->id, 200);
    $cMarcada = nfseReceipt($tenant->id, 300);
    $cMarcada->update(['nfse_numero' => 'X']);

    $r1 = $svc->coberturaNfse($tenant->id);
    expect($r1['total_receitas'])->toBe(3);
    expect($r1['com_nfse'])->toBe(1);
    expect($r1['sem_nfse'])->toBe(2);
    expect($r1['percentual'])->toEqual(33.3);
});

it('attachNfse de outro tenant retorna 404', function () {
    [$tA, $userA] = nfseTenantUser();
    [$tB, $userB] = nfseTenantUser();
    $txB = nfseReceipt($tB->id);

    $this->actingAs($userA)
        ->post("/personal/receipts/{$txB->id}/nfse", [
            'nfse_numero'     => 'X',
            'nfse_emitida_em' => '2026-06-24',
        ])
        ->assertNotFound();
});
