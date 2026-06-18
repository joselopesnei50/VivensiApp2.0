<?php

namespace Tests\Unit\Services;

use App\Services\LeadService;
use PHPUnit\Framework\TestCase;

/**
 * Cobre a parte SEM side-effects do LeadService (Fase 5 — item 5.2):
 * normalização de telefone BR, principal helper de unicidade. Operações
 * com DB (findOrCreate, recordConsent, addTimelineItem, unsubscribe)
 * ficam para Feature test — exigem bootstrap completo.
 */
class LeadServiceTest extends TestCase
{
    private LeadService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new LeadService();
    }

    // ── normalizePhone (chave de unicidade) ───────────────────────────────

    public function test_normaliza_telefone_com_mascara_completa(): void
    {
        $this->assertSame('5511999998888', $this->svc->normalizePhone('(11) 99999-8888'));
    }

    public function test_normaliza_telefone_com_prefixo_55_internacional(): void
    {
        $this->assertSame('5511999998888', $this->svc->normalizePhone('+55 11 99999-8888'));
    }

    public function test_normaliza_telefone_so_digitos_com_55(): void
    {
        $this->assertSame('5511999998888', $this->svc->normalizePhone('5511999998888'));
    }

    public function test_normaliza_telefone_com_DDD_sem_prefixo_internacional(): void
    {
        $this->assertSame('5511999998888', $this->svc->normalizePhone('11999998888'));
        $this->assertSame('5511999998888', $this->svc->normalizePhone('(11)99999-8888'));
    }

    public function test_telefone_fixo_10_digitos_recebe_prefixo_55(): void
    {
        // 11 3333-4444 (fixo SP)
        $this->assertSame('551133334444', $this->svc->normalizePhone('11 3333-4444'));
    }

    public function test_telefone_sem_DDD_passa_como_esta(): void
    {
        // 8 dígitos = só número local (raro mas legal manter)
        $this->assertSame('99998888', $this->svc->normalizePhone('99998888'));
    }

    public function test_telefone_muito_curto_devolve_null(): void
    {
        $this->assertNull($this->svc->normalizePhone('123'));
        $this->assertNull($this->svc->normalizePhone(''));
        $this->assertNull($this->svc->normalizePhone(null));
    }

    public function test_telefone_acima_de_E164_devolve_null(): void
    {
        // Tudo número, 16 dígitos — acima do E.164 max (15).
        $this->assertNull($this->svc->normalizePhone('1234567890123456'));
    }

    public function test_normalize_descarta_pontuacao_e_letras_no_meio(): void
    {
        $this->assertSame('5511999998888', $this->svc->normalizePhone('TELEFONE: (11) 99999-8888'));
    }

    public function test_normalize_preserva_55_quando_ja_tem_no_inicio(): void
    {
        // 13 dígitos começando com 55 → já tem prefixo, não duplica.
        $this->assertSame('5511999998888', $this->svc->normalizePhone('5511999998888'));
    }

    public function test_constantes_de_status_existem(): void
    {
        $this->assertSame('pending',      \App\Models\Lead::STATUS_PENDING);
        $this->assertSame('confirmed',    \App\Models\Lead::STATUS_CONFIRMED);
        $this->assertSame('unsubscribed', \App\Models\Lead::STATUS_UNSUBSCRIBED);
        $this->assertSame('blocked',      \App\Models\Lead::STATUS_BLOCKED);
    }

    public function test_constantes_de_consent_existem(): void
    {
        $this->assertSame('opt_in',           \App\Models\LeadConsent::TYPE_OPT_IN);
        $this->assertSame('double_opt_in',    \App\Models\LeadConsent::TYPE_DOUBLE_OPT_IN);
        $this->assertSame('opt_out',          \App\Models\LeadConsent::TYPE_OPT_OUT);
        $this->assertSame('preference_update',\App\Models\LeadConsent::TYPE_PREFERENCE);
    }

    public function test_constantes_de_timeline_existem(): void
    {
        $this->assertSame('note',             \App\Models\LeadTimelineItem::TYPE_NOTE);
        $this->assertSame('whatsapp_message', \App\Models\LeadTimelineItem::TYPE_WHATSAPP_MESSAGE);
        $this->assertSame('form_completed',   \App\Models\LeadTimelineItem::TYPE_FORM_COMPLETED);
        $this->assertSame('consent',          \App\Models\LeadTimelineItem::TYPE_CONSENT);
        $this->assertSame('next_action',      \App\Models\LeadTimelineItem::TYPE_NEXT_ACTION);
        $this->assertSame('ai_suggestion',    \App\Models\LeadTimelineItem::TYPE_AI_SUGGESTION);
        $this->assertSame('status_change',    \App\Models\LeadTimelineItem::TYPE_STATUS_CHANGE);
    }
}
