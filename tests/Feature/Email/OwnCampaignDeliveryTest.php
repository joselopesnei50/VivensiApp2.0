<?php

use App\Jobs\SendOwnCampaignBatchJob;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\EmailSuppression;
use App\Models\Tenant;
use App\Services\CampaignMail\CampaignRenderer;
use App\Services\CampaignMail\OwnCampaignDispatcher;
use App\Services\EmailUnsubscribeTokenService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Campanhas com envio próprio (SES) — fila, renderização, rastreamento,
 * descadastro, webhook de bounce/reclamação e freios de segurança.
 */

beforeEach(function () {
    Cache::flush();
    config([
        'campaign_mail.mailer'          => 'array',
        'campaign_mail.rate_per_minute' => 500,
        'campaign_mail.max_per_second'  => 1000,
        'campaign_mail.daily_limit'     => 0,
        'campaign_mail.from.address'    => 'contato@contato.vivensi.app.br',
        'campaign_mail.from.name'       => 'Vivensi',
    ]);
});

function ownCampaign(array $attrs = []): EmailCampaign
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $admin  = \App\Models\User::factory()->create(['tenant_id' => $tenant->id]);

    return EmailCampaign::withoutGlobalScopes()->create(array_merge([
        'tenant_id'       => $tenant->id,
        'created_by'      => $admin->id,
        'name'            => 'Prospecção OSCs',
        'subject'         => 'Gestão para sua OSC',
        'html_content'    => '<html><body><p>Olá {primeiro_nome}!</p><a href="https://vivensi.app.br/planos">Ver planos</a></body></html>',
        'audience_type'   => 'manual',
        'delivery_driver' => EmailCampaign::DRIVER_OWN,
        'status'          => 'draft',
    ], $attrs));
}

function sentMessages(): array
{
    return app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->all();
}

it('envia para todos, personaliza, rastreia e conclui a campanha', function () {
    $campaign = ownCampaign();

    $queued = app(OwnCampaignDispatcher::class)->start($campaign, [
        ['email' => 'Maria@OSC.org.br', 'name' => 'Maria Silva'],
        ['email' => 'joao@instituto.org', 'name' => 'João'],
        ['email' => 'maria@osc.org.br', 'name' => 'Duplicada'],
        ['email' => 'invalido', 'name' => 'X'],
    ]);

    expect($queued)->toBe(2);

    $campaign->refresh();
    expect($campaign->status)->toBe('sent')
        ->and($campaign->stat_sent)->toBe(2)
        ->and($campaign->stat_failed)->toBe(0)
        ->and($campaign->recipient_count)->toBe(2);

    $messages = sentMessages();
    expect($messages)->toHaveCount(2);

    $email = $messages[0]->getOriginalMessage();
    $html  = $email->getHtmlBody();

    expect($html)->toContain('Olá Maria!')
        ->and($html)->toContain('/e/o/')                       // pixel
        ->and($html)->toContain('/e/c/')                       // link rastreado
        ->and($html)->not->toContain('href="https://vivensi.app.br/planos"')
        ->and($html)->toContain('/email/descadastro/');         // rodapé LGPD

    $headers = $email->getHeaders();
    expect($headers->get('List-Unsubscribe-Post')->getBodyAsString())->toBe('List-Unsubscribe=One-Click')
        ->and($headers->get('List-Unsubscribe')->getBodyAsString())->toContain('/1-clique')
        ->and($headers->get('X-Vivensi-Campaign')->getBodyAsString())->toBe((string) $campaign->id);
});

it('não envia para e-mails bloqueados (bounce, spam ou descadastro)', function () {
    EmailSuppression::suppress('bounce@x.org', EmailSuppression::REASON_HARD_BOUNCE);
    EmailSuppression::suppress('saiu@x.org', EmailSuppression::REASON_UNSUBSCRIBE);

    $campaign = ownCampaign();
    app(OwnCampaignDispatcher::class)->start($campaign, [
        ['email' => 'bounce@x.org'], ['email' => 'saiu@x.org'], ['email' => 'ok@x.org'],
    ]);

    expect(sentMessages())->toHaveCount(1)
        ->and(EmailCampaignRecipient::pluck('email')->all())->toBe(['ok@x.org']);
});

