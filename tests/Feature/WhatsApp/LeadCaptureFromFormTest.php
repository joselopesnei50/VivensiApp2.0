<?php

use App\Models\Lead;
use App\Models\LeadConsent;
use App\Models\LeadTimelineItem;
use App\Models\Tenant;
use App\Models\WhatsappChat;
use App\Models\WhatsappForm;
use App\Models\WhatsappFormAnswer;
use App\Models\WhatsappFormQuestion;
use App\Models\WhatsappFormSession;
use App\Services\LeadService;
use App\Services\Messaging\LeadCaptureFromForm;
use App\Services\Messaging\WhatsappFormEngine;

/**
 * Captura de lead a partir da conclusão de um formulário WhatsApp
 * (Fase 5 — sub-etapa 5.A.2). Garante:
 *  - mapeamento de field_keys reservados (phone/email/name/city/tags)
 *  - fallback pra contact_phone/wa_id do chat
 *  - registro de OPT_IN com origin canônico
 *  - timeline 'form_completed'
 *  - session.lead_id atualizado
 *  - idempotência (completar duas vezes não duplica lead)
 *  - falha silenciosa quando não há identificador útil
 */

function lcffTenant(): Tenant
{
    return Tenant::factory()->create();
}

function lcffChat(Tenant $tenant, array $attrs = []): WhatsappChat
{
    return WhatsappChat::factory()->create(array_merge(['tenant_id' => $tenant->id], $attrs));
}

function lcffForm(Tenant $tenant, string $name = 'Cadastro'): WhatsappForm
{
    return WhatsappForm::create([
        'tenant_id'  => $tenant->id,
        'name'       => $name,
        'is_active'  => true,
    ]);
}

function lcffQuestion(WhatsappForm $form, string $fieldKey, int $position): WhatsappFormQuestion
{
    return WhatsappFormQuestion::create([
        'form_id'   => $form->id,
        'position'  => $position,
        'field_key' => $fieldKey,
        'text'      => "Pergunta {$fieldKey}",
        'type'      => 'text',
        'required'  => false,
    ]);
}

function lcffSession(Tenant $tenant, WhatsappChat $chat, WhatsappForm $form): WhatsappFormSession
{
    return WhatsappFormSession::create([
        'tenant_id'    => $tenant->id,
        'chat_id'      => $chat->id,
        'form_id'      => $form->id,
        'status'       => WhatsappFormSession::STATUS_COMPLETED,
        'started_at'   => now(),
        'completed_at' => now(),
    ]);
}

function lcffAnswer(WhatsappFormSession $session, WhatsappFormQuestion $q, string $text): WhatsappFormAnswer
{
    return WhatsappFormAnswer::create([
        'session_id'  => $session->id,
        'question_id' => $q->id,
        'field_key'   => $q->field_key,
        'answer_text' => $text,
    ]);
}

function lcffCapture(): LeadCaptureFromForm
{
    return new LeadCaptureFromForm(new LeadService());
}

it('captura lead por field_key phone explícito', function () {
    $tenant  = lcffTenant();
    $chat    = lcffChat($tenant);
    $form    = lcffForm($tenant, 'Apoio');
    $qPhone  = lcffQuestion($form, 'phone', 1);
    $qName   = lcffQuestion($form, 'name', 2);
    $session = lcffSession($tenant, $chat, $form);
    lcffAnswer($session, $qPhone, '(11) 99999-8888');
    lcffAnswer($session, $qName,  'Fulano de Tal');

    $lead = lcffCapture()->capture($session);

    expect($lead)->not->toBeNull();
    expect($lead->phone_normalized)->toBe('5511999998888');
    expect($lead->name)->toBe('Fulano de Tal');
    expect($lead->tenant_id)->toBe($tenant->id);
    expect($lead->whatsapp_chat_id)->toBe($chat->id);
    expect($lead->status)->toBe(Lead::STATUS_PENDING);
});

it('faz fallback pro contact_phone do chat quando o form não pergunta telefone', function () {
    $tenant  = lcffTenant();
    $chat    = lcffChat($tenant, ['contact_phone' => '11988887777', 'wa_id' => '5511988887777']);
    $form    = lcffForm($tenant);
    $qName   = lcffQuestion($form, 'name', 1);
    $session = lcffSession($tenant, $chat, $form);
    lcffAnswer($session, $qName, 'Maria');

    $lead = lcffCapture()->capture($session);

    expect($lead)->not->toBeNull();
    expect($lead->phone_normalized)->toBe('5511988887777');
    expect($lead->name)->toBe('Maria');
});

