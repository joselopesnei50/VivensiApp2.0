@extends('layouts.app')
@section('title', 'Como usar o Hub de Marketing')

@section('content')

{{-- ── HEADER ── --}}
<div style="display:flex; align-items:center; gap:14px; margin-bottom:28px; flex-wrap:wrap;">
    <a href="{{ route('marketing.index') }}" style="display:inline-flex; align-items:center; gap:6px; color:#64748b; font-size:.8rem; font-weight:700; text-decoration:none; padding:7px 14px; border:1px solid #e2e8f0; border-radius:10px; background:#fff;">
        <i class="fas fa-arrow-left" style="font-size:.7rem;"></i> Voltar ao Hub
    </a>
</div>

{{-- ── HERO ── --}}
<div style="background:#0A0A0B; border:1px solid rgba(255,122,26,.2); border-radius:28px; padding:52px 56px 48px; margin-bottom:28px;">
    <div style="display:flex; align-items:center; gap:48px; flex-wrap:wrap;">
        <div style="flex:1; min-width:280px;">
            <div style="display:inline-flex; align-items:center; gap:8px; background:rgba(255,122,26,.12); border:1px solid rgba(255,122,26,.3); border-radius:20px; padding:5px 14px; margin-bottom:18px;">
                <i class="fas fa-brain" style="color:#FF7A1A; font-size:.75rem;"></i>
                <span style="color:#FF7A1A; font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:1.2px;">Hub de Marketing</span>
            </div>
            <h1 style="color:#fff; font-size:2rem; font-weight:900; margin:0 0 14px; letter-spacing:-.5px; line-height:1.15;">Como usar o<br><span style="color:#FF7A1A;">Hub Estratégico</span> do Bruce?</h1>
            <p style="color:rgba(255,255,255,.6); font-size:.92rem; line-height:1.65; margin:0; max-width:560px;">
                O Bruce IA transforma um briefing simples em plano completo com posicionamento, WhatsApp, redes, captação, copywriting e métricas. E depois traduz tudo em uma rota de execução com botões que abrem as ferramentas certas do Vivensi.
            </p>
        </div>
        <div style="flex-shrink:0; display:flex; flex-direction:column; align-items:center; gap:14px;">
            <img src="{{ asset('img/bruce/bruceia-icone-fundo-escuro.svg') }}" alt="Bruce IA"
                 style="width:120px; height:120px; border-radius:28px; background:#0f0f1e; border:1px solid rgba(255,255,255,.08);">
            <span style="color:rgba(255,255,255,.4); font-size:.7rem; font-weight:600;">Explicado por Bruce IA</span>
        </div>
    </div>
</div>

