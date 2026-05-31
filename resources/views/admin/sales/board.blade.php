@extends('layouts.app')

@section('title', 'Funil Comercial')

@section('content')
<style>
/* ── Tokens ─────────────────────────────────────────────────────────────── */
:root {
    --sl-brand:   #6366f1;
    --sl-radius:  14px;
    --sl-divider: #f1f5f9;
    --sl-shadow:  0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.04);
    --sl-shadowmd:0 4px 12px rgba(0,0,0,.10);
}

/* ── Layout ─────────────────────────────────────────────────────────────── */
.sales-page { display: flex; flex-direction: column; height: calc(100vh - 80px); padding: 0 24px 0; }

.sales-topbar {
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    padding: 18px 0 14px;
    border-bottom: 1px solid var(--sl-divider);
    flex-shrink: 0;
}
.sales-title { font-size: 1.15rem; font-weight: 700; color: #0f172a; margin: 0; flex: 1; }
.sales-funnel-total {
    font-size: .82rem; font-weight: 600; color: #15803d;
    background: #dcfce7; border-radius: 999px; padding: 4px 14px;
}
.sales-filter-bar {
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    padding: 10px 0 0;
    flex-shrink: 0;
}
.sales-filter-bar select,
.sales-filter-bar input {
    font-size: .8rem; border: 1px solid #e2e8f0; border-radius: 8px;
    padding: 5px 10px; color: #374151; outline: none;
    background: #fff; height: 34px;
}
.sales-filter-bar select:focus { border-color: var(--sl-brand); }
.btn-sl-primary {
    height: 34px; padding: 0 16px; font-size: .8rem; font-weight: 600;
    background: var(--sl-brand); color: #fff; border: none;
    border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
    transition: opacity .15s;
}
.btn-sl-primary:hover { opacity: .88; }
.btn-sl-outline {
    height: 34px; padding: 0 14px; font-size: .8rem; font-weight: 600;
    background: #fff; color: #374151; border: 1px solid #e2e8f0;
    border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
    transition: border-color .15s;
}
.btn-sl-outline:hover { border-color: var(--sl-brand); color: var(--sl-brand); }
.import-badge {
    background: #ef4444; color: #fff; font-size: .65rem; font-weight: 700;
    border-radius: 999px; padding: 1px 6px; margin-left: 2px;
}

/* ── Kanban board (reusa padrão do kanban de projetos) ──────────────────── */
.kanban-wrapper { flex: 1; overflow: hidden; margin-top: 14px; }
.kanban-board-scroll {
    display: flex; gap: 16px; padding-bottom: 24px;
    height: 100%; overflow-x: auto; overflow-y: hidden;
}
.kanban-board-scroll::-webkit-scrollbar { height: 6px; }
.kanban-board-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 6px; }

