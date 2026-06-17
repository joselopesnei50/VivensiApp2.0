<?php

namespace Tests\Unit\Http\Requests\Manager;

use App\Support\PiiSniffer;
use PHPUnit\Framework\TestCase;

/**
 * Cobre a lógica anti-PII que o UpdatePerfilOperacionalRequest delega
 * para o PiiSniffer (Fase 1 — Etapa B). FormRequest do Laravel 9 puxa
 * Symfony Request, que exige PHP 8.2+; por isso testamos o helper puro
 * que carrega 100% da heurística sensível.
 */
class UpdatePerfilOperacionalRequestTest extends TestCase
{
    public function test_cpf_com_e_sem_mascara_e_detectado(): void
    {
        $casos = [
            'Cliente CPF 123.456.789-00',
            'João 12345678900 fala mal',
            'documento: 987.654.321/00 (com barra atípica não pega, ok)',
            '111.222.333-44 isolado',
        ];
        $detectados = 0;
        foreach ($casos as $texto) {
            if (PiiSniffer::hasCpf($texto)) {
                $detectados++;
            }
        }
        $this->assertGreaterThanOrEqual(3, $detectados, 'deveria pegar pelo menos 3 dos 4 casos típicos');
        $this->assertTrue(PiiSniffer::hasCpf('123.456.789-00'));
        $this->assertTrue(PiiSniffer::hasCpf('12345678900'));
    }

    public function test_telefone_br_em_varios_formatos_e_detectado(): void
    {
        $casos = [
            '11999998888',
            '(21) 98765-4321',
            '+55 11 99999-1234',
            '21987654321',
            '21 9 8765 4321',
        ];
        foreach ($casos as $telefone) {
            $this->assertTrue(
                PiiSniffer::hasPhoneBR($telefone),
                "deveria detectar telefone em: '{$telefone}'"
            );
        }
    }

    public function test_email_simples_e_complexo_sao_detectados(): void
    {
        $this->assertTrue(PiiSniffer::hasEmail('contato joao@exemplo.com'));
        $this->assertTrue(PiiSniffer::hasEmail('maria.silva+lead@empresa.com.br'));
        $this->assertTrue(PiiSniffer::hasEmail('foo-bar@x.io'));
    }

    public function test_texto_generico_sem_pii_nao_dispara(): void
    {
        $casos = [
            'foco em apoiadores recorrentes que costumam doar até R$ 50',
            'priorize editais municipais de cultura, sobretudo de música',
            'tom acolhedor e direto, sem jargão técnico',
            'lembrar que o público é majoritariamente da zona rural',
            '', // string vazia
            'sem nenhum dado pessoal aqui',
        ];
        foreach ($casos as $texto) {
            $this->assertNull(
                PiiSniffer::detect($texto),
                "texto não deveria disparar PII: '{$texto}'"
            );
        }
    }

    public function test_detect_retorna_tipo_do_primeiro_pii(): void
    {
        // Casos NÃO ambíguos (máscara explícita ou separadores distintos)
        $this->assertSame('cpf',   PiiSniffer::detect('Lead 123.456.789-00 ligou'));
        $this->assertSame('phone', PiiSniffer::detect('Ligar para (21) 98765-4321'));
        $this->assertSame('email', PiiSniffer::detect('mandar para teste@exemplo.com'));
        $this->assertNull(PiiSniffer::detect(''));
        $this->assertNull(PiiSniffer::detect('sem PII'));
    }

    public function test_sequencia_de_11_digitos_e_bloqueada_mesmo_que_tipo_seja_ambiguo(): void
    {
        // 11 dígitos consecutivos podem ser CPF sem máscara OU celular sem
        // separadores. Pra Etapa B basta que SEJA bloqueado (não importa o
        // rótulo). Isso evita falso negativo onde operador cola só números.
        foreach (['11999998888', '12345678900', '21987654321'] as $seq) {
            $this->assertNotNull(PiiSniffer::detect($seq), "11 dígitos não foram bloqueados: '{$seq}'");
        }
    }

    public function test_11_digitos_no_meio_de_frase_tambem_e_bloqueado(): void
    {
        // Uso típico: operador descreve o público colando número embutido.
        $casos = [
            'CPF: 12345678900 do fulano',
            'cliente principal 21987654321 sempre responde rápido',
            'líder local cadastrado como 11999998888 confirmou presença',
        ];
        foreach ($casos as $texto) {
            $this->assertNotNull(PiiSniffer::detect($texto),
                "número embutido em frase deveria ser bloqueado: '{$texto}'");
        }
    }

    public function test_quando_cpf_mascarado_e_telefone_e_email_aparecem_cpf_vence(): void
    {
        $textoMisto = 'Ligar (21) 98765-4321, email teste@x.com, CPF 123.456.789-00';
        $this->assertSame('cpf', PiiSniffer::detect($textoMisto),
            'CPF com máscara é o mais nítido — tem precedência sobre telefone e e-mail');
    }

    public function test_numero_curto_isolado_nao_e_telefone(): void
    {
        $this->assertFalse(PiiSniffer::hasPhoneBR('R$ 50 por doação'));
        $this->assertFalse(PiiSniffer::hasPhoneBR('apenas 100 reais'));
        $this->assertFalse(PiiSniffer::hasPhoneBR('R$ 1.000,00'));
    }
}
