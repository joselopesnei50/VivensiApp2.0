@extends('layouts.app')
@section('title', 'Como postar nas redes — Bruce IA')

@section('content')

{{-- ── VOLTAR ── --}}
<div style="display:flex; align-items:center; gap:14px; margin-bottom:28px; flex-wrap:wrap;">
    <a href="{{ route('social.analytics.index') }}" style="display:inline-flex; align-items:center; gap:6px; color:#64748b; font-size:.8rem; font-weight:700; text-decoration:none; padding:7px 14px; border:1px solid #e2e8f0; border-radius:10px; background:#fff;">
        <i class="fas fa-arrow-left" style="font-size:.7rem;"></i> Voltar para Analytics
    </a>
</div>

{{-- ── HERO BRUCE ── --}}
<div style="background:#0A0A0B; border:1px solid rgba(255,122,26,.2); border-radius:28px; padding:52px 56px 48px; margin-bottom:28px;">
    <div style="display:flex; align-items:center; gap:48px; flex-wrap:wrap;">
        <div style="flex:1; min-width:280px;">
            <div style="display:inline-flex; align-items:center; gap:8px; background:rgba(255,122,26,.12); border:1px solid rgba(255,122,26,.3); border-radius:20px; padding:5px 14px; margin-bottom:18px;">
                <i class="fas fa-share-nodes" style="color:#FF7A1A; font-size:.75rem;"></i>
                <span style="color:#FF7A1A; font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:1.2px;">Guia de Publicação</span>
            </div>
            <h1 style="color:#fff; font-size:2rem; font-weight:900; margin:0 0 14px; letter-spacing:-.5px; line-height:1.15;">Como criar e agendar<br><span style="color:#FF7A1A;">posts nas suas redes</span></h1>
            <p style="color:rgba(255,255,255,.6); font-size:.92rem; line-height:1.65; margin:0; max-width:560px;">
                Bora publicar? Neste guia o Bruce mostra o passo a passo — desde conectar sua página do Facebook/Instagram até publicar na hora ou deixar agendado pra sair sozinho no melhor horário.
            </p>
        </div>
        <div style="flex-shrink:0; display:flex; flex-direction:column; align-items:center; gap:14px;">
            <img src="{{ asset('img/bruce/bruceia-icone-fundo-escuro.svg') }}" alt="Bruce IA"
                 style="width:120px; height:120px; border-radius:28px; background:#0f0f1e; border:1px solid rgba(255,255,255,.08);">
            <span style="color:rgba(255,255,255,.4); font-size:.7rem; font-weight:600;">Explicado por Bruce IA</span>
        </div>
    </div>
</div>

{{-- ── PASSO A PASSO ── --}}
<div style="background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:32px; margin-bottom:24px;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:24px;">
        <div style="width:44px; height:44px; background:#FF7A1A; border-radius:13px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i class="fas fa-list-ol" style="color:#fff; font-size:1rem;"></i>
        </div>
        <div>
            <div style="font-size:1rem; font-weight:800; color:#0f172a;">Do zero à publicação — 5 passos</div>
            <div style="font-size:.75rem; color:#94a3b8;">Você faz uma vez e o resto o Bruce te ajuda a repetir</div>
        </div>
    </div>

    <div style="display:flex; flex-direction:column; gap:14px;">
        @foreach([
            ['1', 'fa-plug', 'Conecte sua página', 'Vá em <strong>Redes Sociais → Contas</strong> e clique em "Conectar Facebook". A Meta vai pedir permissão pra publicar na sua Página e Instagram Business vinculado. Autorize uma vez só — não precisa refazer.'],
            ['2', 'fa-pen-to-square', 'Crie o post', 'Em <strong>Posts → Novo Post</strong> escreva a legenda (ou gera com o Bruce no Social AI Hub), suba a imagem/vídeo e escolha onde vai publicar: só Facebook, só Instagram ou nos dois.'],
            ['3', 'fa-images', 'Escolha o formato', 'Feed (foto normal), Carrossel (2-10 imagens no mesmo post) ou Story (dura 24h). O Bruce ajusta as opções conforme sua escolha.'],
            ['4', 'fa-clock', 'Publicar agora ou agendar', '"Publicar agora" manda direto pra Meta em segundos. "Agendar" abre calendário — escolha data e hora e o post sai sozinho, mesmo se você estiver dormindo.'],
            ['5', 'fa-check-double', 'Confirme e pronto', 'Você recebe notificação quando o post publica. Se der algum erro (foto grande, conta desconectada), o Bruce avisa e o status vira "Falhou" no painel — dá pra corrigir e re-agendar num clique.'],
        ] as [$num, $ico, $title, $desc])
        <div style="display:flex; gap:16px; padding:18px; border:1px solid #e2e8f0; border-radius:14px; background:#fafafa;">
            <div style="flex-shrink:0; display:flex; flex-direction:column; align-items:center; gap:8px;">
                <div style="width:38px; height:38px; background:#FF7A1A; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1rem;">{{ $num }}</div>
                <i class="fas {{ $ico }}" style="color:#FF7A1A; font-size:.95rem;"></i>
            </div>
            <div>
                <div style="font-size:.92rem; font-weight:800; color:#0f172a; margin-bottom:4px;">{{ $title }}</div>
                <div style="font-size:.82rem; color:#475569; line-height:1.6;">{!! $desc !!}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- ── DOIS BLOCOS: PUBLICAR AGORA vs AGENDAR ── --}}
