<?php

use App\Models\EmailCampaign;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\EmailCampaignAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/**
 * Bruce IA em email campaign — F1 (analise pre-envio) + F2 (insight pos-envio).
 * Foca em heuristicas deterministicas — chamada DeepSeek mockada via Http::fake.
 */

beforeEach(function () {
    SystemSetting::setValue('deepseek_api_key', 'test-key');
});

// ── F1: Analise pre-envio ─────────────────────────────────────────────────────

it('analyzeBeforeSend devolve score alto para rascunho limpo', function () {
    Http::fake(); // sem AI, ja passa (score alto = sem call)

    $svc = app(EmailCampaignAiService::class);
    $r = $svc->analyzeBeforeSend([
        'subject'      => 'Novidades da Vivensi este mês',
        'html_content' => '<html><body><p>Ola! Aqui vao as novidades desse mes. Muito conteudo util pra sua ONG.</p><p>Se preferir nao receber, {{unsubscribe}}.</p></body></html>',
    ], 1);

    expect($r['score'])->toBeGreaterThanOrEqual(85);
    expect($r['level'])->toBe('excelente');
    expect($r['risks'])->toBeArray();
});

it('analyzeBeforeSend penaliza assunto em MAIUSCULAS + palavras gatilho', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode([
                'notes' => 'notes',
                'suggestions' => ['s1', 's2'],
            ])]]],
        ], 200),
    ]);

    $svc = app(EmailCampaignAiService::class);
    $r = $svc->analyzeBeforeSend([
        'subject'      => 'URGENTE!!! OFERTA IMPERDIVEL GRATIS SO HOJE',
        'html_content' => '<html><body><p>{{unsubscribe}}</p></body></html>',
    ], 1);

    // Assunto CAPS + emojis nao — mas palavras urgente/gratis/imperdivel/so hoje = 4 gatilhos
    expect($r['score'])->toBeLessThan(70);
    expect($r['level'])->toBeIn(['atencao', 'ruim']);

    $msgs = collect($r['risks'])->pluck('msg')->implode(' | ');
    expect($msgs)->toContain('MAIUSCULAS');
    expect($msgs)->toContain('spam');
});

it('analyzeBeforeSend detecta imagens sem alt', function () {
    Http::fake();
    $svc = app(EmailCampaignAiService::class);
    $r = $svc->analyzeBeforeSend([
        'subject'      => 'Assunto normal',
        'html_content' => '<html><body><img src="a.jpg"><img src="b.jpg" alt="ok"><p>texto suficiente pra passar 200 chars. Aqui vai um paragrafo bem longo pra evitar penalidade de ratio imagem/texto. Continuando com mais texto. {{unsubscribe}}</p></body></html>',
    ], 1);

    $msgs = collect($r['risks'])->pluck('msg')->implode(' | ');
    expect($msgs)->toContain('sem atributo alt');
});

it('analyzeBeforeSend alerta quando falta link de descadastro', function () {
    Http::fake();
    $svc = app(EmailCampaignAiService::class);
    $r = $svc->analyzeBeforeSend([
        'subject'      => 'Assunto ok',
        // proposital: SEM mencao a descadastro/unsubscribe nem placeholder {{unsubscribe}}
        'html_content' => '<html><body><p>Conteudo normal, apenas texto, nada de opt-out.</p></body></html>',
    ], 1);

    $msgs = collect($r['risks'])->pluck('msg')->implode(' | ');
    expect($msgs)->toContain('descadastro');
});

it('analyzeBeforeSend bloqueia assunto vazio como high severity', function () {
    Http::fake();
    $svc = app(EmailCampaignAiService::class);
    $r = $svc->analyzeBeforeSend([
        'subject'      => '',
        'html_content' => '<p>ola {{unsubscribe}}</p>',
    ], 1);

    expect($r['level'])->toBeIn(['atencao', 'ruim']);
    $high = collect($r['risks'])->where('severity', 'high')->count();
    expect($high)->toBeGreaterThan(0);
});

// ── F2: Insight pos-envio ─────────────────────────────────────────────────────

it('analyzePerformance devolve insight + benchmark + next_action', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode([
                'insight'     => 'Sua campanha teve 25% abertura, na media do mercado.',
                'next_action' => 'Continue com esse padrao de assunto.',
            ])]]],
        ], 200),
    ]);

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $c = EmailCampaign::create([
        'tenant_id'       => $tenant->id,
        'created_by'      => $user->id,
        'name'            => 'Teste',
        'subject'         => 'Assunto teste',
        'sender_name'     => 'Vivensi',
        'sender_email'    => 'a@a.com',
        'html_content'    => '<p>x</p>',
        'status'          => 'sent',
        'recipient_count' => 1000,
        'stat_delivered'  => 950,
        'stat_opens'      => 240,
        'stat_clicks'     => 30,
        'stat_bounces'    => 25,
        'sent_at'         => now(),
    ]);

    $svc = app(EmailCampaignAiService::class);
    $r = $svc->analyzePerformance($c);

    expect($r['insight'])->toContain('25% abertura');
    expect($r['next_action'])->toContain('padrao de assunto');
    expect($r['benchmark']['this_campaign']['openRate'])->toBeGreaterThan(0);
});

it('analyzePerformance cacheia resultado por 1h (segunda chamada nao hita DeepSeek)', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode([
                'insight'     => 'primeira analise',
                'next_action' => 'acao 1',
            ])]]],
        ], 200),
    ]);

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $c = EmailCampaign::create([
        'tenant_id'       => $tenant->id, 'created_by' => $user->id, 'name' => 'x', 'subject' => 'x',
        'sender_name' => 'x', 'sender_email' => 'a@a.com',
        'html_content' => '<p>x</p>', 'status' => 'sent',
        'recipient_count' => 100, 'stat_delivered' => 100,
        'stat_opens' => 25, 'stat_clicks' => 3, 'stat_bounces' => 0,
        'sent_at' => now(),
    ]);

    $svc = app(EmailCampaignAiService::class);
    $svc->analyzePerformance($c); // 1a chamada — hita DeepSeek
    $svc->analyzePerformance($c); // 2a — deve vir do cache

    Http::assertSentCount(1);
});

it('analyzePerformance fallback quando DeepSeek falha (mensagem generica + next_action util)', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response(['error' => 'boom'], 500),
    ]);

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $c = EmailCampaign::create([
        'tenant_id'       => $tenant->id, 'created_by' => $user->id, 'name' => 'x', 'subject' => 'x',
        'sender_name' => 'x', 'sender_email' => 'a@a.com',
        'html_content' => '<p>x</p>', 'status' => 'sent',
        'recipient_count' => 100, 'stat_delivered' => 90,
        'stat_opens' => 20, 'stat_clicks' => 2,
        'stat_bounces' => 8, // 8% bounce = alto
        'sent_at' => now(),
    ]);

    $svc = app(EmailCampaignAiService::class);
    $r = $svc->analyzePerformance($c);

    expect($r['insight'])->toContain('bounce');
    expect($r['next_action'])->toContain('lista');
});
