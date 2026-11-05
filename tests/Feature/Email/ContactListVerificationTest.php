<?php

use App\Jobs\VerifyEmailContactListJob;
use App\Models\EmailContact;
use App\Models\EmailContactList;
use App\Models\EmailSuppression;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CampaignMail\EmailVerifier;
use App\Services\CampaignMail\VerificationUnavailableException;
use Illuminate\Support\Facades\Queue;

/**
 * "Verificar lista": básica (formato, bloqueios, domínio) e aprofundada (AWS).
 */

function fakeSes(array $verdicts = [], bool $denied = false): object
{
    return new class($verdicts, $denied) {
        public array $calls = [];
        public function __construct(private array $verdicts, private bool $denied) {}
        public function getEmailAddressInsights(array $args)
        {
            $this->calls[] = $args['EmailAddress'];
            if ($this->denied) {
                throw new \Aws\Exception\AwsException('User is not authorized to perform: ses:GetEmailAddressInsights',
                    new \Aws\Command('GetEmailAddressInsights'), ['code' => 'AccessDeniedException']);
            }
            $v = $this->verdicts[$args['EmailAddress']] ?? ['IsValid' => 'HIGH'];
            $wrap = fn ($x) => $x ? ['ConfidenceVerdict' => $x] : null;
            return ['MailboxValidation' => [
                'IsValid'     => $wrap($v['IsValid'] ?? null),
                'Evaluations' => array_filter([
                    'MailboxExists' => $wrap($v['MailboxExists'] ?? null),
                    'IsDisposable'  => $wrap($v['IsDisposable'] ?? null),
                ]),
            ]];
        }
    };
}

function verifierWith(?object $ses = null): EmailVerifier
{
    // "morto.org" simula domínio que não recebe e-mail; o resto existe
    return new EmailVerifier(fn (string $d) => $d !== 'morto.org', $ses);
}

function listWith(array $emails): EmailContactList
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $list = EmailContactList::withoutGlobalScopes()->create([
        'tenant_id' => $tenant->id, 'name' => 'Lista teste', 'opt_in_confirmed' => true, 'opt_in_confirmed_at' => now(),
    ]);
    foreach ($emails as $e) {
        EmailContact::withoutGlobalScopes()->create([
            'email_contact_list_id' => $list->id, 'tenant_id' => $tenant->id, 'email' => $e, 'status' => 'active', 'added_at' => now(),
        ]);
    }
    return $list;
}

it('verificação básica: formato, bloqueio anterior e domínio', function () {
    EmailSuppression::suppress('bounce@ok.org', EmailSuppression::REASON_HARD_BOUNCE);
    $v = verifierWith();

    expect($v->verify('nao-e-email')['result'])->toBe('invalid')
        ->and($v->verify('bounce@ok.org'))->toMatchArray(['result' => 'invalid', 'suppression' => 'hard_bounce'])
        ->and($v->verify('alguem@morto.org')['detail'])->toContain('domínio não recebe')
        ->and($v->verify('Ana@OK.org')['result'])->toBe('valid');
});

it('verificação aprofundada: HIGH válido, MEDIUM arriscado, LOW inválido com motivo', function () {
    $ses = fakeSes([
        'a@ok.org' => ['IsValid' => 'HIGH'],
        'b@ok.org' => ['IsValid' => 'MEDIUM'],
        'c@ok.org' => ['IsValid' => 'LOW', 'MailboxExists' => 'LOW'],
    ]);
    $v = verifierWith($ses);

    expect($v->verify('a@ok.org', true)['result'])->toBe('valid')
        ->and($v->verify('b@ok.org', true)['result'])->toBe('risky')
        ->and($v->verify('c@ok.org', true))->toMatchArray(['result' => 'invalid'])
        ->and($v->verify('c@ok.org', true)['detail'])->toContain('caixa de e-mail provavelmente não existe');

    // Domínio morto nem chega a consultar (e pagar) a AWS
    $v->verify('x@morto.org', true);
    expect($ses->calls)->not->toContain('x@morto.org');
});

it('sem permissão na AWS lança VerificationUnavailableException', function () {
    verifierWith(fakeSes([], denied: true))->verify('a@ok.org', true);
})->throws(VerificationUnavailableException::class);

