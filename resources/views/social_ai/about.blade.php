@extends('layouts.app')

@section('content')

{{-- ── HEADER ── --}}
<div style="display:flex; align-items:center; gap:14px; margin-bottom:28px; flex-wrap:wrap;">
    <a href="{{ route('social-ai.index') }}" style="display:inline-flex; align-items:center; gap:6px; color:#64748b; font-size:.8rem; font-weight:700; text-decoration:none; padding:7px 14px; border:1px solid #e2e8f0; border-radius:10px; background:#fff;">
        <i class="fas fa-arrow-left" style="font-size:.7rem;"></i> Voltar para Social AI
    </a>
</div>

{{-- ── HERO ── --}}
<div style="background:#0A0A0B; border:1px solid rgba(255,122,26,.2); border-radius:28px; padding:52px 56px 48px; margin-bottom:28px;">
    <div style="display:flex; align-items:center; gap:48px; flex-wrap:wrap;">
        <div style="flex:1; min-width:280px;">
            <div style="display:inline-flex; align-items:center; gap:8px; background:rgba(255,122,26,.12); border:1px solid rgba(255,122,26,.3); border-radius:20px; padding:5px 14px; margin-bottom:18px;">
                <i class="fas fa-wand-magic-sparkles" style="color:#FF7A1A; font-size:.75rem;"></i>
                <span style="color:#FF7A1A; font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:1.2px;">Social AI Hub</span>
            </div>
            <h1 style="color:#fff; font-size:2rem; font-weight:900; margin:0 0 14px; letter-spacing:-.5px; line-height:1.15;">Como usar o<br><span style="color:#FF7A1A;">Social AI Hub</span> do Bruce?</h1>
            <p style="color:rgba(255,255,255,.6); font-size:.92rem; line-height:1.65; margin:0; max-width:560px;">
                O Bruce IA escreve legendas persuasivas e gera imagens em segundos, tudo pensado para ONGs e negócios que precisam manter presença em redes sociais sem gastar horas do dia. Aqui você entende cada opção do módulo — e como tirar o máximo de cada geração.
            </p>
        </div>
        <div style="flex-shrink:0; display:flex; flex-direction:column; align-items:center; gap:14px;">
            <img src="{{ asset('img/bruce/bruceia-icone-fundo-escuro.svg') }}" alt="Bruce IA"
                 style="width:120px; height:120px; border-radius:28px; background:#0f0f1e; border:1px solid rgba(255,255,255,.08);">
            <span style="color:rgba(255,255,255,.4); font-size:.7rem; font-weight:600;">Explicado por Bruce IA</span>
        </div>
    </div>
</div>

