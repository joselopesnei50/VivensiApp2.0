@extends('layouts.app')
@section('title', $campaign->name)

@section('content')
<div class="header-page" style="margin-bottom:32px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:16px;">
        <div>
            <a href="{{ route('admin.email_campaigns.index') }}"
               style="display:inline-flex; align-items:center; gap:6px; color:#6366f1; font-weight:700; font-size:0.85rem; text-decoration:none; margin-bottom:14px;">
                <i class="fas fa-arrow-left"></i> Campanhas
            </a>
            <h2 style="margin:0; color:#1e293b; font-weight:900; font-size:2rem; letter-spacing:-1px;">{{ $campaign->name }}</h2>
            <p style="color:#64748b; margin:6px 0 0; font-size:0.9rem;">{{ $campaign->subject }}</p>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            @if(in_array($campaign->status, ['draft', 'error']))
                <form id="formDispararCampanha" action="{{ route('admin.email_campaigns.send', $campaign) }}" method="POST">
                    @csrf
                    <button type="button"
                            onclick="abrirModalDisparar('{{ addslashes($campaign->name) }}', '{{ $campaign->audienceLabel() }}', {{ $campaign->recipient_count ?: 'null' }})"
                            style="padding:14px 24px; border:none; border-radius:14px; background:#6366f1; color:white; font-weight:800; font-size:0.9rem; cursor:pointer;">
                        <i class="fas fa-paper-plane me-2"></i>{{ $campaign->status === 'error' ? 'Tentar novamente' : 'Disparar Agora' }}
                    </button>
                </form>
            @endif
            @if($campaign->status === 'sent' && $campaign->brevo_campaign_id)
                <form action="{{ route('admin.email_campaigns.stats', $campaign) }}" method="POST">
                    @csrf
                    <button type="submit"
                            style="padding:14px 24px; border:none; border-radius:14px; background:#eff6ff; color:#3b82f6; font-weight:800; font-size:0.9rem; cursor:pointer;">
                        <i class="fas fa-arrow-rotate-right me-2"></i>Atualizar Métricas
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

@if(session('success'))
    <div style="background:#ecfdf5; color:#065f46; padding:16px 20px; border-radius:12px; margin-bottom:24px; border:1px solid #a7f3d0; font-weight:700; display:flex; align-items:center; gap:10px;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div style="background:#fef2f2; color:#991b1b; padding:16px 20px; border-radius:12px; margin-bottom:24px; border:1px solid #fca5a5; font-weight:700; display:flex; align-items:center; gap:10px;">
        <i class="fas fa-circle-exclamation"></i> {{ session('error') }}
    </div>
@endif

{{-- Métricas --}}
@if($campaign->status === 'sent')
@php
    // Semaforo: benchmarks de email marketing BR
    $openRate   = $campaign->openRate();   // %
    $clickRate  = $campaign->clickRate();  // %
    $bounceRate = $campaign->bounceRate(); // %
    // Cores por thresholds
    $openColor   = $openRate === null ? '#94a3b8' : ($openRate >= 22 ? '#059669' : ($openRate >= 15 ? '#d97706' : '#ef4444'));
    $clickColor  = $clickRate === null ? '#94a3b8' : ($clickRate >= 3 ? '#059669' : ($clickRate >= 1.5 ? '#d97706' : '#ef4444'));
    $bounceColor = $bounceRate === null ? '#94a3b8' : ($bounceRate <= 2 ? '#059669' : ($bounceRate <= 5 ? '#d97706' : '#ef4444'));
    // Benchmarks BR (media)
    $benchOpen   = 22;
    $benchClick  = 3;
    $benchBounce = 3;
@endphp