it('reprocessar só reenvia quem falhou, nunca quem já recebeu', function () {
    $campaign = ownCampaign();
    app(OwnCampaignDispatcher::class)->start($campaign, [['email' => 'a@x.org'], ['email' => 'b@x.org']]);
    expect(sentMessages())->toHaveCount(2);

    EmailCampaignRecipient::where('email', 'b@x.org')->update(['status' => 'failed', 'error' => 'timeout']);
    $campaign->refresh()->update(['status' => 'error']);

    app(OwnCampaignDispatcher::class)->start($campaign->refresh(), [['email' => 'a@x.org'], ['email' => 'b@x.org']]);

    $to = array_map(fn ($m) => $m->getOriginalMessage()->getTo()[0]->getAddress(), sentMessages());
    expect($to)->toBe(['a@x.org', 'b@x.org', 'b@x.org']);
});

it('respeita a pausa: não envia nada enquanto pausada e retoma de onde parou', function () {
    $campaign = ownCampaign(['status' => 'paused']);
    foreach (['a', 'b', 'c'] as $n) {
        EmailCampaignRecipient::create(['email_campaign_id' => $campaign->id, 'email' => "$n@x.org", 'token' => str_repeat($n, 40), 'status' => 'queued']);
    }

    SendOwnCampaignBatchJob::dispatchSync($campaign->id);
    expect(sentMessages())->toHaveCount(0);

    $campaign->update(['status' => 'sending']);
    SendOwnCampaignBatchJob::dispatchSync($campaign->id);

    expect(sentMessages())->toHaveCount(3)
        ->and($campaign->refresh()->status)->toBe('sent');
});

it('pausa automaticamente quando o bounce passa do limite', function () {
    config(['campaign_mail.safety.min_sample' => 5, 'campaign_mail.safety.max_bounce_rate' => 5, 'campaign_mail.rate_per_minute' => 2]);

    $campaign = ownCampaign(['status' => 'sending', 'recipient_count' => 14]);
    for ($i = 0; $i < 10; $i++) {
        EmailCampaignRecipient::create([
            'email_campaign_id' => $campaign->id, 'email' => "b$i@x.org", 'token' => sprintf('%040d', $i),
            'status' => $i < 3 ? 'bounced' : 'sent', 'sent_at' => now(), 'bounced_at' => $i < 3 ? now() : null,
        ]);
    }
    for ($i = 0; $i < 4; $i++) {
        EmailCampaignRecipient::create(['email_campaign_id' => $campaign->id, 'email' => "q$i@x.org", 'token' => sprintf('q%039d', $i), 'status' => 'queued']);
    }

    SendOwnCampaignBatchJob::dispatchSync($campaign->id);

    $campaign->refresh();
    expect($campaign->status)->toBe('paused')
        ->and($campaign->error_message)->toContain('bounce')
        ->and(EmailCampaignRecipient::where('status', 'queued')->count())->toBe(2); // 1 lote saiu, o resto aguarda
});

it('respeita o teto diário de aquecimento', function () {
    config(['campaign_mail.daily_limit' => 2]);
    \Illuminate\Support\Facades\Queue::fake();

    $campaign = ownCampaign(['status' => 'sending']);
    foreach (['a', 'b', 'c'] as $n) {
        EmailCampaignRecipient::create(['email_campaign_id' => $campaign->id, 'email' => "$n@x.org", 'token' => str_repeat($n, 40), 'status' => 'queued']);
    }

    (new SendOwnCampaignBatchJob($campaign->id))->handle(app(CampaignRenderer::class), app(OwnCampaignDispatcher::class));

    expect(sentMessages())->toHaveCount(2)
        ->and(EmailCampaignRecipient::where('status', 'queued')->count())->toBe(1);
});

