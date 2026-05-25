@extends('layouts.app')

@section('content')
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #impactMap {
        height: 450px;
        width: 100%;
        border-radius: 28px;
        z-index: 1;
        border: 1px solid rgba(255,255,255,0.05);
    }
    .leaflet-container {
        background: #0f172a !important;
    }
</style>
@endpush

@include('partials.onboarding')

<style>
@media (max-width: 768px) {
    .ngo-header-card {
        padding: 24px 20px 20px !important;
        border-radius: 20px !important;
        margin-bottom: 20px !important;
    }
    .ngo-header-title {
        font-size: 2rem !important;
        letter-spacing: -1px !important;
    }
    .ngo-header-subtitle {
        font-size: 0.85rem !important;
        margin-top: 10px !important;
    }
    .ngo-header-actions {
        width: 100%;
        gap: 8px !important;
    }
    .ngo-header-actions .btn-premium {
        padding: 10px 14px !important;
        font-size: 0.78rem !important;
        border-radius: 12px !important;
        flex: 1;
        text-align: center;
        justify-content: center;
    }
}
@media (max-width: 480px) {
    .ngo-header-actions .btn-premium.btn-audit { display: none !important; }
}
</style>

<div class="ngo-header-card header-page" style="margin-bottom: 32px; position: relative; background: linear-gradient(135deg, #0f172a 0%, #1a2540 100%); border-radius: 32px; padding: 48px 56px 44px; border: 1px solid rgba(255,255,255,0.08); overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.35);">
    <div style="background: radial-gradient(circle at 15% 15%, rgba(99, 102, 241, 0.15) 0%, transparent 40%), radial-gradient(circle at 85% 85%, rgba(16, 185, 129, 0.1) 0%, transparent 40%); position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 1;"></div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; position: relative; z-index: 2; flex-wrap: wrap; gap: 20px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: var(--ngo-primary); width: 12px; height: 3px; border-radius: 2px; box-shadow: 0 0 10px var(--ngo-primary);"></span>
                <h6 style="color: var(--ngo-primary); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Dashboard Terceiro Setor</h6>
            </div>
            <h2 class="ngo-header-title" style="margin: 0; color: white; font-weight: 950; font-size: 3.4rem; letter-spacing: -2.5px; line-height: 0.95;">Impacto & Gestão</h2>
            <p class="ngo-header-subtitle" style="color: rgba(255,255,255,0.5); margin: 18px 0 0 0; font-size: 1.1rem; font-weight: 500;">Monitoramento em tempo real da sustentabilidade da organização.</p>
        </div>
        <div class="ngo-header-actions" style="display: flex; gap: 12px; flex-wrap: wrap;">
            <button type="button" data-bs-toggle="modal" data-bs-target="#quickDonationModal" class="btn-premium" style="background: rgba(16,185,129,0.12); color: #34d399; border: 1px solid rgba(16,185,129,0.3); font-weight: 800; padding: 14px 28px; border-radius: 18px; cursor: pointer;">
                <i class="fas fa-hand-holding-heart me-2"></i> Registrar Doação
            </button>
            <a href="{{ url('/ngo/audit') }}" class="btn-premium btn-audit" style="background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.1); text-decoration: none; font-weight: 800; padding: 14px 28px; border-radius: 18px;">
                <i class="fas fa-eye me-2" style="color: var(--ngo-primary);"></i> Central de Auditoria
            </a>
            <a href="{{ url('/ngo/grants/create') }}" class="btn-premium" style="background: white; color: #0f172a; text-decoration: none; border: none; font-weight: 800; padding: 14px 28px; border-radius: 18px; box-shadow: 0 10px 30px rgba(255,255,255,0.1);">
                <i class="fas fa-plus me-2" style="color: var(--ngo-primary);"></i> Novo Edital
            </a>
        </div>
    </div>
</div>