it('faz fallback pro wa_id quando contact_phone está vazio', function () {
    $tenant  = lcffTenant();
    $chat    = lcffChat($tenant, ['contact_phone' => null, 'wa_id' => '5511955554444']);
    $form    = lcffForm($tenant);
    $qName   = lcffQuestion($form, 'name', 1);
    $session = lcffSession($tenant, $chat, $form);
    lcffAnswer($session, $qName, 'João');

    $lead = lcffCapture()->capture($session);

    expect($lead)->not->toBeNull();
    expect($lead->phone_normalized)->toBe('5511955554444');
});

it('captura por email quando não há nenhum telefone disponível', function () {
    $tenant  = lcffTenant();
    $chat    = lcffChat($tenant, ['contact_phone' => null, 'wa_id' => '']);
    $form    = lcffForm($tenant);
    $qEmail  = lcffQuestion($form, 'email', 1);
    $session = lcffSession($tenant, $chat, $form);
    lcffAnswer($session, $qEmail, 'Foo@Example.COM');

    $lead = lcffCapture()->capture($session);

    expect($lead)->not->toBeNull();
    expect($lead->email)->toBe('foo@example.com');
    expect($lead->phone_normalized)->toBeNull();
});

it('separa field_keys reservados de extras em meta', function () {
    $tenant  = lcffTenant();
    $chat    = lcffChat($tenant);
    $form    = lcffForm($tenant);
    $qPhone  = lcffQuestion($form, 'phone', 1);
    $qCity   = lcffQuestion($form, 'city',  2);
    $qIdade  = lcffQuestion($form, 'idade', 3);
    $qBairro = lcffQuestion($form, 'bairro', 4);
    $session = lcffSession($tenant, $chat, $form);
    lcffAnswer($session, $qPhone,  '11999998888');
    lcffAnswer($session, $qCity,   'São Paulo');
    lcffAnswer($session, $qIdade,  '34');
    lcffAnswer($session, $qBairro, 'Pinheiros');

    $lead = lcffCapture()->capture($session);

    expect($lead->city)->toBe('São Paulo');
    expect($lead->meta)->toMatchArray([
        'idade'  => '34',
        'bairro' => 'Pinheiros',
    ]);
});

it('faz split de tags por vírgula', function () {
    $tenant  = lcffTenant();
    $chat    = lcffChat($tenant);
    $form    = lcffForm($tenant);
    $qPhone  = lcffQuestion($form, 'phone', 1);
    $qTags   = lcffQuestion($form, 'tags',  2);
    $session = lcffSession($tenant, $chat, $form);
    lcffAnswer($session, $qPhone, '11999998888');
    lcffAnswer($session, $qTags,  'apoia-x , voluntário, jovem');

    $lead = lcffCapture()->capture($session);

    expect($lead->tags)->toBe(['apoia-x', 'voluntário', 'jovem']);
});

it('registra OPT_IN com origin whatsapp_form e payload do form', function () {
    $tenant  = lcffTenant();
    $chat    = lcffChat($tenant);
    $form    = lcffForm($tenant, 'Apoio Local');
    $qPhone  = lcffQuestion($form, 'phone', 1);
    $session = lcffSession($tenant, $chat, $form);
    lcffAnswer($session, $qPhone, '11999998888');

    $lead = lcffCapture()->capture($session);

    $consent = LeadConsent::where('lead_id', $lead->id)
        ->where('type', LeadConsent::TYPE_OPT_IN)
        ->first();

    expect($consent)->not->toBeNull();
    expect($consent->origin)->toBe("whatsapp_form:{$form->id}");
    expect($consent->payload['form_id'])->toBe($form->id);
    expect($consent->payload['form_name'])->toBe('Apoio Local');
    expect($consent->payload['session_id'])->toBe($session->id);
    expect($lead->fresh()->consent_at)->not->toBeNull();
    // OPT_IN não confirma — espera double opt-in (5.A.4).
    expect($lead->fresh()->status)->toBe(Lead::STATUS_PENDING);
});

it('cria item de timeline form_completed com resumo das respostas', function () {
    $tenant  = lcffTenant();
    $chat    = lcffChat($tenant);
    $form    = lcffForm($tenant, 'Cadastro Eleitor');
    $qPhone  = lcffQuestion($form, 'phone', 1);
    $qIdade  = lcffQuestion($form, 'idade', 2);
    $session = lcffSession($tenant, $chat, $form);
    lcffAnswer($session, $qPhone, '11999998888');
    lcffAnswer($session, $qIdade, '40');

    $lead = lcffCapture()->capture($session);

    $item = LeadTimelineItem::where('lead_id', $lead->id)
        ->where('type', LeadTimelineItem::TYPE_FORM_COMPLETED)
        ->first();

    expect($item)->not->toBeNull();
    expect($item->body)->toContain('Cadastro Eleitor');
    expect($item->meta['form_id'])->toBe($form->id);
    expect($item->meta['answers'])->toMatchArray(['phone' => '11999998888', 'idade' => '40']);
});