{{-- Cards com barra + benchmark inline --}}
<div class="row g-4 mb-3">
    {{-- Destinatários --}}
    <div class="col-md-4">
        <div class="vivensi-card" style="padding:22px; border-radius:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-size:0.72rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">Destinatários</div>
                    <div style="font-size:1.8rem; font-weight:900; color:#0f172a; margin-top:4px;">{{ number_format($campaign->recipient_count) }}</div>
                </div>
                <div style="width:44px; height:44px; background:#eff6ff; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#6366f1; font-size:1rem;">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            @if($campaign->stat_delivered)
                <div style="font-size:0.78rem; color:#64748b; margin-top:8px;">
                    <span style="color:#059669; font-weight:700;">{{ number_format($campaign->stat_delivered) }}</span> entregues
                </div>
            @endif
        </div>
    </div>

    {{-- Aberturas --}}
    <div class="col-md-4">
        <div class="vivensi-card" style="padding:22px; border-radius:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-size:0.72rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">Aberturas</div>
                    <div style="font-size:1.8rem; font-weight:900; color:{{ $openColor }}; margin-top:4px;">{{ $openRate !== null ? $openRate.'%' : '—' }}</div>
                </div>
                <div style="width:44px; height:44px; background:#fffbeb; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#d97706; font-size:1rem;">
                    <i class="fas fa-envelope-open"></i>
                </div>
            </div>
            @if($openRate !== null)
                <div style="height:6px; background:#f1f5f9; border-radius:99px; margin-top:10px; overflow:hidden;">
                    <div style="height:100%; width:{{ min($openRate, 100) }}%; background:{{ $openColor }}; border-radius:99px; transition:width .3s;"></div>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:0.72rem; color:#64748b; margin-top:6px;">
                    <span>{{ number_format($campaign->stat_opens ?? 0) }} únicos</span>
                    <span>Média BR: {{ $benchOpen }}%</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Cliques --}}
    <div class="col-md-4">
        <div class="vivensi-card" style="padding:22px; border-radius:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-size:0.72rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">Cliques</div>
                    <div style="font-size:1.8rem; font-weight:900; color:{{ $clickColor }}; margin-top:4px;">{{ $clickRate !== null ? $clickRate.'%' : '—' }}</div>
                </div>
                <div style="width:44px; height:44px; background:#eff6ff; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#3b82f6; font-size:1rem;">
                    <i class="fas fa-arrow-pointer"></i>
                </div>
            </div>
            @if($clickRate !== null)
                <div style="height:6px; background:#f1f5f9; border-radius:99px; margin-top:10px; overflow:hidden;">
                    <div style="height:100%; width:{{ min($clickRate * 5, 100) }}%; background:{{ $clickColor }}; border-radius:99px; transition:width .3s;"></div>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:0.72rem; color:#64748b; margin-top:6px;">
                    <span>{{ number_format($campaign->stat_clicks ?? 0) }} únicos</span>
                    <span>Média BR: {{ $benchClick }}%</span>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Bounces + Descadastros --}}
<div class="row g-4 mb-3">
    <div class="col-md-6">
        <div class="vivensi-card" style="padding:22px; border-radius:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-size:0.72rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">Bounces</div>
                    <div style="font-size:1.5rem; font-weight:900; color:{{ $bounceColor }}; margin-top:4px;">
                        {{ $bounceRate !== null ? $bounceRate.'%' : ($campaign->stat_bounces !== null ? number_format($campaign->stat_bounces) : '—') }}
                        @if($bounceRate !== null && $bounceRate > 5)
                            <span style="display:inline-block; background:#fef2f2; color:#dc2626; font-size:0.65rem; padding:2px 8px; border-radius:6px; margin-left:6px; letter-spacing:0.3px; vertical-align:middle;">ATENÇÃO</span>
                        @endif
                    </div>
                </div>
                <div style="width:44px; height:44px; background:#fef2f2; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#ef4444; font-size:1rem;">
                    <i class="fas fa-circle-xmark"></i>
                </div>
            </div>
            @if($bounceRate !== null)
                <div style="font-size:0.72rem; color:#64748b; margin-top:6px;">
                    Ideal: &lt; {{ $benchBounce }}%. {{ $bounceRate > 5 ? 'Limpe a lista antes da próxima campanha.' : ($bounceRate > 2 ? 'Aceitável, mas monitore.' : 'Excelente.') }}
                </div>
            @endif
        </div>
    </div>
    <div class="col-md-6">
        <div class="vivensi-card" style="padding:22px; border-radius:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-size:0.72rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">Descadastros</div>
                    <div style="font-size:1.5rem; font-weight:900; color:#0f172a; margin-top:4px;">
                        {{ $campaign->stat_unsubscribes !== null ? number_format($campaign->stat_unsubscribes) : '—' }}
                    </div>
                </div>
                <div style="width:44px; height:44px; background:#f1f5f9; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#64748b; font-size:1rem;">
                    <i class="fas fa-user-minus"></i>
                </div>
            </div>
            @if($campaign->stat_unsubscribes !== null && $campaign->recipient_count)
                @php $unsubRate = round(($campaign->stat_unsubscribes / max($campaign->recipient_count, 1)) * 100, 2); @endphp
                <div style="font-size:0.72rem; color:#64748b; margin-top:6px;">
                    Taxa: {{ $unsubRate }}%. Ideal: &lt; 0.5%.
                </div>
            @endif
        </div>
    </div>
