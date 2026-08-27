@extends('layouts.app')
@section('title', 'Handoff — ' . ($handoff->chat->contact_name ?: 'Lead'))

@push('styles')
<style>
    .brc-back {
        display: inline-flex; align-items: center; gap: 6px;
        color: #6b6b6f; text-decoration: none; font-size: .85rem; font-weight: 600; margin-bottom: 10px;
    }
    .brc-back:hover { color: #FF7A1A; }

    .brc-hero {
        background: linear-gradient(135deg, #0A0A0B 0%, #1a1a1c 100%);
        border-radius: 20px; padding: 24px 30px; margin-bottom: 20px;
        display: flex; align-items: center; gap: 18px;
        box-shadow: 0 10px 30px rgba(10,10,11,.12); flex-wrap: wrap;
    }
    .brc-hero__title { color: #F4F4F5; font-weight: 900; font-size: 1.4rem; margin: 0; }
    .brc-hero__sub   { color: #9a9a9e; font-size: .88rem; margin: 4px 0 0; }
    .brc-hero__body  { display: flex; align-items: center; gap: 18px; flex: 1; min-width: 260px; }

    .brc-status-badge {
        padding: 6px 14px; border-radius: 12px; font-size: .75rem; font-weight: 800;
        letter-spacing: .5px; text-transform: uppercase; white-space: nowrap;
    }
    .brc-status-badge--pendente  { background: rgba(220,38,38,.15); color: #dc2626; }
    .brc-status-badge--assumido  { background: rgba(245,158,11,.15); color: #b45309; }
    .brc-status-badge--resolvido { background: rgba(5,150,105,.15); color: #059669; }

    .brc-flash-success { background: rgba(255,122,26,.08); border-left: 4px solid #FF7A1A; color: #0A0A0B; padding: 14px 18px; border-radius: 10px; margin-bottom: 18px; font-weight: 600; }
    .brc-flash-error   { background: rgba(220,38,38,.08); border-left: 4px solid #dc2626; color: #7f1d1d; padding: 14px 18px; border-radius: 10px; margin-bottom: 18px; font-weight: 600; }

    .brc-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 18px; }
    @media (max-width: 900px) { .brc-grid { grid-template-columns: 1fr; } }

    .brc-panel {
        background: #fff; border-radius: 16px; padding: 24px 28px;
        box-shadow: 0 2px 10px rgba(10,10,11,.06); margin-bottom: 18px;
    }
    .brc-panel__title {
        font-size: .78rem; text-transform: uppercase; color: #6b6b6f;
        font-weight: 800; letter-spacing: 1px; margin: 0 0 12px;
    }

    .brc-briefing {
        color: #0A0A0B; font-size: 1rem; line-height: 1.65; white-space: pre-wrap;
    }

    .brc-kv { display: grid; grid-template-columns: 40% 60%; gap: 8px 12px; font-size: .88rem; }
    .brc-kv__k { color: #6b6b6f; font-weight: 700; }
    .brc-kv__v { color: #0A0A0B; }
    .brc-kv__v--null { color: #9a9a9e; font-style: italic; }

    .brc-list { list-style: none; padding: 0; margin: 0; }
    .brc-list li {
        background: #F4F4F5; padding: 6px 12px; border-radius: 8px;
        margin-bottom: 6px; font-size: .82rem; color: #3A3A3C;
    }

    .brc-actions {
        display: flex; gap: 12px; margin-top: 18px; flex-wrap: wrap;
    }
    .brc-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 12px 22px; border-radius: 10px; font-weight: 800; font-size: .9rem;
        text-decoration: none; border: 0; cursor: pointer; transition: transform .15s;
    }
    .brc-btn:hover { transform: translateY(-1px); }
    .brc-btn--primary { background: #FF7A1A; color: #0A0A0B; box-shadow: 0 4px 14px rgba(255,122,26,.35); }
    .brc-btn--primary:hover { color: #0A0A0B; }
    .brc-btn--dark    { background: #0A0A0B; color: #F4F4F5; }
    .brc-btn--dark:hover { color: #F4F4F5; }
    .brc-btn--neutral { background: #F4F4F5; color: #3A3A3C; }

    .brc-chat {
        max-height: 500px; overflow-y: auto; padding-right: 6px;
    }
    .brc-msg { margin-bottom: 12px; display: flex; }
    .brc-msg--lead   { justify-content: flex-start; }
    .brc-msg--bruno  { justify-content: flex-end; }
    .brc-msg__bubble {
        max-width: 78%; padding: 10px 14px; border-radius: 14px;
        font-size: .88rem; line-height: 1.5; word-wrap: break-word;
    }
    .brc-msg--lead .brc-msg__bubble  { background: #F4F4F5; color: #0A0A0B; border-bottom-left-radius: 4px; }
    .brc-msg--bruno .brc-msg__bubble { background: #FF7A1A; color: #0A0A0B; border-bottom-right-radius: 4px; }
    .brc-msg__time { font-size: .7rem; color: #9a9a9e; margin-top: 3px; text-align: right; }
    .brc-msg--lead .brc-msg__time { text-align: left; }

    .brc-resolve-form textarea {
        width: 100%; padding: 10px 12px; border: 2px solid #d9d9dc; border-radius: 10px;
        font-family: inherit; font-size: .88rem; margin-bottom: 10px;
    }
    .brc-resolve-form textarea:focus { outline: 0; border-color: #FF7A1A; }
</style>
@endpush

@section('content')
<div class="container" style="max-width:1200px; margin:24px auto;">
    <a href="{{ route('admin.bruno.handoffs.index', ['status' => $handoff->status]) }}" class="brc-back">
        <i class="fas fa-arrow-left"></i> Voltar aos handoffs
    </a>

    <div class="brc-hero">
        <div class="brc-hero__body">
            <img src="{{ asset('img/bruce/bruceia-icone-fundo-escuro.svg') }}" alt="Bruce IA" width="48" height="48" style="flex-shrink:0;">
            <div>
                <h1 class="brc-hero__title">{{ $handoff->chat->contact_name ?: 'Lead sem nome' }}</h1>
                <p class="brc-hero__sub">
                    {{ $handoff->chat->contact_phone ?: $handoff->chat->wa_id }}
                    · handoff criado {{ $handoff->created_at->diffForHumans() }}
                    @if($handoff->assumedBy)
                        · assumido por {{ $handoff->assumedBy->name }} {{ $handoff->assumed_at?->diffForHumans() }}
                    @endif
                </p>
            </div>
        </div>
        <span class="brc-status-badge brc-status-badge--{{ $handoff->status }}">{{ $handoff->status }}</span>
    </div>

    @if(session('success'))
        <div class="brc-flash-success">✓ {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="brc-flash-error">✗ {{ session('error') }}</div>
    @endif

    <div class="brc-grid">
        <div>
            <div class="brc-panel">
                <h2 class="brc-panel__title">Briefing</h2>
                <div class="brc-briefing">{{ $handoff->briefing }}</div>

                @if($handoff->isPending())
                    <div class="brc-actions">
                        <form method="POST" action="{{ route('admin.bruno.handoffs.assume', $handoff) }}" style="margin:0;">
                            @csrf
                            <button type="submit" class="brc-btn brc-btn--primary">
                                <i class="fas fa-user-check"></i> Assumir este handoff
                            </button>
                        </form>
                    </div>
                @elseif($handoff->isAssumed())
                    <form method="POST" action="{{ route('admin.bruno.handoffs.resolve', $handoff) }}" class="brc-resolve-form" style="margin-top:18px;">
                        @csrf
                        <label style="display:block; font-weight:700; color:#0A0A0B; margin-bottom:6px; font-size:.85rem;">
                            Como resolveu? <span style="color:#9a9a9e; font-weight:400;">(opcional)</span>
                        </label>
                        <textarea name="resolution_note" rows="3" maxlength="1000"
                                  placeholder="Ex: fechou plano anual, marcou nova conversa, lead desistiu..."></textarea>
                        <button type="submit" class="brc-btn brc-btn--dark">
                            <i class="fas fa-check-double"></i> Marcar como resolvido
                        </button>
                    </form>
                @elseif($handoff->isResolved() && $handoff->resolution_note)
                    <div style="margin-top:16px; padding: 14px 18px; background: #F4F4F5; border-radius: 10px;">
                        <div style="font-size:.75rem; font-weight:800; color:#6b6b6f; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px;">Nota de resolução</div>
                        <div style="color:#0A0A0B; font-size:.9rem; line-height:1.5;">{{ $handoff->resolution_note }}</div>
                    </div>
                @endif
            </div>

            <div class="brc-panel">
                <h2 class="brc-panel__title">Conversa</h2>
                <div class="brc-chat">
                    @forelse($handoff->chat->messages as $msg)
                        @php $isLead = $msg->direction === 'inbound'; @endphp
                        <div class="brc-msg brc-msg--{{ $isLead ? 'lead' : 'bruno' }}">
                            <div>
                                <div class="brc-msg__bubble">{{ $msg->content }}</div>
                                <div class="brc-msg__time">{{ $msg->created_at?->format('d/m H:i') }}</div>
                            </div>
                        </div>
                    @empty
                        <p style="color:#9a9a9e; text-align:center; padding: 20px;">Nenhuma mensagem no histórico.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div>
            <div class="brc-panel">
                <h2 class="brc-panel__title">Dados estruturados</h2>
                @php $sd = $handoff->structured_data ?? []; @endphp
                <div class="brc-kv">
                    <div class="brc-kv__k">Organização</div>
                    <div class="brc-kv__v {{ empty($sd['organizacao']) ? 'brc-kv__v--null' : '' }}">{{ $sd['organizacao'] ?? '—' }}</div>

                    <div class="brc-kv__k">Tipo</div>
                    <div class="brc-kv__v {{ empty($sd['tipo_organizacao']) ? 'brc-kv__v--null' : '' }}">{{ $sd['tipo_organizacao'] ?? '—' }}</div>

                    <div class="brc-kv__k">Cidade</div>
                    <div class="brc-kv__v {{ empty($sd['cidade']) ? 'brc-kv__v--null' : '' }}">{{ $sd['cidade'] ?? '—' }}</div>

                    <div class="brc-kv__k">Área de atuação</div>
                    <div class="brc-kv__v {{ empty($sd['area_atuacao']) ? 'brc-kv__v--null' : '' }}">{{ $sd['area_atuacao'] ?? '—' }}</div>

                    <div class="brc-kv__k">Orçamento</div>
                    <div class="brc-kv__v {{ empty($sd['orcamento_mencionado']) ? 'brc-kv__v--null' : '' }}">{{ $sd['orcamento_mencionado'] ?? '—' }}</div>

                    <div class="brc-kv__k">Urgência</div>
                    <div class="brc-kv__v {{ empty($sd['urgencia']) ? 'brc-kv__v--null' : '' }}">{{ $sd['urgencia'] ?? '—' }}</div>

                    <div class="brc-kv__k">Fase do funil</div>
                    <div class="brc-kv__v {{ empty($sd['fase_funil']) ? 'brc-kv__v--null' : '' }}">{{ $sd['fase_funil'] ?? '—' }}</div>
                </div>
            </div>

            @if(!empty($sd['sinais_compra']))
                <div class="brc-panel">
                    <h2 class="brc-panel__title">Sinais de compra</h2>
                    <ul class="brc-list">
                        @foreach((array) $sd['sinais_compra'] as $s)
                            <li>{{ $s }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(!empty($sd['objecoes_levantadas']))
                <div class="brc-panel">
                    <h2 class="brc-panel__title">Objeções levantadas</h2>
                    <ul class="brc-list">
                        @foreach((array) $sd['objecoes_levantadas'] as $o)
                            <li>{{ $o }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(!empty($sd['proxima_acao']))
                <div class="brc-panel" style="background: #FF7A1A; color: #0A0A0B;">
                    <div style="font-size:.72rem; text-transform:uppercase; font-weight:800; letter-spacing:1px; margin-bottom:8px;">Próxima ação sugerida</div>
                    <div style="font-size:.95rem; font-weight:700; line-height:1.5;">{{ $sd['proxima_acao'] }}</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