.kanban-column-prem {
    flex: 0 0 280px;
    background: #f8fafc;
    border-radius: 20px;
    border: 1px solid var(--sl-divider);
    display: flex; flex-direction: column;
    max-height: 100%;
    transition: border-color .2s;
}
.kanban-column-prem:hover { border-color: #e2e8f0; }

.column-header-prem {
    padding: 14px 16px 10px;
    display: flex; align-items: center; gap: 8px;
    flex-shrink: 0;
}
.col-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.col-name { font-size: .78rem; font-weight: 700; color: #374151; flex: 1; }
.column-count-badge {
    background: #e2e8f0; color: #475569; font-size: .68rem; font-weight: 700;
    border-radius: 999px; padding: 2px 8px;
}
.col-sum { font-size: .68rem; color: #64748b; padding: 0 16px 8px; }

.column-body-prem {
    flex: 1; overflow-y: auto; padding: 0 12px 16px;
    display: flex; flex-direction: column; gap: 8px;
    min-height: 60px;
}
.column-body-prem::-webkit-scrollbar { width: 3px; }
.column-body-prem::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 3px; }
.column-body-prem.drag-over {
    background: rgba(99,102,241,.04);
    border-radius: 0 0 20px 20px;
    outline: 2px dashed #c7d2fe;
}

.col-add-btn {
    width: 100%; margin: 4px 0 0; padding: 7px;
    background: transparent; border: 1.5px dashed #cbd5e1;
    border-radius: 10px; color: #94a3b8; font-size: .75rem; font-weight: 600;
    cursor: pointer; transition: all .15s;
}
.col-add-btn:hover { border-color: var(--sl-brand); color: var(--sl-brand); background: rgba(99,102,241,.04); }

/* ── Lead card ──────────────────────────────────────────────────────────── */
.lead-card-prem {
    background: #fff; border-radius: 12px;
    border: 1px solid #e8ecf0;
    padding: 12px 14px; cursor: pointer;
    box-shadow: var(--sl-shadow);
    transition: transform .15s, box-shadow .15s, border-color .15s;
    user-select: none;
}
.lead-card-prem:hover {
    transform: translateY(-2px);
    box-shadow: var(--sl-shadowmd);
    border-color: #c7d2fe;
}
.lead-card-prem[draggable="true"]:active { opacity: .7; transform: scale(1.02) rotate(1deg); }

.lc-top { display: flex; gap: 5px; flex-wrap: wrap; margin-bottom: 7px; }
.lc-badge { font-size: .62rem; font-weight: 700; border-radius: 6px; padding: 2px 7px; }
.lc-name { font-size: .84rem; font-weight: 700; color: #0f172a; margin-bottom: 3px; line-height: 1.3; }
.lc-company { font-size: .73rem; color: #64748b; margin-bottom: 6px; display: flex; align-items: center; gap: 4px; }
.lc-meta { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; flex-wrap: wrap; }
.lc-value { font-size: .73rem; font-weight: 700; color: #15803d; display: flex; align-items: center; gap: 4px; }
.lc-value .fas { font-size: .6rem; }
.lc-resp { font-size: .7rem; color: #64748b; display: flex; align-items: center; gap: 4px; }
.lc-next { font-size: .7rem; color: #64748b; display: flex; align-items: center; gap: 5px; margin-top: 2px; }
.lc-date { font-size: .65rem; font-weight: 600; background: #f1f5f9; border-radius: 4px; padding: 1px 5px; margin-left: auto; }
.lc-date-late { background: #fee2e2; color: #dc2626; }

/* ── Modals ─────────────────────────────────────────────────────────────── */
.sl-modal-overlay {
    display: none; position: fixed; inset: 0; background: rgba(15,23,42,.45);
    z-index: 9000; align-items: center; justify-content: center; padding: 16px;
}
.sl-modal-overlay.open { display: flex; }
.sl-modal {
    background: #fff; border-radius: 20px; width: 100%; max-width: 560px;
    max-height: 90vh; overflow-y: auto; box-shadow: 0 24px 48px rgba(0,0,0,.18);
    padding: 28px 28px 24px;
}
.sl-modal-lg { max-width: 720px; }
.sl-modal-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px;
}
.sl-modal-title { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; }
.sl-modal-close {
    width: 32px; height: 32px; border: none; background: #f1f5f9;
    border-radius: 8px; cursor: pointer; font-size: 1rem; color: #64748b;
    display: flex; align-items: center; justify-content: center;
}
.sl-modal-close:hover { background: #e2e8f0; }

.sl-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.sl-form-grid.full { grid-template-columns: 1fr; }
.sl-field { display: flex; flex-direction: column; gap: 5px; }
.sl-field.span2 { grid-column: span 2; }
.sl-label { font-size: .72rem; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: .04em; }
.sl-input, .sl-select, .sl-textarea {
    border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 12px;
    font-size: .84rem; color: #0f172a; outline: none; transition: border-color .15s;
    font-family: inherit; width: 100%; box-sizing: border-box;
}
.sl-input:focus, .sl-select:focus, .sl-textarea:focus { border-color: var(--sl-brand); }
.sl-textarea { resize: vertical; min-height: 72px; }

.sl-modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--sl-divider); }
.btn-sl-cancel { height: 38px; padding: 0 18px; font-size: .82rem; font-weight: 600; background: #f8fafc; color: #374151; border: 1px solid #e2e8f0; border-radius: 10px; cursor: pointer; }
.btn-sl-cancel:hover { background: #f1f5f9; }
.btn-sl-save { height: 38px; padding: 0 22px; font-size: .82rem; font-weight: 600; background: var(--sl-brand); color: #fff; border: none; border-radius: 10px; cursor: pointer; transition: opacity .15s; }
.btn-sl-save:hover { opacity: .88; }
.btn-sl-danger { height: 38px; padding: 0 18px; font-size: .82rem; font-weight: 600; background: #fee2e2; color: #dc2626; border: none; border-radius: 10px; cursor: pointer; }

/* Timeline */
.sl-timeline { display: flex; flex-direction: column; gap: 0; }
.sl-tl-item { display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--sl-divider); }
.sl-tl-item:last-child { border-bottom: none; }
.sl-tl-dot { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .7rem; flex-shrink: 0; margin-top: 1px; }
.sl-tl-dot.created      { background: #dbeafe; color: #1d4ed8; }
.sl-tl-dot.stage_changed{ background: #ede9fe; color: #7c3aed; }
.sl-tl-dot.note_added   { background: #fef9c3; color: #854d0e; }
.sl-tl-dot.converted    { background: #dcfce7; color: #15803d; }
.sl-tl-body { flex: 1; }
.sl-tl-content { font-size: .8rem; color: #374151; }
.sl-tl-meta { font-size: .68rem; color: #94a3b8; margin-top: 2px; }

/* Conversion modal */
.conv-option { border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; margin-bottom: 10px; cursor: pointer; transition: border-color .15s; }
.conv-option:hover { border-color: var(--sl-brand); }
.conv-option-title { font-size: .84rem; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
.conv-option-desc  { font-size: .75rem; color: #64748b; }
.conv-copy-box {
    display: flex; gap: 8px; align-items: center;
    background: #f8fafc; border-radius: 8px; padding: 8px 12px;
    border: 1px solid #e2e8f0; margin-top: 8px;
}
.conv-copy-url { flex: 1; font-size: .73rem; color: #374151; word-break: break-all; }
.conv-copy-btn { font-size: .73rem; font-weight: 600; color: var(--sl-brand); background: none; border: none; cursor: pointer; white-space: nowrap; }
</style>

<div class="sales-page">

    {{-- Topbar --}}
    <div class="sales-topbar">
        <h1 class="sales-title">
            <i class="fas fa-funnel-dollar me-2" style="color:var(--sl-brand);"></i>
            Funil Comercial
        </h1>
        <span class="sales-funnel-total">
            <i class="fas fa-dollar-sign me-1"></i>
            R$ {{ number_format($totalFunnel, 0, ',', '.') }} em aberto
        </span>
        @if($pendingBookings > 0)
        <button class="btn-sl-outline" onclick="importBookings()">
            <i class="fas fa-calendar-import"></i>
            Importar Demos
            <span class="import-badge">{{ $pendingBookings }}</span>
        </button>
        @endif
        <button class="btn-sl-primary" onclick="openNewLead()">
            <i class="fas fa-plus"></i> Novo Lead
        </button>
    </div>

    {{-- Filtros --}}
    <div class="sales-filter-bar">
        <select id="filter-responsible" onchange="applyFilter()" class="sl-select" style="height:34px;">
            <option value="">Todos os responsáveis</option>
            @foreach($admins as $admin)
            <option value="{{ $admin->id }}" {{ request('responsible') == $admin->id ? 'selected' : '' }}>
                {{ $admin->name }}
            </option>
            @endforeach
        </select>
        <select id="filter-origin" onchange="applyFilter()" class="sl-select" style="height:34px;">
            <option value="">Todas as origens</option>
            <option value="manual"        {{ request('origin') === 'manual'        ? 'selected' : '' }}>Manual</option>
            <option value="demo_agendada" {{ request('origin') === 'demo_agendada' ? 'selected' : '' }}>Demo Agendada</option>
            <option value="indicacao"     {{ request('origin') === 'indicacao'     ? 'selected' : '' }}>Indicação</option>
        </select>
        @if(request('responsible') || request('origin'))
        <a href="{{ route('admin.sales.board') }}" class="btn-sl-outline" style="text-decoration:none;">
            <i class="fas fa-times"></i> Limpar
        </a>
        @endif
    </div>

    {{-- Board --}}
    <div class="kanban-wrapper">
        <div class="kanban-board-scroll">
            @foreach($stages as $stage)
            @php
                $colSum = $stage->leads->sum('estimated_value');
            @endphp
            <div class="kanban-column-prem" id="col-{{ $stage->id }}">
                <div class="column-header-prem">
                    <div class="col-dot" style="background:{{ $stage->color }};"></div>
                    <span class="col-name">{{ $stage->name }}</span>
                    <span class="column-count-badge" data-colcount="{{ $stage->id }}">{{ $stage->leads->count() }}</span>
                </div>
                @if($colSum > 0)
                <div class="col-sum">R$ {{ number_format($colSum, 0, ',', '.') }}</div>
                @endif
                <div class="column-body-prem"
                     data-stage="{{ $stage->id }}"
                     ondrop="drop(event, {{ $stage->id }})"
                     ondragover="allowDrop(event)"
                     ondragleave="dragLeave(event)">
                    @foreach($stage->leads as $lead)
                        @include('admin.sales.partials.lead_card', ['lead' => $lead])
                    @endforeach
                    <button class="col-add-btn" onclick="openNewLead({{ $stage->id }})">
                        <i class="fas fa-plus me-1"></i> Novo lead
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ── Modal: Novo Lead ─────────────────────────────────────────────────── --}}
<div class="sl-modal-overlay" id="modalNewLead">
    <div class="sl-modal">
        <div class="sl-modal-header">
            <h2 class="sl-modal-title"><i class="fas fa-user-plus me-2" style="color:var(--sl-brand);"></i>Novo Lead</h2>
            <button class="sl-modal-close" onclick="closeModal('modalNewLead')">✕</button>
        </div>
        <form id="formNewLead" onsubmit="saveLead(event)">
            <div class="sl-form-grid">
                <div class="sl-field span2">
                    <label class="sl-label">Nome *</label>
                    <input type="text" name="name" class="sl-input" required placeholder="Nome do contato">
                </div>
                <div class="sl-field">
                    <label class="sl-label">Empresa</label>
                    <input type="text" name="company" class="sl-input" placeholder="Empresa / organização">
                </div>
                <div class="sl-field">
                    <label class="sl-label">Origem *</label>
                    <select name="origin" class="sl-select" required>
                        <option value="manual">Manual</option>
                        <option value="demo_agendada">Demo Agendada</option>
                        <option value="indicacao">Indicação</option>
                    </select>
                </div>
                <div class="sl-field">
                    <label class="sl-label">E-mail</label>
                    <input type="email" name="email" class="sl-input" placeholder="email@exemplo.com">
                </div>
                <div class="sl-field">
                    <label class="sl-label">WhatsApp</label>
                    <input type="text" name="phone" class="sl-input" placeholder="+55 (11) 99999-9999">
                </div>
                <div class="sl-field">
                    <label class="sl-label">Plano de interesse</label>
                    <select name="plan_id" class="sl-select">
                        <option value="">— nenhum —</option>
                        @foreach($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sl-field">
                    <label class="sl-label">Valor estimado (R$)</label>
                    <input type="number" name="estimated_value" class="sl-input" placeholder="0,00" step="0.01" min="0">
                </div>
                <div class="sl-field">
                    <label class="sl-label">Responsável</label>
                    <select name="responsible_id" class="sl-select">
                        <option value="">— nenhum —</option>
                        @foreach($admins as $admin)
                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sl-field">
                    <label class="sl-label">Estágio *</label>
                    <select name="stage_id" id="newLeadStage" class="sl-select" required>
                        @foreach($stages as $stage)
                        <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sl-field">
                    <label class="sl-label">Próximo passo</label>
                    <input type="text" name="next_step" class="sl-input" placeholder="Ex: Enviar proposta">
                </div>
                <div class="sl-field">
                    <label class="sl-label">Data do próximo passo</label>
                    <input type="date" name="next_step_date" class="sl-input">
                </div>
                <div class="sl-field span2">
                    <label class="sl-label">Observações</label>
                    <textarea name="notes" class="sl-textarea" placeholder="Notas internas sobre este lead..."></textarea>
                </div>
            </div>
            <div class="sl-modal-footer">
                <button type="button" class="btn-sl-cancel" onclick="closeModal('modalNewLead')">Cancelar</button>
                <button type="submit" class="btn-sl-save" id="btnSaveLead">
                    <i class="fas fa-check me-1"></i> Criar Lead
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal: Detalhe / Editar Lead ───────────────────────────────────────── --}}
<div class="sl-modal-overlay" id="modalLead">
    <div class="sl-modal sl-modal-lg">
        <div class="sl-modal-header">
            <h2 class="sl-modal-title" id="detailLeadTitle">Lead</h2>
            <button class="sl-modal-close" onclick="closeModal('modalLead')">✕</button>
        </div>
        <div style="display:grid;grid-template-columns:1fr 280px;gap:20px;">
            {{-- Formulário de edição --}}
            <div>
                <form id="formEditLead" onsubmit="updateLead(event)">
                    <input type="hidden" id="editLeadId">
                    <div class="sl-form-grid">
                        <div class="sl-field span2">
                            <label class="sl-label">Nome *</label>
                            <input type="text" name="name" id="editName" class="sl-input" required>
                        </div>
                        <div class="sl-field">
                            <label class="sl-label">Empresa</label>
                            <input type="text" name="company" id="editCompany" class="sl-input">
                        </div>
                        <div class="sl-field">
                            <label class="sl-label">Origem</label>
                            <select name="origin" id="editOrigin" class="sl-select">
                                <option value="manual">Manual</option>
                                <option value="demo_agendada">Demo Agendada</option>
                                <option value="indicacao">Indicação</option>
                            </select>
                        </div>
                        <div class="sl-field">
                            <label class="sl-label">E-mail</label>
                            <input type="email" name="email" id="editEmail" class="sl-input">
                        </div>
                        <div class="sl-field">
                            <label class="sl-label">WhatsApp</label>
                            <input type="text" name="phone" id="editPhone" class="sl-input">
                        </div>
                        <div class="sl-field">
                            <label class="sl-label">Plano de interesse</label>
                            <select name="plan_id" id="editPlan" class="sl-select">
                                <option value="">— nenhum —</option>
                                @foreach($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sl-field">
                            <label class="sl-label">Valor estimado (R$)</label>
                            <input type="number" name="estimated_value" id="editValue" class="sl-input" step="0.01" min="0">
                        </div>
                        <div class="sl-field">
                            <label class="sl-label">Responsável</label>
                            <select name="responsible_id" id="editResponsible" class="sl-select">
                                <option value="">— nenhum —</option>
                                @foreach($admins as $admin)
                                <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sl-field">
                            <label class="sl-label">Próximo passo</label>
                            <input type="text" name="next_step" id="editNextStep" class="sl-input">
                        </div>
                        <div class="sl-field">
                            <label class="sl-label">Data</label>
                            <input type="date" name="next_step_date" id="editNextDate" class="sl-input">
                        </div>
                        <div class="sl-field span2">
                            <label class="sl-label">Observações</label>
                            <textarea name="notes" id="editNotes" class="sl-textarea"></textarea>
                        </div>
                    </div>
                    <div class="sl-modal-footer" style="margin-top:14px;">
                        <button type="button" class="btn-sl-danger" onclick="deleteLead()">
                            <i class="fas fa-trash me-1"></i> Excluir
                        </button>
                        <button type="submit" class="btn-sl-save">
                            <i class="fas fa-save me-1"></i> Salvar
                        </button>
                    </div>
                </form>
            </div>
            {{-- Timeline --}}
            <div>
                <div style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">
                    Histórico
                </div>
                <div id="leadTimeline" class="sl-timeline"></div>
                <div style="margin-top:12px;">
                    <textarea id="noteContent" class="sl-textarea" placeholder="Adicionar anotação..." style="min-height:56px;font-size:.8rem;"></textarea>
                    <button class="btn-sl-primary" style="width:100%;margin-top:6px;height:32px;font-size:.77rem;" onclick="addNote()">
                        <i class="fas fa-plus me-1"></i> Adicionar nota
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal: Conversão (Ganho) ────────────────────────────────────────────── --}}
<div class="sl-modal-overlay" id="modalConvert">
    <div class="sl-modal" style="max-width:480px;">
        <div class="sl-modal-header">
            <h2 class="sl-modal-title">
                <i class="fas fa-trophy me-2" style="color:#22c55e;"></i>
                Lead em Ganho! 🎉
            </h2>
            <button class="sl-modal-close" onclick="closeModal('modalConvert')">✕</button>
        </div>
        <p style="font-size:.84rem;color:#64748b;margin:0 0 16px;">
            Deseja registrar a conversão agora? Você pode pular e fazer depois.
        </p>

        {{-- Opção A: vincular tenant existente --}}
        <div class="conv-option">
            <div class="conv-option-title"><i class="fas fa-link me-2" style="color:var(--sl-brand);"></i>Vincular a tenant existente</div>
            <div class="conv-option-desc">O cliente já criou uma conta? Associe o lead ao tenant.</div>
            <select id="convertTenantSelect" class="sl-select" style="margin-top:10px;">
                <option value="">— selecione o tenant —</option>
                @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}">{{ $tenant->name }} ({{ $tenant->email ?? '' }})</option>
                @endforeach
            </select>
            <button class="btn-sl-save" style="width:100%;margin-top:10px;height:36px;" onclick="convertLead()">
                <i class="fas fa-check me-1"></i> Confirmar vínculo
            </button>
        </div>

        {{-- Opção C: link de registro --}}
        <div class="conv-option">
            <div class="conv-option-title"><i class="fas fa-link me-2" style="color:#f59e0b;"></i>Enviar link de cadastro</div>
            <div class="conv-option-desc">Ainda não tem conta. Copie o link pré-preenchido e envie ao lead.</div>
            <div class="conv-copy-box">
                <span class="conv-copy-url" id="convRegisterLink">—</span>
                <button class="conv-copy-btn" onclick="copyRegisterLink()"><i class="fas fa-copy me-1"></i>Copiar</button>
            </div>
        </div>

        <div class="sl-modal-footer" style="border-top:none;padding-top:4px;">
            <button class="btn-sl-cancel" onclick="closeModal('modalConvert')">Pular por agora</button>
        </div>
    </div>
</div>

<script>
const csrfToken    = '{{ csrf_token() }}';
const boardBaseUrl = '{{ url("/admin/sales") }}';
const registerUrl  = '{{ url("/register") }}';

let draggingEl       = null;
let draggingFromCol  = null;
let currentLeadId    = null;
let pendingConvertId = null;

// ── Drag & Drop (padrão idêntico ao kanban de projetos) ─────────────────

function allowDrop(ev) {
    ev.preventDefault();
    ev.currentTarget.classList.add('drag-over');
}

function dragLeave(ev) {
    if (ev && ev.currentTarget) ev.currentTarget.classList.remove('drag-over');
}

function drag(ev) {
    draggingEl      = ev.target.closest('.lead-card-prem');
    draggingFromCol = draggingEl.closest('.column-body-prem');
    ev.dataTransfer.setData('text', draggingEl.id);
    draggingEl.style.opacity   = '0.5';
    draggingEl.style.transform = 'scale(1.03) rotate(1deg)';
}

function drop(ev, stageId) {
    ev.preventDefault();
    ev.currentTarget.classList.remove('drag-over');

    const data = ev.dataTransfer.getData('text');
    const el   = document.getElementById(data);
    if (!el) return;

    const addBtn = ev.currentTarget.querySelector('.col-add-btn');
    if (addBtn) ev.currentTarget.insertBefore(el, addBtn);
    else        ev.currentTarget.appendChild(el);

    el.style.opacity   = '1';
    el.style.transform = 'none';

    const leadId  = data.replace('lead-', '');
    const colBody = ev.currentTarget;
    const position = Array.from(colBody.querySelectorAll('.lead-card-prem')).indexOf(el);

    updateCounts();

    fetch(`${boardBaseUrl}/leads/${leadId}/move`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ stage_id: stageId, position: position }),
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            if (draggingFromCol) draggingFromCol.appendChild(el);
            updateCounts();
            alert('Falha ao mover o lead.');
            return;
        }
        el.dataset.stage = stageId;
        if (res.needs_conversion) {
            pendingConvertId = leadId;
            openConvertModal(leadId);
        }
    })
    .catch(() => {
        if (draggingFromCol) draggingFromCol.appendChild(el);
        updateCounts();
        alert('Falha de conexão ao mover o lead.');
    });
}

document.addEventListener('dragend', () => {
    document.querySelectorAll('.lead-card-prem').forEach(c => {
        c.style.opacity   = '1';
        c.style.transform = 'none';
    });
    document.querySelectorAll('.column-body-prem').forEach(c => c.classList.remove('drag-over'));
});

function updateCounts() {
    document.querySelectorAll('.column-body-prem[data-stage]').forEach(col => {
        const stageId = col.dataset.stage;
        const badge   = document.querySelector(`[data-colcount="${stageId}"]`);
        if (badge) badge.textContent = col.querySelectorAll('.lead-card-prem').length;
    });
}

// ── Filtros ──────────────────────────────────────────────────────────────

function applyFilter() {
    const responsible = document.getElementById('filter-responsible').value;
    const origin      = document.getElementById('filter-origin').value;
    const params      = new URLSearchParams();
    if (responsible) params.set('responsible', responsible);
    if (origin)      params.set('origin', origin);
    window.location.href = '{{ route("admin.sales.board") }}' + (params.toString() ? '?' + params : '');
}

// ── Modal helpers ─────────────────────────────────────────────────────────

function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('.sl-modal-overlay').forEach(ov => {
    ov.addEventListener('click', e => { if (e.target === ov) ov.classList.remove('open'); });
});

// ── Novo Lead ─────────────────────────────────────────────────────────────

function openNewLead(stageId = null) {
    document.getElementById('formNewLead').reset();
    if (stageId) document.getElementById('newLeadStage').value = stageId;
    openModal('modalNewLead');
}

function saveLead(ev) {
    ev.preventDefault();
    const btn  = document.getElementById('btnSaveLead');
    const form = document.getElementById('formNewLead');
    const data = Object.fromEntries(new FormData(form));

    btn.disabled    = true;
    btn.textContent = 'Salvando…';

    fetch(`${boardBaseUrl}/leads`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify(data),
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) { closeModal('modalNewLead'); window.location.reload(); }
        else alert('Erro ao criar lead.');
    })
    .catch(() => alert('Falha de conexão.'))
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check me-1"></i> Criar Lead'; });
}

// ── Detalhe / Editar ──────────────────────────────────────────────────────

function openLead(leadId) {
    currentLeadId = leadId;
    fetch(`${boardBaseUrl}/leads/${leadId}`, { headers: { 'X-CSRF-TOKEN': csrfToken } })
    .then(r => r.json())
    .then(({ lead, activities }) => {
        document.getElementById('detailLeadTitle').textContent = lead.name;
        document.getElementById('editLeadId').value     = lead.id;
        document.getElementById('editName').value       = lead.name    || '';
        document.getElementById('editCompany').value    = lead.company || '';
        document.getElementById('editOrigin').value     = lead.origin  || 'manual';
        document.getElementById('editEmail').value      = lead.email   || '';
        document.getElementById('editPhone').value      = lead.phone   || '';
        document.getElementById('editPlan').value       = lead.plan_id || '';
        document.getElementById('editValue').value      = lead.estimated_value || '';
        document.getElementById('editResponsible').value = lead.responsible_id || '';
        document.getElementById('editNextStep').value   = lead.next_step      || '';
        document.getElementById('editNextDate').value   = lead.next_step_date ? lead.next_step_date.substring(0, 10) : '';
        document.getElementById('editNotes').value      = lead.notes   || '';
        document.getElementById('noteContent').value    = '';

        renderTimeline(activities);
        openModal('modalLead');
    })
    .catch(() => alert('Falha ao carregar lead.'));
}

function renderTimeline(activities) {
    const icons = {
        created:       { icon: 'fas fa-star',          cls: 'created' },
        stage_changed: { icon: 'fas fa-arrows-alt-h',  cls: 'stage_changed' },
        note_added:    { icon: 'fas fa-sticky-note',   cls: 'note_added' },
        converted:     { icon: 'fas fa-trophy',        cls: 'converted' },
    };
    const tl = document.getElementById('leadTimeline');
    if (!activities.length) {
        tl.innerHTML = '<div style="font-size:.75rem;color:#94a3b8;text-align:center;padding:12px 0;">Sem atividades ainda.</div>';
        return;
    }
    tl.innerHTML = activities.map(a => {
        const ic = icons[a.type] || icons.created;
        const d  = new Date(a.created_at);
        const ds = d.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' }) + ' ' +
                   d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        const author = a.user ? a.user.name.split(' ')[0] : 'Sistema';
        return `<div class="sl-tl-item">
            <div class="sl-tl-dot ${ic.cls}"><i class="${ic.icon}"></i></div>
            <div class="sl-tl-body">
                <div class="sl-tl-content">${escHtml(a.content || '')}</div>
                <div class="sl-tl-meta">${ds} · ${escHtml(author)}</div>
            </div>
        </div>`;
    }).join('');
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function updateLead(ev) {
    ev.preventDefault();
    const id   = document.getElementById('editLeadId').value;
    const form = document.getElementById('formEditLead');
    const data = Object.fromEntries(new FormData(form));

    fetch(`${boardBaseUrl}/leads/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify(data),
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) { closeModal('modalLead'); window.location.reload(); }
        else alert('Erro ao salvar.');
    })
    .catch(() => alert('Falha de conexão.'));
}

function deleteLead() {
    if (!confirm('Excluir este lead? A ação pode ser desfeita.')) return;
    const id = document.getElementById('editLeadId').value;
    fetch(`${boardBaseUrl}/leads/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken },
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) { closeModal('modalLead'); window.location.reload(); }
    });
}

function addNote() {
    const id      = document.getElementById('editLeadId').value;
    const content = document.getElementById('noteContent').value.trim();
    if (!content) return;

    fetch(`${boardBaseUrl}/leads/${id}/note`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ content }),
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) openLead(id);
    });
}

// ── Conversão (Ganho) ─────────────────────────────────────────────────────

function openConvertModal(leadId) {
    pendingConvertId = leadId;
    // Monta o link de registro pré-preenchido
    const card = document.getElementById('lead-' + leadId);
    // Pega dados do modal de detalhe se estiver aberto, senão abre primeiro
    fetch(`${boardBaseUrl}/leads/${leadId}`, { headers: { 'X-CSRF-TOKEN': csrfToken } })
    .then(r => r.json())
    .then(({ lead }) => {
        const params = new URLSearchParams();
        if (lead.name)  params.set('name',  lead.name);
        if (lead.email) params.set('email', lead.email);
        if (lead.phone) params.set('phone', lead.phone);
        if (lead.plan_id) params.set('plan_id', lead.plan_id);
        document.getElementById('convRegisterLink').textContent =
            registerUrl + (params.toString() ? '?' + params : '');
        document.getElementById('convertTenantSelect').value = '';
        openModal('modalConvert');
    });
}

function convertLead() {
    const tenantId = document.getElementById('convertTenantSelect').value || null;
    fetch(`${boardBaseUrl}/leads/${pendingConvertId}/convert`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ tenant_id: tenantId }),
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) { closeModal('modalConvert'); window.location.reload(); }
    });
}

function copyRegisterLink() {
    const url = document.getElementById('convRegisterLink').textContent;
    navigator.clipboard.writeText(url).then(() => {
        const btn = document.querySelector('.conv-copy-btn');
        btn.textContent = 'Copiado!';
        setTimeout(() => btn.innerHTML = '<i class="fas fa-copy me-1"></i>Copiar', 2000);
    });
}

// ── Importar Bookings ─────────────────────────────────────────────────────

function importBookings() {
    if (!confirm('Importar demos pendentes da agenda como leads no funil?')) return;
    fetch('{{ route("admin.sales.import.bookings") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert(`✅ ${res.imported} lead(s) importado(s). ${res.skipped} ignorado(s) por duplicidade.`);
            window.location.reload();
        }
    })
    .catch(() => alert('Falha de conexão.'));
}
</script>
@endsection