<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px;">

    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:30px;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px;">
            <div style="width:44px; height:44px; background:#16a34a; border-radius:13px; display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-bolt" style="color:#fff; font-size:1rem;"></i>
            </div>
            <div>
                <div style="font-size:.95rem; font-weight:800; color:#0f172a;">Publicar na hora</div>
                <div style="font-size:.72rem; color:#94a3b8;">Ideal pra notícias e ações rápidas</div>
            </div>
        </div>
        <ul style="margin:0; padding-left:18px; color:#475569; font-size:.82rem; line-height:1.75;">
            <li>Deixe o campo "Data de publicação" em branco (ou clique em "Publicar agora").</li>
            <li>O Bruce envia direto pra Meta — vai ao ar em até 30 segundos.</li>
            <li>Você recebe um sino de notificação quando aparecer na sua página.</li>
            <li>Bom pra: comunicados urgentes, respostas a eventos, mobilizações.</li>
        </ul>
        <div style="margin-top:16px; padding:12px 14px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px;">
            <div style="font-size:.75rem; color:#166534; line-height:1.55;">
                <strong>Dica do Bruce:</strong> confere se sua conta está conectada antes (Redes Sociais → Contas). Se aparecer "reconectar", faz o refresh do token primeiro.
            </div>
        </div>
    </div>

    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:30px;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px;">
            <div style="width:44px; height:44px; background:#4f46e5; border-radius:13px; display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-calendar-check" style="color:#fff; font-size:1rem;"></i>
            </div>
            <div>
                <div style="font-size:.95rem; font-weight:800; color:#0f172a;">Agendar pra depois</div>
                <div style="font-size:.72rem; color:#94a3b8;">Programe a semana inteira em 10 min</div>
            </div>
        </div>
        <ul style="margin:0; padding-left:18px; color:#475569; font-size:.82rem; line-height:1.75;">
            <li>Marque a data e a hora exata — timezone Brasília (UTC-3) já configurada.</li>
            <li>O post fica com status <strong>"Agendado"</strong> no painel — você pode editar até o horário chegar.</li>
            <li>A cada 5 minutos o Bruce checa a fila e publica o que tá pronto.</li>
            <li>Bom pra: rotina semanal, campanhas com sequência, posts fora de expediente.</li>
        </ul>
        <div style="margin-top:16px; padding:12px 14px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px;">
            <div style="font-size:.75rem; color:#1e40af; line-height:1.55;">
                <strong>Dica do Bruce:</strong> use a <a href="{{ route('social.posts.calendar') }}" style="color:#1e40af; font-weight:700; text-decoration:underline;">visão calendário</a> pra enxergar a semana toda e não repetir horário.
            </div>
        </div>
    </div>
</div>

{{-- ── FORMATOS SUPORTADOS ── --}}
<div style="background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:30px; margin-bottom:24px;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px;">
        <div style="width:44px; height:44px; background:#d97706; border-radius:13px; display:flex; align-items:center; justify-content:center;">
            <i class="fas fa-shapes" style="color:#fff; font-size:1rem;"></i>
        </div>
        <div>
            <div style="font-size:.95rem; font-weight:800; color:#0f172a;">Formatos que o Bruce publica</div>
            <div style="font-size:.72rem; color:#94a3b8;">O que cada tipo aceita e onde vai</div>
        </div>
    </div>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px;">
        @foreach([
            ['fa-square', 'Feed 1 foto', 'JPG/PNG, até 10MB. Vai pro feed do FB e/ou Instagram.', '#0891b2'],
            ['fa-layer-group', 'Carrossel', '2 a 10 mídias no mesmo post (Instagram). Ideal pra storytelling.', '#7c3aed'],
            ['fa-mobile-screen', 'Story 9:16', 'Vertical 1080×1920, dura 24h. Só Instagram por enquanto.', '#db2777'],
            ['fa-video', 'Vídeo/Reel', 'MP4 até 60s. Publica como Reel no Instagram, vídeo no Facebook.', '#16a34a'],
            ['fa-align-left', 'Só texto', 'Post sem mídia — só Facebook aceita, Instagram exige imagem.', '#64748b'],
        ] as [$ico, $title, $desc, $cor])
        <div style="padding:16px; background:#fafafa; border:1px solid #f1f5f9; border-radius:12px;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                <i class="fas {{ $ico }}" style="color:{{ $cor }}; font-size:1rem;"></i>
                <div style="font-size:.85rem; font-weight:800; color:#0f172a;">{{ $title }}</div>
            </div>
            <div style="font-size:.72rem; color:#64748b; line-height:1.55;">{{ $desc }}</div>
        </div>
        @endforeach
    </div>
