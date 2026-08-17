<?php

use App\Models\Post;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Blog\BlogPostGeneratorService;
use App\Services\DeepSeekService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

beforeEach(function () {
    SystemSetting::setValue('deepseek_api_key', 'sk-test-blog', 'api');
    Cache::flush();
});

function makeSuperAdminForBlog(): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $u = User::factory()->create([
        'tenant_id'               => $tenant->id,
        'role'                    => 'super_admin',
        'two_factor_confirmed_at' => now(),
    ]);
    // RequireTwoFactor middleware exige sessao verificada
    session(['2fa_verified' => true]);
    return $u;
}

function makeEmployeeForBlog(): User
{
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    return User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'employee']);
}

function mockDeepSeekOk(array $draft): void
{
    app()->instance(DeepSeekService::class, Mockery::mock(DeepSeekService::class, function (MockInterface $m) use ($draft) {
        $m->shouldReceive('chat')->once()->andReturn([
            'choices' => [['message' => ['content' => json_encode($draft, JSON_UNESCAPED_UNICODE)]]],
        ]);
    }));
}

// ─── Fluxo feliz ────────────────────────────────────────────────────────────

it('super admin gera rascunho de blog via tema curado', function () {
    $admin = makeSuperAdminForBlog();
    mockDeepSeekOk([
        'title'            => 'LGPD no Terceiro Setor: guia rápido',
        'excerpt'          => 'Como sua ONG deve tratar dados de beneficiários.',
        'content'          => '<h2>Introdução</h2><p>Texto de exemplo com <strong>ênfase</strong>.</p>',
        'meta_description' => 'Guia LGPD para ONGs em 2026.',
        'tags'             => 'lgpd, terceiro setor, compliance',
    ]);

    $response = $this->actingAs($admin)->post('/admin/blog/ai-draft', [
        'theme' => 'lgpd-terceiro-setor',
    ]);

    $post = Post::first();
    expect($post)->not->toBeNull()
        ->and($post->title)->toBe('LGPD no Terceiro Setor: guia rápido')
        ->and($post->is_published)->toBeFalse()
        ->and($post->published_at)->toBeNull()
        ->and($post->slug)->toContain('lgpd')
        ->and($post->content)->toContain('<h2>Introdução</h2>');

    $response->assertRedirect(route('admin.blog.edit', $post->id));
});

it('tema livre tem prioridade sobre tema curado', function () {
    $admin = makeSuperAdminForBlog();
    mockDeepSeekOk([
        'title'   => 'Como preparar sua ONG para o edital BNDES',
        'excerpt' => 'Passo a passo.',
        'content' => '<p>Conteúdo específico do BNDES.</p>',
        'meta_description' => 'Guia BNDES.',
        'tags' => 'bndes, edital',
    ]);

    $this->actingAs($admin)->post('/admin/blog/ai-draft', [
        'theme'        => 'lgpd-terceiro-setor',
        'custom_theme' => 'Como preparar sua ONG para o edital BNDES',
    ]);

    expect(Post::first()->title)->toContain('BNDES');
});

// ─── Gate ───────────────────────────────────────────────────────────────────

it('employee comum recebe 403 e nao cria post', function () {
    $employee = makeEmployeeForBlog();

    $response = $this->actingAs($employee)->post('/admin/blog/ai-draft', [
        'theme' => 'lgpd-terceiro-setor',
    ]);

    $response->assertForbidden();
    expect(Post::count())->toBe(0);
});

it('convidado nao autenticado eh redirecionado pro login', function () {
    $response = $this->post('/admin/blog/ai-draft', ['theme' => 'lgpd-terceiro-setor']);
    $response->assertRedirect('/login');
});

// ─── Validação ──────────────────────────────────────────────────────────────

it('sem tema retorna 422', function () {
    $admin = makeSuperAdminForBlog();
    $response = $this->actingAs($admin)->post('/admin/blog/ai-draft', []);
    $response->assertStatus(302); // Laravel validation redirects back with errors
    $response->assertSessionHasErrors(['theme']);
});

// ─── HTML malicioso é sanitizado ────────────────────────────────────────────

it('script tag no content da IA eh removido via sanitize_user_html', function () {
    $admin = makeSuperAdminForBlog();
    mockDeepSeekOk([
        'title'   => 'Teste XSS',
        'excerpt' => 'x',
        'content' => '<p>ok</p><script>alert("xss")</script><iframe src="//evil"></iframe>',
        'meta_description' => '',
        'tags' => '',
    ]);

    $this->actingAs($admin)->post('/admin/blog/ai-draft', ['theme' => 'lgpd-terceiro-setor']);

    $post = Post::first();
    expect($post->content)->not->toContain('<script>')
        ->and($post->content)->not->toContain('<iframe');
});

// ─── Fallback: IA retorna lixo ──────────────────────────────────────────────

it('resposta invalida da IA nao cria post e mostra erro', function () {
    $admin = makeSuperAdminForBlog();
    app()->instance(DeepSeekService::class, Mockery::mock(DeepSeekService::class, function (MockInterface $m) {
        $m->shouldReceive('chat')->once()->andReturn([
            'choices' => [['message' => ['content' => 'não é json de jeito nenhum']]],
        ]);
    }));

    $response = $this->actingAs($admin)->post('/admin/blog/ai-draft', ['theme' => 'lgpd-terceiro-setor']);

    expect(Post::count())->toBe(0);
    $response->assertSessionHas('error');
});

it('erro do provider (chave invalida) nao cria post', function () {
    $admin = makeSuperAdminForBlog();
    app()->instance(DeepSeekService::class, Mockery::mock(DeepSeekService::class, function (MockInterface $m) {
        $m->shouldReceive('chat')->once()->andReturn([
            'error'      => 'Integração de IA com chave inválida.',
            'error_code' => 'ai_invalid_key',
        ]);
    }));

    $this->actingAs($admin)->post('/admin/blog/ai-draft', ['theme' => 'lgpd-terceiro-setor']);
    expect(Post::count())->toBe(0);
});

// ─── Service: themeChoices ──────────────────────────────────────────────────

it('themeChoices retorna lista curada nao vazia', function () {
    $choices = BlogPostGeneratorService::themeChoices();
    expect($choices)->toBeArray()
        ->and(count($choices))->toBeGreaterThan(5)
        ->and($choices)->toHaveKey('lgpd-terceiro-setor');
});
