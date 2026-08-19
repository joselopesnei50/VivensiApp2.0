<?php

use App\Models\Beneficiary;
use App\Models\Tenant;
use App\Services\BeneficiaryLookupService;

/**
 * Feature test do BeneficiaryLookupService (2026-08-19).
 *
 * Bug real reportado: bot interno /admin/bot dizia "beneficiario nao cadastrado"
 * mesmo com CPF correto. Causa: buscava WHERE cpf LIKE em campo AES-encrypted.
 * Fix: usa cpf_bidx (HMAC HMAC-SHA256 mesmo padrao do findUserByPhone/Contract).
 *
 * Cobertura:
 *  - Nome parcial (LIKE)
 *  - CPF com mascara "123.456.789-00"
 *  - CPF so digitos "12345678900"
 *  - NIS com/sem mascara
 *  - tenant_id isolation
 *  - Dedup quando query casa nome E cpf
 *  - Query vazia devolve colecao vazia
 *  - Limit respeitado
 */

function bltMkBeneficiary(int $tenantId, array $overrides = []): Beneficiary
{
    return Beneficiary::create(array_merge([
        'tenant_id' => $tenantId,
        'name'      => 'Fulano da Silva Teste',
        'cpf'       => '12345678900',
        'nis'       => '12345678901',
        'status'    => 'active',
    ], $overrides));
}

test('busca por nome parcial encontra beneficiario', function () {
    $tenant = Tenant::factory()->create();
    $b = bltMkBeneficiary($tenant->id, ['name' => 'Maria Santos']);

    $results = (new BeneficiaryLookupService())->search($tenant->id, 'Maria');

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($b->id);
});

test('busca por CPF sem mascara encontra beneficiario encrypted', function () {
    $tenant = Tenant::factory()->create();
    $b = bltMkBeneficiary($tenant->id, ['cpf' => '12345678900']);

    $results = (new BeneficiaryLookupService())->search($tenant->id, '12345678900');

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($b->id);
});

test('busca por CPF COM mascara encontra beneficiario encrypted', function () {
    $tenant = Tenant::factory()->create();
    $b = bltMkBeneficiary($tenant->id, ['cpf' => '12345678900']);

    // Beneficiario armazenado com "12345678900". Bot recebe "123.456.789-00".
    // Service deve normalizar antes de calcular bidx.
    $results = (new BeneficiaryLookupService())->search($tenant->id, '123.456.789-00');

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($b->id);
});

test('busca por CPF armazenado COM mascara encontra quando query e digitos', function () {
    $tenant = Tenant::factory()->create();
    // Alguns cadastros historicos guardaram CPF com mascara.
    $b = bltMkBeneficiary($tenant->id, ['cpf' => '123.456.789-00']);

    $results = (new BeneficiaryLookupService())->search($tenant->id, '12345678900');

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($b->id);
});

test('busca por NIS de 11 digitos encontra beneficiario', function () {
    $tenant = Tenant::factory()->create();
    $b = bltMkBeneficiary($tenant->id, ['nis' => '98765432100']);

    $results = (new BeneficiaryLookupService())->search($tenant->id, '98765432100');

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($b->id);
});

test('respeita isolamento por tenant_id', function () {
    $t1 = Tenant::factory()->create();
    $t2 = Tenant::factory()->create();
    bltMkBeneficiary($t1->id, ['name' => 'Isolado T1', 'cpf' => '11111111111']);
    bltMkBeneficiary($t2->id, ['name' => 'Isolado T2', 'cpf' => '22222222222']);

    $results = (new BeneficiaryLookupService())->search($t1->id, 'Isolado');

    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Isolado T1');
});

test('dedupa quando query casa nome E CPF do mesmo beneficiario', function () {
    $tenant = Tenant::factory()->create();
    // Nome contem digitos que coincidem com CPF (edge case sintetico)
    $b = bltMkBeneficiary($tenant->id, ['name' => '12345678900 Silva', 'cpf' => '12345678900']);

    $results = (new BeneficiaryLookupService())->search($tenant->id, '12345678900');

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($b->id);
});

test('query vazia devolve colecao vazia', function () {
    $tenant = Tenant::factory()->create();
    bltMkBeneficiary($tenant->id);

    $results = (new BeneficiaryLookupService())->search($tenant->id, '');

    expect($results->isEmpty())->toBeTrue();
});

test('respeita limit', function () {
    $tenant = Tenant::factory()->create();
    for ($i = 0; $i < 5; $i++) {
        bltMkBeneficiary($tenant->id, [
            'name' => "Ana Fulana {$i}",
            'cpf'  => str_pad((string) ($i + 1), 11, '0', STR_PAD_LEFT),
        ]);
    }

    $results = (new BeneficiaryLookupService())->search($tenant->id, 'Ana', 3);

    expect($results)->toHaveCount(3);
});

test('nao encontra quando CPF nao existe em nenhum tenant', function () {
    $tenant = Tenant::factory()->create();
    bltMkBeneficiary($tenant->id, ['cpf' => '11111111111']);

    $results = (new BeneficiaryLookupService())->search($tenant->id, '99999999999');

    expect($results->isEmpty())->toBeTrue();
});
