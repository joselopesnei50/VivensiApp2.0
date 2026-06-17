<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessEvolutionWebhook;
use Illuminate\Container\Container;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Facade;
use Monolog\Handler\NullHandler;
use Monolog\Logger as MonologLogger;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Cobre o parser Baileys do webhook Evolution (Fase 0 do roadmap).
 * Regra de ouro: a thread nunca pode abrir em branco — todo tipo de mensagem
 * deve produzir um content não-vazio. user_text só fica preenchido quando há
 * texto digitado pelo usuário (gating de opt-in/STOP/automation/IA-texto).
 */
class ProcessEvolutionWebhookExtractionTest extends TestCase
{
    private ReflectionMethod $method;
    private ProcessEvolutionWebhook $job;

    protected function setUp(): void
    {
        parent::setUp();

        // Container mínimo com Log silencioso — o fallback de tipo desconhecido
        // chama Log::info; sem bootstrap da app, a Facade explodiria.
        $container = new Container();
        Container::setInstance($container);
        Facade::setFacadeApplication($container);
        $container->instance('log', new Logger(new MonologLogger('test', [new NullHandler()])));

        $this->job = new ProcessEvolutionWebhook(0, 'MESSAGES_UPSERT', []);
        $this->method = new ReflectionMethod(ProcessEvolutionWebhook::class, 'extractMessageContent');
        $this->method->setAccessible(true);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        Facade::clearResolvedInstances();
        parent::tearDown();
    }

    private function extract(array $msg): array
    {
        return $this->method->invoke($this->job, $msg);
    }

    public function test_texto_puro_conversation(): void
    {
        $r = $this->extract(['conversation' => 'oi tudo bem']);
        $this->assertSame('text', $r['type']);
        $this->assertSame('oi tudo bem', $r['content']);
        $this->assertSame('oi tudo bem', $r['user_text']);
        $this->assertNull($r['media_path']);
    }

    public function test_extended_text_message(): void
    {
        $r = $this->extract(['extendedTextMessage' => ['text' => 'com *negrito*']]);
        $this->assertSame('text', $r['type']);
        $this->assertSame('com *negrito*', $r['user_text']);
    }

    public function test_imagem_com_legenda(): void
    {
        $r = $this->extract(['imageMessage' => ['caption' => 'olha que legal', 'url' => 'https://x']]);
        $this->assertSame('image', $r['type']);
        $this->assertSame('olha que legal', $r['content']);
        $this->assertSame('olha que legal', $r['user_text']);
        $this->assertSame('olha que legal', $r['media_caption']);
        $this->assertSame('https://x', $r['media_path']);
    }

    public function test_imagem_sem_legenda_marca_e_zera_user_text(): void
    {
        $r = $this->extract(['imageMessage' => ['url' => 'https://x']]);
        $this->assertSame('image', $r['type']);
        $this->assertSame('[imagem]', $r['content']);
        $this->assertNull($r['user_text']);
        $this->assertNull($r['media_caption']);
    }

    public function test_video_com_legenda(): void
    {
        $r = $this->extract(['videoMessage' => ['caption' => 'video', 'url' => 'https://x']]);
        $this->assertSame('video', $r['type']);
        $this->assertSame('video', $r['user_text']);
    }

    public function test_video_sem_legenda(): void
    {
        $r = $this->extract(['videoMessage' => ['url' => 'https://x']]);
        $this->assertSame('[vídeo]', $r['content']);
        $this->assertNull($r['user_text']);
    }

    public function test_audio_marca_user_text_null_para_fluxo_stt(): void
    {
        $r = $this->extract(['audioMessage' => ['url' => 'https://x', 'base64' => 'AAA']]);
        $this->assertSame('audio', $r['type']);
        $this->assertSame('[Mensagem de Áudio]', $r['content']);
        $this->assertNull($r['user_text']);
    }

    public function test_documento_com_fileName(): void
    {
        $r = $this->extract(['documentMessage' => ['fileName' => 'proposta.pdf', 'url' => 'https://x']]);
        $this->assertSame('document', $r['type']);
        $this->assertSame('[documento: proposta.pdf]', $r['content']);
        $this->assertNull($r['user_text']);
    }

    public function test_documento_com_caption_preenche_user_text(): void
    {
        $r = $this->extract(['documentMessage' => ['fileName' => 'a.pdf', 'caption' => 'leia']]);
        $this->assertSame('leia', $r['user_text']);
        $this->assertSame('leia', $r['media_caption']);
    }