it('registra abertura pelo pixel', function () {
    $campaign = ownCampaign();
    $r = EmailCampaignRecipient::create(['email_campaign_id' => $campaign->id, 'email' => 'a@x.org', 'token' => str_repeat('a', 40), 'status' => 'sent']);

    $this->get('/e/o/' . $r->token)->assertOk()->assertHeader('Content-Type', 'image/gif');
    $this->get('/e/o/' . $r->token);

    $r->refresh();
    expect($r->open_count)->toBe(2)->and($r->opened_at)->not->toBeNull();
});

it('registra clique com URL assinada e recusa URL adulterada', function () {
    $campaign = ownCampaign();
    $r = EmailCampaignRecipient::create(['email_campaign_id' => $campaign->id, 'email' => 'a@x.org', 'token' => str_repeat('a', 40), 'status' => 'sent']);

    $url = 'https://vivensi.app.br/planos';
    $this->get('/e/c/' . $r->token . '?u=' . CampaignRenderer::encodeUrl($url) . '&s=' . CampaignRenderer::sign($r->token, $url))
        ->assertRedirect($url);

    // Open redirect: troca a URL mantendo a assinatura original
    $this->get('/e/c/' . $r->token . '?u=' . CampaignRenderer::encodeUrl('https://phishing.example') . '&s=' . CampaignRenderer::sign($r->token, $url))
        ->assertRedirect('/');

    $r->refresh();
    expect($r->click_count)->toBe(1)->and($r->opened_at)->not->toBeNull();
});

it('descadastro em 1 clique bloqueia o e-mail e reativar desbloqueia', function () {
    $campaign = ownCampaign();
    $r = EmailCampaignRecipient::create(['email_campaign_id' => $campaign->id, 'email' => 'a@x.org', 'token' => str_repeat('a', 40), 'status' => 'sent']);
    $token = app(EmailUnsubscribeTokenService::class)->generate('a@x.org', $campaign->tenant_id);

    $this->post('/email/descadastro/' . $token . '/1-clique?r=' . $r->token, ['List-Unsubscribe' => 'One-Click'])->assertOk();

    expect(EmailSuppression::where('email', 'a@x.org')->value('reason'))->toBe('unsubscribe')
        ->and($r->refresh()->unsubscribed_at)->not->toBeNull();

    $this->post('/email/descadastro/' . $token . '/reativar')->assertOk();
    expect(EmailSuppression::where('email', 'a@x.org')->exists())->toBeFalse();
});

it('reativar nunca remove bloqueio por hard bounce', function () {
    $campaign = ownCampaign();
    EmailSuppression::suppress('a@x.org', EmailSuppression::REASON_HARD_BOUNCE);
    $token = app(EmailUnsubscribeTokenService::class)->generate('a@x.org', $campaign->tenant_id);

    $this->post('/email/descadastro/' . $token . '/reativar')->assertOk();

    expect(EmailSuppression::where('email', 'a@x.org')->value('reason'))->toBe('hard_bounce');
});

// ── Webhook SES/SNS ───────────────────────────────────────────────────────────

function snsSigned(array $payload): array
{
    static $key = null, $certPem = null;
    if (!$key) {
        $key  = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $csr  = openssl_csr_new(['commonName' => 'sns.amazonaws.com'], $key);
        openssl_x509_export(openssl_csr_sign($csr, null, $key, 1), $certPem);
    }
    $certUrl = 'https://sns.us-east-1.amazonaws.com/SimpleNotificationService-test.pem';
    Http::fake([$certUrl => Http::response($certPem, 200)]);

    $payload += ['MessageId' => 'm-1', 'Timestamp' => now()->toIso8601String(), 'TopicArn' => 'arn:aws:sns:us-east-1:1:vivensi', 'SignatureVersion' => '2', 'SigningCertURL' => $certUrl];
    $keys = $payload['Type'] === 'Notification'
        ? ['Message', 'MessageId', 'Subject', 'Timestamp', 'TopicArn', 'Type']
        : ['Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'];
    $str = '';
    foreach ($keys as $k) {
        if (array_key_exists($k, $payload)) $str .= $k . "\n" . $payload[$k] . "\n";
    }
    openssl_sign($str, $sig, $key, OPENSSL_ALGO_SHA256);
    $payload['Signature'] = base64_encode($sig);
    return $payload;
}

