@extends('layouts.app')

@section('content')
@php
    $basePath = rtrim(request()->getBaseUrl(), '/');
@endphp
<div class="header-page" style="margin-bottom: 40px; position: relative;">
    <div style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%); position: absolute; top: -30px; left: -30px; right: -30px; bottom: 0; z-index: -1;"></div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Ecossistema Vivensi</h6>
            </div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.5rem; letter-spacing: -1.5px;">Portfólio de Projetos</h2>
            <p style="color: #64748b; margin: 8px 0 0 0; font-size: 1.1rem; font-weight: 500;">Gerencie suas iniciativas com inteligência e controle total.</p>
        </div>
        <a href="{{ $basePath . '/projects/create' }}" class="btn-premium btn-premium-shine" style="border: none; padding: 14px 28px; font-weight: 800; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-plus-circle"></i> Iniciar Projeto
        </a>
    </div>
</div>

@if(session('success'))
    <div style="background: #ecfdf5; color: #065f46; padding: 20px; border-radius: 16px; margin-bottom: 30px; border: 1px solid #a7f3d0; font-weight: 700; display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i> {{ session('success') }}
    </div>
@endif

<div class="vivensi-card mb-3" style="padding: 16px 18px; border-radius: 18px;">
    <div style="display:flex; gap: 12px; align-items:center; justify-content: space-between; flex-wrap: wrap;">
        <div style="display:flex; gap: 10px; align-items:center; flex: 1; min-width: 260px;">
            <div style="position: relative; flex: 1;">
                <i class="fas fa-search" style="position:absolute; left: 14px; top: 12px; color:#94a3b8;"></i>
                <input id="projectsSearch" type="text" placeholder="Buscar por nome ou descrição..."
                       style="width: 100%; padding: 10px 12px 10px 38px; border-radius: 12px; border:1px solid #e2e8f0; background:#fff; font-weight: 700; color:#0f172a;">
            </div>
            <button type="button" onclick="clearProjectsFilters()" class="btn-outline" style="padding: 8px 12px; border-radius: 12px; font-weight: 900;">
                Limpar
            </button>
        </div>

        <div style="display:flex; gap: 8px; flex-wrap: wrap; align-items:center;">
            <button type="button" class="btn-outline" data-pfilter="all" onclick="setProjectsFilter(this)" style="padding: 8px 12px; border-radius: 999px; font-weight: 900;">Todos</button>
            <button type="button" class="btn-outline" data-pfilter="active" onclick="setProjectsFilter(this)" style="padding: 8px 12px; border-radius: 999px; font-weight: 900;">Em Execução</button>
            <button type="button" class="btn-outline" data-pfilter="paused" onclick="setProjectsFilter(this)" style="padding: 8px 12px; border-radius: 999px; font-weight: 900;">Pausados</button>
            <button type="button" class="btn-outline" data-pfilter="completed" onclick="setProjectsFilter(this)" style="padding: 8px 12px; border-radius: 999px; font-weight: 900;">Concluídos</button>
            <button type="button" class="btn-outline" data-pfilter="canceled" onclick="setProjectsFilter(this)" style="padding: 8px 12px; border-radius: 999px; font-weight: 900;">Cancelados</button>
            <span style="width:1px; height: 26px; background:#e2e8f0; margin: 0 2px;"></span>
            <button type="button" class="btn-outline" data-pflag="over_budget" onclick="setProjectsFlag(this)" style="padding: 8px 12px; border-radius: 999px; font-weight: 900;">Budget estourado</button>
            <select id="projectsSort" onchange="setProjectsSort(this.value)" style="padding: 8px 12px; border-radius: 999px; border:1px solid #e2e8f0; background:#fff; font-weight: 900; color:#0f172a;">
                <option value="default">Ordenar: Padrão</option>
                <option value="urgent">Ordenar: Urgente</option>
                <option value="deadline">Ordenar: Deadline</option>
                <option value="budget">Ordenar: Uso do Budget</option>
            </select>
        </div>
    </div>

    <div id="projectsFilterHint" style="margin-top: 10px; color:#94a3b8; font-weight:800; font-size:.85rem;"></div>
</div>

