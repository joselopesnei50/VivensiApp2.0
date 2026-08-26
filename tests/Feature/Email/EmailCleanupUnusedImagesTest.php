<?php

use App\Models\EmailCampaign;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Cleanup semanal de imagens de campanha uploaded sem referencia em nenhum
 * html_content. Safety: nao apaga arquivos com menos de N dias (default 7).
 */

beforeEach(function () {
    Storage::fake('public');

    $this->tenant = Tenant::factory()->create();
    $this->user   = User::factory()->create(['tenant_id' => $this->tenant->id]);
});

function makeCampaign(int $tenantId, int $userId, string $html): EmailCampaign
{
    return EmailCampaign::create([
        'tenant_id'    => $tenantId,
        'created_by'   => $userId,
        'name'         => 'X',
        'subject'      => 'X',
        'sender_name'  => 'X',
        'sender_email' => 'a@a.com',
        'html_content' => $html,
        'status'       => 'draft',
    ]);
}

function fakeUpload(string $filename, int $daysOld = 30): void
{
    Storage::disk('public')->put("email_campaigns_uploads/{$filename}", 'bytes');
    // torna o arquivo "velho" pra passar o filtro min-days
    $abs = Storage::disk('public')->path("email_campaigns_uploads/{$filename}");
    touch($abs, now()->subDays($daysOld)->getTimestamp());
}

it('dry-run mostra candidatos e NAO apaga', function () {
    fakeUpload('orphan1.png');
    fakeUpload('orphan2.png');

    \Illuminate\Support\Facades\Artisan::call('email:cleanup-unused-images');

    // Ainda estao la
    expect(Storage::disk('public')->exists('email_campaigns_uploads/orphan1.png'))->toBeTrue();
    expect(Storage::disk('public')->exists('email_campaigns_uploads/orphan2.png'))->toBeTrue();
});

it('--confirm apaga arquivos orfaos antigos', function () {
    fakeUpload('orphan.png');

    \Illuminate\Support\Facades\Artisan::call('email:cleanup-unused-images', ['--confirm' => true]);

    expect(Storage::disk('public')->exists('email_campaigns_uploads/orphan.png'))->toBeFalse();
});

it('preserva arquivos referenciados em html_content', function () {
    fakeUpload('em_uso.png');
    fakeUpload('orfa.png');

    makeCampaign($this->tenant->id, $this->user->id,
        '<html><body><img src="https://vivensi.app.br/storage/email_campaigns_uploads/em_uso.png"></body></html>'
    );

    \Illuminate\Support\Facades\Artisan::call('email:cleanup-unused-images', ['--confirm' => true]);

    expect(Storage::disk('public')->exists('email_campaigns_uploads/em_uso.png'))->toBeTrue();
    expect(Storage::disk('public')->exists('email_campaigns_uploads/orfa.png'))->toBeFalse();
});

it('preserva arquivos recentes (< min-days) mesmo sem referencia', function () {
    fakeUpload('nova.png', daysOld: 2); // subiu ha 2 dias

    \Illuminate\Support\Facades\Artisan::call('email:cleanup-unused-images', ['--confirm' => true, '--min-days' => 7]);

    expect(Storage::disk('public')->exists('email_campaigns_uploads/nova.png'))->toBeTrue();
});
