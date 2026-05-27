@extends('layouts.app')

@push('styles')
<style>
/* ── Layout ── */
.sp-header { margin-bottom: 28px; }
.sp-kpis   { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 28px; }

.sp-kpi {
    flex: 1; min-width: 160px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 18px 20px;
    display: flex; align-items: center; gap: 14px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
}
.sp-kpi-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
}
.sp-kpi-label { font-size: 0.72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; }
.sp-kpi-value { font-size: 1.15rem; font-weight: 900; color: #0f172a; margin-top: 2px; line-height: 1.2; }

/* ── Kanban Board ── */
.kanban-board {
    display: flex; gap: 16px;
    overflow-x: auto; padding-bottom: 24px;
    min-height: calc(100vh - 320px);
    scroll-snap-type: x mandatory;
}
.kanban-board::-webkit-scrollbar { height: 6px; }
.kanban-board::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 99px; }
.kanban-board::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }

.kanban-col {
    min-width: 288px; max-width: 288px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 16px;
    display: flex; flex-direction: column;
    scroll-snap-align: start;
}

.kanban-col-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 14px; padding-bottom: 12px;
    border-bottom: 1px solid #e2e8f0;
}
.kanban-col-title {
    display: flex; align-items: center; gap: 8px;
    font-weight: 800; font-size: 0.82rem;
    text-transform: uppercase; letter-spacing: .06em;
}
.kanban-col-dot {
    width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
}
.kanban-col-meta {
    display: flex; align-items: center; gap: 6px;
}
.kanban-count {
    background: #e2e8f0; color: #475569;
    font-size: 0.72rem; font-weight: 800;
    padding: 2px 8px; border-radius: 999px;
}
.kanban-subtotal {
    font-size: 0.72rem; font-weight: 700; color: #64748b;
    display: none;
}

.kanban-drop-zone {
    flex: 1; min-height: 80px;
    border-radius: 12px;
    transition: background .15s, border .15s;
}
.kanban-drop-zone.drag-over {
    background: #e0e7ff;
    border: 2px dashed #6366f1;
    border-radius: 12px;
}

/* ── Cards ── */
.k-card {
    background: #fff;
    border: 1px solid #e8edf3;
    border-radius: 14px;
    padding: 14px 16px;
    margin-bottom: 10px;
    cursor: grab;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
    transition: transform .18s, box-shadow .18s, border-color .18s;
    position: relative;
    user-select: none;
}
.k-card:active { cursor: grabbing; }
.k-card:hover  { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); border-color: #c7d2e1; }
.k-card.is-dragging { opacity: .45; transform: rotate(1.5deg); }

