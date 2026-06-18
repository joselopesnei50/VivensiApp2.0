<?php

namespace Tests\Unit\Services\Messaging;

use App\Services\GeminiService;
use App\Services\Messaging\AudioTranscriptionService;
use PHPUnit\Framework\TestCase;

/**
 * Cobre a parte pura do AudioTranscriptionService (Fase 4 — item 2.4):
 * normalização de resposta do LLM (strip aspas, prefácios) e validações
 * de tamanho/base64. A chamada real ao Gemini fica para Feature test
 * (exige Http::fake + bootstrap completo).
 */
class AudioTranscriptionServiceTest extends TestCase
{
    private AudioTranscriptionService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new AudioTranscriptionService(new GeminiService());
    }

    public function test_normalize_remove_aspas_em_volta(): void
    {
        $this->assertSame('texto puro', $this->svc->normalize('"texto puro"'));
    }

    public function test_normalize_remove_prefacio_transcricao(): void
    {
        $this->assertSame('boa noite', $this->svc->normalize('Transcrição: boa noite'));
        $this->assertSame('boa noite', $this->svc->normalize('transcricao: boa noite'));
        $this->assertSame('boa noite', $this->svc->normalize('Texto: boa noite'));
    }

    public function test_normalize_remove_prefacio_o_audio_diz(): void
    {
        $this->assertSame('confirmado', $this->svc->normalize('O áudio diz: confirmado'));
        $this->assertSame('confirmado', $this->svc->normalize('o audio diz: confirmado'));
    }

    public function test_normalize_aplica_trim(): void
    {
        $this->assertSame('mensagem', $this->svc->normalize("   mensagem   \n"));
    }

    public function test_normalize_mantem_texto_quando_ja_limpo(): void
    {
        $this->assertSame('mensagem direta', $this->svc->normalize('mensagem direta'));
    }

    public function test_normalize_string_vazia_continua_vazia(): void
    {
        $this->assertSame('', $this->svc->normalize(''));
        $this->assertSame('', $this->svc->normalize('   '));
    }

    public function test_transcribe_rejeita_base64_vazio(): void
    {
        $r = $this->svc->transcribe('');
        $this->assertNotNull($r['error']);
        $this->assertStringContainsString('Base64', $r['error']);
    }

    public function test_transcribe_rejeita_base64_invalido(): void
    {
        // String que NÃO é base64 válido — base64_decode strict devolve false.
        $r = $this->svc->transcribe('!!@@##');
        $this->assertNotNull($r['error']);
    }

    public function test_max_audio_bytes_eh_8mb(): void
    {
        $this->assertSame(8 * 1024 * 1024, AudioTranscriptionService::MAX_AUDIO_BYTES);
    }
}