it('atualiza session.lead_id ao capturar', function () {
    $tenant  = lcffTenant();
    $chat    = lcffChat($tenant);
    $form    = lcffForm($tenant);
    $qPhone  = lcffQuestion($form, 'phone', 1);
    $session = lcffSession($tenant, $chat, $form);
    lcffAnswer($session, $qPhone, '11999998888');

    expect($session->lead_id)->toBeNull();

    $lead = lcffCapture()->capture($session);

    // (int): sqlite devolve string em colunas sem cast no model
    expect((int) $session->fresh()->lead_id)->toBe($lead->id);
});

it('é idempotente: capturar duas vezes devolve o mesmo lead', function () {
    $tenant  = lcffTenant();
    $chat    = lcffChat($tenant);
    $form    = lcffForm($tenant);
    $qPhone  = lcffQuestion($form, 'phone', 1);
    $session = lcffSession($tenant, $chat, $form);
    lcffAnswer($session, $qPhone, '11999998888');

    $svc = lcffCapture();
    $first  = $svc->capture($session);
    $second = $svc->capture($session->fresh());

    expect($second->id)->toBe($first->id);
    expect(Lead::where('tenant_id', $tenant->id)->count())->toBe(1);
});

it('não faz nada quando session não está completed', function () {
    $tenant  = lcffTenant();
    $chat    = lcffChat($tenant);
    $form    = lcffForm($tenant);
    $qPhone  = lcffQuestion($form, 'phone', 1);
    $session = lcffSession($tenant, $chat, $form);
    $session->update(['status' => WhatsappFormSession::STATUS_IN_PROGRESS]);
    lcffAnswer($session, $qPhone, '11999998888');

    $lead = lcffCapture()->capture($session->fresh());

    expect($lead)->toBeNull();
    expect(Lead::where('tenant_id', $tenant->id)->count())->toBe(0);
});

it('devolve null sem explodir quando não há telefone nem email nem chat fallback', function () {
    $tenant  = lcffTenant();
    $chat    = lcffChat($tenant, ['contact_phone' => null, 'wa_id' => '']);
    $form    = lcffForm($tenant);
    $qName   = lcffQuestion($form, 'name', 1);
    $session = lcffSession($tenant, $chat, $form);
    lcffAnswer($session, $qName, 'Só Nome');

    $lead = lcffCapture()->capture($session);

    expect($lead)->toBeNull();
    expect(Lead::where('tenant_id', $tenant->id)->count())->toBe(0);
});

it('engine completa a sessão e dispara captura via container', function () {
    $tenant  = lcffTenant();
    $chat    = lcffChat($tenant);
    $form    = lcffForm($tenant, 'Apoio via Engine');
    $qPhone  = lcffQuestion($form, 'phone', 1);

    $engine = new WhatsappFormEngine();

    $session = WhatsappFormSession::create([
        'tenant_id'           => $tenant->id,
        'chat_id'             => $chat->id,
        'form_id'             => $form->id,
        'current_question_id' => $qPhone->id,
        'status'              => WhatsappFormSession::STATUS_IN_PROGRESS,
        'started_at'          => now(),
    ]);

    $result = $engine->processInbound($session, '(11) 91234-5678');

    expect($result['next_question'])->toBeNull();
    expect($result['session']->status)->toBe(WhatsappFormSession::STATUS_COMPLETED);

    $lead = Lead::where('tenant_id', $tenant->id)->first();
    expect($lead)->not->toBeNull();
    expect($lead->phone_normalized)->toBe('5511912345678');
    expect((int) $session->fresh()->lead_id)->toBe($lead->id);
});

it('engine não quebra a UX do WA se captura falhar', function () {
    $tenant  = lcffTenant();
    $chat    = lcffChat($tenant, ['contact_phone' => null, 'wa_id' => '']);
    $form    = lcffForm($tenant);
    $qName   = lcffQuestion($form, 'name', 1);

    $engine = new WhatsappFormEngine();

    $session = WhatsappFormSession::create([
        'tenant_id'           => $tenant->id,
        'chat_id'             => $chat->id,
        'form_id'             => $form->id,
        'current_question_id' => $qName->id,
        'status'              => WhatsappFormSession::STATUS_IN_PROGRESS,
        'started_at'          => now(),
    ]);

    $result = $engine->processInbound($session, 'Anônimo');

    expect($result['error'])->toBeNull();
    expect($result['session']->status)->toBe(WhatsappFormSession::STATUS_COMPLETED);
    expect(Lead::where('tenant_id', $tenant->id)->count())->toBe(0);
});