.k-card-top { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 10px; }
.k-avatar {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff; font-weight: 900; font-size: 0.85rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.k-company { font-weight: 800; color: #0f172a; font-size: 0.88rem; line-height: 1.3; }
.k-contact  { font-size: 0.75rem; color: #64748b; margin-top: 2px; }

.k-value {
    display: inline-flex; align-items: center; gap: 5px;
    background: #f0fdf4; color: #15803d;
    border: 1px solid #bbf7d0;
    font-weight: 800; font-size: 0.8rem;
    padding: 3px 10px; border-radius: 999px;
    margin-bottom: 8px;
}
.k-meta { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.k-chip {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 0.7rem; color: #64748b;
    background: #f1f5f9; border-radius: 6px;
    padding: 2px 7px;
}
.k-notes-preview {
    font-size: 0.73rem; color: #94a3b8;
    line-height: 1.4; margin-top: 8px;
    display: -webkit-box; -webkit-line-clamp: 2;
    -webkit-box-orient: vertical; overflow: hidden;
    border-top: 1px solid #f1f5f9; padding-top: 6px;
}

.k-actions {
    position: absolute; top: 10px; right: 10px;
    display: flex; gap: 4px;
    opacity: 0; transition: opacity .15s;
}
.k-card:hover .k-actions { opacity: 1; }
.k-btn-action {
    width: 26px; height: 26px; border-radius: 7px;
    border: none; display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 0.7rem; transition: background .15s;
}

/* ── Empty State ── */
.k-empty {
    text-align: center; padding: 28px 16px;
    color: #cbd5e1;
}
.k-empty i { font-size: 1.8rem; margin-bottom: 8px; display: block; }
.k-empty span { font-size: 0.78rem; font-weight: 600; }

/* ── Filter bar ── */
.sp-filter-bar {
    display: flex; align-items: center; gap: 10px;
    flex-wrap: wrap; margin-bottom: 20px;
}
.sp-search-wrap {
    position: relative; flex: 1; min-width: 200px; max-width: 320px;
}
.sp-search-wrap i {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    color: #94a3b8; font-size: .78rem; pointer-events: none;
}
.sp-search-input {
    width: 100%; background: #fff; border: 1px solid #e2e8f0;
    border-radius: 10px; padding: 9px 13px 9px 32px;
    font-size: .85rem; color: #0f172a; outline: none;
    transition: border-color .15s, box-shadow .15s;
}
.sp-search-input:focus {
    border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1);
}
.sp-stage-pills { display: flex; gap: 6px; flex-wrap: wrap; }
.sp-pill {
    padding: 6px 13px; border-radius: 99px; font-size: .75rem; font-weight: 700;
    border: 1.5px solid #e2e8f0; background: #fff; color: #64748b;
    cursor: pointer; transition: all .15s; white-space: nowrap;
}
.sp-pill.active, .sp-pill:hover { color: #fff; border-color: transparent; }
.sp-pill[data-stage="all"].active      { background: #0f172a; }
.sp-pill[data-stage="prospecting"].active       { background: #6366f1; }
.sp-pill[data-stage="meeting_scheduled"].active  { background: #f59e0b; }
.sp-pill[data-stage="negotiating"].active        { background: #3b82f6; }
.sp-pill[data-stage="won"].active                { background: #10b981; }
.sp-pill[data-stage="lost"].active               { background: #ef4444; }
.sp-results-info { font-size: .78rem; color: #94a3b8; font-weight: 600; white-space: nowrap; }

/* ── Modal ── */
.form-field { margin-bottom: 16px; }
.form-field label { display: block; font-size: 0.78rem; font-weight: 700; color: #64748b; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .04em; }
.form-field input, .form-field textarea, .form-field select {
    width: 100%; background: #f8fafc; border: 1px solid #e2e8f0;
    border-radius: 10px; padding: 10px 13px; font-size: 0.85rem;
    color: #0f172a; transition: border-color .15s, box-shadow .15s;
    outline: none;
}
.form-field input:focus, .form-field textarea:focus, .form-field select:focus {
    border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1);
    background: #fff;
}
</style>
@endpush

@section('content')
@php
    $totalPipeline = $deals->whereNotIn('stage', ['lost'])->sum('expected_value');
    $wonValue      = $deals->where('stage', 'won')->sum('expected_value');
    $wonCount      = $deals->where('stage', 'won')->count();
    $activeCount   = $deals->whereNotIn('stage', ['won', 'lost'])->count();
    $totalCount    = $deals->count();
    $convRate      = $totalCount > 0 ? round(($wonCount / $totalCount) * 100) : 0;

    $cols = [
        'prospecting'       => ['label' => 'Prospecção',      'color' => '#6366f1', 'icon' => 'fa-search'],
        'meeting_scheduled' => ['label' => 'Reunião Marcada', 'color' => '#f59e0b', 'icon' => 'fa-calendar-check'],
        'negotiating'       => ['label' => 'Em Negociação',   'color' => '#3b82f6', 'icon' => 'fa-handshake'],
        'won'               => ['label' => 'Conquistado',     'color' => '#10b981', 'icon' => 'fa-trophy'],
        'lost'              => ['label' => 'Perdido',         'color' => '#ef4444', 'icon' => 'fa-times-circle'],
    ];
@endphp

{{-- Header --}}
<div class="sp-header" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
    <div>
        <h6 style="color:#6366f1; font-weight:700; text-transform:uppercase; margin:0 0 4px; letter-spacing:1px; font-size:.75rem;">Terceiro Setor</h6>
        <h2 style="margin:0; color:#0f172a; font-weight:900; font-size:1.75rem;">CRM de Patrocínios</h2>
        <p style="color:#64748b; margin:4px 0 0; font-size:.85rem;">Pipeline visual de empresas parceiras e captação B2B.</p>
    </div>
    <button class="btn-premium" data-bs-toggle="modal" data-bs-target="#dealModal">
        <i class="fas fa-plus me-2"></i> Novo Patrocínio
    </button>
</div>

{{-- KPIs --}}
<div class="sp-kpis">
    <div class="sp-kpi">
        <div class="sp-kpi-icon" style="background:#eef2ff; color:#6366f1;"><i class="fas fa-funnel-dollar"></i></div>
        <div>
            <div class="sp-kpi-label">Pipeline Ativo</div>
            <div class="sp-kpi-value">R$ {{ number_format($totalPipeline, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="sp-kpi">
        <div class="sp-kpi-icon" style="background:#f0fdf4; color:#10b981;"><i class="fas fa-trophy"></i></div>
        <div>
            <div class="sp-kpi-label">Captado (Won)</div>
            <div class="sp-kpi-value">R$ {{ number_format($wonValue, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="sp-kpi">
        <div class="sp-kpi-icon" style="background:#fff7ed; color:#f59e0b;"><i class="fas fa-building"></i></div>
        <div>
            <div class="sp-kpi-label">Em Andamento</div>
            <div class="sp-kpi-value">{{ $activeCount }} empresa{{ $activeCount != 1 ? 's' : '' }}</div>
        </div>
    </div>
    <div class="sp-kpi">
        <div class="sp-kpi-icon" style="background:#fdf4ff; color:#a855f7;"><i class="fas fa-percentage"></i></div>
        <div>
            <div class="sp-kpi-label">Taxa Conversão</div>
            <div class="sp-kpi-value">{{ $convRate }}%</div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 rounded-4 mb-4 shadow-sm">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
    </div>
@endif

{{-- Filter bar --}}
<div class="sp-filter-bar">
    <div class="sp-search-wrap">
        <i class="fas fa-search"></i>
        <input type="text" class="sp-search-input" id="spSearch" placeholder="Buscar empresa ou contato…" autocomplete="off">
    </div>
    <div class="sp-stage-pills" id="stagePills">
        <button class="sp-pill active" data-stage="all">Todos</button>
        @foreach($cols as $stage => $col)
        <button class="sp-pill" data-stage="{{ $stage }}" style="--dot:{{ $col['color'] }};">{{ $col['label'] }}</button>
        @endforeach
    </div>
    <span class="sp-results-info" id="spResultsInfo"></span>
</div>

{{-- Kanban Board --}}
<div class="kanban-board" id="kanbanBoard">
    @foreach($cols as $stage => $col)
    @php
        $colDeals    = $groupedDeals[$stage];
        $colSubtotal = $colDeals->sum('expected_value');
    @endphp
    <div class="kanban-col">
        <div class="kanban-col-header">
            <div class="kanban-col-title">
                <span class="kanban-col-dot" style="background:{{ $col['color'] }};"></span>
                <span style="color:{{ $col['color'] }};">{{ $col['label'] }}</span>
            </div>
            <div class="kanban-col-meta">
                @if($colSubtotal > 0)
                <span class="kanban-subtotal" style="display:inline;">
                    R$ {{ number_format($colSubtotal, 0, ',', '.') }}
                </span>
                @endif
                <span class="kanban-count">{{ $colDeals->count() }}</span>
            </div>
        </div>

        <div class="kanban-drop-zone" data-stage="{{ $stage }}" id="col-{{ $stage }}">
            @forelse($colDeals as $deal)
            <div class="k-card" draggable="true"
                data-id="{{ $deal->id }}"
                data-company="@js($deal->company_name)"
                data-contact="@js($deal->contact_person ?? '')"
                data-email="@js($deal->email ?? '')"
                data-phone="@js($deal->phone ?? '')"
                data-value="{{ $deal->expected_value }}"
                data-value-fmt="{{ number_format($deal->expected_value,2,',','.') }}"
                data-contact-date="{{ $deal->contact_date?->format('Y-m-d') ?? '' }}"
                data-notes="@js($deal->notes ?? '')"
                data-stage="{{ $deal->stage }}"
                data-created="{{ $deal->created_at->format('d/m/Y') }}"
                id="deal-{{ $deal->id }}">
                {{-- Actions --}}
                <div class="k-actions">
                    <button type="button" class="k-btn-action"
                        style="background:#f0fdf4; color:#16a34a;"
                        onclick="openEdit(this.closest('.k-card'))"
                        title="Editar">
                        <i class="fas fa-pen"></i>
                    </button>
                    <button type="button" class="k-btn-action"
                        style="background:#eff6ff; color:#3b82f6;"
                        onclick="openDetail(this.closest('.k-card'))"
                        title="Ver detalhes">
                        <i class="fas fa-eye"></i>
                    </button>
                    <form action="{{ url('/ngo/sponsorships/'.$deal->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Excluir esta negociação?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="k-btn-action" style="background:#fee2e2; color:#ef4444;" title="Excluir">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>
                </div>

                {{-- Card body --}}
                <div class="k-card-top">
                    <div class="k-avatar">{{ strtoupper(substr($deal->company_name, 0, 2)) }}</div>
                    <div style="flex:1; min-width:0; padding-right:50px;">
                        <div class="k-company">{{ $deal->company_name }}</div>
                        @if($deal->contact_person)
                        <div class="k-contact"><i class="fas fa-user" style="font-size:.65rem;"></i> {{ $deal->contact_person }}</div>
                        @endif
                    </div>
                </div>

                <div class="k-value">
                    <i class="fas fa-dollar-sign" style="font-size:.7rem;"></i>
                    R$ {{ number_format($deal->expected_value, 2, ',', '.') }}
                </div>

                <div class="k-meta">
                    @if($deal->email)
                    <span class="k-chip"><i class="fas fa-envelope" style="font-size:.62rem;"></i> {{ Str::limit($deal->email, 22) }}</span>
                    @endif
                    @if($deal->contact_date)
                    <span class="k-chip"><i class="fas fa-calendar" style="font-size:.62rem;"></i> {{ $deal->contact_date->format('d/m/Y') }}</span>
                    @endif
                    <span class="k-chip"><i class="fas fa-clock" style="font-size:.62rem;"></i> {{ $deal->created_at->format('d/m/y') }}</span>
                </div>

                @if($deal->notes)
                <div class="k-notes-preview">{{ $deal->notes }}</div>
                @endif
            </div>
            @empty
            <div class="k-empty">
                <i class="fas {{ $col['icon'] }}" style="color:{{ $col['color'] }}; opacity:.3;"></i>
                <span>Nenhuma empresa aqui</span>
            </div>
            @endforelse
        </div>
    </div>
    @endforeach
</div>

{{-- ── MODAL: Novo Patrocínio ── --}}
<div class="modal fade" id="dealModal" role="dialog" aria-modal="true" aria-labelledby="dealModalLabel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:540px;">
        <div class="modal-content" style="border-radius:22px; border:none; box-shadow:0 25px 60px rgba(0,0,0,0.15); overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#6366f1,#8b5cf6); padding:24px 28px; border:none;">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0" id="dealModalLabel" style="font-size:1.05rem;">
                        <i class="fas fa-handshake me-2"></i> Novo Patrocínio
                    </h5>
                    <p style="color:rgba(255,255,255,.65); font-size:.78rem; margin:3px 0 0;">Adicione uma empresa ao funil de captação.</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:28px;">
                <form action="{{ url('/ngo/sponsorships') }}" method="POST">
                    @csrf
                    <div class="form-field">
                        <label for="company_name">Empresa ou Organização</label>
                        <input type="text" name="company_name" required placeholder="Ex: Itaú BBA S.A." id="company_name">
                    </div>
                    <div class="row g-3">
                        <div class="col-7">
                            <div class="form-field mb-0">
                                <label for="contact_person">Pessoa de Contato</label>
                                <input type="text" name="contact_person" placeholder="Ex: Maria Souza" id="contact_person">
                            </div>
                        </div>
                        <div class="col-5">
                            <div class="form-field mb-0">
                                <label for="phone">Telefone / WhatsApp</label>
                                <input type="text" name="phone" placeholder="(11) 99999-0000" id="phone">
                            </div>
                        </div>
                    </div>
                    <div class="form-field mt-3">
                        <label for="email">E-mail Corporativo</label>
                        <input type="email" name="email" placeholder="contato@empresa.com.br" id="email">
                    </div>
                    <div class="row g-3">
                        <div class="col-7">
                            <div class="form-field mb-0">
                                <label for="new_value">Valor Esperado (R$)</label>
                                <input type="text" name="expected_value" id="new_value" inputmode="numeric" placeholder="Ex: 50.000,00" required>
                            </div>
                        </div>
                        <div class="col-5">
                            <div class="form-field mb-0">
                                <label for="contact_date">Data da Reunião</label>
                                <input type="date" name="contact_date" id="contact_date">
                            </div>
                        </div>
                    </div>
                    <div class="form-field mt-3">
                        <label for="notes">Observações</label>
                        <textarea name="notes" rows="3" placeholder="Contexto, histórico, próximos passos..." id="notes"></textarea>
                    </div>
                    <button type="submit" class="btn-premium w-100 mt-1" style="justify-content:center;">
                        <i class="fas fa-plus me-2"></i> Adicionar ao Funil
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ── MODAL: Detalhes do Deal ── --}}
<div class="modal fade" id="detailModal" role="dialog" aria-modal="true" aria-labelledby="detailModalLabel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content" style="border-radius:22px; border:none; box-shadow:0 25px 60px rgba(0,0,0,0.15); overflow:hidden;">
            <div class="modal-header" style="padding:22px 26px; border-bottom:1px solid #f1f5f9;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div id="detail_avatar" class="k-avatar" style="width:44px;height:44px;font-size:1rem;border-radius:12px;"></div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="detail_company" style="font-size:1rem; color:#0f172a;"></h5>
                        <div id="detail_contact" class="text-muted" style="font-size:.78rem;"></div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:24px 26px;">
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:14px; text-align:center;">
                            <div style="font-size:.7rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em;">Valor Esperado</div>
                            <div id="detail_value" style="font-weight:900; color:#15803d; font-size:1.1rem; margin-top:4px;"></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px; text-align:center;">
                            <div style="font-size:.7rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em;">Adicionado em</div>
                            <div id="detail_date" style="font-weight:800; color:#0f172a; font-size:.95rem; margin-top:4px;"></div>
                        </div>
                    </div>
                </div>
                <div id="detail_email_row" style="display:none; margin-bottom:10px;">
                    <div style="font-size:.75rem; font-weight:700; color:#94a3b8; margin-bottom:4px;">EMAIL</div>
                    <div id="detail_email" style="font-size:.88rem; color:#0f172a; font-weight:600;"></div>
                </div>
                <div id="detail_phone_row" style="display:none; margin-bottom:10px;">
                    <div style="font-size:.75rem; font-weight:700; color:#94a3b8; margin-bottom:4px;">TELEFONE</div>
                    <div id="detail_phone" style="font-size:.88rem; color:#0f172a; font-weight:600;"></div>
                </div>
                <div id="detail_meeting_row" style="display:none; margin-bottom:10px;">
                    <div style="font-size:.75rem; font-weight:700; color:#94a3b8; margin-bottom:4px;">PRÓXIMA REUNIÃO</div>
                    <div id="detail_meeting" style="font-size:.88rem; color:#0f172a; font-weight:600;"></div>
                </div>
                <div id="detail_notes_row" style="display:none; margin-top:14px;">
                    <div style="font-size:.75rem; font-weight:700; color:#94a3b8; margin-bottom:6px;">OBSERVAÇÕES</div>
                    <div id="detail_notes" style="font-size:.85rem; color:#334155; background:#f8fafc; border-radius:10px; padding:12px; line-height:1.6; white-space:pre-wrap;"></div>
                </div>
            </div>
            <div class="modal-footer" style="padding:16px 26px; border-top:1px solid #f1f5f9; justify-content:space-between;">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal" style="border-radius:8px;">Fechar</button>
                <button type="button" id="btnOpenEditFromDetail" class="btn btn-sm" style="background:#6366f1; color:#fff; border:none; border-radius:8px; font-weight:700;">
                    <i class="fas fa-pen me-1"></i> Editar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── MODAL: Editar Deal ── --}}
<div class="modal fade" id="editDealModal" role="dialog" aria-modal="true" aria-labelledby="editDealModalLabel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:540px;">
        <div class="modal-content" style="border-radius:22px; border:none; box-shadow:0 25px 60px rgba(0,0,0,0.15); overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#0ea5e9,#6366f1); padding:24px 28px; border:none;">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0" id="editDealModalLabel" style="font-size:1.05rem;">
                        <i class="fas fa-pen me-2"></i> Editar Patrocínio
                    </h5>
                    <p style="color:rgba(255,255,255,.65); font-size:.78rem; margin:3px 0 0;" id="edit_modal_subtitle">—</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:28px;">
                <input type="hidden" id="edit_deal_id">
                <div class="form-field">
                    <label for="edit_company_name">Empresa ou Organização</label>
                    <input type="text" id="edit_company_name" name="company_name" required placeholder="Ex: Itaú BBA S.A.">
                </div>
                <div class="row g-3">
                    <div class="col-7">
                        <div class="form-field mb-0">
                            <label for="edit_contact_person">Pessoa de Contato</label>
                            <input type="text" id="edit_contact_person" name="contact_person" placeholder="Ex: Maria Souza">
                        </div>
                    </div>
                    <div class="col-5">
                        <div class="form-field mb-0">
                            <label for="edit_phone">Telefone / WhatsApp</label>
                            <input type="text" id="edit_phone" name="phone" placeholder="(11) 99999-0000">
                        </div>
                    </div>
                </div>
                <div class="form-field mt-3">
                    <label for="edit_email">E-mail Corporativo</label>
                    <input type="email" id="edit_email" name="email" placeholder="contato@empresa.com.br">
                </div>
                <div class="row g-3">
                    <div class="col-5">
                        <div class="form-field mb-0">
                            <label for="edit_expected_value">Valor Esperado (R$)</label>
                            <input type="text" id="edit_expected_value" name="expected_value" inputmode="numeric" placeholder="Ex: 50.000,00">
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-field mb-0">
                            <label for="edit_contact_date">Data da Reunião</label>
                            <input type="date" id="edit_contact_date" name="contact_date">
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-field mb-0">
                            <label for="edit_stage">Estágio</label>
                            <select id="edit_stage" name="stage">
                                @foreach($cols as $stageKey => $col)
                                <option value="{{ $stageKey }}">{{ $col['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-field mt-3">
                    <label for="edit_notes">Observações</label>
                    <textarea id="edit_notes" name="notes" rows="3" placeholder="Contexto, histórico, próximos passos..."></textarea>
                </div>
                <div id="edit_error" style="display:none; color:#dc2626; font-size:.82rem; margin-bottom:8px;"></div>
                <button type="button" id="btnSaveDeal" class="btn-premium w-100 mt-1" style="justify-content:center;">
                    <i class="fas fa-save me-2"></i> Salvar Alterações
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const BASE_URL = '{{ url('/ngo/sponsorships') }}';
const CSRF     = '{{ csrf_token() }}';

// ── Read card data ───────────────────────────────────────────────────────────
function cardData(card) {
    return {
        id:          card.dataset.id,
        company:     JSON.parse(card.dataset.company),
        contact:     JSON.parse(card.dataset.contact),
        email:       JSON.parse(card.dataset.email),
        phone:       JSON.parse(card.dataset.phone),
        value:       card.dataset.valueFmt,
        rawValue:    card.dataset.value,
        date:        card.dataset.contactDate,
        notes:       JSON.parse(card.dataset.notes),
        stage:       card.dataset.stage,
        created:     card.dataset.created,
    };
}

// ── Detail Modal ─────────────────────────────────────────────────────────────
let _detailCard = null;
function openDetail(card) {
    _detailCard = card;
    const d = cardData(card);
    document.getElementById('detail_avatar').textContent  = d.company.substring(0,2).toUpperCase();
    document.getElementById('detail_company').textContent = d.company;
    document.getElementById('detail_contact').textContent = d.contact || '';
    document.getElementById('detail_value').textContent   = 'R$ ' + d.value;
    document.getElementById('detail_date').textContent    = d.created;

    const showRow = (rowId, valId, val) => {
        const show = val && val.trim() !== '';
        document.getElementById(rowId).style.display = show ? 'block' : 'none';
        if (show) document.getElementById(valId).textContent = val;
    };
    showRow('detail_email_row',   'detail_email',   d.email);
    showRow('detail_phone_row',   'detail_phone',   d.phone);
    showRow('detail_meeting_row', 'detail_meeting', d.date);
    showRow('detail_notes_row',   'detail_notes',   d.notes);

    bootstrap.Modal.getOrCreateInstance(document.getElementById('detailModal')).show();
}

document.getElementById('btnOpenEditFromDetail')?.addEventListener('click', function () {
    bootstrap.Modal.getInstance(document.getElementById('detailModal'))?.hide();
    if (_detailCard) setTimeout(() => openEdit(_detailCard), 300);
});

// ── Edit Modal ───────────────────────────────────────────────────────────────
function openEdit(card) {
    const d = cardData(card);
    document.getElementById('edit_deal_id').value           = d.id;
    document.getElementById('edit_modal_subtitle').textContent = d.company;
    document.getElementById('edit_company_name').value      = d.company;
    document.getElementById('edit_contact_person').value    = d.contact;
    document.getElementById('edit_email').value             = d.email;
    document.getElementById('edit_phone').value             = d.phone;
    document.getElementById('edit_expected_value').value    = d.value;
    document.getElementById('edit_contact_date').value      = d.date;
    document.getElementById('edit_notes').value             = d.notes;
    document.getElementById('edit_stage').value             = d.stage;
    document.getElementById('edit_error').style.display     = 'none';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editDealModal')).show();
}

document.getElementById('btnSaveDeal')?.addEventListener('click', function () {
    const id       = document.getElementById('edit_deal_id').value;
    const errEl    = document.getElementById('edit_error');
    const btn      = this;
    const company  = document.getElementById('edit_company_name').value.trim();
    if (!company) { errEl.textContent = 'O nome da empresa é obrigatório.'; errEl.style.display = 'block'; return; }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Salvando…';

    const body = {
        company_name:   company,
        contact_person: document.getElementById('edit_contact_person').value,
        email:          document.getElementById('edit_email').value,
        phone:          document.getElementById('edit_phone').value,
        expected_value: document.getElementById('edit_expected_value').value,
        contact_date:   document.getElementById('edit_contact_date').value || null,
        notes:          document.getElementById('edit_notes').value,
        stage:          document.getElementById('edit_stage').value,
    };

    fetch(`${BASE_URL}/${id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify(body),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) throw new Error(data.message || 'Erro ao salvar.');
        bootstrap.Modal.getInstance(document.getElementById('editDealModal'))?.hide();
        location.reload();
    })
    .catch(err => {
        errEl.textContent = err.message;
        errEl.style.display = 'block';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-2"></i> Salvar Alterações';
    });
});

// ── Currency format (both modals) ────────────────────────────────────────────
['new_value', 'edit_expected_value'].forEach(function (id) {
    const inp = document.getElementById(id);
    if (!inp) return;
    inp.addEventListener('input', function () {
        let v = this.value.replace(/\D/g, '');
        if (!v) { this.value = ''; return; }
        v = (parseInt(v, 10) / 100).toFixed(2);
        this.value = parseFloat(v).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
    });
});

// ── Kanban Drag & Drop ───────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    let draggedCard = null;

    function bindCard(card) {
        card.addEventListener('dragstart', function (e) {
            if (e.target.closest('form') || e.target.closest('button')) { e.preventDefault(); return; }
            draggedCard = card;
            requestAnimationFrame(() => card.classList.add('is-dragging'));
        });
        card.addEventListener('dragend', function () {
            card.classList.remove('is-dragging');
            draggedCard = null;
        });
    }

    document.querySelectorAll('.k-card').forEach(bindCard);

    document.querySelectorAll('.kanban-drop-zone').forEach(zone => {
        zone.addEventListener('dragover',  function (e) { e.preventDefault(); this.classList.add('drag-over'); });
        zone.addEventListener('dragleave', function ()  { this.classList.remove('drag-over'); });
        zone.addEventListener('drop',      function (e) {
            this.classList.remove('drag-over');
            if (!draggedCard) return;
            const empty = this.querySelector('.k-empty');
            if (empty) empty.remove();
            this.appendChild(draggedCard);
            draggedCard.dataset.stage = this.dataset.stage;
            updateStage(draggedCard.dataset.id, this.dataset.stage);
        });
    });

    function updateStage(id, stage) {
        fetch(`${BASE_URL}/${id}/stage`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ stage }),
        })
        .then(r => r.json())
        .then(d => { if (!d.success) console.error('Falha ao salvar estágio'); })
        .catch(console.error);
    }

    // ── Filter & Search ───────────────────────────────────────────────────────
    const searchInput  = document.getElementById('spSearch');
    const stagePills   = document.getElementById('stagePills');
    const resultsInfo  = document.getElementById('spResultsInfo');
    let activeStage    = 'all';
    let searchQuery    = '';

    function applyFilters() {
        const q = searchQuery.toLowerCase();
        let visible = 0;

        document.querySelectorAll('.kanban-col').forEach(col => {
            const colStage = col.querySelector('.kanban-drop-zone')?.dataset.stage;
            const stageMatch = activeStage === 'all' || activeStage === colStage;
            col.style.display = stageMatch ? '' : 'none';
            if (!stageMatch) return;

            let colVisible = 0;
            col.querySelectorAll('.k-card').forEach(card => {
                const text = (JSON.parse(card.dataset.company) + ' ' + JSON.parse(card.dataset.contact)).toLowerCase();
                const match = !q || text.includes(q);
                card.style.display = match ? '' : 'none';
                if (match) { colVisible++; visible++; }
            });

            // show/hide empty state per column
            let emptyEl = col.querySelector('.k-empty');
            if (colVisible === 0 && q) {
                if (!emptyEl) {
                    emptyEl = document.createElement('div');
                    emptyEl.className = 'k-empty sp-filter-empty';
                    emptyEl.innerHTML = '<i class="fas fa-search"></i><span>Sem resultados</span>';
                    col.querySelector('.kanban-drop-zone').appendChild(emptyEl);
                } else {
                    emptyEl.style.display = '';
                }
            } else if (emptyEl && emptyEl.classList.contains('sp-filter-empty')) {
                emptyEl.style.display = 'none';
            }
        });

        resultsInfo.textContent = q ? `${visible} resultado${visible !== 1 ? 's' : ''}` : '';
    }

    searchInput?.addEventListener('input', function () {
        searchQuery = this.value.trim();
        applyFilters();
    });

    stagePills?.addEventListener('click', function (e) {
        const pill = e.target.closest('.sp-pill');
        if (!pill) return;
        stagePills.querySelectorAll('.sp-pill').forEach(p => p.classList.remove('active'));
        pill.classList.add('active');
        activeStage = pill.dataset.stage;
        applyFilters();
    });
});
</script>
@endpush
