<?php

use App\Models\Post;
use App\Models\SystemSetting;
use App\Services\DeepSeekService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

beforeEach(function () {
    SystemSetting::setValue('deepseek_api_key', 'sk-test-cmd', 'api');
    Cache::flush();
});

function mockDeepSeekCmdOk(array $draft): void
{
    app()->instance(DeepSeekService::class, Mockery::mock(DeepSeekService::class, function (MockInterface $m) use ($draft) {
        $m->shouldReceive('chat')->once()->andReturn([
            'choices' => [['message' => ['content' => json_encode($draft, JSON_UNESCAPED_UNICODE)]]],
        ]);
    }));
}

it('comando gera rascunho com tema sorteado quando nao especificado', function () {
    mockDeepSeekCmdOk([
        'title'   => 'Voluntariado eficaz',
        'excerpt' => 'guia',
        'content' => '<p>corpo</p>',
        'meta_description' => 'x',
        'tags'    => 'voluntariado',
    ]);

    $this->artisan('blog:generate-draft')->assertSuccessful();

    $post = Post::first();
    expect($post)->not->toBeNull()
        ->and($post->is_published)->toBeFalse()
        ->and($post->title)->toBe('Voluntariado eficaz');
});

it('comando aceita --theme e usa tema informado', function () {
    mockDeepSeekCmdOk([
        'title'   => 'CEBAS na prática',
        'excerpt' => 'x',
        'content' => '<p>corpo cebas</p>',
        'meta_description' => 'y',
        'tags'    => 'cebas',
    ]);

    $this->artisan('blog:generate-draft', ['--theme' => 'cebas-passo-a-passo'])->assertSuccessful();
    expect(Post::first()->title)->toBe('CEBAS na prática');
});

it('comando rejeita tema desconhecido', function () {
    $this->artisan('blog:generate-draft', ['--theme' => 'tema-que-nao-existe'])->assertFailed();
    expect(Post::count())->toBe(0);
});

it('dry-run chama IA mas nao grava Post', function () {
    mockDeepSeekCmdOk([
        'title'   => 'Teste dry-run',
        'excerpt' => 'x',
        'content' => '<p>x</p>',
        'meta_description' => 'y',
        'tags'    => 'a',
    ]);

    $this->artisan('blog:generate-draft', ['--dry-run' => true])->assertSuccessful();
    expect(Post::count())->toBe(0);
});

it('IA quebrada faz o comando retornar failure', function () {
    app()->instance(DeepSeekService::class, Mockery::mock(DeepSeekService::class, function (MockInterface $m) {
        $m->shouldReceive('chat')->once()->andReturn([
            'error'      => 'Integração de IA com chave inválida.',
            'error_code' => 'ai_invalid_key',
        ]);
    }));

    $this->artisan('blog:generate-draft', ['--theme' => 'lgpd-terceiro-setor'])->assertFailed();
    expect(Post::count())->toBe(0);
});

it('quando todos os temas foram usados recentemente, comando avisa e sai OK sem gravar', function () {
    // Cria post recente cobrindo cada slug de tema — todos considerados usados
    foreach (\App\Services\Blog\BlogPostGeneratorService::themeChoices() as $key => $label) {
        Post::create([
            'title'        => $label, // slug do label vai colidir com o slug do label
            'content'      => 'x',
            'slug'         => $key . '-' . uniqid(),
            'is_published' => false,
        ]);
    }

    // NAO instala mock — se comando chamar IA, o Mock::shouldNotReceive n existe
    // aqui, mas o test do handle deve sair antes de tocar a IA.
    app()->instance(DeepSeekService::class, Mockery::mock(DeepSeekService::class, function (MockInterface $m) {
        $m->shouldNotReceive('chat');
    }));

    $initialCount = Post::count();
    $this->artisan('blog:generate-draft')->assertSuccessful();
    expect(Post::count())->toBe($initialCount); // sem novo Post
});