it('job marca inválidos, reativa quem voltou a ser válido e grava o resumo', function () {
    $list = listWith(['ok@ok.org', 'morto@morto.org', 'volta@ok.org', 'saiu@ok.org']);
    EmailContact::where('email', 'volta@ok.org')->update(['status' => 'invalid']);
    EmailSuppression::suppress('saiu@ok.org', EmailSuppression::REASON_UNSUBSCRIBE);
    app()->instance(EmailVerifier::class, verifierWith());

    $list->forceFill(['verification_status' => 'running', 'verification_started_at' => now()->subSecond()])->save();
    VerifyEmailContactListJob::dispatchSync($list->id, false);

    $status = EmailContact::where('email_contact_list_id', $list->id)->pluck('status', 'email')->all();
    expect($status)->toEqualCanonicalizing(['ok@ok.org' => 'active', 'morto@morto.org' => 'invalid', 'volta@ok.org' => 'active', 'saiu@ok.org' => 'unsubscribed'])
        ->and($status['morto@morto.org'])->toBe('invalid')
        ->and($status['saiu@ok.org'])->toBe('unsubscribed')
        ->and($status['volta@ok.org'])->toBe('active');

    $list->refresh();
    expect($list->verification_status)->toBe('done')
        ->and($list->verification_summary)->toMatchArray(['checked' => 4, 'valid' => 2, 'invalid' => 2]);
});

it('job sem permissão na AWS continua só com a básica e avisa', function () {
    $list = listWith(['a@ok.org', 'b@morto.org']);
    app()->instance(EmailVerifier::class, verifierWith(fakeSes([], denied: true)));

    $list->forceFill(['verification_status' => 'running', 'verification_deep' => true, 'verification_started_at' => now()->subSecond()])->save();
    VerifyEmailContactListJob::dispatchSync($list->id, true);

    $list->refresh();
    expect($list->verification_status)->toBe('done')
        ->and($list->verification_summary['warning'])->toContain('ses:GetEmailAddressInsights')
        ->and(EmailContact::where('email', 'b@morto.org')->value('status'))->toBe('invalid');
});

it('job processa em blocos e se re-agenda', function () {
    Queue::fake();
    $list = listWith(array_map(fn ($i) => "c{$i}@ok.org", range(1, VerifyEmailContactListJob::CHUNK + 5)));
    app()->instance(EmailVerifier::class, verifierWith());
    $list->forceFill(['verification_status' => 'running', 'verification_started_at' => now()->subSecond()])->save();

    (new VerifyEmailContactListJob($list->id))->handle(app(EmailVerifier::class));

    Queue::assertPushed(VerifyEmailContactListJob::class, fn ($j) => $j->afterId > 0);
    expect(EmailContact::whereNotNull('verified_at')->count())->toBe(VerifyEmailContactListJob::CHUNK)
        ->and($list->refresh()->verification_status)->toBe('running');
});

it('botão Verificar lista enfileira o job e mostra o custo da aprofundada', function () {
    Queue::fake();
    $list  = listWith(['a@ok.org', 'b@ok.org']);
    $admin = User::factory()->create(['tenant_id' => $list->tenant_id, 'role' => 'super_admin']);

    $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class)
        ->actingAs($admin)
        ->post(route('admin.email_campaigns.lists.verify', $list), ['deep' => '1'])
        ->assertSessionHas('success', fn ($m) => str_contains($m, '2 contato(s)') && str_contains($m, 'US$ 0,02'));

    Queue::assertPushed(VerifyEmailContactListJob::class, fn ($j) => $j->deep === true);
    expect($list->refresh()->verification_status)->toBe('running');
});

it('contato inválido fica fora do envio da campanha', function () {
    $list = listWith(['ok@ok.org', 'ruim@morto.org']);
    EmailContact::where('email', 'ruim@morto.org')->update(['status' => 'invalid']);

    $ref = new ReflectionMethod(\App\Http\Controllers\Admin\EmailCampaignController::class, 'resolveRecipients');
    $ref->setAccessible(true);
    $emails = array_column($ref->invoke(app(\App\Http\Controllers\Admin\EmailCampaignController::class), 'contact_list', null, $list->id), 'email');

    expect($emails)->toBe(['ok@ok.org']);
});