{{-- ── FLUXO EM 4 PASSOS ── --}}
<div style="background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:32px; margin-bottom:24px;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:24px;">
        <div style="width:44px; height:44px; background:#FF7A1A; border-radius:13px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i class="fas fa-route" style="color:#fff; font-size:1rem;"></i>
        </div>
        <div>
            <div style="font-size:1rem; font-weight:800; color:#0f172a;">Fluxo em 4 passos</div>
            <div style="font-size:.75rem; color:#94a3b8;">Do tema à publicação</div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:16px;">
        @foreach([
            ['1','fa-lightbulb','Escreva o tema','Ex: "Impacto do nosso projeto na comunidade" ou "Nova campanha de doações". Quanto mais específico, melhor.'],
            ['2','fa-sliders','Ajuste opções','Escolha formato (Feed / Story), estilo visual (foto, cartoon, aquarela...) e, se quiser, suba uma imagem de referência.'],
            ['3','fa-wand-magic-sparkles','Gere com o Bruce','Ele cria 3 variações de legenda + uma imagem. Você escolhe qual legenda usar ou regenera se não gostar.'],
            ['4','fa-paper-plane','Publique','Envie pro Broadcast WhatsApp, agende no calendário Instagram/Facebook ou copie a legenda.'],
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

    {{-- ── IMAGEM DE REFERÊNCIA ── --}}
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:30px;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px;">
            <div style="width:44px; height:44px; background:#4f46e5; border-radius:13px; display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-image" style="color:#fff; font-size:1rem;"></i>
            </div>
            <div>
                <div style="font-size:.95rem; font-weight:800; color:#0f172a;">Imagem de Referência</div>
                <div style="font-size:.72rem; color:#94a3b8;">Duas formas de usar</div>
            </div>
        </div>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <div style="padding:14px; background:#f5f3ff; border:1px solid #ddd6fe; border-radius:12px;">
                <div style="font-size:.82rem; font-weight:800; color:#5b21b6; margin-bottom:4px;">🎨 Como referência visual</div>
                <div style="font-size:.75rem; color:#4c1d95; line-height:1.55;">Você sobe uma foto de inspiração. No campo de instruções, descreve o que quer copiar dela (cores, mood, composição). O Bruce gera uma imagem NOVA inspirada.</div>
            </div>
            <div style="padding:14px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px;">
                <div style="font-size:.82rem; font-weight:800; color:#166534; margin-bottom:4px;">📸 Como imagem final</div>
                <div style="font-size:.75rem; color:#14532d; line-height:1.55;">Sua foto vira a imagem do post — sem gerar nada com IA. O Bruce só cria a legenda. <strong>Economiza 1 crédito da cota.</strong></div>
            </div>
        </div>
    </div>

    {{-- ── VARIAÇÕES DE LEGENDA ── --}}
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:30px;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px;">
            <div style="width:44px; height:44px; background:#0891b2; border-radius:13px; display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-list-ol" style="color:#fff; font-size:1rem;"></i>
            </div>
            <div>
                <div style="font-size:.95rem; font-weight:800; color:#0f172a;">3 Variações de Legenda</div>
                <div style="font-size:.72rem; color:#94a3b8;">Cada geração vem com 3 opções</div>
            </div>
        </div>
        <p style="font-size:.82rem; color:#475569; line-height:1.6; margin-bottom:14px;">
            O Bruce escreve 3 legendas para o mesmo post, cada uma com abordagem diferente. Clique nas pills "Legenda 1 / 2 / 3" no card para alternar. A escolhida é a que vai pro Broadcast/Instagram.
        </p>
        <div style="display:flex; gap:8px; margin-bottom:12px;">
            <span style="background:#4F46E5; color:#fff; padding:4px 10px; border-radius:20px; font-size:.7rem; font-weight:800;">Legenda 1</span>
            <span style="background:#f5f3ff; color:#5b21b6; padding:4px 10px; border-radius:20px; font-size:.7rem; font-weight:800; border:1px solid #ddd6fe;">Legenda 2</span>
            <span style="background:#f5f3ff; color:#5b21b6; padding:4px 10px; border-radius:20px; font-size:.7rem; font-weight:800; border:1px solid #ddd6fe;">Legenda 3</span>
        </div>
        <div style="font-size:.72rem; color:#64748b; background:#f8fafc; padding:10px 12px; border-radius:8px; border-left:3px solid #4F46E5;">
            <strong>Dica:</strong> abordagens típicas são "emocional" (foco em história), "direta" (call-to-action) e "informativa" (dados e fatos).
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px;">

    {{-- ── FORMATO + ESTILO ── --}}
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:30px;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px;">
            <div style="width:44px; height:44px; background:#d97706; border-radius:13px; display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-palette" style="color:#fff; font-size:1rem;"></i>
            </div>
            <div>
                <div style="font-size:.95rem; font-weight:800; color:#0f172a;">Formato & Estilo Visual</div>
                <div style="font-size:.72rem; color:#94a3b8;">Controla a saída da imagem</div>
            </div>
        </div>
        <div style="display:flex; flex-direction:column; gap:8px;">
            @foreach([
                ['Feed 1:1','Quadrado 1024×1024 — Instagram feed, Facebook, LinkedIn','fa-square'],
                ['Story 9:16','Vertical 768×1344 — Instagram/Facebook stories, reels','fa-mobile-screen'],
            ] as [$title, $desc, $ico])
            <div style="display:flex; align-items:center; gap:10px; padding:10px 12px; background:#fafafa; border:1px solid #f1f5f9; border-radius:10px;">
                <i class="fas {{ $ico }}" style="color:#d97706; font-size:.85rem; width:16px; text-align:center;"></i>
                <div>
                    <div style="font-size:.8rem; font-weight:700; color:#0f172a;">{{ $title }}</div>
                    <div style="font-size:.7rem; color:#64748b;">{{ $desc }}</div>
                </div>
            </div>
            @endforeach
        </div>
        <div style="margin-top:14px; padding-top:14px; border-top:1px dashed #e2e8f0;">
            <div style="font-size:.72rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.08em; margin-bottom:8px;">Estilos disponíveis</div>
            <div style="display:flex; flex-wrap:wrap; gap:6px;">
                @foreach(['📷 Foto','🎨 Ilustração','🎭 Cartoon','💼 Corporativo','◻️ Minimalista','🖌️ Aquarela'] as $tag)
                    <span style="background:#fef3c7; color:#78350f; font-size:.68rem; font-weight:700; padding:3px 8px; border-radius:6px;">{{ $tag }}</span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── REGENERAR / COTA ── --}}
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:30px;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px;">
            <div style="width:44px; height:44px; background:#16a34a; border-radius:13px; display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-rotate-right" style="color:#fff; font-size:1rem;"></i>
            </div>
            <div>
                <div style="font-size:.95rem; font-weight:800; color:#0f172a;">Regenerar & Cota</div>
                <div style="font-size:.72rem; color:#94a3b8;">O que consome crédito</div>
            </div>
        </div>
        <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:14px;">
            <div style="display:flex; gap:10px; align-items:flex-start;">
                <i class="fas fa-check-circle" style="color:#16a34a; margin-top:3px;"></i>
                <div>
                    <div style="font-size:.82rem; font-weight:700; color:#0f172a;">Nova legenda — grátis</div>
                    <div style="font-size:.72rem; color:#64748b;">Regenerar só o texto não consome cota.</div>
                </div>
            </div>
            <div style="display:flex; gap:10px; align-items:flex-start;">
                <i class="fas fa-exclamation-triangle" style="color:#d97706; margin-top:3px;"></i>
                <div>
                    <div style="font-size:.82rem; font-weight:700; color:#0f172a;">Nova imagem — 1 crédito</div>
                    <div style="font-size:.72rem; color:#64748b;">Regerar imagem consome 1 da sua cota mensal (o Bruce pede confirmação antes).</div>
                </div>
            </div>
            <div style="display:flex; gap:10px; align-items:flex-start;">
                <i class="fas fa-piggy-bank" style="color:#16a34a; margin-top:3px;"></i>
                <div>
                    <div style="font-size:.82rem; font-weight:700; color:#0f172a;">Sua foto como final — grátis</div>
                    <div style="font-size:.72rem; color:#64748b;">Se você marca "Usar como imagem final do post", o Bruce não gera imagem — cota preservada.</div>
                </div>
            </div>
        </div>
        <div style="padding:12px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px;">
            <div style="font-size:.75rem; color:#166534; line-height:1.55;">
                <strong>Cota mensal:</strong> 60 imagens geradas por IA, por usuário. Renova todo dia 1º do mês.
            </div>
        </div>
    </div>
