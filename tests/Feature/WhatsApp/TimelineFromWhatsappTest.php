<?php

use App\Models\Lead;
use App\Models\LeadTimelineItem;
use App\Models\Tenant;
use App\Models\WhatsappChat;
use App\Models\WhatsappMessage;
use App\Services\Messaging\LeadQualificationService;

/**
 * P1.7 — Timeline do lead a partir do WhatsApp + sugestões Bruce.
 * Cobre o observer (cada WhatsappMessage criada vira item de timeline
 * do lead vinculado) e o LeadQualificationService::attachToLeadTimeline
 * (AI_SUGGESTION + NEXT_ACTION para o lead resolvido pelo chat).
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function twTenant(): Tenant
{
    return Tenant::factory()->create();
}

function twChatWithLead(Tenant $tenant, string $phone = '5511987654321'): array
{
    $chat = WhatsappChat::factory()->create([
        'tenant_id'     => $tenant->id,
        'wa_id'         => $phone,
        'contact_phone' => $phone,
    ]);
    $lead = Lead::create([
        'tenant_id'        => $tenant->id,
        'name'             => 'Lead',
        'phone'            => $phone,
        'phone_normalized' => $phone,
        'status'           => Lead::STATUS_PENDING,
    ]);
    return [$chat, $lead];
}

it('grava item de timeline ao receber mensagem inbound do contato', function () {
    $tenant = twTenant();
    [$chat, $lead] = twChatWithLead($tenant);

    WhatsappMessage::create([
        'tenant_id'  => $tenant->id,
        'chat_id'    => $chat->id,
        'message_id' => 'wamid.test.' . \Illuminate\Support\Str::uuid(),
        'content'    => 'Oi, tudo bem?',
        'direction' => 'inbound',
        'type'      => 'conversation',
    ]);

    $items = LeadTimelineItem::where('lead_id', $lead->id)
        ->where('type', LeadTimelineItem::TYPE_WHATSAPP_MESSAGE)
        ->get();

    expect($items)->toHaveCount(1);
    expect($items[0]->body)->toContain('[Contato]');
    expect($items[0]->body)->toContain('Oi, tudo bem?');
});

it('grava item de timeline ao enviar mensagem outbound do atendente', function () {
    $tenant = twTenant();
    [$chat, $lead] = twChatWithLead($tenant);

    WhatsappMessage::create([
        'tenant_id'  => $tenant->id,
        'chat_id'    => $chat->id,
        'message_id' => 'wamid.test.' . \Illuminate\Support\Str::uuid(),
        'content'    => 'Boa! Posso te ajudar',
        'direction' => 'outbound',
        'type'      => 'conversation',
    ]);

    $items = LeadTimelineItem::where('lead_id', $lead->id)
        ->where('type', LeadTimelineItem::TYPE_WHATSAPP_MESSAGE)
        ->get();

    expect($items)->toHaveCount(1);
    expect($items[0]->body)->toContain('[Atendente]');
});

it('nao grava timeline para chat sem lead vinculado', function () {
    $tenant = twTenant();
    $chat = WhatsappChat::factory()->create([
        'tenant_id'     => $tenant->id,
        'wa_id'         => '5511999990000',
        'contact_phone' => '5511999990000',
    ]);

    WhatsappMessage::create([
        'tenant_id'  => $tenant->id,
        'chat_id'    => $chat->id,
        'message_id' => 'wamid.test.' . \Illuminate\Support\Str::uuid(),
        'content'    => 'sem lead',
        'direction' => 'inbound',
        'type'      => 'conversation',
    ]);

    expect(LeadTimelineItem::count())->toBe(0);
});

it('trunca corpo em 500 chars', function () {
    $tenant = twTenant();
    [$chat, $lead] = twChatWithLead($tenant);

    $longContent = str_repeat('A', 800);
    WhatsappMessage::create([
        'tenant_id'  => $tenant->id,
        'chat_id'    => $chat->id,
        'message_id' => 'wamid.test.' . \Illuminate\Support\Str::uuid(),
        'content'    => $longContent,
        'direction' => 'inbound',
        'type'      => 'conversation',
    ]);

    $item = LeadTimelineItem::where('lead_id', $lead->id)->first();
    // 10 chars do prefixo "[Contato] " + 500 do corpo truncado
    expect(mb_strlen($item->body))->toBe(510);
});

it('nao grava timeline para conteudo vazio', function () {
    $tenant = twTenant();
    [$chat, $lead] = twChatWithLead($tenant);

    WhatsappMessage::create([
        'tenant_id'  => $tenant->id,
        'chat_id'    => $chat->id,
        'message_id' => 'wamid.test.' . \Illuminate\Support\Str::uuid(),
        'content'    => '',
        'direction' => 'inbound',
        'type'      => 'conversation',
    ]);

    expect(LeadTimelineItem::where('lead_id', $lead->id)->count())->toBe(0);
});

it('qualificacao gera AI_SUGGESTION e NEXT_ACTION na timeline do lead', function () {
    $tenant = twTenant();
    [$chat, $lead] = twChatWithLead($tenant);

    $svc = app(LeadQualificationService::class);
    $method = new \ReflectionMethod($svc, 'attachToLeadTimeline');
    $method->setAccessible(true);

    $method->invoke($svc, $chat, [
        'qualification' => 'quente',
        'intent'        => 'interesse',
        'summary'       => 'Quer agendar reunião',
        'next_action'   => 'Ligar amanhã 10h',
        'confidence'    => 0.92,
        'provider'      => 'deepseek',
        'error'         => null,
    ]);

    $suggestion = LeadTimelineItem::where('lead_id', $lead->id)
        ->where('type', LeadTimelineItem::TYPE_AI_SUGGESTION)
        ->first();
    $nextAction = LeadTimelineItem::where('lead_id', $lead->id)
        ->where('type', LeadTimelineItem::TYPE_NEXT_ACTION)
        ->first();

    expect($suggestion)->not->toBeNull();
    expect($suggestion->body)->toBe('Quer agendar reunião');
    expect($nextAction)->not->toBeNull();
    expect($nextAction->body)->toBe('Ligar amanhã 10h');
});

it('qualificacao com erro nao gera timeline', function () {
    $tenant = twTenant();
    [$chat, $lead] = twChatWithLead($tenant);

    $svc = app(LeadQualificationService::class);
    $method = new \ReflectionMethod($svc, 'attachToLeadTimeline');
    $method->setAccessible(true);

    $method->invoke($svc, $chat, [
        'qualification' => null,
        'intent'        => null,
        'summary'       => '',
        'next_action'   => null,
        'confidence'    => 0.0,
        'provider'      => 'deepseek',
        'error'         => 'A IA falhou',
    ]);

    expect(LeadTimelineItem::where('lead_id', $lead->id)
        ->whereIn('type', [LeadTimelineItem::TYPE_AI_SUGGESTION, LeadTimelineItem::TYPE_NEXT_ACTION])
        ->count())->toBe(0);
});