it('webhook SES: hard bounce marca destinatário e bloqueia o e-mail', function () {
    $campaign = ownCampaign(['status' => 'sent']);
    EmailCampaignRecipient::create(['email_campaign_id' => $campaign->id, 'email' => 'a@x.org', 'token' => str_repeat('a', 40), 'status' => 'sent', 'sent_at' => now(), 'provider_message_id' => 'ses-123']);

    $payload = snsSigned(['Type' => 'Notification', 'Message' => json_encode([
        'eventType' => 'Bounce',
        'mail'      => ['messageId' => 'ses-123'],
        'bounce'    => ['bounceType' => 'Permanent', 'bouncedRecipients' => [['emailAddress' => 'a@x.org', 'diagnosticCode' => '550 user unknown']]],
    ])]);

    $this->postJson('/api/webhooks/ses-campaigns', $payload)->assertOk();

    expect(EmailSuppression::where('email', 'a@x.org')->value('reason'))->toBe('hard_bounce')
        ->and(EmailCampaignRecipient::first()->status)->toBe('bounced')
        ->and($campaign->refresh()->stat_bounces)->toBe(1);
});

it('webhook SES: reclamação de spam bloqueia o e-mail', function () {
    $campaign = ownCampaign(['status' => 'sent']);
    EmailCampaignRecipient::create(['email_campaign_id' => $campaign->id, 'email' => 'a@x.org', 'token' => str_repeat('a', 40), 'status' => 'sent', 'sent_at' => now(), 'provider_message_id' => 'ses-9']);

    $payload = snsSigned(['Type' => 'Notification', 'Message' => json_encode([
        'notificationType' => 'Complaint',
        'mail'             => ['messageId' => 'ses-9'],
        'complaint'        => ['complainedRecipients' => [['emailAddress' => 'a@x.org']]],
    ])]);

    $this->postJson('/api/webhooks/ses-campaigns', $payload)->assertOk();

    expect(EmailSuppression::where('email', 'a@x.org')->value('reason'))->toBe('complaint')
        ->and($campaign->refresh()->stat_spam)->toBe(1);
});

it('webhook SES: recusa assinatura inválida e certificado fora da AWS', function () {
    $payload = snsSigned(['Type' => 'Notification', 'Message' => json_encode(['eventType' => 'Bounce'])]);

    $tampered = $payload;
    $tampered['Message'] = json_encode(['eventType' => 'Complaint']);
    $this->postJson('/api/webhooks/ses-campaigns', $tampered)->assertForbidden();

    $evilCert = $payload;
    $evilCert['SigningCertURL'] = 'https://evil.example.com/cert.pem';
    $this->postJson('/api/webhooks/ses-campaigns', $evilCert)->assertForbidden();
});

it('watchdog retoma campanha própria parada em vez de marcar erro', function () {
    \Illuminate\Support\Facades\Queue::fake();

    $campaign = ownCampaign(['status' => 'sending']);
    EmailCampaignRecipient::create(['email_campaign_id' => $campaign->id, 'email' => 'meio@x.org', 'token' => str_repeat('m', 40), 'status' => 'sending']);
    EmailCampaignRecipient::create(['email_campaign_id' => $campaign->id, 'email' => 'fila@x.org', 'token' => str_repeat('f', 40), 'status' => 'queued']);
    EmailCampaign::withoutGlobalScopes()->whereKey($campaign->id)->update(['updated_at' => now()->subHours(2)]);

    $this->artisan('emails:unstuck-campaigns')->assertSuccessful();

    expect($campaign->refresh()->status)->toBe('sending')
        ->and(EmailCampaignRecipient::where('email', 'meio@x.org')->value('status'))->toBe('failed');
    \Illuminate\Support\Facades\Queue::assertPushed(SendOwnCampaignBatchJob::class);
});