<div id="projectsGrid" class="projects-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 30px;">
    @forelse($projects as $project)
        @php
            $spent = (float) ($spentByProject[$project->id] ?? 0);
            $budget = (float) ($project->budget ?? 0);
            $budgetPctRaw = $budget > 0 ? (($spent / $budget) * 100) : 0;
            $budgetPct = min(100, max(0, $budgetPctRaw));
            $searchText = mb_strtolower(trim(($project->name ?? '') . ' ' . ($project->description ?? '')));
            $rowStatus = (string) ($project->status ?? 'active');
            $daysLeft = $project->end_date
                ? \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($project->end_date)->startOfDay(), false)
                : null;
            $deadlineDays = $daysLeft === null ? 99999 : (int) $daysLeft;
            $isOverBudget = $budgetPctRaw > 100.0001;
            $isDeadlineSoon = $daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 7;
            $isDeadlineOverdue = $daysLeft !== null && $daysLeft < 0;
        @endphp
        <div class="project-card"
             data-status="{{ $rowStatus }}"
             data-search="{{ e($searchText) }}"
             data-budgetpctraw="{{ number_format($budgetPctRaw, 4, '.', '') }}"
             data-deadline-days="{{ (int) $deadlineDays }}"
             data-order="{{ (int) $loop->index }}"
             style="background: white; border-radius: 28px; box-shadow: 0 15px 45px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; padding: 35px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden;"
             onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 30px 60px rgba(0,0,0,0.05)'; this.style.borderColor='var(--primary-color)';"
             onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 15px 45px rgba(0,0,0,0.02)'; this.style.borderColor='#f1f5f9';">
            
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px;">
                <div class="project-icon" style="width: 56px; height: 56px; background: #eef2ff; color: var(--primary-color); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; border: 1px solid #e0e7ff;">
                    <i class="fas fa-rocket"></i>
                </div>
                <div style="display:flex; flex-direction: column; align-items:flex-end; gap: 8px;">
                     @php
                        $statusColors = [
                            'active' => ['bg' => '#ecfdf5', 'text' => '#10b981', 'label' => '🚀 Em Execução'],
                            'paused' => ['bg' => '#fff7ed', 'text' => '#f59e0b', 'label' => '⏸️ Pausado'],
                            'completed' => ['bg' => '#eff6ff', 'text' => '#3b82f6', 'label' => '✅ Concluído'],
                            'canceled' => ['bg' => '#fee2e2', 'text' => '#ef4444', 'label' => '⛔ Cancelado'],
                        ];
                        $st = $statusColors[$project->status] ?? ['bg' => '#f1f5f9', 'text' => '#64748b', 'label' => $project->status];
                    @endphp
                    <span class="status-badge" style="background: {{ $st['bg'] }}; color: {{ $st['text'] }}; padding: 8px 16px; border-radius: 12px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
                        {{ $st['label'] }}
                    </span>

                    @if($isOverBudget)
                        <span style="background: #fee2e2; color: #b91c1c; padding: 6px 12px; border-radius: 999px; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; border: 1px solid #fecaca;">
                            Budget estourado
                        </span>
                    @endif

                    @if($isDeadlineOverdue)
                        <span style="background: #fff1f2; color: #be123c; padding: 6px 12px; border-radius: 999px; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; border: 1px solid #ffe4e6;">
                            Atrasado {{ abs((int) $daysLeft) }}d
                        </span>
                    @elseif($isDeadlineSoon)
                        <span style="background: #fff7ed; color: #c2410c; padding: 6px 12px; border-radius: 999px; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; border: 1px solid #ffedd5;">
                            Vence em {{ (int) $daysLeft }}d
                        </span>
                    @endif
                </div>
            </div>
            
            <h4 style="margin: 0 0 12px 0; font-size: 1.4rem; color: #1e293b; font-weight: 900; letter-spacing: -0.5px;">{{ $project->name }}</h4>
            <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 25px; line-height: 1.6; font-weight: 500;">
                {{ Str::limit($project->description, 100) }}
            </p>
            
            <div style="margin-bottom: 25px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Uso do Budget</span>
                    <span style="font-size: 0.75rem; color: #1e293b; font-weight: 900;">
                        {{ number_format($isOverBudget ? min($budgetPctRaw, 999) : $budgetPct, 0) }}%
                    </span>
                </div>
                <div style="height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                    <div style="height: 100%; background: {{ $isOverBudget ? 'linear-gradient(90deg, #ef4444 0%, #f97316 100%)' : 'linear-gradient(90deg, var(--primary-color) 0%, var(--secondary-color) 100%)' }}; width: {{ $budgetPct }}%;"></div>
                </div>
                <div style="display:flex; justify-content: space-between; margin-top: 10px; font-weight:700; font-size:.8rem; color:#64748b;">
                    <span>Gasto: R$ {{ number_format($spent, 2, ',', '.') }}</span>
                    <span>Membros: {{ (int) ($project->members_count ?? 0) }}</span>
                </div>
            </div>

            <div class="project-meta" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 25px 0; border-top: 1px solid #f8fafc; border-bottom: 1px solid #f8fafc; margin-bottom: 25px;">
                <div>
                    <span style="display: block; font-size: 0.65rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Investimento</span>
                    <span style="font-weight: 800; color: #1e293b; font-size: 1.1rem;">R$ {{ number_format($project->budget, 0, ',', '.') }}</span>
                </div>
                <div style="text-align: right;">
                    <span style="display: block; font-size: 0.65rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Deadline</span>
                    <span style="font-weight: 800; color: #475569; font-size: 1.1rem;">{{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('d M, Y') : 'Fluxo Contínuo' }}</span>
                    @if($daysLeft !== null)
                        <div style="margin-top: 4px; font-size: .8rem; font-weight: 800; color: {{ $isDeadlineOverdue ? '#be123c' : ($isDeadlineSoon ? '#c2410c' : '#94a3b8') }};">
                            {{ $isDeadlineOverdue ? ('Atrasado ' . abs((int) $daysLeft) . ' dia(s)') : ((int) $daysLeft . ' dia(s)') }}
                        </div>
                    @endif
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <a href="{{ $basePath . '/projects/details/'.$project->id }}" style="text-align: center; background: white; padding: 15px; border-radius: 14px; color: #1e293b; font-weight: 800; text-decoration: none; border: 2px solid #f1f5f9; transition: all 0.2s; font-size: 0.85rem;">
                    <i class="fas fa-eye me-2 text-primary"></i> Visão Geral
                </a>
                <a href="{{ $basePath . '/projects/'.$project->id.'/kanban' }}" style="text-align: center; background: #1e293b; padding: 15px; border-radius: 14px; color: white; font-weight: 800; text-decoration: none; border: none; transition: all 0.2s; font-size: 0.85rem;">
                    <i class="fas fa-columns me-2" style="color: var(--primary-light);"></i> Kanban
                </a>
            </div>
        </div>
    @empty
        <div style="grid-column: 1 / -1; text-align: center; padding: 100px 20px; background: white; border-radius: 28px; border: 2px dashed #f1f5f9;">
            <div style="width: 100px; height: 100px; background: #f8fafc; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px;">
                <i class="fas fa-folder-open" style="font-size: 3rem; color: #e2e8f0;"></i>
            </div>
            <h3 style="color: #1e293b; font-weight: 900; font-size: 1.8rem; margin-bottom: 10px;">Silêncio no Radar</h3>
            <p style="color: #94a3b8; font-size: 1.1rem; max-width: 500px; margin: 0 auto 30px; font-weight: 500;">Parece que você ainda não iniciou nenhuma missão estratégica. Vamos começar agora?</p>
            <a href="{{ $basePath . '/projects/create' }}" class="btn-premium" style="display: inline-block; text-decoration: none; font-weight: 800; padding: 15px 40px;">CRIA PRIMEIRO PROJETO</a>
        </div>
    @endforelse
