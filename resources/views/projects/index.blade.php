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
        @if(in_array(auth()->user()->role, ['manager', 'super_admin'], true))
            <a href="{{ $basePath . '/projects/create' }}" class="btn-premium btn-premium-shine" style="border: none; padding: 14px 28px; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-plus-circle"></i> Iniciar Projeto
            </a>
        @endif
    </div>
</div>

@if(session('success'))
    <div style="background: #ecfdf5; color: #065f46; padding: 20px; border-radius: 16px; margin-bottom: 30px; border: 1px solid #a7f3d0; font-weight: 700; display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i> {{ session('success') }}
    </div>
@endif

<div class="vivensi-card mb-3" style="padding: 16px 18px; border-radius: 18px;">
    <form method="GET" action="{{ $basePath . '/projects' }}">
        <div style="display:flex; gap: 12px; align-items:end; justify-content: space-between; flex-wrap: wrap;">
            <div style="display:flex; gap: 10px; align-items:end; flex: 1; min-width: 280px; flex-wrap: wrap;">
                <div style="position: relative; flex: 1; min-width: 260px;">
                    <label style="display:block; font-size:.7rem; font-weight:900; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px;">Busca</label>
                    <i class="fas fa-search" style="position:absolute; left: 14px; top: 38px; color:#94a3b8;"></i>
                    <input name="q" value="{{ $q ?? '' }}" type="text" placeholder="Buscar por nome ou descrição..."
                           style="width: 100%; padding: 10px 12px 10px 38px; border-radius: 12px; border:1px solid #e2e8f0; background:#fff; font-weight: 700; color:#0f172a;">
                </div>

                <div style="min-width: 180px;">
                    <label style="display:block; font-size:.7rem; font-weight:900; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px;">Status</label>
                    <select name="status" style="width:100%; padding: 10px 12px; border-radius: 12px; border:1px solid #e2e8f0; background:#fff; font-weight: 900; color:#0f172a;">
                        <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>Todos</option>
                        <option value="active" {{ ($status ?? 'all') === 'active' ? 'selected' : '' }}>Em Execução</option>
                        <option value="paused" {{ ($status ?? 'all') === 'paused' ? 'selected' : '' }}>Pausados</option>
                        <option value="completed" {{ ($status ?? 'all') === 'completed' ? 'selected' : '' }}>Concluídos</option>
                        <option value="canceled" {{ ($status ?? 'all') === 'canceled' ? 'selected' : '' }}>Cancelados</option>
                    </select>
                </div>

                @if(!empty($teamUsers))
                    <div style="min-width: 220px;">
                        <label style="display:block; font-size:.7rem; font-weight:900; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px;">Membro</label>
                        <select name="member" style="width:100%; padding: 10px 12px; border-radius: 12px; border:1px solid #e2e8f0; background:#fff; font-weight: 900; color:#0f172a;">
                            <option value="">Todos</option>
                            @foreach($teamUsers as $u)
                                <option value="{{ $u->id }}" {{ (string)($memberId ?? '') === (string)$u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <div style="display:flex; gap: 10px; flex-wrap: wrap; align-items:end;">
                <div style="min-width: 220px;">
                    <label style="display:block; font-size:.7rem; font-weight:900; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px;">Ordem</label>
                    <select name="sort" style="width:100%; padding: 10px 12px; border-radius: 12px; border:1px solid #e2e8f0; background:#fff; font-weight: 900; color:#0f172a;">
                        <option value="recent" {{ ($sort ?? 'recent') === 'recent' ? 'selected' : '' }}>Mais recentes</option>
                        <option value="deadline" {{ ($sort ?? 'recent') === 'deadline' ? 'selected' : '' }}>Deadline</option>
                        <option value="budget" {{ ($sort ?? 'recent') === 'budget' ? 'selected' : '' }}>Uso do budget</option>
                        <option value="risk" {{ ($sort ?? 'recent') === 'risk' ? 'selected' : '' }}>Risco</option>
                    </select>
                </div>

                <div style="min-width: 170px;">
                    <label style="display:block; font-size:.7rem; font-weight:900; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px;">Flags</label>
                    <label style="display:flex; gap:10px; align-items:center; padding: 10px 12px; border-radius: 12px; border:1px solid #e2e8f0; background:#fff; font-weight: 900; color:#0f172a;">
                        <input type="checkbox" name="flag" value="over_budget" {{ ($flag ?? '') === 'over_budget' ? 'checked' : '' }}>
                        Budget estourado
                    </label>
                </div>

                <div style="display:flex; gap: 10px;">
                    <button type="submit" class="btn-premium" style="border:none; padding: 12px 18px; font-weight: 900; border-radius: 14px;">
                        Aplicar
                    </button>
                    <a href="{{ $basePath . '/projects' }}" class="btn-outline" style="padding: 12px 18px; border-radius: 14px; font-weight: 900; text-decoration:none;">
                        Limpar
                    </a>
                </div>
            </div>
        </div>
    </form>

    <div style="margin-top: 10px; color:#94a3b8; font-weight:800; font-size:.85rem;">
        Mostrando {{ $projects->count() }} de {{ $projects->total() }} projeto(s)
    </div>
</div>

<div id="projectsGrid" class="projects-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 30px;">
    @forelse($projects as $project)
        @php
            $spent = (float) ($project->total_spent ?? 0);
            $budget = (float) ($project->budget ?? 0);
            $budgetPctRaw = $budget > 0 ? (($spent / $budget) * 100) : 0;
            $budgetPct = min(100, max(0, $budgetPctRaw));
            $rowStatus = (string) ($project->status ?? 'active');
            $daysLeft = $project->end_date
                ? \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($project->end_date)->startOfDay(), false)
                : null;
            $isOverBudget = $budgetPctRaw > 100.0001;
            $isDeadlineSoon = $daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 7;
            $isDeadlineOverdue = $daysLeft !== null && $daysLeft < 0;
            $totalTasks = (int) ($project->total_tasks ?? 0);
            $doneTasks = (int) ($project->done_tasks ?? 0);
            $openTasks = (int) ($project->open_tasks ?? max(0, $totalTasks - $doneTasks));
            $overdueTasks = (int) ($project->overdue_tasks ?? 0);
            $criticalOpenTasks = (int) ($project->critical_open_tasks ?? 0);
            $progressPct = $totalTasks > 0 ? (int) round(($doneTasks / $totalTasks) * 100) : 0;

            $riskScore = 0;
            $riskScore += $isOverBudget ? 40 : 0;
            $riskScore += $overdueTasks > 0 ? min(40, $overdueTasks * 10) : 0;
            $riskScore += $criticalOpenTasks > 0 ? min(30, $criticalOpenTasks * 10) : 0;
            if ($isDeadlineOverdue) {
                $riskScore += 50;
            } elseif ($isDeadlineSoon) {
                $riskScore += 20;
            }
            $riskScore = min(100, $riskScore);

            $riskLabel = $riskScore >= 70 ? 'Crítico' : ($riskScore >= 40 ? 'Atenção' : 'Estável');
            $riskColor = $riskScore >= 70 ? '#be123c' : ($riskScore >= 40 ? '#c2410c' : '#10b981');
        @endphp
        <div class="project-card"
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

                    <span style="background: {{ $riskColor }}10; color: {{ $riskColor }}; padding: 6px 12px; border-radius: 999px; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; border: 1px solid {{ $riskColor }}30;">
                        Risco: {{ $riskLabel }}
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

            <div style="margin-bottom: 25px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Execução (Tarefas)</span>
                    <span style="font-size: 0.75rem; color: #1e293b; font-weight: 900;">{{ $progressPct }}%</span>
                </div>
                <div style="height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                    <div style="height: 100%; background: linear-gradient(90deg, #10b981 0%, #22c55e 100%); width: {{ $progressPct }}%;"></div>
                </div>
                <div style="display:flex; justify-content: space-between; margin-top: 10px; font-weight:700; font-size:.8rem; color:#64748b;">
                    <span>Concluídas: {{ $doneTasks }}/{{ $totalTasks }}</span>
                    <span style="color: {{ $overdueTasks > 0 ? '#be123c' : '#64748b' }};">Atrasadas: {{ $overdueTasks }}</span>
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

@if(method_exists($projects, 'links'))
    <div style="margin-top: 22px;">
        {{ $projects->links() }}
    </div>
@endif
@endsection