it('grava o MessageId do SES (header X-SES-Message-ID), que é o usado nos eventos SNS', function () {
    $email = (new \Symfony\Component\Mime\Email())->from('a@x.org')->to('b@x.org')->text('x');
    $email->getHeaders()->addTextHeader('X-SES-Message-ID', '0100018f-ses-id');
    $sent = new \Illuminate\Mail\SentMessage(new \Symfony\Component\Mailer\SentMessage($email, \Symfony\Component\Mailer\Envelope::create($email)));

    $ref = new ReflectionMethod(SendOwnCampaignBatchJob::class, 'messageId');
    $ref->setAccessible(true);

    expect($ref->invoke(new SendOwnCampaignBatchJob(1), $sent))->toBe('0100018f-ses-id');
});

it('erro de credencial da AWS pausa a campanha em vez de marcar todos como falha', function () {
    config(['campaign_mail.mailer' => 'campaign_ses', 'mail.mailers.campaign_ses.key' => null, 'mail.mailers.campaign_ses.secret' => null]);
    \Illuminate\Support\Facades\Mail::shouldReceive('mailer->send')->andThrow(new \RuntimeException('Error retrieving credentials from the instance profile metadata service.'));

    $campaign = ownCampaign(['status' => 'sending']);
    foreach (['a', 'b', 'c'] as $n) {
        EmailCampaignRecipient::create(['email_campaign_id' => $campaign->id, 'email' => "$n@x.org", 'token' => str_repeat($n, 40), 'status' => 'queued']);
    }

    SendOwnCampaignBatchJob::dispatchSync($campaign->id);

    expect($campaign->refresh()->status)->toBe('paused')
        ->and(EmailCampaignRecipient::where('status', 'queued')->count())->toBe(2)
        ->and(EmailCampaignRecipient::where('status', 'failed')->count())->toBe(1);
});

it('motor padrão de nova campanha vem da config (brevo até o SES estar pronto)', function () {
    $campaign = new EmailCampaign();
    expect(config('campaign_mail.default_driver'))->toBe('brevo')
        ->and($campaign->usesOwnDelivery())->toBeFalse();
});

it('usa o Responder-para padrão quando a campanha não define um', function () {
    config(['campaign_mail.reply_to' => 'contato@vivensi.app.br']);

    app(OwnCampaignDispatcher::class)->start(ownCampaign(), [['email' => 'a@x.org']]);
    app(OwnCampaignDispatcher::class)->start(ownCampaign(['reply_to_email' => 'outro@vivensi.app.br']), [['email' => 'b@x.org']]);

    $replyTo = array_map(fn ($m) => $m->getOriginalMessage()->getReplyTo()[0]->getAddress(), sentMessages());
    expect($replyTo)->toBe(['contato@vivensi.app.br', 'outro@vivensi.app.br']);
});

it('bounce depois de descadastro vira bloqueio permanente e reativar não remove', function () {
    $campaign = ownCampaign();
    EmailSuppression::suppress('a@x.org', EmailSuppression::REASON_UNSUBSCRIBE);
    EmailSuppression::suppress('a@x.org', EmailSuppression::REASON_HARD_BOUNCE, '550 user unknown');
    EmailSuppression::suppress('a@x.org', EmailSuppression::REASON_UNSUBSCRIBE); // não rebaixa

    expect(EmailSuppression::where('email', 'a@x.org')->value('reason'))->toBe('hard_bounce');

    $token = app(EmailUnsubscribeTokenService::class)->generate('a@x.org', $campaign->tenant_id);
    $this->post('/email/descadastro/' . $token . '/reativar')->assertOk();

    expect(EmailSuppression::where('email', 'a@x.org')->value('reason'))->toBe('hard_bounce');
});

