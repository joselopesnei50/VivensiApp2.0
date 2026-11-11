<?php

use App\Mail\RaffleReservationsExpiring;
use App\Mail\RaffleTicketPaid;
use App\Mail\RaffleTicketReleased;
use App\Models\Raffle;
use App\Models\RaffleTicket;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/*
| Correções do módulo de rifas (2026-11).
*/

beforeEach(function () {
    Mail::fake();
    $this->tenant = Tenant::factory()->create(['type' => 'ngo', 'subscription_status' => 'active', 'pix_key' => 'pix@ong.org', 'cidade' => 'Araraquara', 'razao_social' => 'Associação Semente']);
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'ngo', 'email' => 'r_' . uniqid() . '@example.com']);
    $this->actingAs($this->user)->post('/raffles', ['title' => 'Rifa Solidária', 'description' => 'Cesta', 'ticket_price' => 10, 'total_tickets' => 120])->assertRedirect();
    auth()->logout();
    $this->raffle = Raffle::withoutGlobalScopes()->where('title', 'Rifa Solidária')->sole();
    $this->buy = fn (array $numbers, string $email = 'comprador@example.com') => $this->post(route('public.raffle.reserve', $this->raffle->slug), [
        'buyer_name' => 'Maria Souza', 'buyer_email' => $email, 'buyer_phone' => '16999990000', 'selected_numbers' => $numbers,
    ]);
});

afterEach(fn () => Carbon::setTestNow());

it('cria a rifa com todos os números de uma vez', function () {
    expect(RaffleTicket::where('raffle_id', $this->raffle->id)->count())->toBe(120)
        ->and(RaffleTicket::where('raffle_id', $this->raffle->id)->max('number'))->toBe(120);
});

it('a gestão abre mesmo sem data de sorteio', function () {
    $this->actingAs($this->user)->get("/raffles/{$this->raffle->id}")->assertOk()->assertSee('A definir');
});

it('limita a quantidade de números por compra e por pessoa', function () {
    ($this->buy)(range(1, 51))->assertSessionHasErrors('selected_numbers');
    expect(RaffleTicket::where('raffle_id', $this->raffle->id)->where('status', 'pending')->count())->toBe(0);

    ($this->buy)(range(1, 40))->assertRedirect();
    ($this->buy)(range(41, 52))->assertSessionHas('error'); // 40 + 12 > 50
    expect(RaffleTicket::where('raffle_id', $this->raffle->id)->where('status', 'pending')->count())->toBe(40);
});

it('depois de reservar vai para a página do pedido, que pode ser reaberta', function () {
    $r = ($this->buy)([3, 7]);
    $token = RaffleTicket::where('raffle_id', $this->raffle->id)->where('number', 3)->value('reservation_token');
    expect($token)->toHaveLength(40);
    $url = route('public.raffle.order', [$this->raffle->slug, $token]);
    $r->assertRedirect($url);

    $this->get($url)->assertOk()->assertSee('Quase lá')->assertSee('#03')->assertSee('#07')->assertSee('ARARAQUARA');
    $this->get($url)->assertOk(); // F5 continua funcionando
    $this->get(route('public.raffle.order', [$this->raffle->slug, str_repeat('x', 40)]))->assertRedirect(route('public.raffle.show', $this->raffle->slug));
});

it('reserva fica segura por 24h, a ONG é avisada antes e o comprador quando vence', function () {
    Carbon::setTestNow('2026-11-11 10:00:00');
    ($this->buy)([5, 6]);

    Carbon::setTestNow('2026-11-11 10:30:00'); // passou dos antigos 30 minutos
    $this->artisan('raffles:cleanup-reservations');
    expect(RaffleTicket::where('raffle_id', $this->raffle->id)->where('status', 'pending')->count())->toBe(2);

    Carbon::setTestNow('2026-11-12 06:30:00'); // faltam 3h30
    $this->artisan('raffles:cleanup-reservations');
    $this->artisan('raffles:cleanup-reservations');
    Mail::assertSent(RaffleReservationsExpiring::class, 1);

    Carbon::setTestNow('2026-11-12 10:01:00'); // venceu
    $this->artisan('raffles:cleanup-reservations');
    expect(RaffleTicket::where('raffle_id', $this->raffle->id)->where('status', 'pending')->count())->toBe(0);
    Mail::assertSent(RaffleTicketReleased::class, fn ($m) => $m->hasTo('comprador@example.com') && $m->tickets->count() === 2);
});

it('confirma a compra inteira com um lançamento só, e não duplica no segundo clique', function () {
    ($this->buy)([10, 11, 12]);
    $t = RaffleTicket::where('raffle_id', $this->raffle->id)->where('number', 11)->first();

    $this->actingAs($this->user)->post(route('raffles.confirm-payment', $t->id), ['scope' => 'order'])->assertSessionHas('success');
    $this->actingAs($this->user)->post(route('raffles.confirm-payment', $t->id), ['scope' => 'order'])->assertSessionHas('error');

    expect(RaffleTicket::where('raffle_id', $this->raffle->id)->where('status', 'paid')->count())->toBe(3);
    $tx = Transaction::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->where('description', 'like', '%Rifa Solidária%')->get();
    expect($tx)->toHaveCount(1)->and((float) $tx->first()->amount)->toBe(30.0);
    Mail::assertSent(RaffleTicketPaid::class, 1);

    // a página do pedido mostra pago
    $token = $t->fresh()->reservation_token;
    $this->get(route('public.raffle.order', [$this->raffle->slug, $token]))->assertSee('Pagamento confirmado');
});

it('depois do sorteio a página pública mostra o resultado e não vende mais', function () {
    ($this->buy)([20]);
    $t = RaffleTicket::where('raffle_id', $this->raffle->id)->where('number', 20)->first();
    $this->actingAs($this->user)->post(route('raffles.confirm-payment', $t->id));
    $this->actingAs($this->user)->from("/raffles/{$this->raffle->id}")->post(route('raffles.draw', $this->raffle->id))->assertRedirect();
    $this->get("/raffles/{$this->raffle->id}"); // consome o aviso da gestão
    auth()->logout();

    $this->get(route('public.raffle.show', $this->raffle->slug))->assertOk()
        ->assertSee('Sorteio realizado')->assertSee('#20')->assertSee('Maria S.')->assertDontSee('Maria Souza')
        ->assertDontSee('id="reserveForm"', false);
    ($this->buy)([30])->assertNotFound();
});