{{-- ── FLUXO EM 3 PASSOS ── --}}
<div style="background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:32px; margin-bottom:24px;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:24px;">
        <div style="width:44px; height:44px; background:#FF7A1A; border-radius:13px; display:flex; align-items:center; justify-content:center;">
            <i class="fas fa-route" style="color:#fff; font-size:1rem;"></i>
        </div>
        <div>
            <div style="font-size:1rem; font-weight:800; color:#0f172a;">Do briefing ao plano executável</div>
            <div style="font-size:.75rem; color:#94a3b8;">3 passos, ~2 minutos total</div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:16px;">
        @foreach([
            ['1','fa-clipboard-list','Preencha o briefing','Objetivo, público-alvo, abrangência (online/presencial), tom, orçamento, concorrentes e projeto vinculado (se houver).'],
            ['2','fa-wand-magic-sparkles','Bruce gera o plano','~30-60s. Você recebe posicionamento, personas, ações WhatsApp, redes, captação, copies prontas, métricas e plano de 72h.'],
            ['3','fa-arrow-up-right-from-square','Execute com 1 clique','A aba "Guia do Bruce" transforma o plano em passos prescritivos com botões "Abrir agora" pros módulos do Vivensi.'],
        ] as [$num, $ico, $title, $desc])
        <div style="padding:18px; border:1px solid #e2e8f0; border-radius:14px; background:#fafafa;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                <div style="width:28px; height:28px; background:#FF7A1A; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:.85rem;">{{ $num }}</div>
                <i class="fas {{ $ico }}" style="color:#FF7A1A; font-size:.9rem;"></i>
            </div>
            <div style="font-size:.88rem; font-weight:800; color:#0f172a; margin-bottom:6px;">{{ $title }}</div>
            <div style="font-size:.75rem; color:#64748b; line-height:1.55;">{{ $desc }}</div>
        </div>
        @endforeach
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px;">

    {{-- ── PLANO EXECUTIVO ── --}}
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:30px;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px;">
            <div style="width:44px; height:44px; background:#4f46e5; border-radius:13px; display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-diagram-project" style="color:#fff; font-size:1rem;"></i>
            </div>
            <div>
                <div style="font-size:.95rem; font-weight:800; color:#0f172a;">Aba "Plano Executivo"</div>
                <div style="font-size:.72rem; color:#94a3b8;">O plano completo em blocos</div>
            </div>
        </div>
        <p style="font-size:.82rem; color:#475569; line-height:1.6; margin-bottom:14px;">
            Cards numerados em fluxo vertical, conectados por setas. Cada card = 1 seção do plano (Posicionamento, WhatsApp, Redes, Captação, Copy, Métricas, Plano 72h).
        </p>
        <div style="display:flex; flex-direction:column; gap:8px;">
            @foreach([
                ['fa-bullseye','Posicionamento estratégico'],
                ['fa-brands fa-whatsapp','WhatsApp (bot, grupos, scripts)'],
                ['fa-hashtag','Redes sociais e conteúdo'],
                ['fa-magnet','Captação de leads'],
                ['fa-feather','Copywriting (copies prontas)'],
                ['fa-chart-line','Métricas e KPIs'],
                ['fa-flag-checkered','Plano de ação 72h'],
            ] as [$ico, $label])
            <div style="display:flex; align-items:center; gap:10px; padding:8px 12px; background:#fafafa; border:1px solid #f1f5f9; border-radius:8px; font-size:.78rem; color:#334155; font-weight:600;">
                <i class="fas {{ $ico }}" style="color:#4f46e5; width:18px; text-align:center;"></i>
                {{ $label }}
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── GUIA DO BRUCE ── --}}
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:30px;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px;">
            <img src="{{ asset('img/bruce/bruceia-icone-fundo-escuro.svg') }}" alt="Bruce"
                 style="width:44px; height:44px; border-radius:13px; background:#0f0f1e; border:1px solid rgba(255,255,255,.08);">
            <div>
                <div style="font-size:.95rem; font-weight:800; color:#0f172a;">Aba "Guia do Bruce"</div>
                <div style="font-size:.72rem; color:#94a3b8;">Como executar hoje mesmo</div>
            </div>
        </div>
        <p style="font-size:.82rem; color:#475569; line-height:1.6; margin-bottom:14px;">
            O Bruce lê o plano e devolve um checklist prescritivo dividido em fases (Semana 1, Semana 2, Mês 1...). Cada passo tem <strong>botão "Abrir agora"</strong> que leva direto pra ferramenta certa do Vivensi.
        </p>
        <div style="padding:14px; background:#f5f3ff; border:1px solid #ddd6fe; border-radius:12px;">
            <div style="font-size:.78rem; font-weight:800; color:#5b21b6; margin-bottom:8px;">Exemplo de passo</div>
            <div style="background:#fff; border:1px solid #e9d5ff; border-radius:10px; padding:12px;">
                <div style="font-size:.82rem; font-weight:800; color:#0f172a; margin-bottom:4px;">Ative o bot de atendimento</div>
                <div style="font-size:.72rem; color:#64748b; margin-bottom:8px;">Configure a saudação com o tom de voz que você escolheu no briefing.</div>
                <div style="display:inline-flex; align-items:center; gap:6px; background:#FF7A1A; color:#fff; font-size:.72rem; font-weight:800; padding:6px 12px; border-radius:8px;">
                    <i class="fas fa-arrow-up-right-from-square" style="font-size:.62rem;"></i> Abrir agora
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── FERRAMENTAS LINKADAS ── --}}
<div style="background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:32px; margin-bottom:24px;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
        <div style="width:44px; height:44px; background:#0891b2; border-radius:13px; display:flex; align-items:center; justify-content:center;">
            <i class="fas fa-toolbox" style="color:#fff; font-size:1rem;"></i>
        </div>
        <div>
            <div style="font-size:1rem; font-weight:800; color:#0f172a;">Ferramentas que o Bruce pode acionar</div>
            <div style="font-size:.72rem; color:#94a3b8;">O guia sempre linka pra estas rotas do sistema</div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:12px;">
        @foreach([
            ['fa-brands fa-whatsapp','Disparo em massa','#25D366'],
            ['fa-robot','Bot WhatsApp','#25D366'],
            ['fa-tags','Etiquetas','#7c3aed'],
            ['fa-envelope','E-mail Marketing','#0891b2'],
            ['fa-wand-magic-sparkles','Social AI Hub','#d97706'],
            ['fa-calendar-days','Calendário Insta/FB','#0891b2'],
            ['fa-magnet','Prospecção de Leads','#dc2626'],
            ['fa-file-lines','Landing Pages','#4f46e5'],
            ['fa-ticket','Rifas Online','#d97706'],
            ['fa-hand-holding-heart','Portal do Doador','#dc2626'],
            ['fa-satellite-dish','Radar de Editais','#16a34a'],
            ['fa-diagram-project','Projetos'  ,'#4f46e5'],
        ] as [$ico, $label, $color])
        <div style="display:flex; align-items:center; gap:10px; padding:12px 14px; background:#fafafa; border:1px solid #f1f5f9; border-radius:10px;">
            <i class="fas {{ $ico }}" style="color:{{ $color }}; font-size:.85rem; width:20px; text-align:center;"></i>
            <span style="font-size:.78rem; font-weight:600; color:#334155;">{{ $label }}</span>
        </div>
        @endforeach
    </div>