it('teto diário conta lotes reservados por outros workers (status sending)', function () {
    config(['campaign_mail.daily_limit' => 3]);
    \Illuminate\Support\Facades\Queue::fake();

    // Outra campanha com 2 e-mails já reservados num lote em andamento
    $other = ownCampaign(['status' => 'sending']);
    foreach (['x', 'y'] as $n) {
        EmailCampaignRecipient::create(['email_campaign_id' => $other->id, 'email' => "$n@x.org", 'token' => str_repeat($n, 40), 'status' => 'sending', 'claim_token' => 'outro-worker']);
    }

    $campaign = ownCampaign(['status' => 'sending']);
    foreach (['a', 'b', 'c'] as $n) {
        EmailCampaignRecipient::create(['email_campaign_id' => $campaign->id, 'email' => "$n@x.org", 'token' => str_repeat($n, 40), 'status' => 'queued']);
    }

    (new SendOwnCampaignBatchJob($campaign->id))->handle(app(CampaignRenderer::class), app(OwnCampaignDispatcher::class));

    expect(sentMessages())->toHaveCount(1)
        ->and(EmailCampaignRecipient::where('email_campaign_id', $campaign->id)->where('status', 'queued')->count())->toBe(2);
});

it('webhook SES: destinatário com nome ("Nome" <email>) é normalizado, marcado e bloqueado', function () {
    $campaign = ownCampaign(['status' => 'sent']);
    EmailCampaignRecipient::create(['email_campaign_id' => $campaign->id, 'email' => 'bounce@simulator.amazonses.com', 'name' => 'Teste Bounce', 'token' => str_repeat('b', 40), 'status' => 'sent', 'sent_at' => now(), 'provider_message_id' => 'ses-b']);
    EmailCampaignRecipient::create(['email_campaign_id' => $campaign->id, 'email' => 'complaint@simulator.amazonses.com', 'name' => 'Teste Spam', 'token' => str_repeat('c', 40), 'status' => 'sent', 'sent_at' => now(), 'provider_message_id' => 'ses-c']);

    // Formato real recebido em produção (04/11/2026)
    $this->postJson('/api/webhooks/ses-campaigns', snsSigned(['Type' => 'Notification', 'Message' => json_encode([
        'eventType' => 'Bounce',
        'mail'      => ['messageId' => 'ses-b'],
        'bounce'    => ['bounceType' => 'Permanent', 'bouncedRecipients' => [['emailAddress' => '"teste bounce" <bounce@simulator.amazonses.com>']]],
    ])]))->assertOk();
    $this->postJson('/api/webhooks/ses-campaigns', snsSigned(['Type' => 'Notification', 'Message' => json_encode([
        'eventType' => 'Complaint',
        'mail'      => ['messageId' => 'ses-c'],
        'complaint' => ['complainedRecipients' => [['emailAddress' => 'teste spam <complaint@simulator.amazonses.com>']]],
    ])]))->assertOk();

    expect(EmailSuppression::pluck('reason', 'email')->all())->toBe([
            'bounce@simulator.amazonses.com'    => 'hard_bounce',
            'complaint@simulator.amazonses.com' => 'complaint',
        ])
        ->and(EmailCampaignRecipient::where('email', 'bounce@simulator.amazonses.com')->value('status'))->toBe('bounced')
        ->and(EmailCampaignRecipient::where('email', 'complaint@simulator.amazonses.com')->value('status'))->toBe('complained')
        ->and($campaign->refresh()->stat_bounces)->toBe(1)
        ->and($campaign->stat_spam)->toBe(1);

    // E o bloqueio funciona num envio novo
    app(OwnCampaignDispatcher::class)->start(ownCampaign(), [['email' => 'bounce@simulator.amazonses.com'], ['email' => 'ok@x.org']]);
    $to = array_map(fn ($m) => $m->getOriginalMessage()->getTo()[0]->getAddress(), sentMessages());
    expect($to)->toBe(['ok@x.org']);
});

