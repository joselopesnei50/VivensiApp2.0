<?php

use App\Jobs\GenerateWebChatReplyJob;
use App\Mail\WebChatResumeMail;
use App\Models\MeetingBooking;
use App\Models\Notification;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WebChatConversation;
use App\Services\BruceAiService;
use App\Services\WebChat\WebChatService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

/**
 * Chat do site: o Bruno (mesmo cérebro do WhatsApp) atendendo visitantes,
 * agendamento, retomada por e-mail e a caixa de atendimento do super admin.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

const WC_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

beforeEach(function () {
    $this->brunoTenant = Tenant::factory()->create(['subscription_status' => 'active']);
    SystemSetting::setValue('bruno_sales_bot_tenant_id', (string) $this->brunoTenant->id);
    SystemSetting::setValue('web_chat_enabled', '1');
    $this->withHeaders(['User-Agent' => WC_UA]);
});

function wcFakeBruce(array|callable $result): object
{
    $mock = Mockery::mock(BruceAiService::class);
    $mock->shouldReceive('chat')->andReturnUsing(is_callable($result) ? $result : fn () => $result);
    app()->instance(BruceAiService::class, $mock);
    return $mock;
}

function wcStart($test, array $extra = []): array
{
    $res = $test->postJson('/chat/iniciar', ['page_url' => 'https://vivensi.app.br/blog/x', 'page_title' => 'Blog: captar recursos', 'referrer' => 'https://www.google.com/'] + $extra);
    $res->assertCreated();
    return [$res->json('conversation.id'), $res->json('conversation.token'), $res];
}

function wcAdmin(): User
{
    $u = User::factory()->create(['role' => 'super_admin', 'two_factor_confirmed_at' => now()]);
    session(['2fa_verified' => true]);
    return $u;
}

it('inicia a conversa com a saudação do Bruno e guarda origem e dispositivo', function () {
    [$id, $token, $res] = wcStart($this);

    expect($token)->toHaveLength(48)
        ->and($res->json('messages.0.sender'))->toBe('bot')
        ->and($res->json('messages.0.kind'))->toBe('greeting');

    $c = WebChatConversation::where('public_id', $id)->first();
    expect($c->source)->toBe('google')
        ->and($c->device)->toBe('desktop')
        ->and($c->page_title)->toBe('Blog: captar recursos')
        ->and($c->secret_hash)->not->toBe($token)
        ->and($c->toArray())->not->toHaveKey('secret_hash');
});

it('recusa robô, honeypot, chat desligado e excesso de conversas por IP', function () {
    $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)'])->postJson('/chat/iniciar')->assertForbidden();
    $this->withHeaders(['User-Agent' => WC_UA])->postJson('/chat/iniciar', ['website' => 'spam'])->assertStatus(422);

    config(['web_chat.max_conversations_per_ip_day' => 1]);
    wcStart($this);
    $this->postJson('/chat/iniciar')->assertStatus(429);

    SystemSetting::setValue('web_chat_enabled', '0');
    $this->postJson('/chat/iniciar')->assertStatus(503);
});

it('só o dono do segredo lê a conversa', function () {
    [$id, $token] = wcStart($this);

    $this->getJson("/chat/{$id}/mensagens")->assertNotFound();
    $this->withHeaders(['X-Chat-Token' => 'errado'])->getJson("/chat/{$id}/mensagens")->assertNotFound();
    $this->withHeaders(['X-Chat-Token' => $token])->getJson("/chat/{$id}/mensagens")->assertOk()->assertJsonPath('status', 'bot');
});

it('mensagem do visitante agenda a resposta do Bruno na fila de IA', function () {
    Queue::fake();
    [$id, $token] = wcStart($this);

    $this->withHeaders(['X-Chat-Token' => $token])->postJson("/chat/{$id}/mensagens", ['body' => 'Quanto custa?'])
        ->assertOk()->assertJsonPath('bot_typing', true);

    Queue::assertPushedOn('ai', GenerateWebChatReplyJob::class);
});

it('Bruno responde com o mesmo cérebro do WhatsApp, isolado por conversa e ciente do canal', function () {
    Queue::fake();
    $captured = [];
    wcFakeBruce(function (...$args) use (&$captured) {
        // chat(userMessage, tenantId, role, userId, contextType, contextId, campaignContext, leadContext)
        $captured = array_combine(
            ['userMessage', 'tenantId', 'role', 'userId', 'contextType', 'contextId', 'campaignContext', 'leadContext'],
            array_pad($args, 8, null)
        );
        return ['reply' => 'O plano para ONGs custa R$ 99. Quer agendar uma conversa?'];
    });
    [$id, $token] = wcStart($this);
    $c = WebChatConversation::where('public_id', $id)->first();

    $h = ['X-Chat-Token' => $token];
    $this->withHeaders($h)->postJson("/chat/{$id}/mensagens", ['body' => 'Oi']);
    $this->withHeaders($h)->postJson("/chat/{$id}/mensagens", ['body' => 'Sou de uma ONG']);

    app(WebChatService::class)->generateReply($c->id);

    expect($captured['userMessage'])->toBe("Oi\nSou de uma ONG")         // junta o que chegou junto
        ->and($captured['role'])->toBe('sales_bot')
        ->and($captured['tenantId'])->toBe($this->brunoTenant->id)
        ->and($captured['userId'])->toBe(-$c->id)
        ->and($captured['contextType'])->toBe('web_chat')
        ->and($captured['leadContext']['note'])->toContain('chat do SITE')
        ->and($captured['leadContext']['origin'])->toContain('Google');

    $state = $this->withHeaders($h)->getJson("/chat/{$id}/mensagens?depois=0")->json();
    expect(collect($state['messages'])->last())->toMatchArray(['sender' => 'bot', 'name' => 'Bruno'])
        ->and($state['bot_typing'])->toBeFalse()
        ->and($c->refresh()->ai_replies)->toBe(1);

    // Sem mensagem nova, não responde de novo
    expect(app(WebChatService::class)->generateReply($c->id))->toBeNull();
});

it('quando o Bruno passa para humano, a conversa vai para a fila da equipe e os admins são avisados', function () {
    Queue::fake();
    $admin = wcAdmin();
    wcFakeBruce(['reply' => 'Vou acionar o atendimento humano para te ajudar com isso!']);
    [$id, $token] = wcStart($this);
    $c = WebChatConversation::where('public_id', $id)->first();

    $this->withHeaders(['X-Chat-Token' => $token])->postJson("/chat/{$id}/mensagens", ['body' => 'Preciso de algo específico']);
    app(WebChatService::class)->generateReply($c->id);

    expect($c->refresh()->status)->toBe('waiting_human')
        ->and(Notification::where('user_id', $admin->id)->where('type', 'web_chat')->exists())->toBeTrue();

    // Com a equipe no comando, mensagem nova não aciona o Bruno
    Queue::fake();
    $this->withHeaders(['X-Chat-Token' => $token])->postJson("/chat/{$id}/mensagens", ['body' => 'Alô?']);
    Queue::assertNotPushed(GenerateWebChatReplyJob::class);
});

it('falha da IA vira resposta de contingência, sem deixar o visitante no vácuo', function () {
    Queue::fake();
    wcFakeBruce(['error' => 'timeout']);
    [$id, $token] = wcStart($this);
    $c = WebChatConversation::where('public_id', $id)->first();
    $this->withHeaders(['X-Chat-Token' => $token])->postJson("/chat/{$id}/mensagens", ['body' => 'Oi']);

    $msg = app(WebChatService::class)->generateReply($c->id);
    expect($msg->meta['kind'])->toBe('fallback')->and($c->refresh()->ai_replies)->toBe(0);
});

it('limites: mensagens por conversa e respostas automáticas', function () {
    Queue::fake();
    config(['web_chat.max_visitor_messages' => 2, 'web_chat.max_ai_replies' => 0]);
    wcFakeBruce(['reply' => 'oi']);
    [$id, $token] = wcStart($this);
    $h = ['X-Chat-Token' => $token];

    $this->withHeaders($h)->postJson("/chat/{$id}/mensagens", ['body' => '1'])->assertOk();
    $this->withHeaders($h)->postJson("/chat/{$id}/mensagens", ['body' => '2'])->assertOk();
    $this->withHeaders($h)->postJson("/chat/{$id}/mensagens", ['body' => '3'])->assertStatus(429);

    $c = WebChatConversation::where('public_id', $id)->first();
    app(WebChatService::class)->generateReply($c->id);
    expect($c->refresh()->status)->toBe('waiting_human'); // sem cota de IA, chama a equipe
});

it('agenda pelo chat na mesma agenda do Bruno e não deixa agendar duas vezes', function () {
    Queue::fake();
    SystemSetting::setValue('booking_days', '0,1,2,3,4,5,6');
    [$id, $token] = wcStart($this);
    $h = ['X-Chat-Token' => $token];

    $days = $this->withHeaders($h)->getJson("/chat/{$id}/horarios")->assertOk()->json('days');
    expect($days)->not->toBeEmpty();
    $day = $days[0];

    $this->withHeaders($h)->postJson("/chat/{$id}/agendar", [
        'name' => 'Maria Souza', 'email' => 'maria@ong.org', 'phone' => '(16) 99999-8888',
        'date' => $day['date'], 'time' => $day['slots'][0],
    ])->assertOk()->assertJsonPath('has_booking', true);

    $booking = MeetingBooking::first();
    expect($booking->notes)->toContain('[via Bruno]')->toContain('Chat do site')
        ->and($booking->meeting_time)->toBe($day['slots'][0]);

    $c = WebChatConversation::where('public_id', $id)->first();
    expect($c->meeting_booking_id)->toBe($booking->id)
        ->and($c->visitor_email)->toBe('maria@ong.org')
        ->and($c->messages()->where('sender', 'system')->latest('id')->value('body'))->toContain('Reunião marcada');

    $this->withHeaders($h)->postJson("/chat/{$id}/agendar", [
        'name' => 'Maria Souza', 'email' => 'maria@ong.org', 'phone' => '16999998888',
        'date' => $day['date'], 'time' => $day['slots'][1] ?? $day['slots'][0],
    ])->assertStatus(422);
});

it('link por e-mail retoma a conversa em outro aparelho e invalida o segredo antigo', function () {
    Mail::fake();
    [$id, $token] = wcStart($this);

    $this->withHeaders(['X-Chat-Token' => $token])->postJson("/chat/{$id}/link-email", ['email' => 'ana@ong.org', 'name' => 'Ana'])
        ->assertOk()->assertJsonPath('has_email', true);
    Mail::assertQueued(WebChatResumeMail::class, fn ($m) => $m->hasTo('ana@ong.org'));

    $this->get("/chat/retomar/{$id}")->assertForbidden(); // sem assinatura

    $url = URL::temporarySignedRoute('web_chat.resume', now()->addDay(), ['conversation' => $id]);
    $page = $this->get($url)->assertOk()->assertSee('vivchat');
    preg_match('/token: "([A-Za-z0-9]{48})"/', $page->getContent(), $m);
    expect($m[1] ?? null)->not->toBeNull();

    $this->withHeaders(['X-Chat-Token' => $token])->getJson("/chat/{$id}/mensagens")->assertNotFound();
    $this->withHeaders(['X-Chat-Token' => $m[1]])->getJson("/chat/{$id}/mensagens")->assertOk();
});

it('equipe assume, responde, devolve ao Bruno e encerra pelo painel', function () {
    Queue::fake();
    $admin = wcAdmin();
    [$id, $token] = wcStart($this);
    $this->withHeaders(['X-Chat-Token' => $token])->postJson("/chat/{$id}/mensagens", ['body' => 'Quero falar com alguém']);
    $c = WebChatConversation::where('public_id', $id)->first();

    $this->actingAs($admin)->get(route('admin.web_chat.index'))->assertOk()->assertSee('Quero falar com alguém');
    $this->actingAs($admin)->get(route('admin.web_chat.show', $c))->assertOk()->assertSee('Blog: captar recursos');

    $this->actingAs($admin)->postJson(route('admin.web_chat.reply', $c), ['body' => 'Oi! Sou a Carla, posso ajudar.'])->assertOk();
    expect($c->refresh()->status)->toBe('human')->and($c->assigned_user_id)->toBe($admin->id);

    $last = collect($this->withHeaders(['X-Chat-Token' => $token])->getJson("/chat/{$id}/mensagens")->json('messages'))->last();
    expect($last['sender'])->toBe('agent')->and($last['name'])->toContain('Equipe Vivensi');

    $poll = $this->actingAs($admin)->getJson(route('admin.web_chat.poll', $c) . '?depois=0')->assertOk();
    expect($poll->json('messages'))->not->toBeEmpty();

    $this->actingAs($admin)->post(route('admin.web_chat.release', $c))->assertRedirect();
    expect($c->refresh()->status)->toBe('bot');

    $this->actingAs($admin)->post(route('admin.web_chat.close', $c))->assertRedirect(route('admin.web_chat.index'));
    expect($c->refresh()->status)->toBe('closed');
});

it('liga e desliga o chat pelo painel; usuário comum não acessa', function () {
    $admin = wcAdmin();
    $this->actingAs($admin)->post(route('admin.web_chat.settings'), ['enabled' => 0])->assertRedirect();
    expect(WebChatService::isEnabled())->toBeFalse();
    $this->actingAs($admin)->post(route('admin.web_chat.settings'), ['enabled' => 1]);
    expect(WebChatService::isEnabled())->toBeTrue();

    $user = User::factory()->create(['role' => 'manager']);
    $this->actingAs($user)->get(route('admin.web_chat.index'))->assertForbidden();
});

it('widget aparece nas páginas públicas só quando o chat está ligado', function () {
    // Chat ligado: o atendimento começa pelo Bruno e o botão do WhatsApp sai (fica no menu do chat)
    $this->get('/termos')->assertOk()->assertSee('vc-root', false)->assertSee('Fale com o Bruno')
        ->assertDontSee('class="whatsapp-float"', false)->assertSee('Prefere WhatsApp?');

    // Desligado: volta o botão do WhatsApp, o site nunca fica sem contato
    SystemSetting::setValue('web_chat_enabled', '0');
    $this->get('/termos')->assertOk()->assertDontSee('vc-root', false)->assertSee('class="whatsapp-float"', false);
});

it('na home, o Bruno substitui o cartão de agendamento e o botão do WhatsApp', function () {
    $this->get('/')->assertOk()
        ->assertSee('id="vc-launcher"', false)
        ->assertSee('id="vc-teaser"', false)
        ->assertSee('data-vivchat="agendar"', false)
        ->assertSee('Fale com o Bruno')
        ->assertDontSee('id="bookingWidget"', false)
        ->assertDontSee('class="whatsapp-float"', false);

    // Chat desligado: volta tudo como era (cartão de agendamento + WhatsApp)
    SystemSetting::setValue('web_chat_enabled', '0');
    $this->get('/')->assertOk()
        ->assertDontSee('data-vivchat', false)
        ->assertSee('id="bookingWidget"', false)
        ->assertSee('Falar com a equipe')
        ->assertSee('class="whatsapp-float"', false);
});

it('extrai nome, e-mail e telefone do que o visitante escreve', function () {
    $X = \App\Services\WebChat\VisitorContactExtractor::class;
    $ask = 'Pra começar, como posso te chamar?';

    expect($X::name('me chamo ana paula', null))->toBe('Ana Paula')
        ->and($X::name('Oi! Meu nome é Carlos Mendes, sou de uma ONG', null))->toBe('Carlos Mendes')
        ->and($X::name('Maria', $ask))->toBe('Maria')
        ->and($X::name('é a Joana.', $ask))->toBe('Joana')
        ->and($X::name('maria da silva', $ask))->toBe('Maria da Silva')
        ->and($X::name('Quais são os planos?', $ask))->toBeNull()   // pergunta, não nome
        ->and($X::name('sim', $ask))->toBeNull()
        ->and($X::name('Maria', 'Quer agendar?'))->toBeNull()        // sem pergunta de nome antes
        ->and($X::name('sou de uma ONG de Araraquara', null))->toBeNull();

    expect($X::email('meu e-mail é Ana@ONGLuz.org.'))->toBe('ana@ongluz.org')
        ->and($X::phone('meu zap 16 99999-8888'))->toBe('(16) 99999-8888')
        ->and($X::phone('+55 (11) 3456-7890'))->toBe('(11) 3456-7890')
        ->and($X::phone('temos 4 projetos e 2 convênios'))->toBeNull();
});

it('guarda nome e contato na conversa e orienta o Bruno a pedir só o que falta, uma vez', function () {
    Queue::fake();
    $notes = [];
    wcFakeBruce(function (...$args) use (&$notes) {
        $notes[] = $args[7]['note'] ?? '';
        return ['reply' => count($notes) === 1 ? 'Temos planos a partir de R$ 99! Aliás, com quem eu falo?' : 'Prazer! Posso te mandar a proposta? Qual o seu WhatsApp ou e-mail?'];
    });
    [$id, $token] = wcStart($this);
    $c = WebChatConversation::where('public_id', $id)->first();
    $h = ['X-Chat-Token' => $token];
    $svc = app(WebChatService::class);

    // 1) Pulou a saudação e perguntou do plano: Bruno responde e pede o nome
    $this->withHeaders($h)->postJson("/chat/{$id}/mensagens", ['body' => 'Quais são os planos?']);
    $svc->generateReply($c->id);
    expect($notes[0])->toContain('ainda não sabe o nome');

    // 2) Responde o nome → fica salvo; Bruno passa a pedir contato
    $this->withHeaders($h)->postJson("/chat/{$id}/mensagens", ['body' => 'Fernanda']);
    expect($c->refresh()->visitor_name)->toBe('Fernanda');
    $svc->generateReply($c->id);
    expect($notes[1])->toContain('se chama Fernanda')->toContain('Ainda não temos contato');

    // 3) Passa o WhatsApp → salvo; Bruno não pede de novo
    $this->withHeaders($h)->postJson("/chat/{$id}/mensagens", ['body' => 'pode ser no whats 16 98888-7777']);
    expect($c->refresh()->visitor_phone)->toBe('(16) 98888-7777');
    $svc->generateReply($c->id);
    expect($notes[2])->toContain('Contato já registrado')->toContain('não peça de novo');

    // Painel mostra quem é
    $admin = wcAdmin();
    $this->actingAs($admin)->get(route('admin.web_chat.index', ['status' => 'todas']))->assertSee('Fernanda');
});

it('se a pessoa não quis dizer o nome, o Bruno não insiste', function () {
    Queue::fake();
    $notes = [];
    wcFakeBruce(function (...$args) use (&$notes) { $notes[] = $args[7]['note'] ?? ''; return ['reply' => 'Claro! Aliás, com quem eu falo?']; });
    [$id, $token] = wcStart($this);
    $c = WebChatConversation::where('public_id', $id)->first();
    $h = ['X-Chat-Token' => $token];

    $this->withHeaders($h)->postJson("/chat/{$id}/mensagens", ['body' => 'Vocês têm app?']);
    app(WebChatService::class)->generateReply($c->id);
    $this->withHeaders($h)->postJson("/chat/{$id}/mensagens", ['body' => 'Prefiro não dizer, só quero saber do app']);
    app(WebChatService::class)->generateReply($c->id);

    expect($c->refresh()->visitor_name)->toBeNull()
        ->and($notes[1])->toContain('já perguntou o nome');
});

it('corta o nome onde a frase continua', function () {
    $X = \App\Services\WebChat\VisitorContactExtractor::class;
    expect($X::name('me chamo ana e sou da ONG Luz', null))->toBe('Ana')
        ->and($X::name('meu nome é joão pedro da silva, trabalho num instituto', null))->toBe('João Pedro da Silva')
        ->and($X::name('aqui é a Bia do Instituto Sol', null))->toBe('Bia')
        ->and($X::name('aqui é muito caro pra gente', null))->toBeNull();
});