</div>

{{-- ── DICAS DE PROMPT ── --}}
<div style="background:#0A0A0B; border:1px solid rgba(255,122,26,.15); border-radius:20px; padding:32px; margin-bottom:24px;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
        <div style="width:44px; height:44px; background:#FF7A1A; border-radius:13px; display:flex; align-items:center; justify-content:center;">
            <i class="fas fa-lightbulb" style="color:#fff; font-size:1rem;"></i>
        </div>
        <div>
            <div style="font-size:1rem; font-weight:800; color:#fff;">6 Dicas do Bruce para Prompts Melhores</div>
            <div style="font-size:.72rem; color:rgba(255,255,255,.5);">Quanto mais contexto, melhor o resultado</div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
        @foreach([
            ['fa-bullseye','Seja específico com o tema','Ruim: "Post sobre nossa ONG". Bom: "Resultado da campanha de agasalhos: 800 famílias atendidas em 3 bairros da zona leste."'],
            ['fa-comments','Descreva o tom no campo instruções','Ex: "Tom emocional, primeira pessoa, foco em impacto humano" — o Bruce entrega uma legenda muito mais poderosa.'],
            ['fa-hashtag','Peça hashtags obrigatórias','Nas instruções: "Incluir hashtags #TerceiroSetor #CausasSociais #NomeDaMinhaOng" — o Bruce coloca no fim da legenda.'],
            ['fa-user-group','Identifique o público-alvo','"Público: potenciais doadores empresariais" gera legenda diferente de "público: voluntários jovens".'],
            ['fa-image','Combine com estilo visual','Tema "criança sorrindo" + estilo "aquarela" = visual delicado. Tema + estilo "corporativo" = visual profissional.'],
            ['fa-arrows-rotate','Use variações a favor','Gerou e não gostou? Clique "Nova legenda" pra novo trio. Se a imagem não bateu com o tema, "Nova imagem" (mas confere a cota antes).'],
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

{{-- ── CTA FINAL ── --}}
<div style="background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:32px 36px; display:flex; align-items:center; justify-content:space-between; gap:24px; flex-wrap:wrap;">
    <div>
        <div style="font-size:.95rem; font-weight:800; color:#0f172a; margin-bottom:6px;">Pronto para gerar seu primeiro post?</div>
        <p style="font-size:.82rem; color:#64748b; margin:0;">Volte ao Social AI Hub e deixe o Bruce cuidar do texto e da imagem.</p>
    </div>
    <a href="{{ route('social-ai.index') }}" style="display:inline-flex; align-items:center; gap:8px; background:#FF7A1A; color:#fff; font-size:.82rem; font-weight:800; padding:12px 26px; border-radius:12px; text-decoration:none;">
        <i class="fas fa-wand-magic-sparkles"></i> Ir para o Social AI Hub
    </a>
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
