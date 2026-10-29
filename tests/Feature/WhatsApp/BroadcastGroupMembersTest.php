<?php

use App\Jobs\ProcessBroadcastCampaignJob;
use App\Models\BroadcastCampaign;
use App\Models\Tenant;
use App\Models\WhatsappAuditLog;
use App\Models\WhatsappChat;
use App\Services\WhatsappOutboundPolicy;

/**
 * Opção B do fix de 2026-10-28: toggle `members_optin_implicit` na campanha.
 * Quando grupo em modo "members" + flag ligada: pulamos a exigência de opt-in
 * individual (membership = consentimento), mas AINDA respeitamos opt_out,
 * blacklist e blocked_at.
 */

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function bgmTenant(): Tenant
{
    return Tenant::factory()->create(['subscription_status' => 'active']);
}

// Replica a decisão feita pelo job pra uma recipient que veio de expansão de grupo.
function groupMemberDecision(
    WhatsappOutboundPolicy $policy,
    bool $requireOptIn,
    bool $groupMembershipAsOptIn,
    array $blacklistSet,
    WhatsappChat $chat
): ?string {
    $effectiveRequireOptIn = $groupMembershipAsOptIn ? false : $requireOptIn;
    return $policy->complianceStatus(
        $effectiveRequireOptIn,
        $chat->opt_in_at,
        $chat->opt_out_at,
        $chat->blocked_at,
        isset($blacklistSet[$chat->wa_id])
    );
}

// ── Toggle on: membership libera opt-in exigido ───────────────────────────────

it('toggle on + membro sem opt_in: libera (bypass OPTIN_REQUIRED)', function () {
    $t    = bgmTenant();
    $chat = WhatsappChat::factory()->create(['tenant_id' => $t->id, 'opt_in_at' => null]);
    $policy = new WhatsappOutboundPolicy();

    $decision = groupMemberDecision($policy, true, true, [], $chat);
    expect($decision)->toBeNull();
});

it('toggle off + membro sem opt_in: bloqueia (OPTIN_REQUIRED)', function () {
    $t    = bgmTenant();
    $chat = WhatsappChat::factory()->create(['tenant_id' => $t->id, 'opt_in_at' => null]);
    $policy = new WhatsappOutboundPolicy();

    $decision = groupMemberDecision($policy, true, false, [], $chat);
    expect($decision)->toBe('OPTIN_REQUIRED');
});

// ── Toggle on respeita direitos LGPD intocados ────────────────────────────────

it('toggle on + membro com opt_out_at: ainda bloqueia (OPTOUT nao pode ser bypassado)', function () {
    $t    = bgmTenant();
    $chat = WhatsappChat::factory()->create(['tenant_id' => $t->id, 'opt_out_at' => now()->subDay()]);
    $policy = new WhatsappOutboundPolicy();

    $decision = groupMemberDecision($policy, true, true, [], $chat);
    expect($decision)->toBe('CONTACT_OPTOUT');
});

it('toggle on + membro com blocked_at: ainda bloqueia', function () {
    $t    = bgmTenant();
    $chat = WhatsappChat::factory()->create(['tenant_id' => $t->id, 'blocked_at' => now()]);
    $policy = new WhatsappOutboundPolicy();

    $decision = groupMemberDecision($policy, true, true, [], $chat);
    expect($decision)->toBe('CONTACT_BLOCKED');
});

it('toggle on + membro na blacklist: ainda bloqueia', function () {
    $t    = bgmTenant();
    $chat = WhatsappChat::factory()->create(['tenant_id' => $t->id]);
    $policy = new WhatsappOutboundPolicy();

    $decision = groupMemberDecision($policy, true, true, [$chat->wa_id => true], $chat);
    expect($decision)->toBe('CONTACT_BLACKLISTED');
});

// ── Fórmula do chunk size (fix latencia) ──────────────────────────────────────

it('calcEffectiveChunkSize com cadencia 3s retorna CHUNK_SIZE (10) — fix latencia', function () {
    expect(ProcessBroadcastCampaignJob::calcEffectiveChunkSize(3))->toBe(10);
});

it('calcEffectiveChunkSize com cadencia 5s retorna CHUNK_SIZE (10)', function () {
    expect(ProcessBroadcastCampaignJob::calcEffectiveChunkSize(5))->toBe(10);
});

it('calcEffectiveChunkSize com cadencia 10s retorna CHUNK_SIZE (10)', function () {
    expect(ProcessBroadcastCampaignJob::calcEffectiveChunkSize(10))->toBe(10);
});

it('calcEffectiveChunkSize com cadencia 30s (max do UI) retorna CHUNK_SIZE', function () {
    // Esse É o fix: antes dava 1-2 msgs (fórmula pessimista). Agora todas as
    // cadências selecionáveis pelo cliente (1-30s) resultam no chunk cheio.
    expect(ProcessBroadcastCampaignJob::calcEffectiveChunkSize(30))->toBe(10);
});

it('calcEffectiveChunkSize em modo conservador reduz quando cadencia e alta', function () {
    // Com cadencia 60s + conservative (mult 2x): (60+10)*2+5=145s, floor(1200/145)=8
    expect(ProcessBroadcastCampaignJob::calcEffectiveChunkSize(60, true))
        ->toBeLessThan(ProcessBroadcastCampaignJob::calcEffectiveChunkSize(60, false));
});

it('maxMsgDelay cadencia 3s: ~20s (nao mais 70+ do codigo antigo)', function () {
    // Antes do fix 2026-10-28 era (5+10)*1 + 50 = 65s.
    // Agora: (5+10)*1 + 5 = 20s.
    expect(ProcessBroadcastCampaignJob::maxMsgDelay(3))->toBe(20);
});

// ── Model: cast + fillable + default ─────────────────────────────────────────

it('BroadcastCampaign aceita members_optin_implicit true', function () {
    $t = bgmTenant();
    $c = BroadcastCampaign::create([
        'tenant_id'              => $t->id,
        'created_by'             => 1,
        'name'                   => 'Camp Teste',
        'message'                => 'oi',
        'audience_type'          => 'groups',
        'group_send_mode'        => 'members',
        'members_optin_implicit' => true,
        'status'                 => 'queued',
    ]);
    expect($c->fresh()->members_optin_implicit)->toBeTrue();
});

it('BroadcastCampaign aceita members_optin_implicit false', function () {
    $t = bgmTenant();
    $c = BroadcastCampaign::create([
        'tenant_id'              => $t->id,
        'created_by'             => 1,
        'name'                   => 'Camp Teste 2',
        'message'                => 'oi',
        'audience_type'          => 'groups',
        'group_send_mode'        => 'members',
        'members_optin_implicit' => false,
        'status'                 => 'queued',
    ]);
    expect($c->fresh()->members_optin_implicit)->toBeFalse();
});