@include('partials.quick_access')

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 32px;">

    <!-- Runway -->
    <div style="background: #0f172a; border: 1px solid rgba(255,255,255,0.08); border-radius: 24px; min-height: 180px; padding: 28px; position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;">
        <div style="position: absolute; top: -15px; right: -10px; font-size: 5rem; color: rgba(245,158,11,0.08); transform: rotate(-10deg);"><i class="fas fa-hourglass-half"></i></div>
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #f59e0b, transparent); border-radius: 24px 24px 0 0;"></div>
        <div>
            <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.2); border-radius: 8px; padding: 5px 10px; margin-bottom: 14px;">
                <i class="fas fa-hourglass-half" style="color: #f59e0b; font-size: 0.7rem;"></i>
                <span style="color: rgba(255,255,255,0.6); font-weight: 900; text-transform: uppercase; letter-spacing: 1.5px; font-size: 0.6rem;">RUNWAY ESTIMADA</span>
            </div>
            <div style="color: white; font-size: 3rem; font-weight: 950; letter-spacing: -2px; line-height: 1;">{{ (int)$stats['runway'] }}<span style="font-size: 1rem; opacity: 0.5; font-weight: 600; margin-left: 6px;">meses</span></div>
        </div>
        <div>
            <div style="height: 4px; background: rgba(255,255,255,0.08); border-radius: 2px; margin-bottom: 8px;">
                <div style="height: 100%; width: {{ min((float)$stats['runway'] * 10, 100) }}%; background: {{ (float)$stats['runway'] < 3 ? '#ef4444' : '#f59e0b' }}; border-radius: 2px; box-shadow: 0 0 10px {{ (float)$stats['runway'] < 3 ? 'rgba(239,68,68,0.5)' : 'rgba(245,158,11,0.5)' }};"></div>
            </div>
            <div style="font-size: 0.65rem; color: rgba(255,255,255,0.35); font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Autonomia de Caixa Atual</div>
        </div>
    </div>

    <!-- Arrecadação -->
    <div style="background: #0f172a; border: 1px solid rgba(255,255,255,0.08); border-radius: 24px; min-height: 180px; padding: 28px; position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;">
        <div style="position: absolute; top: -15px; right: -10px; font-size: 5rem; color: rgba(16,185,129,0.08); transform: rotate(-10deg);"><i class="fas fa-dollar-sign"></i></div>
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #10b981, transparent); border-radius: 24px 24px 0 0;"></div>
        <div>
            <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); border-radius: 8px; padding: 5px 10px; margin-bottom: 14px;">
                <i class="fas fa-arrow-trend-up" style="color: #10b981; font-size: 0.7rem;"></i>
                <span style="color: rgba(255,255,255,0.6); font-weight: 900; text-transform: uppercase; letter-spacing: 1.5px; font-size: 0.6rem;">ARRECADAÇÃO (MÊS)</span>
            </div>
            <div style="color: #34d399; font-size: 2rem; font-weight: 950; letter-spacing: -1px; line-height: 1;">R$ {{ number_format($stats['monthly_income'], 0, ',', '.') }}</div>
        </div>
        @php
            $ic = $stats['income_change'] ?? null;
            $icColor = ($ic === null) ? '#94a3b8' : ($ic >= 0 ? '#10b981' : '#ef4444');
            $icIcon  = ($ic === null) ? 'fa-minus' : ($ic >= 0 ? 'fa-trending-up' : 'fa-trending-down');
            $icText  = ($ic === null) ? 'Sem dados do mês ant.' : (($ic >= 0 ? '+' : '') . number_format($ic, 1) . '% vs mês ant.');
        @endphp
        <div style="color: {{ $icColor }}; font-size: 0.8rem; font-weight: 800; display: flex; align-items: center; gap: 6px;">
            <i class="fas {{ $icIcon }}"></i>
            {{ $icText }}
        </div>
    </div>

    <!-- Voluntários -->
    <div style="background: #0f172a; border: 1px solid rgba(255,255,255,0.08); border-radius: 24px; min-height: 180px; padding: 28px; position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;">
        <div style="position: absolute; top: -15px; right: -10px; font-size: 5rem; color: rgba(99,102,241,0.08); transform: rotate(-10deg);"><i class="fas fa-users"></i></div>
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #818cf8, transparent); border-radius: 24px 24px 0 0;"></div>
        <div>
            <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2); border-radius: 8px; padding: 5px 10px; margin-bottom: 14px;">
                <i class="fas fa-users" style="color: #818cf8; font-size: 0.7rem;"></i>
                <span style="color: rgba(255,255,255,0.6); font-weight: 900; text-transform: uppercase; letter-spacing: 1.5px; font-size: 0.6rem;">TIME VOLUNTÁRIO</span>
            </div>
            <div style="color: white; font-size: 3rem; font-weight: 950; letter-spacing: -2px; line-height: 1;">{{ $stats['volunteers_count'] }}</div>
        </div>
        <div style="color: #818cf8; font-size: 0.8rem; font-weight: 800; display: flex; align-items: center; gap: 6px;">
            <i class="fas fa-user-check"></i> Membros Ativos
        </div>
    </div>

    <!-- Doadores -->
    <div style="background: #0f172a; border: 1px solid rgba(255,255,255,0.08); border-radius: 24px; min-height: 180px; padding: 28px; position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;">
        <div style="position: absolute; top: -15px; right: -10px; font-size: 5rem; color: rgba(239,68,68,0.08); transform: rotate(-10deg);"><i class="fas fa-heart"></i></div>
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #f87171, transparent); border-radius: 24px 24px 0 0;"></div>
        <div>
            <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); border-radius: 8px; padding: 5px 10px; margin-bottom: 14px;">
                <i class="fas fa-heart" style="color: #f87171; font-size: 0.7rem;"></i>
                <span style="color: rgba(255,255,255,0.6); font-weight: 900; text-transform: uppercase; letter-spacing: 1.5px; font-size: 0.6rem;">BASE DE DOADORES</span>
            </div>
            <div style="color: white; font-size: 3rem; font-weight: 950; letter-spacing: -2px; line-height: 1;">{{ $stats['total_donors'] }}</div>
        </div>
        <div style="color: #f87171; font-size: 0.8rem; font-weight: 800; display: flex; align-items: center; gap: 6px;">
            <i class="fas fa-users"></i>
            {{ $stats['beneficiary_count'] ?? 0 }} Beneficiários cadastrados
        </div>
    </div>