</div>

{{-- ── DICAS DE BRIEFING ── --}}
<div style="background:#0A0A0B; border:1px solid rgba(255,122,26,.15); border-radius:20px; padding:32px; margin-bottom:24px;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
        <div style="width:44px; height:44px; background:#FF7A1A; border-radius:13px; display:flex; align-items:center; justify-content:center;">
            <i class="fas fa-lightbulb" style="color:#fff; font-size:1rem;"></i>
        </div>
        <div>
            <div style="font-size:1rem; font-weight:800; color:#fff;">6 dicas para um briefing que gera plano poderoso</div>
            <div style="font-size:.72rem; color:rgba(255,255,255,.5);">Quanto mais concreto, melhor o resultado</div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
        @foreach([
            ['fa-bullseye','Objetivo mensurável','Ruim: "aumentar impacto". Bom: "captar R$ 50k em 90 dias com foco em pessoa física via Instagram e WhatsApp".'],
            ['fa-users','Público-alvo específico','Ruim: "todo mundo". Bom: "mulheres 25-45 anos, moradoras de SP capital, sensíveis a causas de educação infantil".'],
            ['fa-microphone','Escolha o tom certo','Inspirador = doadores emocionais. Profissional = empresas/patrocinadores. Amigável = comunidade. Urgente = campanhas de curto prazo.'],
            ['fa-link','Referencie concorrentes','Cole links de perfis/sites que você admira. O Bruce usa como benchmark de estilo e estratégia.'],
            ['fa-coins','Seja honesto no orçamento','Se é zero, marque "só orgânico" — o Bruce prioriza ações grátis. Se tem verba, cita canais pagos e allocation.'],
            ['fa-diagram-project','Vincule ao projeto','Se o plano é pra um projeto específico já cadastrado no Vivensi, escolha no campo "Projeto" — o Bruce contextualiza tudo.'],
        ] as [$ico, $title, $desc])
        <div style="padding:16px; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); border-radius:12px;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                <i class="fas {{ $ico }}" style="color:#FF7A1A; font-size:.85rem;"></i>
                <div style="font-size:.85rem; font-weight:800; color:#fff;">{{ $title }}</div>
            </div>
            <div style="font-size:.75rem; color:rgba(255,255,255,.55); line-height:1.55;">{{ $desc }}</div>
        </div>
        @endforeach
    </div>
</div>

{{-- ── CTA ── --}}
<div style="background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:32px 36px; display:flex; align-items:center; justify-content:space-between; gap:24px; flex-wrap:wrap;">
    <div>
        <div style="font-size:.95rem; font-weight:800; color:#0f172a; margin-bottom:6px;">Pronto pra criar seu primeiro plano?</div>
        <p style="font-size:.82rem; color:#64748b; margin:0;">Preencha o briefing e deixe o Bruce montar o mapa e o guia de execução.</p>
    </div>
    <a href="{{ route('marketing.create') }}" style="display:inline-flex; align-items:center; gap:8px; background:#FF7A1A; color:#fff; font-size:.82rem; font-weight:800; padding:12px 26px; border-radius:12px; text-decoration:none;">
        <i class="fas fa-plus"></i> Criar novo plano
    </a>
</div>

@push('styles')
<style>
@media (max-width: 900px) {
    div[style*="grid-template-columns:1fr 1fr"],
    div[style*="grid-template-columns:repeat(3, 1fr)"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
@endpush

@endsection