</div>

@if($campaign->stats_fetched_at)
    <p style="color:#94a3b8; font-size:0.75rem; text-align:right; margin:-8px 0 24px;">
        Métricas atualizadas em {{ $campaign->stats_fetched_at->format('d/m/Y H:i') }}
    </p>
@endif

{{-- Bruce IA — insight pos-envio (F2). Identidade oficial Bruce: fundo #0A0A0B, accent #FF7A1A. --}}
@if($campaign->status === 'sent')
<div style="background:#0A0A0B; border-radius:18px; overflow:hidden; margin-bottom:24px; box-shadow:0 8px 30px rgba(10,10,11,.15);">
    <div style="padding:22px 26px; display:flex; justify-content:space-between; align-items:center; gap:18px; flex-wrap:wrap;">
        <div style="display:flex; align-items:center; gap:16px; flex:1; min-width:280px;">
            <img src="{{ asset('img/bruce/bruceia-icone-fundo-escuro.svg') }}" alt="Bruce IA" width="52" height="52" style="flex-shrink:0;">
            <div>
                <div style="color:#F4F4F5; font-weight:800; font-size:1.05rem; letter-spacing:-.3px;">Bruce analisa esta campanha</div>
                <div style="color:#9a9a9e; font-size:0.85rem; margin-top:3px; line-height:1.4;">
                    Interpretação das métricas + próxima ação recomendada
                </div>
            </div>
        </div>
        <button type="button" id="btnBruceInsight"
                data-url="{{ route('admin.email_campaigns.ai.insight', $campaign) }}"
                style="padding:12px 22px; background:#FF7A1A; color:#0A0A0B; border:0; border-radius:12px; font-weight:800; font-size:.9rem; cursor:pointer; letter-spacing:.2px; box-shadow:0 4px 12px rgba(255,122,26,.35); transition:transform .15s;">
            Gerar insight
        </button>
    </div>
    <div id="bruceInsightResult" style="display:none; padding:0 26px 26px;"></div>
</div>
@endif
@endif

<div class="row g-4">
    <div class="col-lg-8">
        {{-- Preview --}}
        <div class="vivensi-card" style="padding:0; border-radius:20px; overflow:hidden;">
            <div style="padding:18px 24px; border-bottom:1px solid #f1f5f9; background:#f8fafc; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-eye" style="color:#6366f1;"></i>
                <strong style="font-size:0.9rem; color:#1e293b;">Preview do E-mail</strong>
            </div>
            {{-- Preview sandbox: bloqueia JS inline do HTML da campanha no painel (defesa em profundidade) --}}
            <iframe srcdoc="{{ $campaign->html_content }}"
                    sandbox="allow-popups allow-popups-to-escape-sandbox"
                    referrerpolicy="no-referrer"
                    style="width:100%; height:600px; border:none;"></iframe>
        </div>
    </div>
    <div class="col-lg-4">
        {{-- Detalhes --}}
        <div class="vivensi-card" style="padding:28px; border-radius:20px;">
            <h4 style="margin:0 0 20px; font-weight:900; color:#1e293b; font-size:1rem;">Detalhes</h4>
            @php
                $details = [
                    ['label'=>'Status', 'value'=> ucfirst($campaign->status)],
                    ['label'=>'Remetente', 'value'=> $campaign->sender_email ?: 'Padrão do sistema'],
                    ['label'=>'Reply-To', 'value'=> $campaign->reply_to_email ?: '—'],
                    ['label'=>'Público', 'value'=> $campaign->audienceLabel()],
                    ['label'=>'Criada por', 'value'=> $campaign->creator->name ?? '—'],
                    ['label'=>'Criada em', 'value'=> $campaign->created_at->format('d/m/Y H:i')],
                    ['label'=>'Enviada em', 'value'=> $campaign->sent_at ? $campaign->sent_at->format('d/m/Y H:i') : '—'],
                    ['label'=>'ID Brevo', 'value'=> $campaign->brevo_campaign_id ?? '—'],
                ];
            @endphp
            @foreach($details as $d)
            <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f1f5f9; font-size:0.85rem;">
                <span style="color:#64748b; font-weight:600;">{{ $d['label'] }}</span>
                <span style="color:#1e293b; font-weight:700; text-align:right; max-width:60%;">{{ $d['value'] }}</span>
            </div>
            @endforeach
            @if($campaign->error_message)
            <div style="background:#fef2f2; border:1px solid #fca5a5; border-radius:10px; padding:14px; margin-top:16px; font-size:0.8rem; color:#991b1b; line-height:1.6;">
                <strong><i class="fas fa-triangle-exclamation me-1"></i>Erro:</strong> {{ $campaign->error_message }}
            </div>
            @endif
        </div>
    </div>
</div>
{{-- Modal de confirmação de disparo --}}
<div id="modalDisparar" style="display:none; position:fixed; inset:0; z-index:9999; align-items:center; justify-content:center;">
    <div onclick="fecharModalDisparar()" style="position:absolute; inset:0; background:rgba(15,23,42,0.55); backdrop-filter:blur(4px);"></div>
    <div style="position:relative; background:#fff; border-radius:24px; padding:40px; max-width:460px; width:90%; box-shadow:0 25px 50px rgba(0,0,0,0.15); border:1px solid #e2e8f0;">

        {{-- Ícone --}}
        <div style="width:64px; height:64px; background:#fef3c7; border-radius:18px; display:flex; align-items:center; justify-content:center; margin:0 auto 24px; font-size:1.6rem;">
            <i class="fas fa-paper-plane" style="color:#d97706;"></i>
        </div>

        {{-- Título --}}
        <h3 style="text-align:center; margin:0 0 8px; font-size:1.25rem; font-weight:900; color:#0f172a;">Confirmar disparo</h3>
        <p style="text-align:center; color:#64748b; font-size:0.88rem; margin:0 0 28px; line-height:1.6;">
            Esta ação é <strong>irreversível</strong>. O e-mail será enviado imediatamente a todos os destinatários.
        </p>

        {{-- Detalhes da campanha --}}
        <div style="background:#f8fafc; border-radius:14px; padding:16px 20px; margin-bottom:28px; border:1px solid #e2e8f0;">
            <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f1f5f9; font-size:0.83rem;">
                <span style="color:#64748b; font-weight:600;">Campanha</span>
                <span id="modalNome" style="color:#1e293b; font-weight:800; text-align:right; max-width:60%;"></span>
            </div>
            <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f1f5f9; font-size:0.83rem;">
                <span style="color:#64748b; font-weight:600;">Público</span>
                <span id="modalPublico" style="color:#1e293b; font-weight:800;"></span>
            </div>
            <div style="display:flex; justify-content:space-between; padding:6px 0; font-size:0.83rem;">
                <span style="color:#64748b; font-weight:600;">Destinatários est.</span>
                <span id="modalDestinatarios" style="color:#6366f1; font-weight:800;"></span>
            </div>
        </div>

        {{-- Botões --}}
        <div style="display:flex; gap:12px;">
            <button onclick="fecharModalDisparar()"
                    style="flex:1; padding:14px; border:2px solid #e2e8f0; border-radius:12px; background:white; color:#64748b; font-weight:800; font-size:0.9rem; cursor:pointer;">
                Cancelar
            </button>
            <button onclick="confirmarDisparar()"
                    style="flex:1; padding:14px; border:none; border-radius:12px; background:#6366f1; color:white; font-weight:800; font-size:0.9rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;">
                <i class="fas fa-paper-plane"></i> Disparar agora
            </button>
        </div>
    </div>
</div>

<script>
function abrirModalDisparar(nome, publico, destinatarios) {
    document.getElementById('modalNome').textContent     = nome;
    document.getElementById('modalPublico').textContent  = publico;
    document.getElementById('modalDestinatarios').textContent = destinatarios ? destinatarios.toLocaleString('pt-BR') + ' contatos' : 'a calcular no envio';
    const modal = document.getElementById('modalDisparar');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function fecharModalDisparar() {
    document.getElementById('modalDisparar').style.display = 'none';
    document.body.style.overflow = '';
}
function confirmarDisparar() {
    const btn = event.currentTarget;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    document.getElementById('formDispararCampanha').submit();
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') fecharModalDisparar(); });

// ── Bruce IA F2: insight pos-envio ────────────────────────────────────────
var btnInsight = document.getElementById('btnBruceInsight');
if (btnInsight) {
    btnInsight.addEventListener('click', async function () {
        var btn = this;
        var box = document.getElementById('bruceInsightResult');
        var url = btn.getAttribute('data-url');

        btn.disabled = true;
        btn.textContent = 'Analisando…';
        btn.style.opacity = '.7';
        box.style.display = 'block';
        box.innerHTML = '<div style="padding:14px 16px; background:#3A3A3C; color:#F4F4F5; border-radius:12px; font-size:.9rem;">Bruce está interpretando os dados…</div>';

        try {
            var r = await fetch(url, { headers: { 'Accept': 'application/json' } });
            var data = await r.json();
            if (!r.ok || data.error) {
                box.innerHTML = '<div style="padding:14px 16px; background:#3A3A3C; color:#ff9a5a; border-radius:12px; font-size:.9rem;">' + (data.error || 'Falha') + '</div>';
                return;
            }
            var b = data.benchmark || {};
            var me = b.this_campaign || {};
            var avg = b.tenant_avg || {};

            var html = '';
            // Insight text
            html += '<div style="padding:18px 22px; background:#3A3A3C; border-radius:14px; color:#F4F4F5; font-size:.95rem; line-height:1.6; margin-bottom:14px;">';
            html += (data.insight || '—');
            html += '</div>';

            // Next action com barra accent
            html += '<div style="padding:16px 22px; background:#F4F4F5; border-left:4px solid #FF7A1A; border-radius:12px; margin-bottom:14px;">';
            html += '<div style="font-size:.7rem; text-transform:uppercase; letter-spacing:1.2px; color:#FF7A1A; font-weight:800; margin-bottom:6px;">Próxima ação</div>';
            html += '<div style="color:#0A0A0B; font-size:.92rem; line-height:1.55;">' + (data.next_action || '—') + '</div>';
            html += '</div>';

            // Comparativo
            if (b.sample_size > 0) {
                html += '<div style="padding:16px 22px; background:#F4F4F5; border-radius:12px;">';
                html += '<div style="font-size:.7rem; text-transform:uppercase; letter-spacing:1.2px; color:#6b6b6f; font-weight:800; margin-bottom:10px;">Comparativo — últimas ' + b.sample_size + ' campanhas</div>';
                html += '<table style="width:100%; font-size:.85rem; border-collapse:collapse;">';
                html += '<tr style="color:#6b6b6f;"><th style="text-align:left; padding:6px 0; font-weight:700;">Métrica</th><th style="text-align:right; padding:6px 0; font-weight:700;">Esta</th><th style="text-align:right; padding:6px 0; font-weight:700;">Média</th></tr>';
                var rows = [
                    ['Abertura', me.openRate,  avg.open],
                    ['Clique',   me.clickRate, avg.click],
                    ['Bounce',   me.bounceRate, avg.bounce],
                ];
                rows.forEach(function (row) {
                    var diff = row[1] - row[2];
                    var diffColor = row[0] === 'Bounce' ? (diff <= 0 ? '#059669' : '#dc2626') : (diff >= 0 ? '#059669' : '#dc2626');
                    var sign = diff > 0 ? '+' : '';
                    html += '<tr style="border-top:1px solid #d9d9dc;">';
                    html += '<td style="padding:8px 0; color:#0A0A0B; font-weight:600;">' + row[0] + '</td>';
                    html += '<td style="padding:8px 0; text-align:right; color:#0A0A0B; font-weight:800;">' + row[1] + '%</td>';
                    html += '<td style="padding:8px 0; text-align:right; color:#6b6b6f;">' + row[2] + '% ';
                    html += '<span style="color:' + diffColor + '; font-weight:700; margin-left:4px;">(' + sign + diff.toFixed(1) + ')</span></td>';
                    html += '</tr>';
                });
                html += '</table></div>';
            }

            box.innerHTML = html;
        } catch (e) {
            box.innerHTML = '<div style="padding:14px 16px; background:#3A3A3C; color:#ff9a5a; border-radius:12px; font-size:.9rem;">Falha de rede.</div>';
        } finally {
            btn.disabled = false;
            btn.textContent = 'Gerar de novo';
            btn.style.opacity = '1';
        }
    });
}
</script>
@endsection