    public function test_sticker(): void
    {
        $r = $this->extract(['stickerMessage' => ['url' => 'https://x']]);
        $this->assertSame('sticker', $r['type']);
        $this->assertSame('[sticker]', $r['content']);
        $this->assertNull($r['user_text']);
    }

    public function test_localizacao_nao_persiste_coordenadas(): void
    {
        $r = $this->extract(['locationMessage' => ['degreesLatitude' => -23.5505, 'degreesLongitude' => -46.6333]]);
        $this->assertSame('location', $r['type']);
        $this->assertSame('[localização compartilhada]', $r['content']);

        $blob = json_encode($r);
        $this->assertStringNotContainsString('23.55', $blob, 'coordenada vazou no resultado (LGPD)');
        $this->assertStringNotContainsString('46.63', $blob, 'coordenada vazou no resultado (LGPD)');
        $this->assertNull($r['media_path']);
    }

    public function test_live_location_tambem_marca_sem_coordenadas(): void
    {
        $r = $this->extract(['liveLocationMessage' => ['degreesLatitude' => 1.0]]);
        $this->assertSame('location', $r['type']);
        $this->assertStringNotContainsString('1.0', json_encode($r));
    }

    public function test_contato_nao_persiste_vcard_nem_nome(): void
    {
        $r = $this->extract(['contactMessage' => ['displayName' => 'Joao', 'vcard' => 'BEGIN:VCARD']]);
        $this->assertSame('contact', $r['type']);
        $this->assertSame('[contato compartilhado]', $r['content']);

        $blob = json_encode($r);
        $this->assertStringNotContainsString('VCARD', $blob, 'vCard vazou no resultado (PII)');
        $this->assertStringNotContainsString('Joao', $blob, 'nome do contato vazou no resultado (PII)');
    }

    public function test_resposta_de_botao_alimenta_user_text(): void
    {
        $r = $this->extract(['buttonsResponseMessage' => ['selectedDisplayText' => 'Sim, quero']]);
        $this->assertSame('button_reply', $r['type']);
        $this->assertSame('Sim, quero', $r['user_text']);
    }

    public function test_resposta_de_lista_alimenta_user_text(): void
    {
        $r = $this->extract(['listResponseMessage' => ['title' => 'Opcao A', 'singleSelectReply' => ['selectedRowId' => 'a']]]);
        $this->assertSame('list_reply', $r['type']);
        $this->assertSame('Opcao A', $r['user_text']);
    }

    public function test_interactive_response_cloud_api(): void
    {
        $r = $this->extract(['interactiveResponseMessage' => ['body' => ['text' => 'Confirmo']]]);
        $this->assertSame('interactive_reply', $r['type']);
        $this->assertSame('Confirmo', $r['user_text']);
    }

    public function test_reacao_nao_dispara_ia_texto(): void
    {
        $r = $this->extract(['reactionMessage' => ['text' => 'fire']]);
        $this->assertSame('reaction', $r['type']);
        $this->assertSame('[reação: fire]', $r['content']);
        $this->assertNull($r['user_text']);
    }

    public function test_tipo_desconhecido_cai_no_fallback_sem_quebrar(): void
    {
        $r = $this->extract(['pollCreationMessage' => ['name' => 'p']]);
        $this->assertSame('unsupported', $r['type']);
        $this->assertSame('[mensagem não suportada]', $r['content']);
        $this->assertNull($r['user_text']);
    }

    public function test_payload_vazio_cai_no_fallback_sem_quebrar(): void
    {
        $r = $this->extract([]);
        $this->assertSame('unsupported', $r['type']);
        $this->assertSame('[mensagem não suportada]', $r['content']);
    }

    /**
     * Invariante central da Fase 0: o histórico nunca pode abrir em branco —
     * todo tipo de mensagem deve produzir content não-vazio.
     */
    public function test_invariante_content_nunca_vazio(): void
    {
        $payloads = [
            ['conversation' => 'x'],
            ['extendedTextMessage' => ['text' => 'x']],
            ['imageMessage' => []],
            ['videoMessage' => []],
            ['audioMessage' => []],
            ['documentMessage' => []],
            ['stickerMessage' => []],
            ['locationMessage' => []],
            ['liveLocationMessage' => []],
            ['contactMessage' => []],
            ['contactsArrayMessage' => []],
            ['buttonsResponseMessage' => []],
            ['listResponseMessage' => []],
            ['interactiveResponseMessage' => []],
            ['reactionMessage' => []],
            ['pollCreationMessage' => []],
            [],
        ];

        foreach ($payloads as $i => $msg) {
            $r = $this->extract($msg);
            $this->assertNotSame('', $r['content'], "content veio vazio no payload index {$i}");
        }
    }
}