it('normalizeEmail cobre os formatos do SES', function () {
    expect(EmailSuppression::normalizeEmail('"Maria Silva" <Maria@OSC.org.br>'))->toBe('maria@osc.org.br')
        ->and(EmailSuppression::normalizeEmail('teste spam <a@x.org>'))->toBe('a@x.org')
        ->and(EmailSuppression::normalizeEmail('  B@X.ORG '))->toBe('b@x.org')
        ->and(EmailSuppression::normalizeEmail(''))->toBe('');
});

// ── Melhorias: saudação sem nome, versão em texto, mensagens e detalhes ──────

it('saudação: contato sem nome (ou com o e-mail como nome) não mostra o endereço', function () {
    $r = app(CampaignRenderer::class);

    expect($r->personalize('<p>Olá, {primeiro_nome}!</p>', '', 'a@x.org'))->toBe('<p>Olá!</p>')
        ->and($r->personalize('<p>Olá, {primeiro_nome}!</p>', 'a@x.org', 'a@x.org'))->toBe('<p>Olá!</p>')
        ->and($r->personalize('<p>{primeiro_nome}, veja isto</p>', '', 'a@x.org'))->toBe('<p>veja isto</p>')
        ->and($r->personalize('<p>Olá {primeiro_nome}!</p>', '', 'a@x.org'))->toBe('<p>Olá!</p>')
        ->and($r->personalize('<p>Olá, {primeiro_nome}!</p>', 'Maria Silva', 'a@x.org'))->toBe('<p>Olá, Maria!</p>')
        ->and($r->personalize('<p>Oi {primeiro_nome|amigo(a)}!</p>', '', 'a@x.org'))->toBe('<p>Oi amigo(a)!</p>')
        ->and($r->personalize('<p>Oi {primeiro_nome|amigo(a)}!</p>', 'João', 'a@x.org'))->toBe('<p>Oi João!</p>')
        ->and($r->personalize('<p>{nome} — {email}</p>', 'Ana <b>', 'a@x.org'))->toBe('<p>Ana &lt;b&gt; — a@x.org</p>');
});

it('e-mail sai com versão em texto simples, com links reais e sem pixel', function () {
    $campaign = ownCampaign();
    app(OwnCampaignDispatcher::class)->start($campaign, [['email' => 'a@x.org']]);

    $email = sentMessages()[0]->getOriginalMessage();
    $text  = $email->getTextBody();

    expect($email->getHtmlBody())->toContain('/e/c/')                       // HTML segue rastreado
        ->and($text)->toContain('Olá!')                                     // sem nome → saudação neutra
        ->and($text)->toContain('Ver planos (https://vivensi.app.br/planos)')
        ->and($text)->toContain('/email/descadastro/')                      // descadastro presente no texto
        ->and($text)->not->toContain('/e/o/')
        ->and($text)->not->toContain('<');
});

it('htmlToText ignora pré-cabeçalho oculto e estilos', function () {
    $html = '<html><head><style>p{color:red}</style></head><body><div style="display:none">pre</div><p>Linha 1</p><p>Linha&nbsp;2</p></body></html>';
    expect(CampaignRenderer::htmlToText($html))->toBe("Linha 1\n\nLinha 2");
});

it('disparo explica quando todos os endereços estão bloqueados', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $admin  = \App\Models\User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'super_admin']);
    EmailSuppression::suppress('bounce@x.org', EmailSuppression::REASON_HARD_BOUNCE);
    EmailSuppression::suppress('spam@x.org', EmailSuppression::REASON_COMPLAINT);

    $campaign = ownCampaign([
        'tenant_id'     => $tenant->id,
        'created_by'    => $admin->id,
        'manual_emails' => json_encode([['email' => 'bounce@x.org', 'name' => ''], ['email' => 'spam@x.org', 'name' => '']]),
    ]);

    $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class)
        ->actingAs($admin)
        ->post(route('admin.email_campaigns.send', $campaign))
        ->assertSessionHas('error', 'Nenhum destinatário novo para enviar: 2 endereços bloqueados (bounce, spam ou descadastro).');
});
