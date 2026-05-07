@extends('layouts.app')
@section('title', 'Automações WhatsApp')

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <div style="width:34px;height:34px;border-radius:10px;background:rgba(37,211,102,.12);display:inline-flex;align-items:center;justify-content:center;">
                    <i class="fab fa-whatsapp" style="color:#25d366;font-size:1rem;"></i>
                </div>
                <span style="color:#6366f1;font-weight:700;text-transform:uppercase;font-size:.68rem;letter-spacing:1.8px;">WhatsApp / Automações</span>
            </div>
            <h2 class="fw-800 mb-1" style="font-size:1.85rem;color:#0f172a;line-height:1.2;">Automações de Reativação</h2>
            <p class="text-muted mb-0" style="font-size:.88rem;">Regras que enviam mensagens automáticas para contatos inativos no momento certo.</p>
        </div>
        <a href="{{ route('whatsapp.automations.create') }}" class="btn btn-primary fw-bold px-4 py-2 rounded-3 d-flex align-items-center gap-2">
            <i class="fas fa-plus"></i> Nova Automação
        </a>
    </div>

    @if(session('success'))
        <div class="d-flex align-items-center gap-2 rounded-3 px-3 py-2 mb-4" style="background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.2);color:#059669;">
            <i class="fas fa-circle-check"></i>
            <span style="font-size:.88rem;">{{ session('success') }}</span>
        </div>
    @endif

    @if($automations->isEmpty())
        <div class="text-center py-5 rounded-4" style="background:#f8faff;border:2px dashed #e0e7ff;">
            <div style="width:64px;height:64px;background:#ede9fe;border-radius:18px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                <i class="fas fa-robot" style="font-size:1.6rem;color:#7c3aed;"></i>
            </div>
            <h5 class="fw-bold text-dark mb-2">Nenhuma automação criada ainda</h5>
            <p class="text-muted small mb-4" style="max-width:380px;margin-inline:auto;">Crie regras para reativar doadores, patrocinadores e contatos automaticamente via WhatsApp.</p>
            <a href="{{ route('whatsapp.automations.create') }}" class="btn btn-primary px-4 fw-bold rounded-3">
                <i class="fas fa-plus me-2"></i> Criar primeira automação
            </a>
        </div>
    @else
        {{-- Stats overview --}}
        @php
            $totalActive = $automations->where('is_active', true)->count();
            $totalSent   = $automations->sum('logs_count');
        @endphp
        <div class="row g-3 mb-4">
            <div class="col-4">
                <div class="rounded-4 p-3 text-center" style="background:#f8faff;border:1px solid #e0e7ff;">
                    <div class="fw-800" style="font-size:1.7rem;color:#4f46e5;line-height:1.1;">{{ $automations->count() }}</div>
                    <div style="font-size:.68rem;color:#6b7280;text-transform:uppercase;letter-spacing:.07em;margin-top:2px;">automações</div>
                </div>
            </div>
            <div class="col-4">
                <div class="rounded-4 p-3 text-center" style="background:rgba(16,185,129,.06);border:1px solid rgba(16,185,129,.2);">
                    <div class="fw-800" style="font-size:1.7rem;color:#059669;line-height:1.1;">{{ $totalActive }}</div>
                    <div style="font-size:.68rem;color:#6b7280;text-transform:uppercase;letter-spacing:.07em;margin-top:2px;">ativas</div>
                </div>
            </div>
            <div class="col-4">
                <div class="rounded-4 p-3 text-center" style="background:#f8faff;border:1px solid #e0e7ff;">
                    <div class="fw-800" style="font-size:1.7rem;color:#4f46e5;line-height:1.1;">{{ number_format($totalSent) }}</div>
                    <div style="font-size:.68rem;color:#6b7280;text-transform:uppercase;letter-spacing:.07em;margin-top:2px;">total enviados</div>
                </div>
            </div>
        </div>

        {{-- Automation cards --}}
        <div class="d-flex flex-column gap-3">
            @foreach($automations as $automation)
            @php
                $triggerMeta = [
                    'donor_inactive_days'    => ['label' => 'Doador sem doação',               'icon' => 'heart',        'color' => '#10b981', 'bg' => 'rgba(16,185,129,.1)'],
                    'sponsorship_stale_days' => ['label' => 'Patrocínio parado em proposta',    'icon' => 'handshake',    'color' => '#6366f1', 'bg' => 'rgba(99,102,241,.1)'],
                    'no_contact_days'        => ['label' => 'Contato sem interação',            'icon' => 'user-clock',   'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,.1)'],
                    'open_conversation_days' => ['label' => 'Conversa aberta sem resposta',     'icon' => 'comment-dots', 'color' => '#3b82f6', 'bg' => 'rgba(59,130,246,.1)'],
                ];
                $audienceLabels = ['all' => 'Todos', 'donors' => 'Doadores', 'sponsors' => 'Patrocinadores', 'contacts' => 'Contatos'];
                $tm = $triggerMeta[$automation->trigger] ?? ['label' => $automation->trigger, 'icon' => 'bolt', 'color' => '#6366f1', 'bg' => 'rgba(99,102,241,.1)'];
                $accentColor = $automation->is_active ? $tm['color'] : '#cbd5e1';
            @endphp

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden automation-card"
                 style="border-left: 4px solid {{ $accentColor }} !important;{{ $automation->is_active ? '' : 'opacity:.6;' }}">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between gap-4 flex-wrap">

                        {{-- Icon + Main info --}}
                        <div class="d-flex align-items-start gap-3 flex-1" style="min-width:0;">
                            <div style="width:48px;height:48px;border-radius:14px;background:{{ $tm['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fas fa-{{ $tm['icon'] }}" style="color:{{ $tm['color'] }};font-size:1.15rem;"></i>
                            </div>
                            <div style="min-width:0;flex:1;">
                                {{-- Name + status badge --}}
                                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                    <span class="fw-bold text-dark" style="font-size:1rem;">{{ $automation->name }}</span>
                                    @if($automation->is_active)
                                        <span class="badge rounded-pill px-2 py-1" style="font-size:.62rem;font-weight:700;background:rgba(16,185,129,.12);color:#059669;letter-spacing:.02em;">
                                            <i class="fas fa-circle" style="font-size:.35rem;vertical-align:middle;margin-right:2px;"></i> Ativa
                                        </span>
                                    @else
                                        <span class="badge rounded-pill px-2 py-1" style="font-size:.62rem;font-weight:700;background:rgba(100,116,139,.1);color:#64748b;letter-spacing:.02em;">
                                            <i class="fas fa-pause" style="font-size:.55rem;vertical-align:middle;margin-right:2px;"></i> Pausada
                                        </span>
                                    @endif
                                </div>
                                {{-- Meta tags --}}
                                <div class="d-flex align-items-center gap-3 flex-wrap mb-2" style="font-size:.78rem;color:#64748b;">
                                    <span>
                                        <i class="fas fa-bolt me-1" style="color:{{ $tm['color'] }};"></i>
                                        {{ $tm['label'] }} há <strong class="text-dark">{{ $automation->trigger_days }} dias</strong>
                                    </span>
                                    <span>
                                        <i class="fas fa-clock me-1"></i>
                                        {{ substr($automation->send_window_start, 0, 5) }} – {{ substr($automation->send_window_end, 0, 5) }}
                                    </span>
                                    <span>
                                        <i class="fas fa-users me-1"></i>
                                        {{ $audienceLabels[$automation->audience] ?? $automation->audience }}
                                    </span>
                                </div>
                                {{-- Message preview --}}
                                <div class="px-3 py-2 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;font-size:.75rem;font-style:italic;color:#64748b;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:560px;">
                                    "{{ Str::limit($automation->message_template, 100) }}"
                                </div>
                            </div>
                        </div>

                        {{-- Stat + Actions --}}
                        <div class="d-flex align-items-center gap-3 flex-shrink-0">
                            <a href="{{ route('whatsapp.automations.logs', $automation) }}" class="text-center text-decoration-none px-3" title="Ver histórico de envios">
                                <div class="fw-800" style="font-size:1.5rem;color:#4f46e5;line-height:1;">{{ number_format($automation->logs_count) }}</div>
                                <div style="font-size:.6rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em;">enviados</div>
                            </a>

                            <div style="width:1px;height:36px;background:#e2e8f0;"></div>

                            <div class="d-flex gap-2 align-items-center">
                                <form action="{{ route('whatsapp.automations.toggle', $automation) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm rounded-3 fw-600 d-flex align-items-center gap-1"
                                            style="font-size:.75rem;padding:5px 12px;{{ $automation->is_active ? 'background:rgba(245,158,11,.12);color:#d97706;border:1px solid rgba(245,158,11,.3);' : 'background:rgba(16,185,129,.1);color:#059669;border:1px solid rgba(16,185,129,.3);' }}">
                                        <i class="fas fa-{{ $automation->is_active ? 'pause' : 'play' }}"></i>
                                        {{ $automation->is_active ? 'Pausar' : 'Ativar' }}
                                    </button>
                                </form>
                                <a href="{{ route('whatsapp.automations.edit', $automation) }}"
                                   class="btn btn-sm btn-outline-secondary rounded-3"
                                   style="padding:5px 10px;" title="Editar">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form action="{{ route('whatsapp.automations.destroy', $automation) }}" method="POST"
                                      onsubmit="return confirm('Remover esta automação permanentemente?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger rounded-3" style="padding:5px 10px;" title="Remover">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Info footer --}}
        <div class="d-flex align-items-start gap-2 mt-4 p-3 rounded-3" style="background:#f8faff;border:1px solid #e0e7ff;font-size:.8rem;color:#64748b;">
            <i class="fas fa-circle-info text-primary mt-1" style="flex-shrink:0;"></i>
            <span>
                Automações são processadas diariamente às <strong class="text-dark">10:00</strong>.
                Use <code>{nome}</code> e <code>{organizacao}</code> nas mensagens para personalização.
                Cada contato recebe no máximo <strong class="text-dark">1 mensagem por automação a cada 24h</strong>.
            </span>
        </div>
    @endif
</div>

<style>
.fw-600 { font-weight: 600; }
.fw-700 { font-weight: 700; }
.fw-800 { font-weight: 800; }
.automation-card { transition: box-shadow .15s, transform .15s; }
.automation-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.09) !important; transform: translateY(-1px); }
</style>
@endsection
