<?php

use App\Models\SystemSetting;
use App\Services\SocialAIContentService;
use Illuminate\Support\Facades\Http;

/**
 * Trava o fix de 2026-08-24: DeepSeek v4-flash as vezes devolve `image_prompt`
 * ou items de `captions` como objeto aninhado ({"text": "..."} em vez de string),
 * e o `(string) $array` + `strval($array)` triggerem "Array to string conversion"
 * e virava literalmente a palavra "Array" — quebrando /social-ai com mensagem
 * generica "Falha na geracao".
 */

beforeEach(function () {
    SystemSetting::setValue('deepseek_api_key', 'test-ds-key');
    SystemSetting::setValue('together_ai_api_key', 'test-tg-key');
});

it('flattenToString devolve string quando input ja e string', function () {
    expect(SocialAIContentService::flattenToString('ola mundo'))->toBe('ola mundo');
    expect(SocialAIContentService::flattenToString('  trim me  '))->toBe('trim me');
});

it('flattenToString extrai chave text/description quando input e array aninhado', function () {
    expect(SocialAIContentService::flattenToString(['text' => 'meu texto']))->toBe('meu texto');
    expect(SocialAIContentService::flattenToString(['description' => 'descricao']))->toBe('descricao');
    expect(SocialAIContentService::flattenToString(['caption' => 'legenda']))->toBe('legenda');
    expect(SocialAIContentService::flattenToString(['prompt' => 'ingles']))->toBe('ingles');
});

it('flattenToString cai pra concat de strings escalares quando nao tem chave conhecida', function () {
    $r = SocialAIContentService::flattenToString(['foto de gato', 'no telhado']);
    expect($r)->toBe('foto de gato no telhado');
});

it('flattenToString cai pra json quando array so tem escalares nao-string', function () {
    $r = SocialAIContentService::flattenToString(['a' => 1, 'b' => 2]);
    // vira "1 2" pela concat de escalares
    expect($r)->toContain('1');
});

it('flattenToString devolve string vazia pra null', function () {
    expect(SocialAIContentService::flattenToString(null))->toBe('');
});

it('generateContent aceita image_prompt como objeto aninhado (bug prod)', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'captions' => ['legenda 1', 'legenda 2', 'legenda 3'],
                        // DeepSeek devolveu OBJETO em vez de string — bug real
                        'image_prompt' => ['description' => 'a beautiful sunset', 'style' => 'cinematic'],
                    ]),
                ],
            ]],
        ], 200),
    ]);

    $result = (new SocialAIContentService())->generateContent('sunset');

    expect($result['image_prompt'])->toBe('a beautiful sunset');
    expect($result['captions'])->toBe(['legenda 1', 'legenda 2', 'legenda 3']);
});

it('generateContent aceita items de captions como objetos aninhados', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'captions' => [
                            ['text' => 'primeira legenda'],
                            'segunda como string mesmo',
                            ['caption' => 'terceira aninhada'],
                        ],
                        'image_prompt' => 'a nice prompt',
                    ]),
                ],
            ]],
        ], 200),
    ]);

    $result = (new SocialAIContentService())->generateContent('teste');

    expect($result['captions'])->toBe([
        'primeira legenda',
        'segunda como string mesmo',
        'terceira aninhada',
    ]);
});

it('generateContent injeta style modifier no image_prompt quando visual_style informado', function () {
    Http::fake([
        'api.deepseek.com/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode([
                'captions' => ['a', 'b', 'c'],
                'image_prompt' => 'gato no telhado',
            ])]]],
        ], 200),
    ]);

    $r = (new SocialAIContentService())->generateContent('teste', null, 'photorealistic');
    expect($r['image_prompt'])->toContain('gato no telhado');
    expect($r['image_prompt'])->toContain('cinematic lighting');
});

it('generateContent explode quando image_prompt fica vazio pos-normalizacao', function () {
    // Array vazio passa pelo isset() mas flattenToString devolve '' → nova mensagem
    Http::fake([
        'api.deepseek.com/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode([
                'captions' => ['a', 'b', 'c'],
                'image_prompt' => [],
            ])]]],
        ], 200),
    ]);

    expect(fn () => (new SocialAIContentService())->generateContent('teste'))
        ->toThrow(Exception::class, 'image_prompt vazio');
});