</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div style="background: #0f172a; border-radius: 28px; padding: 36px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2); height: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <div>
                    <h3 style="color: white; font-weight: 950; font-size: 1.5rem; letter-spacing: -1px; margin: 0;">Performance de Captação</h3>
                    <p style="color: rgba(255,255,255,0.5); font-size: .85rem; margin: 4px 0 0 0;">Novos doadores e receita (6 meses)</p>
                </div>
            </div>
            <div style="height: 320px; width: 100%;">
                <canvas id="ngoPerformanceChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div style="background: #0f172a; border-radius: 28px; padding: 36px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2); height: 100%; display: flex; flex-direction: column; justify-content: center;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h3 style="color: white; font-weight: 950; font-size: 1.3rem; letter-spacing: -1px; margin: 0;">Radar de Saúde</h3>
                <p style="color: var(--ngo-primary); font-size: .75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px;">Diagnóstico Institucional</p>
            </div>
            <div style="position: relative; width: 100%; max-width: 280px; margin: 0 auto;">
                <canvas id="ngoHealthRadarChart"></canvas>
            </div>
            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-around;">
                <div style="text-align: center;">
                    <div style="font-size: 0.6rem; color: rgba(255,255,255,0.4); font-weight: 900; text-transform: uppercase;">Vitalidade</div>
                    <div style="font-size: 1.2rem; font-weight: 950; color: #10b981;">{{ round(collect($radarData['scores'])->avg()) }}%</div>
                </div>
                @php
                    $radarAvg = round(collect($radarData['scores'])->avg());
                    $confidenceLabel = $radarAvg >= 70 ? 'Alta' : ($radarAvg >= 40 ? 'Média' : 'Baixa');
                    $confidenceColor = $radarAvg >= 70 ? 'var(--ngo-primary)' : ($radarAvg >= 40 ? '#f59e0b' : '#ef4444');
                @endphp
                <div style="text-align: center;">
                    <div style="font-size: 0.6rem; color: rgba(255,255,255,0.4); font-weight: 900; text-transform: uppercase;">Confiança</div>
                    <div style="font-size: 1.2rem; font-weight: 950; color: {{ $confidenceColor }};">{{ $confidenceLabel }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div style="background: #0f172a; border-radius: 28px; padding: 36px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2); height: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; gap: 12px; flex-wrap: wrap;">
                <div>
                    <h3 style="color: white; font-weight: 950; font-size: 1.5rem; letter-spacing: -1px; margin: 0;">Agenda & Tarefas</h3>
                    <p style="color: rgba(255,255,255,0.5); font-size: .85rem; margin: 4px 0 0 0;">Próximos prazos e distribuição do trabalho</p>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="{{ url('/tasks/calendar') }}" class="btn-premium" style="background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.1); text-decoration: none; font-weight: 800; padding: 10px 18px; border-radius: 14px;">
                        <i class="fas fa-calendar-alt me-2" style="color: var(--ngo-primary);"></i> Calendário
                    </a>
                    <a href="{{ url('/tasks') }}" class="btn-premium" style="background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.1); text-decoration: none; font-weight: 800; padding: 10px 18px; border-radius: 14px;">
                        <i class="fas fa-list-check me-2" style="color: #10b981;"></i> Minhas Tarefas
                    </a>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                @forelse($upcomingTasks as $task)
                    @php
                        $due = $task->due_date ? \Carbon\Carbon::parse($task->due_date) : null;
                        $isOverdue = $due ? $due->isPast() : false;
                        $prioColors = ['critical' => '#dc2626', 'high' => '#ef4444', 'medium' => '#f59e0b', 'low' => '#10b981'];
                        $pc = $prioColors[$task->priority] ?? '#94a3b8';
                    @endphp
                    <div style="padding: 16px 18px; border-radius: 16px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                        <div style="width: 10px; height: 10px; border-radius: 50%; background: {{ $isOverdue ? '#dc2626' : $pc }}; box-shadow: 0 0 10px {{ $isOverdue ? 'rgba(220,38,38,0.5)' : $pc.'40' }};"></div>
                        <div style="flex: 1; min-width: 220px;">
                            <div style="font-weight: 900; color: white; font-size: 0.95rem; margin-bottom: 3px;">{{ \Illuminate\Support\Str::limit($task->title, 70) }}</div>
                            <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; color: rgba(255,255,255,0.5); font-size: 0.75rem; font-weight: 700;">
                                <span>
                                    <i class="far fa-calendar me-1"></i>
                                    {{ $due ? $due->format('d/m/Y') : 'Sem prazo' }}
                                    @if($isOverdue) <span style="color:#dc2626; font-weight:900;">(Atrasada)</span>@endif
                                </span>
                                <span>
                                    <i class="fas fa-user me-1"></i>
                                    {{ $task->assignee?->name ?? 'Sem responsável' }}
                                </span>
                            </div>
                        </div>
                        <div style="display:flex; gap:10px; align-items:center;">
                            <span style="font-size: .65rem; background: {{ $pc }}20; color: {{ $pc }}; padding: 4px 10px; border-radius: 99px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px;">{{ $task->priority ?? 'medium' }}</span>
                        </div>
                    </div>
                @empty
                    <x-empty-state
                        icon="fa-calendar-check"
                        title="Nenhum prazo próximo"
                        description="Cadastre tarefas com datas e responsáveis para ativar o acompanhamento por agenda."
                        action_label="Criar Tarefa"
                        action_url="{{ url('/tasks/create') }}"
                    />
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div style="background: #0f172a; border-radius: 28px; padding: 36px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2); height: 100%;">
            <div style="margin-bottom: 18px;">
                <h3 style="color: white; font-weight: 950; font-size: 1.25rem; letter-spacing: -1px; margin: 0;">Distribuir Tarefa</h3>
                <p style="color: rgba(255,255,255,0.5); font-size: .85rem; margin: 6px 0 0 0;">Atribuição rápida para a equipe cadastrada</p>
            </div>

            <form method="POST" action="{{ url('/tasks') }}">
                @csrf
                <input type="hidden" name="status" value="todo">

                <div class="mb-3">
                    <label for="title" style="display:block; margin-bottom:8px; color: rgba(255,255,255,0.7); font-weight: 800; font-size: .8rem;">Título</label>
                    <input name="title" class="form-control" required maxlength="255" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); color: white; border-radius: 14px; padding: 12px 14px;" id="title">
                </div>

                <div class="mb-3">
                    <label for="assigned_to" style="display:block; margin-bottom:8px; color: rgba(255,255,255,0.7); font-weight: 800; font-size: .8rem;">Responsável</label>
                    <select name="assigned_to" class="form-select" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); color: white; border-radius: 14px; padding: 12px 14px;" id="assigned_to">
                        <option value="">Sem responsável</option>
                        @foreach($teamUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label for="due_date" style="display:block; margin-bottom:8px; color: rgba(255,255,255,0.7); font-weight: 800; font-size: .8rem;">Prazo</label>
                        <input type="date" name="due_date" class="form-control" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); color: white; border-radius: 14px; padding: 12px 14px;" id="due_date">
                    </div>
                    <div class="col-6">
                        <label for="priority" style="display:block; margin-bottom:8px; color: rgba(255,255,255,0.7); font-weight: 800; font-size: .8rem;">Prioridade</label>
                        <select name="priority" class="form-select" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); color: white; border-radius: 14px; padding: 12px 14px;" id="priority">
                            <option value="medium" selected>Média</option>
                            <option value="low">Baixa</option>
                            <option value="high">Alta</option>
                            <option value="critical">Crítica</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" style="display:block; margin-bottom:8px; color: rgba(255,255,255,0.7); font-weight: 800; font-size: .8rem;">Descrição</label>
                    <textarea name="description" rows="3" class="form-control" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); color: white; border-radius: 14px; padding: 12px 14px;" id="description"></textarea>
                </div>

                <button type="submit" class="btn-premium w-100" style="background: var(--ngo-primary); border: none; color: white; font-weight: 900; padding: 12px 18px; border-radius: 16px;">
                    <i class="fas fa-paper-plane me-2"></i> Atribuir
                </button>
            </form>

            <div style="margin-top: 16px;">
                <a href="{{ url('/ngo/team') }}" class="btn-premium w-100" style="background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.9); border: 1px solid rgba(255,255,255,0.12); text-decoration: none; font-weight: 900; padding: 12px 18px; border-radius: 16px; display: inline-flex; justify-content: center; align-items: center;">
                    <i class="fas fa-users me-2" style="color: #818cf8;"></i> Ver Equipe
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ===== PORTFÓLIO DE PROJETOS ===== --}}
<div class="row g-4 mb-4">
    <div class="col-12">
        <div style="background: #0f172a; border-radius: 28px; padding: 36px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2); height: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px;">
                <div>
                    <h3 style="color: white; font-weight: 950; font-size: 1.5rem; letter-spacing: -1px; margin: 0;">Portfólio de Projetos</h3>
                    <p style="color: rgba(255,255,255,0.5); font-size: .85rem; margin: 4px 0 0 0;">Acompanhamento Físico e Financeiro</p>
                </div>
                <a href="{{ url('/projects') }}" style="font-size: 0.75rem; font-weight: 900; color: var(--ngo-primary); text-decoration: none; text-transform: uppercase; letter-spacing: 1px;">
                    Ver Todos <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px;">
                @forelse($projects as $proj)
                @php
                    $statusColors = [
                        'active'    => ['#10b981', 'rgba(16,185,129,0.1)', 'Ativo'],
                        'paused'    => ['#f59e0b', 'rgba(245,158,11,0.1)', 'Pausado'],
                        'completed' => ['#6366f1', 'rgba(99,102,241,0.1)', 'Concluído'],
                        'canceled'  => ['#94a3b8', 'rgba(255,255,255,0.05)', 'Cancelado'],
                    ];
                    $sc = $statusColors[$proj->status] ?? ['white','rgba(255,255,255,0.05)','—'];
                    $barColor = $proj->progress >= 100 ? '#10b981' : ($proj->progress >= 60 ? '#6366f1' : '#f59e0b');
                @endphp
                <div style="padding: 18px 20px; border: 1px solid rgba(255,255,255,0.05); background: rgba(255,255,255,0.02); border-radius: 18px; transition:.2s;" onmouseover="this.style.borderColor='rgba(255,255,255,0.15)'; this.style.transform='translateY(-4px)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'; this.style.transform=''">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                        <div>
                            <a href="{{ url('/projects/'.$proj->id) }}" style="font-weight:800; color:white; font-size:1.05rem; text-decoration:none;">{{ $proj->name }}</a>
                        </div>
                        <span style="font-size: 0.65rem; background: {{ $sc[1] }}; color: {{ $sc[0] }}; padding: 4px 10px; border-radius: 99px; font-weight: 900; text-transform: uppercase;">{{ $sc[2] }}</span>
                    </div>
                    <div style="display: flex; gap: 20px; margin-bottom: 8px; flex-direction: column;">
                        <!-- Progresso de Tarefas -->
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.65rem; font-weight: 800; color: rgba(255,255,255,0.4); text-transform: uppercase; margin-bottom: 4px;">
                                <span>Tarefas: {{ $proj->progress }}%</span>
                            </div>
                            <div style="height: 6px; border-radius: 3px; background: rgba(255,255,255,0.05); overflow: hidden;">
                                <div style="height: 100%; width:{{ $proj->progress }}%; background:{{ $barColor }}; box-shadow: 0 0 10px {{ $barColor }};"></div>
                            </div>
                        </div>
                        <!-- Progresso Financeiro (Budget) -->
                        @if($proj->budget > 0)
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.65rem; font-weight: 800; color: rgba(255,255,255,0.4); text-transform: uppercase; margin-bottom: 4px;">
                                <span>Verba Consumida: {{ $proj->budget_percent }}%</span>
                                <span style="color: {{ $proj->budget_percent >= 100 ? '#ef4444' : '#10b981' }}">
                                    R$ {{ number_format($proj->spent, 0, ',', '.') }} / R$ {{ number_format($proj->budget, 0, ',', '.') }}
                                </span>
                            </div>
                            <div style="height: 6px; border-radius: 3px; background: rgba(255,255,255,0.05); overflow: hidden;">
                                <div style="height: 100%; width:{{ min(100, $proj->budget_percent) }}%; background:{{ $proj->budget_percent >= 100 ? '#ef4444' : '#10b981' }}; box-shadow: 0 0 10px {{ $proj->budget_percent >= 100 ? '#ef4444' : '#10b981' }};"></div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @empty
                <div style="grid-column: 1 / -1;">
                    <x-empty-state 
                        icon="fa-rocket" 
                        title="Nenhum Projeto Ativo" 
                        description="Crie seu primeiro projeto para gerenciar tarefas e orçamentos." 
                        action_label="Criar Novo Projeto" 
                        action_url="{{ url('/projects/create') }}" 
                    />
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('ngoPerformanceChart').getContext('2d');
        
        const gradientDonations = ctx.createLinearGradient(0, 0, 0, 320);
        gradientDonations.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
        gradientDonations.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

        const gradientDonors = ctx.createLinearGradient(0, 0, 0, 320);
        gradientDonors.addColorStop(0, 'rgba(139, 92, 246, 0.2)');
        gradientDonors.addColorStop(1, 'rgba(139, 92, 246, 0.0)');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!},
                datasets: [
                    {
                        label: 'Receita (R$)',
                        data: {!! json_encode($chartDonations, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!},
                        backgroundColor: gradientDonations,
                        borderColor: '#10b981',
                        borderWidth: 2,
                        borderRadius: 8,
                        barPercentage: 0.6,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Novos Doadores',
                        data: {!! json_encode($chartDonors, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!},
                        backgroundColor: gradientDonors,
                        borderColor: '#8B5CF6',
                        borderWidth: 2,
                        borderRadius: 8,
                        barPercentage: 0.6,
                        type: 'line',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#0f172a',
                        pointBorderColor: '#8B5CF6',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleColor: '#ffffff',
                        bodyColor: '#e2e8f0',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        padding: 12
                    }
                },
                scales: {
                    x: { grid: { display: false, drawBorder: false }, ticks: { color: 'rgba(255,255,255,0.4)', font: { size: 12, family: "'Outfit', sans-serif" } } },
                    y: { 
                        type: 'linear', display: true, position: 'left',
                        grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false }, 
                        ticks: { color: 'rgba(255,255,255,0.4)' },
                        beginAtZero: true 
                    },
                    y1: {
                        type: 'linear', display: true, position: 'right',
                        grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.4)' },
                        beginAtZero: true
                    }
                }
            }
        });

        // Radar Chart Configuration
        const ctxRadar = document.getElementById('ngoHealthRadarChart').getContext('2d');
        new Chart(ctxRadar, {
            type: 'radar',
            data: {
                labels: @json($radarData['labels']),
                datasets: [{
                    label: 'Saúde Institucional',
                    data: @json($radarData['scores']),
                    backgroundColor: 'rgba(99, 102, 241, 0.2)',
                    borderColor: '#6366f1',
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#6366f1',
                    borderWidth: 3
                }]
            },
            options: {
                scales: {
                    r: {
                        angleLines: { color: 'rgba(255, 255, 255, 0.1)' },
                        grid: { color: 'rgba(255, 255, 255, 0.1)' },
                        pointLabels: {
                            color: 'rgba(255, 255, 255, 0.7)',
                            font: { size: 10, weight: '700', family: 'Inter' }
                        },
                        ticks: { display: false, stepSize: 20 },
                        suggestedMin: 0,
                        suggestedMax: 100
                    }
                },
                plugins: {
                    legend: { display: false }
                },
                maintainAspectRatio: true
            }
        });

        // Impact Map Configuration
        const mapData = @json($mapMarkers);
        const map = L.map('impactMap', {
            center: [-15.7801, -47.9292], // Brasília center
            zoom: 4,
            zoomControl: false
        });

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; Vivensi Intelligence'
        }).addTo(map);

        L.control.zoom({ position: 'bottomright' }).addTo(map);

        if (mapData.length > 0) {
            const markers = [];
            mapData.forEach(marker => {
                const iconColor = marker.type === 'beneficiary' ? '#6366f1' : '#10b981';
                const m = L.circleMarker([marker.lat, marker.lng], {
                    radius: 8,
                    fillColor: iconColor,
                    color: "#fff",
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.8
                }).addTo(map).bindPopup(`<strong style="color:#1e293b">${marker.label}</strong><br><span style="color:#64748b; font-size:11px">${marker.type === 'beneficiary' ? 'Beneficiário' : 'Doador'}</span>`);
                markers.push([marker.lat, marker.lng]);
            });
            
            if(markers.length > 1) {
                map.fitBounds(markers, { padding: [50, 50] });
            } else if (markers.length === 1) {
                map.setView(markers[0], 12);
            }
        }
    });
