{{--
    Parcial de seção "Planos" reutilizavel nas 3 landings publicas (2026-08-07).
    Cada landing filtra $plans pelo target_audience correto no controller.

    Vars esperadas:
      $plans          — Collection de SubscriptionPlan (pode estar vazia)
      $accentColor    — hex ("#4f46e5") pra card destacado
      $bgColor        — hex ("#f8fafc") do fundo da secao
      $wppMessage     — texto encoded pra abrir no WhatsApp quando vazio
      $fallbackTitle  — headline quando $plans vazio

    Escalonamento visual: card do meio (index 1) recebe destaque "Mais popular".
    Se so ha 1 plano, ele nao ganha destaque (nao tem contra o que comparar).
--}}
@php
    $accentColor   = $accentColor   ?? '#4f46e5';
    $bgColor       = $bgColor       ?? '#f8fafc';
    $wppMessage    = $wppMessage    ?? 'Ol%C3%A1%21%20Quero%20conhecer%20os%20planos%20do%20Vivensi.';
    $fallbackTitle = $fallbackTitle ?? 'Planos personalizados por porte';
    $plansCount    = $plans instanceof \Illuminate\Support\Collection ? $plans->count() : count($plans ?? []);
    $hotIndex      = $plansCount >= 3 ? 1 : ($plansCount === 2 ? 1 : null);
@endphp

<section id="planos" style="padding: 90px 0; background: {{ $bgColor }};">
    <div class="container">
        <div style="text-align:center; max-width:640px; margin: 0 auto 44px;">
            <span style="display:inline-block; font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:1.4px; color:{{ $accentColor }}; background:{{ $accentColor }}18; border:1px solid {{ $accentColor }}44; padding:6px 14px; border-radius:100px; margin-bottom:14px;">Planos</span>
            <h2 style="font-size: clamp(1.6rem, 3vw, 2.2rem); font-weight:800; color:#0f172a; letter-spacing:-.8px; margin:0 0 10px;">Escolha o plano da sua operação</h2>
            <p style="font-size:.95rem; color:#64748b; line-height:1.6; margin:0;">Sem addon, sem cobrança por módulo, sem tarifa escondida. Cancele quando quiser.</p>
        </div>

        @if($plansCount > 0)
            <div style="display:grid; grid-template-columns: repeat({{ min($plansCount, 3) }}, minmax(0,1fr)); gap:18px; max-width:{{ min($plansCount, 3) * 340 }}px; margin: 0 auto;"
                 class="lps-grid">
                @foreach($plans as $i => $plan)
                    @php
                        $isHot = ($hotIndex !== null && $i === $hotIndex);
                        $price = number_format((float) $plan->price, 2, ',', '.');
                        $interval = ($plan->interval ?? 'monthly') === 'yearly' ? 'ano' : 'mês';
                    @endphp
                    <div style="position:relative; background:#fff; border:2px solid {{ $isHot ? $accentColor : '#e2e8f0' }}; border-radius:16px; padding:28px 24px; display:flex; flex-direction:column; box-shadow: {{ $isHot ? '0 10px 30px ' . $accentColor . '22' : '0 2px 8px rgba(15,23,42,.04)' }};">
                        @if($isHot)
                            <div style="position:absolute; top:-12px; left:50%; transform:translateX(-50%); background:{{ $accentColor }}; color:#fff; font-size:.65rem; font-weight:800; text-transform:uppercase; letter-spacing:1.2px; padding:5px 14px; border-radius:100px;">Mais popular</div>
                        @endif

                        <div style="font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.1em; color:#94a3b8; margin-bottom:12px;">{{ $plan->name }}</div>

                        <div style="display:flex; align-items:baseline; gap:4px; margin-bottom:6px;">
                            <span style="font-size:1.1rem; color:#64748b; font-weight:600;">R$</span>
                            <span style="font-size:2.6rem; font-weight:900; color:#0f172a; letter-spacing:-1.5px; line-height:1;">{{ $price }}</span>
                            <span style="font-size:.9rem; color:#64748b; font-weight:500; margin-left:4px;">/{{ $interval }}</span>
                        </div>
                        <div style="font-size:.75rem; color:#94a3b8; margin-bottom:22px;">
                            @if($plan->price_yearly && (float) $plan->price_yearly > 0)
                                ou R$ {{ number_format((float) $plan->price_yearly, 2, ',', '.') }}/ano
                            @else
                                Cobrado {{ $interval === 'ano' ? 'anualmente' : 'mensalmente' }}
                            @endif
                        </div>

                        <ul style="list-style:none; padding:0; margin:0 0 22px; flex:1;">
                            @foreach(($plan->features ?? []) as $f)
                                <li style="display:flex; align-items:flex-start; gap:10px; padding:6px 0; color:#334155; font-size:.86rem; line-height:1.5;">
                                    <i class="fas fa-circle-check" style="color:#22c55e; margin-top:4px; font-size:.85rem; flex-shrink:0;"></i>
                                    <span>{{ $f }}</span>
                                </li>
                            @endforeach
                            @if(empty($plan->features))
                                <li style="color:#94a3b8; font-size:.85rem; font-style:italic;">Recursos serão listados em breve.</li>
                            @endif
                        </ul>

                        <a href="{{ route('register', ['plan_id' => $plan->id]) }}"
                           style="display:block; text-align:center; padding:12px 20px; border-radius:10px; font-weight:800; font-size:.88rem; text-decoration:none; transition:opacity .15s;
                                  {{ $isHot ? 'background:' . $accentColor . '; color:#fff;' : 'background:#f1f5f9; color:#0f172a; border:1px solid #e2e8f0;' }}">
                            {{ $isHot ? '🚀 Assinar agora' : 'Escolher plano' }}
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            {{-- Fallback quando nao ha plano cadastrado no audience --}}
            <div style="max-width:560px; margin:0 auto; text-align:center; padding:40px 28px; background:#fff; border:1px solid #e2e8f0; border-radius:16px;">
                <i class="fab fa-whatsapp" style="font-size:2.4rem; color:#22c55e; margin-bottom:14px;"></i>
                <h3 style="font-size:1.15rem; font-weight:800; color:#0f172a; margin:0 0 8px;">{{ $fallbackTitle }}</h3>
                <p style="color:#64748b; font-size:.9rem; line-height:1.6; margin:0 0 22px;">
                    Fale com a gente pra montar o plano do tamanho da sua operação.
                </p>
                <a href="https://wa.me/551697618695?text={{ $wppMessage }}" target="_blank" rel="noopener"
                   style="display:inline-flex; align-items:center; gap:8px; background:#22c55e; color:#fff; padding:12px 22px; border-radius:10px; font-weight:700; font-size:.9rem; text-decoration:none;">
                    <i class="fab fa-whatsapp"></i> Falar no WhatsApp
                </a>
            </div>
        @endif
    </div>
</section>

<style>
    @media (max-width: 900px) { .lps-grid { grid-template-columns: 1fr !important; max-width: 420px !important; } }
</style>
