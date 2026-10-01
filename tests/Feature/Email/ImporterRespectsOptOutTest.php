<?php

use App\Models\EmailContact;
use App\Models\EmailContactList;
use App\Models\Tenant;
use App\Services\EmailContactListImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

/**
 * P2 (2026-09-29) — Import CSV deve respeitar opt-out historico do tenant.
 * Cenario: usuario descadastrou via link -> marca 'unsubscribed' em todas
 * as ocorrencias -> admin cria lista nova + reimporta CSV com mesmo email.
 * Antes: linha criada com status='active' (dado corrompido, envio bloqueado
 * so pelo safety net). Depois: linha criada ja com status='unsubscribed'
 * (dado consistente, cliente ve a marcacao na UI).
 */

uses(RefreshDatabase::class);

function importerTenant(): Tenant
{
    return Tenant::factory()->create(['subscription_status' => 'active']);
}

function importerList(Tenant $t): EmailContactList
{
    return EmailContactList::create([
        'tenant_id'           => $t->id,
        'name'                => 'Lista ' . uniqid(),
        'opt_in_confirmed'    => true,
        'opt_in_confirmed_at' => now(),
    ]);
}

function importerCsvFile(string $content): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($path, $content);
    return new UploadedFile($path, 'test.csv', 'text/csv', null, true);
}

it('CSV importer respeita opt-out anterior do tenant (P2)', function () {
    $t = importerTenant();

    // Contato ja descadastrou em uma lista antiga
    $listaAntiga = importerList($t);
    EmailContact::create([
        'email_contact_list_id' => $listaAntiga->id,
        'tenant_id'             => $t->id,
        'email'                 => 'saiu@x.com',
        'status'                => 'unsubscribed',
        'source'                => 'csv_upload',
        'added_at'              => now(),
        'unsubscribed_at'       => now(),
    ]);

    // Admin cria lista nova e importa CSV com o mesmo email + um novo
    $listaNova = importerList($t);
    $csv = importerCsvFile("email,name\nsaiu@x.com,Saiu\nnovato@x.com,Novato\n");

    $importer = new EmailContactListImportService();
    $summary = $importer->importFromCsv($listaNova, $csv);

    // Contadores esperados
    expect($summary['imported'])->toBe(1)                // so o novato
        ->and($summary['blocked_optout'])->toBe(1)       // saiu foi respeitado
        ->and($summary['duplicates_in_list'])->toBe(0)
        ->and($summary['invalid_emails'])->toBe(0);

    // Ambos os contatos foram CRIADOS na lista nova, mas com status diferentes
    $novato = EmailContact::where('email_contact_list_id', $listaNova->id)
        ->where('email', 'novato@x.com')->first();
    $saiu = EmailContact::where('email_contact_list_id', $listaNova->id)
        ->where('email', 'saiu@x.com')->first();

    expect($novato->status)->toBe('active');
    expect($saiu->status)->toBe('unsubscribed');
    expect($saiu->unsubscribed_at)->not->toBeNull();
});

it('CSV importer sem opt-out cria tudo como active', function () {
    $t = importerTenant();
    $list = importerList($t);

    $csv = importerCsvFile("email,name\na@x.com,A\nb@x.com,B\n");
    $summary = (new EmailContactListImportService())->importFromCsv($list, $csv);

    expect($summary['imported'])->toBe(2)
        ->and($summary['blocked_optout'])->toBe(0);
});

it('CSV importer respeita opt-out mesmo com case diferente no CSV', function () {
    $t = importerTenant();

    $listaAntiga = importerList($t);
    EmailContact::create([
        'email_contact_list_id' => $listaAntiga->id,
        'tenant_id'             => $t->id,
        'email'                 => 'joao@x.com',  // lowercased no BD
        'status'                => 'unsubscribed',
        'source'                => 'csv_upload',
        'added_at'              => now(),
    ]);

    $listaNova = importerList($t);
    $csv = importerCsvFile("email\nJOAO@X.COM\n");  // CSV veio caixa alta
    $summary = (new EmailContactListImportService())->importFromCsv($listaNova, $csv);

    expect($summary['blocked_optout'])->toBe(1);
    expect($summary['imported'])->toBe(0);
});
