<?php

use App\Http\Controllers\SponsorshipDealController;
use App\Models\SponsorshipDeal;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\WhatsappLink;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create(['type' => 'ngo', 'subscription_status' => 'active', 'document' => '04213444000166']);
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'ngo']);
});

it('link do WhatsApp leva o código do país', function () {
    expect(WhatsappLink::number('(85) 99999-1234'))->toBe('5585999991234');
    expect(WhatsappLink::number('85 3333-1234'))->toBe('558533331234');
    expect(WhatsappLink::number('+55 85 99999-1234'))->toBe('5585999991234');
    expect(WhatsappLink::number('085999991234'))->toBe('5585999991234');
});

it('valor do patrocínio aceita o formato brasileiro', function (string $digitado, float $esperado) {
    expect(SponsorshipDealController::parseMoney($digitado))->toBe($esperado);
})->with([
    ['50.000,00', 50000.0], ['50000', 50000.0], ['1.234,5', 1234.5], ['50000.00', 50000.0], ['R$ 12.500', 12500.0], ['', 0.0],
]);

it('novo patrocínio com "50.000,00" é salvo (antes: "deve ser um número")', function () {
    $this->actingAs($this->user)
        ->post('/ngo/sponsorships', ['company_name' => 'Padaria (exemplo)', 'expected_value' => '50.000,00'])
        ->assertSessionHasNoErrors();

    expect((float) SponsorshipDeal::withoutGlobalScopes()->where('company_name', 'Padaria (exemplo)')->value('expected_value'))->toBe(50000.0);
});

it('recibo público mostra o CNPJ formatado e mascara CPF', function () {
    $t = Transaction::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id, 'type' => 'income', 'status' => 'paid', 'amount' => 100,
        'description' => 'Doação', 'date' => now()->toDateString(),
    ]);
    $token = $t->public_receipt_token;

    $this->get('/r/' . $token)->assertOk()->assertSee('04.213.444/0001-66')->assertSee('CNPJ');

    expect(Tenant::publicDocumentFor('123.456.789-01'))->toBe(['CPF', '***.456.789-**']);
});