</div>

<script>
    let __projectsFilter = 'all';
    let __projectsFlag = 'all'; // all | over_budget
    let __projectsSort = 'default';

    function setProjectsFilter(btn) {
        const f = btn && btn.dataset ? (btn.dataset.pfilter || 'all') : 'all';
        __projectsFilter = f;

        document.querySelectorAll('[data-pfilter]').forEach(b => {
            const active = (b.dataset.pfilter === __projectsFilter);
            b.style.background = active ? '#0f172a' : '';
            b.style.color = active ? '#fff' : '';
            b.style.borderColor = active ? '#0f172a' : '';
        });

        applyProjectsFilters();
    }

    function setProjectsFlag(btn) {
        const f = btn && btn.dataset ? (btn.dataset.pflag || 'all') : 'all';
        __projectsFlag = (__projectsFlag === f) ? 'all' : f; // toggle
        const flagBtn = document.querySelector('[data-pflag="over_budget"]');
        if (flagBtn) {
            const active = (__projectsFlag === 'over_budget');
            flagBtn.style.background = active ? '#7f1d1d' : '';
            flagBtn.style.color = active ? '#fff' : '';
            flagBtn.style.borderColor = active ? '#7f1d1d' : '';
        }
        applyProjectsFilters();
    }

    function setProjectsSort(v) {
        __projectsSort = (v || 'default');
        applyProjectsFilters();
    }

    function clearProjectsFilters() {
        const inp = document.getElementById('projectsSearch');
        if (inp) inp.value = '';
        __projectsFilter = 'all';
        __projectsFlag = 'all';
        __projectsSort = 'default';
        document.querySelectorAll('[data-pfilter]').forEach(b => {
            const active = (b.dataset.pfilter === 'all');
            b.style.background = active ? '#0f172a' : '';
            b.style.color = active ? '#fff' : '';
            b.style.borderColor = active ? '#0f172a' : '';
        });
        const flagBtn = document.querySelector('[data-pflag="over_budget"]');
        if (flagBtn) {
            flagBtn.style.background = '';
            flagBtn.style.color = '';
            flagBtn.style.borderColor = '';
        }
        const sortEl = document.getElementById('projectsSort');
        if (sortEl) sortEl.value = 'default';
        applyProjectsFilters();
    }

    function applyProjectsFilters() {
        const inp = document.getElementById('projectsSearch');
        const q = (inp && inp.value ? inp.value : '').toString().trim().toLowerCase();

        const cards = document.querySelectorAll('#projectsGrid .project-card[data-status]');
        let shown = 0;
        cards.forEach(c => {
            const st = (c.dataset.status || 'active');
            const s = (c.dataset.search || '');
            const budgetPctRaw = parseFloat(c.dataset.budgetpctraw || '0');
            const statusOk = (__projectsFilter === 'all') ? true : (st === __projectsFilter);
            const searchOk = (!q) ? true : (s.indexOf(q) !== -1);
            const flagOk = (__projectsFlag === 'over_budget') ? (budgetPctRaw > 100.0001) : true;
            const ok = statusOk && searchOk && flagOk;
            c.style.display = ok ? '' : 'none';
            if (ok) shown++;
        });

        // Sorting (reorders DOM for grid)
        const grid = document.getElementById('projectsGrid');
        if (grid) {
            const list = Array.from(grid.querySelectorAll('.project-card'));
            const getNum = (el, key, dflt) => {
                const v = parseFloat(el.dataset[key] || '');
                return Number.isFinite(v) ? v : dflt;
            };
            const getInt = (el, key, dflt) => {
                const v = parseInt(el.dataset[key] || '', 10);
                return Number.isFinite(v) ? v : dflt;
            };

            list.sort((a, b) => {
                if (__projectsSort === 'budget') {
                    return getNum(b, 'budgetpctraw', 0) - getNum(a, 'budgetpctraw', 0);
                }
                if (__projectsSort === 'deadline') {
                    return getInt(a, 'deadlineDays', 99999) - getInt(b, 'deadlineDays', 99999);
                }
                if (__projectsSort === 'urgent') {
                    const da = getInt(a, 'deadlineDays', 99999);
                    const db = getInt(b, 'deadlineDays', 99999);
                    if (da !== db) return da - db;
                    return getNum(b, 'budgetpctraw', 0) - getNum(a, 'budgetpctraw', 0);
                }
                // default
                return getInt(a, 'order', 0) - getInt(b, 'order', 0);
            });

            // Re-append in sorted order
            list.forEach(el => grid.appendChild(el));
        }

        const hint = document.getElementById('projectsFilterHint');
        if (hint) {
            const labelMap = { all: 'Todos', active: 'Em Execução', paused: 'Pausados', completed: 'Concluídos', canceled: 'Cancelados' };
            const lf = labelMap[__projectsFilter] || __projectsFilter;
            const flagText = (__projectsFlag === 'over_budget') ? ' • Flag: Budget estourado' : '';
            const sortMap = { default: 'Padrão', urgent: 'Urgente', deadline: 'Deadline', budget: 'Uso do Budget' };
            const sortText = ' • Ordem: ' + (sortMap[__projectsSort] || __projectsSort);
            hint.innerText = 'Mostrando ' + shown + ' projeto(s) • Filtro: ' + lf + flagText + sortText + (q ? (' • Busca: "' + q + '"') : '');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const inp = document.getElementById('projectsSearch');
        if (inp) inp.addEventListener('input', applyProjectsFilters);
        const defaultBtn = document.querySelector('[data-pfilter="all"]');
        if (defaultBtn) setProjectsFilter(defaultBtn);
        const sortEl = document.getElementById('projectsSort');
        if (sortEl) sortEl.value = 'default';
    });
</script>
@endsection