</div>

{{-- ── PERGUNTAS FREQUENTES ── --}}
<div style="background:#0A0A0B; border:1px solid rgba(255,122,26,.15); border-radius:20px; padding:32px; margin-bottom:24px;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
        <div style="width:44px; height:44px; background:#FF7A1A; border-radius:13px; display:flex; align-items:center; justify-content:center;">
            <i class="fas fa-circle-question" style="color:#fff; font-size:1rem;"></i>
        </div>
        <div>
            <div style="font-size:1rem; font-weight:800; color:#fff;">Bruce responde as dúvidas mais comuns</div>
            <div style="font-size:.72rem; color:rgba(255,255,255,.5);">Perguntas reais de quem tá começando</div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
        @foreach([
            ['E se eu esquecer de conectar a página?', 'O Bruce avisa quando você tentar publicar sem conta ativa — te leva direto pra tela de conexão. Sem drama.'],
            ['Posso editar um post depois de agendado?', 'Sim! Enquanto o horário não chegar, o post tá editável na lista. Depois de publicado, alteração precisa ser feita direto no Facebook/Instagram.'],
            ['O post falhou. E agora?', 'O Bruce guarda o motivo do erro (foto grande, token expirado, etc). No painel aparece "Falhou" com o detalhe — corrige e re-agenda em 1 clique.'],
            ['Onde vejo o que já foi publicado?', 'Painel de posts filtra por status: Agendado, Publicado, Falhou. Também dá pra abrir o post real no Facebook/Instagram direto de lá.'],
            ['Posso agendar o mesmo post pras duas redes?', 'Sim, na hora de criar escolhe "Facebook + Instagram". O Bruce publica nas duas simultaneamente com a mesma legenda e mídia.'],
            ['Quantos posts dá pra agendar?', 'Sem limite. Agende a semana, o mês, o trimestre. O agendador roda a cada 5 min checando o que tá pronto.'],
        ] as [$q, $a])
        <div style="padding:16px; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); border-radius:12px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                <i class="fas fa-comment-dots" style="color:#FF7A1A; font-size:.8rem;"></i>
                <div style="font-size:.85rem; font-weight:800; color:#fff;">{{ $q }}</div>
            </div>
            <div style="font-size:.75rem; color:rgba(255,255,255,.6); line-height:1.6;">{{ $a }}</div>
        </div>
        @endforeach
    </div>
</div>

{{-- ── CTA FINAL ── --}}
<div style="background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:32px 36px; display:flex; align-items:center; justify-content:space-between; gap:24px; flex-wrap:wrap;">
    <div>
        <div style="font-size:.95rem; font-weight:800; color:#0f172a; margin-bottom:6px;">Bora publicar o primeiro post?</div>
        <p style="font-size:.82rem; color:#64748b; margin:0;">Se preferir que o Bruce escreva a legenda e gere a imagem, use o Social AI Hub.</p>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a href="{{ route('social-ai.index') }}" style="display:inline-flex; align-items:center; gap:8px; background:#fff; color:#FF7A1A; font-size:.82rem; font-weight:800; padding:12px 20px; border-radius:12px; text-decoration:none; border:2px solid #FF7A1A;">
            <i class="fas fa-wand-magic-sparkles"></i> Gerar com IA
        </a>
        <a href="{{ route('social.posts.create') }}" style="display:inline-flex; align-items:center; gap:8px; background:#FF7A1A; color:#fff; font-size:.82rem; font-weight:800; padding:12px 26px; border-radius:12px; text-decoration:none;">
            <i class="fas fa-paper-plane"></i> Criar novo post
        </a>
    </div>
</div>

@push('styles')
<style>
@media (max-width: 900px) {
    div[style*="grid-template-columns:1fr 1fr"],
    div[style*="grid-template-columns:repeat(4, 1fr)"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
@endpush

@endsection
