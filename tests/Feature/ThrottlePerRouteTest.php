<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
| throttle:X,Y conta por rota: ações frequentes não podem esgotar o limite
| do checkout (antes o contador era um só por usuário e dava 429 no pagamento).
*/

beforeEach(function () {
    Route::middleware(['web', 'throttle:60,1'])->get('/_t/poll', fn () => 'ok');
    Route::middleware(['web', 'throttle:3,1'])->post('/_t/pay', fn () => 'ok');
    $this->user = User::factory()->create(['email' => 'thr_' . uniqid() . '@example.com']);
    $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
});

it('requisições em outra rota não consomem o limite do pagamento', function () {
    $this->actingAs($this->user);
    for ($i = 0; $i < 20; $i++) {
        $this->get('/_t/poll')->assertOk();
    }
    $this->post('/_t/pay')->assertOk();
});

it('o limite da própria rota continua valendo', function () {
    $this->actingAs($this->user);
    $this->post('/_t/pay')->assertOk();
    $this->post('/_t/pay')->assertOk();
    $this->post('/_t/pay')->assertOk();
    $this->post('/_t/pay')->assertStatus(429);
});

it('usuários diferentes não dividem o contador', function () {
    $other = User::factory()->create(['email' => 'thr2_' . uniqid() . '@example.com']);
    $this->actingAs($this->user);
    for ($i = 0; $i < 3; $i++) $this->post('/_t/pay')->assertOk();
    $this->actingAs($other)->post('/_t/pay')->assertOk();
});