</script>

{{-- ===== MAPA DE IMPACTO GEOSOCIAL ===== --}}
<div style="background: #0f172a; border-radius: 28px; padding: 36px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2); margin-bottom: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px;">
        <div>
            <h3 style="color: white; font-weight: 950; font-size: 1.5rem; letter-spacing: -1px; margin: 0;">Rede de Impacto Global</h3>
            <p style="color: rgba(255,255,255,0.5); font-size:.85rem; margin:4px 0 0 0;">Doadores e Beneficiários em sincronismo geográfico</p>
        </div>
        <div style="display: flex; gap: 20px;">
             <div style="display: flex; align-items: center; gap: 8px;">
                <span style="width: 10px; height: 10px; background: #6366f1; border-radius: 50%; box-shadow: 0 0 8px #6366f1;"></span>
                <span style="font-size: 0.75rem; color: white; font-weight: 800;">Beneficiários</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="width: 10px; height: 10px; background: #10b981; border-radius: 50%; box-shadow: 0 0 8px #10b981;"></span>
                <span style="font-size: 0.75rem; color: white; font-weight: 800;">Doadores</span>
            </div>
        </div>
    </div>
    
    <div id="impactMap"></div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <!-- AI Insight Box (Premium Glassmorphism Dark) -->
        <div class="vivensi-card" style="padding: 35px; background: #0f172a; color: white; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2); position: relative; overflow: hidden; border-radius: 28px; margin-bottom: 30px;">
             <!-- Decorative Glows -->
             <div style="position: absolute; top: -30px; right: -30px; width: 120px; height: 120px; background: var(--ngo-primary); filter: blur(60px); opacity: 0.4;"></div>
             <div style="position: absolute; bottom: -30px; left: -30px; width: 100px; height: 100px; background: var(--ngo-success); filter: blur(50px); opacity: 0.2;"></div>
            
            <div style="display: flex; align-items: center; margin-bottom: 25px; position: relative; z-index: 1;">
                <div style="position: relative; margin-right: 15px;">
                    <img loading="lazy" src="{{ asset('img/bruce-ai.png') }}" alt="Bruce" style="width: 48px; height: 48px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.2); padding: 2px; object-fit: cover;">
                    <div style="position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; background: #10b981; border: 2px solid #0f172a; border-radius: 50%;"></div>
                </div>
                <div>
                    <h5 style="margin: 0; font-weight: 950; font-size: 1.1rem; letter-spacing: -0.5px; color: white;">Bruce AI Financial Advisor</h5>
                    <span style="font-size: 0.7rem; color: rgba(255,255,255,0.5); font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Sincronizado via Gemini Pro</span>
                </div>
                <a href="{{ url('/smart-analysis') }}" class="btn-premium" style="margin-left: auto; background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(10px); font-size: 0.7rem; font-weight: 800; padding: 10px 20px;">ANÁLISE COGNITIVA</a>
            </div>
            
            <div id="bruce-ngo-insight" style="position: relative; z-index: 1; padding: 25px; border-radius: 20px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); backdrop-filter: blur(5px);">
                <p class="mb-0" style="font-size:0.85rem;color:rgba(255,255,255,0.4);">
                    <i class="fas fa-circle-notch fa-spin me-2"></i> Analisando dados da sua ONG...
                </p>
            </div>
        </div>
        <script>
        (async function() {
            try {
                const res  = await fetch('/api/bruce/insight', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
                const data = await res.json();
                const el   = document.getElementById('bruce-ngo-insight');
                if (el && data.insight) {
                    el.innerHTML = `<p class="mb-0" style="font-size:0.95rem;color:#e2e8f0;line-height:1.6;">${data.insight.replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>').replace(/\n/g,'<br>')}</p>`;
                }
            } catch(e) {}
        })();
        </script>

        <div class="vivensi-card" style="padding: 35px; border-radius: 28px; min-height: 400px; background: #0f172a; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2); margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h4 style="margin: 0; font-size: 1.25rem; color: white; font-weight: 950; letter-spacing: -0.5px;">Linha do Tempo de Impacto</h4>
                <div style="display: flex; align-items: center; gap: 8px;">
                     <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; display: inline-block; box-shadow: 0 0 10px #10b981;"></span>
                     <span style="font-size: 0.75rem; color: #10b981; font-weight: 800; text-transform: uppercase;">Monitoramento Ativo</span>
                </div>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 0;">
                @forelse($stats['impactFeed'] as $item)
                    <div style="display: flex; align-items: center; gap: 20px; padding: 25px; border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)';" onmouseout="this.style.background='transparent';">
                        <div style="width: 52px; height: 52px; min-width: 52px; border-radius: 16px; background: {{ $item['color'] }}20; color: {{ $item['color'] }}; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; border: 1px solid {{ $item['color'] }}30;">
                            <i class="fas {{ $item['icon'] }}"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 800; color: white; font-size: 1.05rem; margin-bottom: 3px;">{{ $item['title'] }}</div>
                             <div style="font-size: 0.8rem; color: rgba(255,255,255,0.5); font-weight: 700;">{{ $item['time'] }}</div>
                        </div>
                    </div>
                @empty
                    <div class="col-12" style="padding: 20px;">
                        <x-empty-state 
                            icon="fa-seedling" 
                            title="Nenhum Marco Registrado" 
                            description="A inteligência do Bruce ainda não encontrou novos marcos de impacto na sua ONG hoje." 
                        />
                    </div>
                @endforelse
            </div>
        </div>

        <div style="padding: 35px; border-radius: 28px; background: #0f172a; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h4 style="margin: 0; font-size: 1.25rem; color: white; font-weight: 950; letter-spacing: -0.5px;">Editais & Convênios Recentes</h4>
                <a href="{{ url('/ngo/grants') }}" style="font-size: 0.7rem; font-weight: 800; background: rgba(255,255,255,0.05); color: #818cf8; border: 1px solid rgba(255,255,255,0.1); text-decoration: none; padding: 8px 16px; border-radius: 10px;">VER TODOS</a>
            </div>

            {{-- Cabeçalho --}}
            <div style="display: flex; gap: 0; padding: 0 16px 10px; border-bottom: 1px solid rgba(255,255,255,0.06); margin-bottom: 6px;">
                <div style="flex: 2; font-size: 0.68rem; font-weight: 900; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 1.5px;">Edital / Projeto</div>
                <div style="flex: 1; font-size: 0.68rem; font-weight: 900; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 1.5px;">Valor</div>
                <div style="flex: 1; font-size: 0.68rem; font-weight: 900; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 1.5px; text-align: right;">Status</div>
            </div>

            {{-- Linhas --}}
            @forelse($stats['recent_grants'] as $grant)
            @php
                $grantStatusMap = [
                    'open'      => ['#10b981', 'rgba(16,185,129,0.15)', 'Aberto'],
                    'reporting' => ['#f59e0b', 'rgba(245,158,11,0.15)', 'Em Prestação'],
                    'closed'    => ['#94a3b8', 'rgba(148,163,184,0.1)',  'Encerrado'],
                ];
                $gs = $grantStatusMap[$grant->status] ?? ['#a5b4fc', 'rgba(129,140,248,0.12)', $grant->status];
                $daysLeft = $grant->deadline ? now()->diffInDays(\Carbon\Carbon::parse($grant->deadline), false) : null;
                $isUrgent = $daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 7;
            @endphp
            <div style="display: flex; align-items: center; gap: 0; padding: 16px; border-radius: 12px; background: {{ $isUrgent ? 'rgba(239,68,68,0.05)' : 'rgba(255,255,255,0.03)' }}; border: 1px solid {{ $isUrgent ? 'rgba(239,68,68,0.2)' : 'transparent' }}; margin-bottom: 8px; transition: background 0.2s; cursor: pointer;"
                 onmouseover="this.style.background='rgba(255,255,255,0.07)'"
                 onmouseout="this.style.background='{{ $isUrgent ? 'rgba(239,68,68,0.05)' : 'rgba(255,255,255,0.03)' }}'">
                <div style="flex: 2; padding-right: 12px;">
                    <div style="font-weight: 700; font-size: 0.9rem; color: white;">{{ \Illuminate\Support\Str::limit($grant->title, 38) }}</div>
                    @if($daysLeft !== null && $daysLeft >= 0)
                    <div style="font-size: 0.68rem; color: {{ $isUrgent ? '#ef4444' : 'rgba(255,255,255,0.4)' }}; font-weight: 700; margin-top: 2px;">
                        @if($isUrgent) ⚠️ @endif
                        Prazo: {{ \Carbon\Carbon::parse($grant->deadline)->format('d/m/Y') }}
                        ({{ $daysLeft === 0 ? 'hoje' : "em {$daysLeft} dia(s)" }})
                    </div>
                    @endif
                </div>
                <div style="flex: 1; font-weight: 800; font-size: 0.9rem; color: #34d399;">R$ {{ number_format($grant->value, 0, ',', '.') }}</div>
                <div style="flex: 1; text-align: right;">
                    <span style="display: inline-block; background: {{ $gs[1] }}; color: {{ $gs[0] }}; padding: 4px 12px; border-radius: 8px; font-size: 0.65rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px;">{{ $gs[2] }}</span>
                </div>
            </div>
            @empty
            <x-empty-state 
                icon="fa-hands-helping" 
                title="Sem Novos Editais" 
                description="Você não possui editais ou convênios ativos no radar. Registre novos editais para acompanhar a captação." 
                action_label="Adicionar Edital" 
                action_url="{{ url('/ngo/grants/create') }}" 
            />
            @endforelse
        </div>
    </div>

    <div class="col-md-4">
        <div class="vivensi-card" style="padding: 35px; border-radius: 28px; background: #0f172a; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h4 style="margin: 0; font-size: 1.25rem; color: white; font-weight: 950; letter-spacing: -0.5px;">Campanhas Alpha</h4>
                <a href="{{ url('/ngo/campaigns') }}" style="font-size: 0.75rem; font-weight: 900; color: #818cf8; text-decoration: none;">HISTÓRICO</a>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 20px;">
                @foreach($stats['active_campaigns'] as $campaign)
                    <div style="padding: 20px; border-radius: 20px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <div style="font-weight: 800; color: white; font-size: 1rem;">{{ $campaign->title }}</div>
                            <span style="font-size: 0.65rem; background: rgba(16,185,129,0.1); color: #10b981; padding: 4px 10px; border-radius: 10px; font-weight: 900;">ATIVO</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="font-size: 0.75rem; color: rgba(255,255,255,0.5); font-weight: 700;">Progresso</span>
                            <span style="font-size: 0.75rem; color: white; font-weight: 800;">R$ {{ number_format($campaign->current_amount, 0, ',', '.') }}</span>
                        </div>
                        <div style="height: 8px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.05); border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; background: linear-gradient(90deg, #818cf8 0%, #10b981 100%); width: {{ min(100, ($campaign->current_amount / max(1, (float)$campaign->target_amount)) * 100) }}%;"></div>
                        </div>
                    </div>
                @endforeach
                @if(count($stats['active_campaigns']) == 0)
                    <x-empty-state 
                        icon="fa-bullhorn" 
                        title="Nenhuma Captação Ativa" 
                        description="Você não tem campanhas Alpha rodando no momento. Crie uma para começar a arrecadar fundos e bater metas." 
                    />
                @endif
            </div>
            
            <a href="{{ url('/ngo/campaigns/create') }}" class="btn-premium" style="width: 100%; text-align: center; display: block; margin-top: 30px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; padding: 15px; font-weight: 800; font-size: 0.9rem;">
                <i class="fas fa-rocket me-2" style="color: #818cf8;"></i> Lançar Nova Campanha
            </a>
        </div>

        <!-- Próximos Prazos de Editais -->
        @if(isset($stats['upcoming_deadlines']) && $stats['upcoming_deadlines']->isNotEmpty())
        <div class="mt-4" style="padding: 24px; background: #0f172a; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05);">
            <h6 style="color: rgba(255,255,255,0.35); font-weight: 900; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 18px;">⏳ EDITAIS COM PRAZO PRÓXIMO</h6>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach($stats['upcoming_deadlines'] as $dl)
                    @php
                        $days = now()->diffInDays(\Carbon\Carbon::parse($dl->deadline), false);
                        $dlColor = $days <= 3 ? '#ef4444' : ($days <= 7 ? '#f59e0b' : '#818cf8');
                        $dlBg    = $days <= 3 ? 'rgba(239,68,68,0.08)' : ($days <= 7 ? 'rgba(245,158,11,0.06)' : 'rgba(129,140,248,0.06)');
                        $dlBorder= $days <= 3 ? 'rgba(239,68,68,0.2)'  : ($days <= 7 ? 'rgba(245,158,11,0.15)' : 'rgba(129,140,248,0.12)');
                    @endphp
                    <div style="display: flex; gap: 12px; align-items: center; padding: 12px 14px; background: {{ $dlBg }}; border-radius: 12px; border: 1px solid {{ $dlBorder }};">
                        <div style="width: 8px; height: 8px; min-width: 8px; background: {{ $dlColor }}; border-radius: 50%; box-shadow: 0 0 6px {{ $dlColor }};"></div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-size: 0.82rem; color: #e2e8f0; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $dl->title }}</div>
                            <div style="font-size: 0.68rem; color: {{ $dlColor }}; font-weight: 800; margin-top: 2px;">
                                {{ $days === 0 ? 'Vence hoje!' : "Vence em {$days} dia(s)" }} — {{ \Carbon\Carbon::parse($dl->deadline)->format('d/m') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

{{-- ===== MODAL: Registrar Doação Rápida ===== --}}
<div class="modal fade" id="quickDonationModal" role="dialog" aria-modal="true" aria-labelledby="quickDonationModalLabel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content" style="background: #0f172a; border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; overflow: hidden;">
            <div style="padding: 28px 32px 0;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                    <div>
                        <h5 style="color: white; font-weight: 950; font-size: 1.2rem; margin: 0; letter-spacing: -0.5px;">Registrar Doação</h5>
                        <p style="color: rgba(255,255,255,0.4); font-size: 0.8rem; margin: 4px 0 0;">Lançamento rápido de receita / doação</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
            </div>
            <form method="POST" action="{{ url('/transactions') }}" style="padding: 0 32px 32px;">
                @csrf
                <input type="hidden" name="type" value="income">
                <input type="hidden" name="status" value="paid">

                <div style="margin-bottom: 16px;">
                    <label for="description" style="color: rgba(255,255,255,0.6); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">Doador / Descrição</label>
                    <input type="text" name="description" required placeholder="Ex: Doação de João Silva"
                        style="width:100%; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:12px 16px; color:white; font-size:0.9rem; outline:none;"
                        onfocus="this.style.borderColor='rgba(16,185,129,0.5)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'" id="description">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <div>
                        <label for="amount" style="color: rgba(255,255,255,0.6); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">Valor (R$)</label>
                        <input type="text" name="amount" required placeholder="0,00" inputmode="decimal"
                            style="width:100%; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:12px 16px; color:white; font-size:0.9rem; outline:none;"
                            onfocus="this.style.borderColor='rgba(16,185,129,0.5)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'" id="amount">
                    </div>
                    <div>
                        <label for="date" style="color: rgba(255,255,255,0.6); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">Data</label>
                        <input type="date" name="date" required value="{{ now()- id="date">format('Y-m-d') }}"
                            style="width:100%; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:12px 16px; color:white; font-size:0.9rem; outline:none;"
                            onfocus="this.style.borderColor='rgba(16,185,129,0.5)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
                    </div>
                </div>

                <button type="submit" style="width:100%; background:linear-gradient(135deg,#10b981,#059669); color:white; border:none; border-radius:14px; padding:14px; font-weight:900; font-size:0.95rem; cursor:pointer; box-shadow:0 8px 24px rgba(16,185,129,0.25); transition:.2s;" onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                    <i class="fas fa-check me-2"></i> Registrar Doação
                </button>
            </form>
        </div>
    </div>
</div>

@endsection
