<?php

use App\Mail\Transport\BrevoApiTransport;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    SystemSetting::setValue('brevo_api_key', 'test-brevo-key-xyz');
    SystemSetting::setValue('email_from', 'noreply@vivensi.app.br');
    SystemSetting::setValue('email_from_name', 'Vivensi Testes');

    // Aponta o mailer padrao pro nosso driver 'brevo' pra este teste
    config(['mail.default' => 'brevo']);
    config(['mail.mailers.brevo' => ['transport' => 'brevo']]);
    config(['mail.from' => ['address' => 'noreply@vivensi.app.br', 'name' => 'Vivensi Testes']]);

    Mail::extend('brevo', fn () => new BrevoApiTransport());
    Mail::purge('brevo');
});

it('envia via API Brevo com payload esperado (sender + to + subject + html)', function () {
    Http::fake([
        'https://api.brevo.com/v3/smtp/email' => Http::response(['messageId' => 'brevo_msg_1'], 201),
    ]);

    Mail::raw('conteudo em texto puro', function ($m) {
        $m->to('cliente@exemplo.com', 'Cliente Teste')
          ->subject('Teste transport Brevo');
    });

    Http::assertSent(function ($request) {
        $body = $request->data();
        return $request->url() === 'https://api.brevo.com/v3/smtp/email'
            && $request->hasHeader('api-key', 'test-brevo-key-xyz')
            && $body['sender']['email'] === 'noreply@vivensi.app.br'
            && $body['sender']['name']  === 'Vivensi Testes'
            && $body['to'][0]['email']  === 'cliente@exemplo.com'
            && $body['to'][0]['name']   === 'Cliente Teste'
            && $body['subject']         === 'Teste transport Brevo'
            && str_contains($body['textContent'] ?? '', 'conteudo em texto puro');
    });
});

it('propaga cc, bcc e replyTo quando presentes', function () {
    Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response([], 201)]);

    Mail::html('<p>corpo html</p>', function ($m) {
        $m->to('a@exemplo.com')
          ->cc('c@exemplo.com')
          ->bcc('b@exemplo.com')
          ->replyTo('resposta@exemplo.com', 'Suporte')
          ->subject('multi');
    });

    Http::assertSent(function ($request) {
        $body = $request->data();
        return $body['cc'][0]['email']      === 'c@exemplo.com'
            && $body['bcc'][0]['email']     === 'b@exemplo.com'
            && $body['replyTo']['email']    === 'resposta@exemplo.com'
            && $body['replyTo']['name']     === 'Suporte'
            && $body['htmlContent']         === '<p>corpo html</p>';
    });
});

it('lanca excecao quando Brevo responde erro (mailable vai pra failed_jobs)', function () {
    Http::fake([
        'https://api.brevo.com/v3/smtp/email' => Http::response(['message' => 'Invalid sender'], 400),
    ]);

    Mail::raw('teste', fn ($m) => $m->to('x@x.com')->subject('vai falhar'));
})->throws(\RuntimeException::class, 'Brevo API respondeu HTTP 400');

it('lanca excecao quando brevo_api_key nao esta configurada', function () {
    SystemSetting::where('key', 'brevo_api_key')->delete();

    Mail::raw('teste', fn ($m) => $m->to('x@x.com')->subject('sem chave'));
})->throws(\RuntimeException::class, 'brevo_api_key nao configurada');

it('sobrescreve o sender do Symfony pelo SystemSetting (nunca deixa hello@example.com escapar)', function () {
    // Laravel injeta MAIL_FROM_ADDRESS = 'hello@example.com' quando .env nao seta
    config(['mail.from' => ['address' => 'hello@example.com', 'name' => 'Example']]);

    Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response([], 201)]);

    Mail::raw('teste', fn ($m) => $m->to('cliente@exemplo.com')->subject('sender override'));

    Http::assertSent(function ($request) {
        $body = $request->data();
        // SystemSetting.email_from (do beforeEach) tem prioridade sobre o do Symfony
        return $body['sender']['email'] === 'noreply@vivensi.app.br'
            && $body['sender']['name']  === 'Vivensi Testes';
    });
});

// 2026-08-24: regressao pra bug prod que barrava StageOverdueAlertMail (e outros
// mailables sem ->from() explicito) com 'An email must have a From or Sender header'.
// AppServiceProvider agora seta mail.from.address a partir de SystemSetting.email_from
// no boot — o teste abaixo simula um Mailable que NAO chama ->from() e espera que
// o envio Brevo aconteca (sem explodir na ensureValidity do Symfony).
it('envia mailable sem ->from() explicito quando mail.from.address esta configurado', function () {
    Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response([], 201)]);

    // Mailable anonimo minimo — nao chama ->from(), depende do default do config
    $mailable = new class extends \Illuminate\Mail\Mailable {
        public function build(): self
        {
            // Sem ->from() proposital — o teste valida que o default do config nao deixa vazar
            return $this->subject('Alerta sem from explicito')
                        ->html('<p>corpo teste</p>');
        }
    };

    Mail::to('cliente@exemplo.com')->send($mailable);

    Http::assertSent(function ($request) {
        $body = $request->data();
        return $body['sender']['email'] === 'noreply@vivensi.app.br';
    });
});
