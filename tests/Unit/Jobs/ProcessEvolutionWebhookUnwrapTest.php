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
 * Bug 1 (fix-whatsapp-critico): Evolution v2/Baileys embrulha o conteúdo em
 * wrappers (ephemeralMessage, viewOnceMessage, documentWithCaptionMessage,
 * editedMessage). Antes do helper unwrapEvolutionMessage, mensagens com esses
 * wrappers caíam em "[mensagem não suportada]" e a IA não disparava.
 */
class ProcessEvolutionWebhookUnwrapTest extends TestCase
{
    private ReflectionMethod $method;
    private ProcessEvolutionWebhook $job;

    protected function setUp(): void
    {
        parent::setUp();
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

    public function test_ephemeral_message_com_conversation_extrai_texto(): void
    {
        $payload = [
            'ephemeralMessage' => [
                'message' => [
                    'conversation' => 'Oi, tenho interesse!',
                ],
            ],
        ];
        $r = $this->extract($payload);
        $this->assertSame('Oi, tenho interesse!', $r['content']);
        $this->assertSame('Oi, tenho interesse!', $r['user_text']);
        $this->assertSame('text', $r['type']);
    }

    public function test_ephemeral_message_com_extendedTextMessage(): void
    {
        $payload = [
            'ephemeralMessage' => [
                'message' => [
                    'extendedTextMessage' => [
                        'text' => 'Sim, pode ligar',
                    ],
                ],
            ],
        ];
        $r = $this->extract($payload);
        $this->assertSame('Sim, pode ligar', $r['content']);
        $this->assertSame('Sim, pode ligar', $r['user_text']);
    }

    public function test_view_once_v2_com_image(): void
    {
        $payload = [
            'viewOnceMessageV2' => [
                'message' => [
                    'imageMessage' => [
                        'caption' => 'meu comprovante',
                    ],
                ],
            ],
        ];
        $r = $this->extract($payload);
        $this->assertSame('meu comprovante', $r['content']);
        $this->assertSame('image', $r['type']);
    }

    public function test_document_with_caption_message(): void
    {
        $payload = [
            'documentWithCaptionMessage' => [
                'message' => [
                    'documentMessage' => [
                        'fileName' => 'rg.pdf',
                        'caption'  => 'meu RG',
                    ],
                ],
            ],
        ];
        $r = $this->extract($payload);
        $this->assertNotEmpty($r['content']);
        $this->assertSame('document', $r['type']);
    }

    public function test_edited_message_extrai_conteudo_corrigido(): void
    {
        $payload = [
            'editedMessage' => [
                'message' => [
                    'protocolMessage' => [
                        'editedMessage' => [
                            'conversation' => 'corrigindo: era 100 não 1000',
                        ],
                    ],
                ],
            ],
        ];
        $r = $this->extract($payload);
        $this->assertSame('corrigindo: era 100 não 1000', $r['content']);
    }

    public function test_texto_simples_sem_wrapper_continua_funcionando(): void
    {
        $payload = ['conversation' => 'Quero doar'];
        $r = $this->extract($payload);
        $this->assertSame('Quero doar', $r['content']);
        $this->assertSame('Quero doar', $r['user_text']);
    }

    public function test_wrapper_aninhado_nao_quebra(): void
    {
        // Edge case raro: ephemeral dentro de editedMessage (não deve loopar)
        $payload = [
            'ephemeralMessage' => [
                'message' => [
                    'editedMessage' => [
                        'message' => [
                            'protocolMessage' => [
                                'editedMessage' => [
                                    'conversation' => 'texto duplo-wrapped',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $r = $this->extract($payload);
        $this->assertSame('texto duplo-wrapped', $r['content']);
    }
}
