@extends('layouts.app')

@section('content')
@include('partials.onboarding')

@include('partials.quick_access')

{{-- Estilos movidos para public/css/design-system.css (Phase 3.2) --}}

{{-- ===== HERO / COMMAND CENTER ===== --}}
<div class="command-center-hero">
    <div style="position: relative; z-index: 10;">
        <div class="hero-glass-pill">
            <span style="width: 8px; height: 8px; background: #6366f1; border-radius: 50%; box-shadow: 0 0 10px #6366f1;"></span>
             Project Manager Intelligence
        </div>
        
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; flex-wrap: wrap; gap: 20px;">
            <div>
                <h1 style="font-size: 3.4rem; font-weight: 950; margin: 0; letter-spacing: -2.5px; line-height: 0.95; color: white;">Centro de Comando</h1>
                <p style="margin: 18px 0 0 0; color: rgba(255,255,255,0.5); font-size: 1.1rem; font-weight: 500;">
                    Visão estratégica consolidada — {{ now()->translatedFormat('l, d \d\e F') }}
                </p>
            </div>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="{{ url('/manager/approvals') }}" class="btn-action-pro" style="background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5;">
                    <i class="fas fa-clock-rotate-left"></i> Aprovações
                    @if($stats['pending_approvals'] > 0)
                        <span style="background: #ef4444; color: white; border-radius: 99px; padding: 2px 8px; font-size: .7rem;">{{ $stats['pending_approvals'] }}</span>
                    @endif
                </a>
                <a href="{{ url('/smart-analysis') }}" class="btn-action-pro" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white;">
                    <i class="fas fa-chart-line" style="color: #94a3b8;"></i> Relatórios
                </a>
                <a href="{{ url('/projects/create') }}" class="btn-action-pro" style="background: white; color: #0f172a; box-shadow: 0 10px 30px rgba(255,255,255,0.1);">
                    <i class="fas fa-plus" style="color: #6366f1;"></i> Novo Projeto
                </a>
            </div>
        </div>

        {{-- KPIs no hero — CSS Grid auto-fit se adapta a qualquer N (4, 5, 6, 7…)
             + compact override reduzindo padding/height/font pra evitar boxes gigantes.
             Escopo do override: `.hero-kpi-grid .cmd-stat-card` — nao vaza pra outras views. --}}
        <style>
            .hero-kpi-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
                gap: 12px;
            }
            .hero-kpi-grid .cmd-stat-card {
                padding: 20px;
                min-height: 130px;
                border-radius: 20px;
            }
            .hero-kpi-grid .kpi-value      { font-size: 2rem;   line-height: 1.05; letter-spacing: -1.5px; margin-top: 6px; }
            .hero-kpi-grid .kpi-value-cash { font-size: 1.35rem; line-height: 1.05; letter-spacing: -1px;   margin-top: 6px; }
            .hero-kpi-grid .kpi-label      { font-size: 0.62rem; letter-spacing: 1.5px; }
            .hero-kpi-grid .kpi-footer     { font-size: 0.75rem; }
        </style>
        <div class="hero-kpi-grid">
            <div class="cmd-stat-card">
                <div>
                    <span class="kpi-label" style="font-weight: 900; color: rgba(255,255,255,0.4); text-transform: uppercase;">Missões Ativas</span>
                    <div class="kpi-value" style="font-weight: 950;">{{ $activeProjects }}</div>
                </div>
                <div class="kpi-footer" style="display: flex; align-items: center; gap: 8px; color: #10b981; font-weight: 800;">
                    <i class="fas fa-satellite-dish"></i> Sincronizado
                </div>
            </div>
            <div class="cmd-stat-card">
                <div>
                    <span class="kpi-label" style="font-weight: 900; color: rgba(255,255,255,0.4); text-transform: uppercase;">Radar de Alertas</span>
                    <div class="kpi-value" style="font-weight: 950; color: #f59e0b;">{{ $stats['pending_tasks'] }}</div>
                    @if($stats['overdue_tasks'] > 0)
                    <div style="font-size: 0.7rem; font-weight: 800; color: #ef4444; margin-top: 4px;">
                        <i class="fas fa-circle-exclamation me-1"></i>{{ $stats['overdue_tasks'] }} vencida{{ $stats['overdue_tasks'] > 1 ? 's' : '' }}
                    </div>
                    @endif
                </div>
                <div class="kpi-footer" style="display: flex; align-items: center; gap: 8px; color: #f59e0b; font-weight: 800;">
                    <i class="fas fa-wave-square"></i> Ação Requerida
                </div>
            </div>
            <div class="cmd-stat-card">
                <div>
                    <span class="kpi-label" style="font-weight: 900; color: rgba(255,255,255,0.4); text-transform: uppercase;">Entrada / Mês</span>
                    <div class="kpi-value-cash" style="font-weight: 950; color: #34d399;">
                        R$ {{ number_format($stats['monthly_income'], 0, ',', '.') }}
                    </div>
                    @if($stats['income_change'] !== null)
                    @php $ic = $stats['income_change']; @endphp
                    <div style="font-size: 0.7rem; font-weight: 800; color: {{ $ic >= 0 ? '#34d399' : '#f87171' }}; margin-top: 4px;">
                        {{ $ic >= 0 ? '▲' : '▼' }} {{ number_format(abs($ic), 1) }}% vs mês anterior
                    </div>
                    @endif
                </div>
                <div class="kpi-footer" style="display: flex; align-items: center; gap: 8px; color: #34d399; font-weight: 800;">
                    <i class="fas fa-arrow-trend-up"></i>
                    Saldo: R$ {{ number_format($stats['monthly_balance'], 0, ',', '.') }}
                </div>
            </div>
            <div class="cmd-stat-card">
                <div>
                    <span class="kpi-label" style="font-weight: 900; color: rgba(255,255,255,0.4); text-transform: uppercase;">Célula Operacional</span>
                    <div class="kpi-value" style="font-weight: 950;">{{ $stats['team_size'] }}</div>
                </div>
                <div class="kpi-footer" style="display: flex; align-items: center; gap: 8px; color: rgba(255,255,255,0.4); font-weight: 800;">
                    <i class="fas fa-network-wired"></i> Membros Ativos
                </div>
            </div>
            {{-- KPIs do Perfil Operacional (Fase 1 — Etapa C). Grid auto-fit
                 adapta pra qualquer quantidade extra sem quebra feia. --}}
            @foreach($resolvedKpis ?? [] as $key => $kpi)
                <div class="cmd-stat-card" style="border-left: 3px solid #6366f1;">
                    <div>
                        <span class="kpi-label" style="font-weight: 900; color: rgba(255,255,255,0.4); text-transform: uppercase;">{{ $kpi['label'] }}</span>
                        <div class="{{ $kpi['kind'] === 'currency' ? 'kpi-value-cash' : 'kpi-value' }}" style="font-weight: 950;">
                            @if($kpi['kind'] === 'currency')
                                R$ {{ number_format((float) $kpi['value'], 0, ',', '.') }}
                            @elseif($kpi['kind'] === 'percent')
                                {{ number_format((float) $kpi['value'], 0) }}%
                            @else
                                {{ number_format((int) $kpi['value'], 0, ',', '.') }}
                            @endif
                        </div>
                    </div>
                    <div class="kpi-footer" style="display: flex; align-items: center; gap: 8px; color: #a5b4fc; font-weight: 800;">
                        <i class="fas fa-compass"></i> Perfil Operacional
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ===== BASE DE CADASTROS (P1.6) — só perfis mobilizacao/eleitoral ===== --}}
@if(!empty($leadsBreakdown))
<div class="row g-4 mb-4">
    <div class="col-12">
        <div style="background: #0f172a; border-radius: 28px; padding: 36px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; flex-wrap: wrap; gap: 20px;">
                <div>
                    <h3 style="color: white; font-weight: 950; font-size: 1.5rem; letter-spacing: -1px; margin: 0;">Base de Cadastros</h3>
                    <p style="color: rgba(255,255,255,0.5); font-size: .85rem; margin: 4px 0 0 0;">Distribuição da base ativa (opt-in pendente + confirmado) por cidade e segmentação.</p>
                </div>
                <div style="display: inline-flex; align-items: baseline; gap: 8px; padding: 10px 18px; background: rgba(99,102,241,0.12); border: 1px solid rgba(99,102,241,0.25); border-radius: 14px;">
                    <span style="font-size: .7rem; font-weight: 900; color: #a5b4fc; text-transform: uppercase; letter-spacing: 2px;">Base Ativa</span>
                    <span style="font-size: 1.6rem; font-weight: 950; color: white;">{{ number_format($leadsBreakdown['total'], 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; padding: 24px; height: 100%; display: flex; flex-direction: column;">
                        <h4 style="font-weight: 900; color: white; font-size: 1rem; margin-bottom: 20px;">Por Cidade (Top 10)</h4>
                        @if($leadsBreakdown['total'] === 0)
                            <div style="color: rgba(255,255,255,0.5); font-size: .9rem; padding: 32px 0; text-align: center;">
                                Nenhum cadastro ativo ainda — cadastros aparecem aqui quando o opt-in (público ou WhatsApp) for confirmado.
                            </div>
                        @else
                            <div style="flex: 1; min-height: 280px;">
                                <canvas id="leadsCityChart"></canvas>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-lg-6">
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; padding: 24px; height: 100%; display: flex; flex-direction: column;">
                        <h4 style="font-weight: 900; color: white; font-size: 1rem; margin-bottom: 20px;">Por Segmentação (Top 10 tags)</h4>
                        @if($leadsBreakdown['total'] === 0)
                            <div style="color: rgba(255,255,255,0.5); font-size: .9rem; padding: 32px 0; text-align: center;">
                                Sem tags ainda. Adicione segmentações nos formulários públicos ou no CRM para ver a distribuição aqui.
                            </div>
                        @else
                            <div style="flex: 1; min-height: 280px;">
                                <canvas id="leadsTagsChart"></canvas>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ===== MÁQUINA DE ENGAJAMENTO ===== --}}
<div class="row g-4 mb-4">
    <div class="col-12">
        <div style="background: #0f172a; border-radius: 28px; padding: 36px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; flex-wrap: wrap; gap: 20px;">
                <div>
                    <h3 style="color: white; font-weight: 950; font-size: 1.5rem; letter-spacing: -1px; margin: 0;">Máquina de Engajamento</h3>
                    <p style="color: rgba(255,255,255,0.5); font-size: .85rem; margin: 4px 0 0 0;">Análise de performance de disparos em massa e interações</p>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button class="btn-action-pro" style="background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.1);" data-bs-toggle="modal" data-bs-target="#addPersonGlobalModal">
                        <i class="fas fa-user-plus text-info"></i> Cadastrar Pessoa
                    </button>
                    <button class="btn-action-pro" style="background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.1);" data-bs-toggle="modal" data-bs-target="#importPersonGlobalModal">
                        <i class="fas fa-file-csv text-warning"></i> Importar CSV
                    </button>
                    <a href="{{ route('whatsapp.broadcast.index') }}" class="btn-action-pro" style="background: #10b981; color: white; border: none; box-shadow: 0 10px 30px rgba(16,185,129,0.2);">
                        <i class="fab fa-whatsapp"></i> Novo Disparo em Massa
                    </a>
                </div>
            </div>

            <div class="row g-4">
                {{-- Funil de Conversão --}}
                <div class="col-lg-7">
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; padding: 24px; height: 100%;">
                        <h4 style="font-weight: 900; color: white; font-size: 1rem; margin-bottom: 24px;">Funil de Conversão do WhatsApp</h4>
                        
                        @php
                            $sent = $whatsappStats['total_sent'] ?? 0;
                            $delivered = $whatsappStats['total_delivered'] ?? 0;
                            $replies = $whatsappStats['total_replies'] ?? 0;
                            
                            $deliveredPct = $sent > 0 ? round(($delivered / $sent) * 100) : 0;
                            $repliesPct = $sent > 0 ? round(($replies / $sent) * 100) : 0;
                        @endphp

                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            {{-- Enviadas --}}
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <div style="width: 120px; font-weight: 800; color: #94a3b8; font-size: 0.85rem;">Enviadas</div>
                                <div style="flex: 1; height: 28px; background: rgba(255,255,255,0.05); border-radius: 8px; position: relative; overflow: hidden;">
                                    <div style="position: absolute; top: 0; left: 0; height: 100%; width: 100%; background: #6366f1; border-radius: 8px;"></div>
                                </div>
                                <div style="width: 60px; text-align: right; font-weight: 900; color: white;">{{ number_format($sent, 0, ',', '.') }}</div>
                            </div>

                            {{-- Entregues --}}
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <div style="width: 120px; font-weight: 800; color: #94a3b8; font-size: 0.85rem;">Entregues ({{ $deliveredPct }}%)</div>
                                <div style="flex: 1; height: 28px; background: rgba(255,255,255,0.05); border-radius: 8px; position: relative; overflow: hidden;">
                                    <div style="position: absolute; top: 0; left: 0; height: 100%; width: {{ $deliveredPct }}%; background: #10b981; border-radius: 8px;"></div>
                                </div>
                                <div style="width: 60px; text-align: right; font-weight: 900; color: white;">{{ number_format($delivered, 0, ',', '.') }}</div>
                            </div>

                            {{-- Respostas --}}
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <div style="width: 120px; font-weight: 800; color: #94a3b8; font-size: 0.85rem;">Respostas ({{ $repliesPct }}%)</div>
                                <div style="flex: 1; height: 28px; background: rgba(255,255,255,0.05); border-radius: 8px; position: relative; overflow: hidden;">
                                    <div style="position: absolute; top: 0; left: 0; height: 100%; width: {{ $repliesPct }}%; background: #f59e0b; border-radius: 8px;"></div>
                                </div>
                                <div style="width: 60px; text-align: right; font-weight: 900; color: white;">{{ number_format($replies, 0, ',', '.') }}</div>
                            </div>
                        </div>

                        @php
                            if ($sent > 10 && $deliveredPct < 50) {
                                $engagementTip = 'Sua taxa de entrega está abaixo de 50%. Verifique se os números estão corretos com DDI (+55) e sem espaços.';
                            } elseif ($sent > 10 && $repliesPct < 10) {
                                $engagementTip = 'Taxa de resposta baixa. Personalize as mensagens com o nome do contato e envie em horários de pico (9h–11h ou 18h–20h).';
                            } elseif ($sent > 10 && $deliveredPct >= 70) {
                                $engagementTip = 'Boa taxa de entrega! Para aumentar respostas, inclua uma pergunta direta ou call-to-action claro no início da mensagem.';
                            } else {
                                $engagementTip = 'Gere listas segmentadas de contatos diretamente dos seus projetos ativos em "Pessoas & Contatos" na visualização do projeto.';
                            }
                        @endphp
                        <div style="margin-top: 24px; padding: 16px; background: rgba(99,102,241,0.1); border-radius: 12px; border: 1px solid rgba(99,102,241,0.2); display: flex; gap: 12px; align-items: flex-start;">
                            <i class="fas fa-lightbulb" style="color: #818cf8; margin-top: 2px;"></i>
                            <div style="font-size: 0.8rem; color: #cbd5e1;">
                                <strong style="color: white;">Dica de Engajamento:</strong> {{ $engagementTip }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Gráfico de Volume Diário --}}
                <div class="col-lg-5">
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; padding: 24px; height: 100%; display: flex; flex-direction: column;">
                        <h4 style="font-weight: 900; color: white; font-size: 1rem; margin-bottom: 20px;">Volume de Mensagens (7d)</h4>
                        
                        <div style="flex: 1; min-height: 180px;">
                            <canvas id="engagementChart"></canvas>
                        </div>

                        <div style="display: flex; gap: 15px; margin-top: 20px;">
                            <div style="flex: 1; background: rgba(16,185,129,0.1); border-radius: 12px; padding: 12px; text-align: center;">
                                <div style="font-size: 0.65rem; font-weight: 800; color: #10b981; text-transform: uppercase;">Entrega</div>
                                <div style="font-size: 1.2rem; font-weight: 900; color: white;">{{ $deliveredPct }}%</div>
                            </div>
                            <div style="flex: 1; background: rgba(245,158,11,0.1); border-radius: 12px; padding: 12px; text-align: center;">
                                <div style="font-size: 0.65rem; font-weight: 800; color: #f59e0b; text-transform: uppercase;">Resposta</div>
                                <div style="font-size: 1.2rem; font-weight: 900; color: white;">{{ $repliesPct }}%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== LINHA PRINCIPAL ===== --}}
<div class="row g-4 mb-4">

    {{-- Portfólio de Projetos --}}
    <div class="col-lg-8">
        <div style="background: #0f172a; border-radius: 28px; padding: 36px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2); height: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px;">
                <div>
                    <h3 style="color: white; font-weight: 950; font-size: 1.5rem; letter-spacing: -1px; margin: 0;">Portfólio de Projetos</h3>
                    <p style="color: rgba(255,255,255,0.5); font-size: .85rem; margin: 4px 0 0 0;">Progresso em tempo real</p>
                </div>
                <a href="{{ url('/projects') }}" style="font-size: 0.75rem; font-weight: 900; color: #6366f1; text-decoration: none; text-transform: uppercase; letter-spacing: 1px;">
                    Ver Todos <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>

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
            <div style="padding: 18px 20px; border: 1px solid rgba(255,255,255,0.05); background: rgba(255,255,255,0.02); border-radius: 18px; margin-bottom: 12px; transition:.2s;" onmouseover="this.style.borderColor='rgba(255,255,255,0.15)'; this.style.transform='translateX(4px)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'; this.style.transform=''">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <div>
                        <a href="{{ url('/projects/'.$proj->id) }}" style="font-weight:800; color:white; font-size:.95rem; text-decoration:none;">{{ $proj->name }}</a>
                        @if($proj->end_date)
                            @php $daysLeft = now()->diffInDays($proj->end_date, false); @endphp
                            <span style="margin-left:10px; font-size:.7rem; color:{{ $daysLeft < 7 ? '#dc2626' : '#94a3b8' }}; font-weight:700;">
                                <i class="far fa-clock me-1"></i>
                                {{ $daysLeft < 0 ? 'Vencido há '.abs((int)$daysLeft).'d' : (int)$daysLeft.'d restantes' }}
                            </span>
                        @endif
                    </div>
                    <span style="font-size:.6rem; font-weight:900; color:{{ $sc[0] }}; background:{{ $sc[1] }}; padding:3px 10px; border-radius:99px; border:1px solid {{ $sc[0] }}40; text-transform:uppercase; letter-spacing:.5px;">{{ $sc[2] }}</span>
                </div>
                <div style="display: flex; gap: 20px; margin-bottom: 8px;">
                    <!-- Progresso de Tarefas -->
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.65rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px;">
                            <span>Tarefas: {{ $proj->progress }}%</span>
                        </div>
                        <div class="progress-mini" style="background: rgba(255,255,255,0.05);">
                            <div class="progress-mini-bar" style="width:{{ $proj->progress }}%; background:{{ $barColor }}; box-shadow: 0 0 10px {{ $barColor }};"></div>
                        </div>
                    </div>
                    <!-- Progresso Financeiro (Budget) -->
                    @if($proj->budget > 0)
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.65rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px;">
                            <span>Verba: {{ $proj->budget_percent }}% consumida</span>
                            <span style="color: {{ $proj->budget_percent >= 100 ? '#f43f5e' : '#10b981' }}">
                                R$ {{ number_format($proj->spent, 0, ',', '.') }} / R$ {{ number_format($proj->budget, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="progress-mini" style="background: rgba(255,255,255,0.05);">
                            <div class="progress-mini-bar" style="width:{{ min(100, $proj->budget_percent) }}%; background:{{ $proj->budget_percent >= 100 ? '#f43f5e' : '#10b981' }}; box-shadow: 0 0 10px {{ $proj->budget_percent >= 100 ? '#f43f5e' : '#10b981' }};"></div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @empty
            <x-empty-state 
                icon="fa-rocket" 
                title="Nenhum Projeto Ativo" 
                description="Você ainda não possui projetos nesta organização. Crie seu primeiro projeto para começar a gerenciar tarefas e orçamentos." 
                action_label="Criar Novo Projeto" 
                action_url="{{ url('/projects/create') }}" 
            />
            @endforelse
        </div>
    </div>

    {{-- Radar de Saúde AI --}}
    <div class="col-lg-4">
        <div style="background: #0f172a; border-radius: 28px; padding: 36px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2); height: 100%; display: flex; flex-direction: column; justify-content: center;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h3 style="color: white; font-weight: 950; font-size: 1.3rem; letter-spacing: -1px; margin: 0;">Radar de Saúde</h3>
                <p style="color: #6366f1; font-size: .75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px;">Análise Operacional IA</p>
            </div>
            <div style="position: relative; width: 100%; max-width: 300px; margin: 0 auto;">
                <canvas id="healthRadarChart"></canvas>
            </div>
            @php
                $radarAvg = round(collect($radarData['scores'])->avg());
                $healthLabel = $radarAvg >= 70 ? 'Saudável' : ($radarAvg >= 40 ? 'Estável' : 'Crítico');
                $healthColor = $radarAvg >= 70 ? '#10b981' : ($radarAvg >= 40 ? '#f59e0b' : '#ef4444');
            @endphp
            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-around;">
                <div style="text-align: center;">
                    <div style="font-size: 0.6rem; color: rgba(255,255,255,0.4); font-weight: 900; text-transform: uppercase;">Média Geral</div>
                    <div style="font-size: 1.2rem; font-weight: 950; color: {{ $healthColor }};">{{ $radarAvg }}%</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 0.6rem; color: rgba(255,255,255,0.4); font-weight: 900; text-transform: uppercase;">Status</div>
                    <div style="font-size: 1.2rem; font-weight: 950; color: {{ $healthColor }};">{{ $healthLabel }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div style="background: #0f172a; border-radius: 28px; padding: 36px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2); margin-bottom: 20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
                <div>
                    <h3 style="color: white; font-weight: 950; font-size: 1.5rem; letter-spacing: -1px; margin: 0;">Cadência Operacional</h3>
                    <p style="color: rgba(255,255,255,0.5); font-size: .85rem; margin: 4px 0 0 0;">Projetos criados e tarefas concluídas (6 meses)</p>
                </div>
                <div style="display:flex; gap: 10px; flex-wrap: wrap;">
                    <a href="{{ url('/projects') }}" class="btn-action-pro" style="background: rgba(255,255,255,0.03); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.05); padding: 10px 16px;">
                        <i class="fas fa-folder-open" style="color:#6366f1;"></i> Projetos
                    </a>
                    <a href="{{ url('/tasks') }}" class="btn-action-pro" style="background: rgba(255,255,255,0.03); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.05); padding: 10px 16px;">
                        <i class="fas fa-list-check" style="color:#10b981;"></i> Tarefas
                    </a>
                </div>
            </div>
            <div style="height: 300px;">
                <canvas id="usageChart"></canvas>
            </div>
        </div>

        <div style="background: #0f172a; border-radius: 28px; padding: 36px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 12px; flex-wrap: wrap;">
                <div>
                    <h3 style="color: white; font-weight: 950; font-size: 1.5rem; letter-spacing: -1px; margin: 0;">Feed de Impacto</h3>
                    <p style="color: rgba(255,255,255,0.5); font-size:.85rem; margin:4px 0 0 0;">Marcos concluídos recentemente pela equipe</p>
                </div>
                <a href="{{ url('/tasks') }}" style="font-size: 0.75rem; font-weight: 900; color: #6366f1; text-decoration: none; text-transform: uppercase; letter-spacing: 1px;">
                    Ver Atividades <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="row g-3">
                @forelse($impactFeed as $item)
                    <div class="col-md-6">
                        <div class="timeline-item-pro" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); margin-bottom: 0;">
                            <div style="width: 50px; height: 50px; min-width: 50px; border-radius: 14px; background: {{ $item['color'] }}20; border: 1px solid {{ $item['color'] }}30; display: flex; align-items: center; justify-content: center; color: {{ $item['color'] }}; font-size: 1.1rem;">
                                <i class="fas {{ $item['icon'] }}"></i>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 800; color: white; font-size: .9rem; margin-bottom: 4px;">{{ $item['title'] }}</div>
                                <div style="display: flex; align-items: center; gap: 8px; color: #94a3b8; font-size: 0.75rem; font-weight: 700;">
                                    <span>{{ $item['time'] }}</span>
                                    <span style="color: #6366f1;">· Registro Automático</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <x-empty-state
                            icon="fa-satellite-dish"
                            title="Nenhuma Atividade Recente"
                            description="O radar ainda não captou atualizações. Conclua tarefas ou projetos para gerar impacto."
                        />
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div style="background: #0f172a; border-radius: 28px; padding: 30px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2); margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px;">
                <h4 style="color: white; font-weight: 950; font-size: 1.1rem; margin: 0;">⚠️ Tarefas Urgentes</h4>
                <a href="{{ url('/tasks') }}" style="font-size:.7rem; font-weight:900; color:#6366f1; text-decoration:none; text-transform:uppercase;">Ver Todas</a>
            </div>
            @forelse($urgentTasks as $task)
            @php
                $isOverdue = $task->due_date && \Carbon\Carbon::parse($task->due_date)->isPast();
                $prioColors = ['high'=>'#ef4444','critical'=>'#dc2626','medium'=>'#f59e0b','low'=>'#10b981'];
                $pc = $prioColors[$task->priority] ?? '#94a3b8';
            @endphp
            <div style="padding:14px 16px; border-radius:14px; border-left:4px solid {{ $isOverdue ? '#dc2626' : $pc }}; background:rgba(255,255,255,0.03); margin-bottom:10px; border-top:1px solid rgba(255,255,255,0.02); border-right:1px solid rgba(255,255,255,0.02); border-bottom:1px solid rgba(255,255,255,0.02);">
                <div style="font-weight:800; color:white; font-size:.875rem; margin-bottom:5px;">{{ \Illuminate\Support\Str::limit($task->title, 50) }}</div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:.7rem; color:{{ $isOverdue ? '#dc2626' : '#94a3b8' }}; font-weight:700;">
                        <i class="far fa-calendar-xmark me-1"></i>
                        {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') : '—' }}
                        @if($isOverdue) <strong>(VENCIDA)</strong>@endif
                    </span>
                    <span style="font-size:.65rem; background:{{ $pc }}20; color:{{ $pc }}; padding:3px 8px; border-radius:99px; font-weight:900; text-transform:uppercase;">{{ $task->priority }}</span>
                </div>
            </div>
            @empty
            <x-empty-state
                icon="fa-glass-cheers"
                title="Tudo em dia!"
                description="Nenhuma tarefa atrasada ou urgente no momento. Ótimo trabalho da equipe!"
            />
            @endforelse
        </div>

        @if($pendingApprovals->count() > 0)
        <div style="background: #0f172a; border-radius: 28px; padding: 30px; border: 1px solid rgba(239,68,68,0.2); box-shadow: 0 10px 40px rgba(0,0,0,0.2); margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px;">
                <h4 style="color: white; font-weight: 950; font-size: 1.1rem; margin: 0;">🔔 Aprovações Pendentes</h4>
                <span style="background:rgba(239,68,68,0.2); color:#ef4444; font-size:.7rem; font-weight:900; padding:4px 10px; border-radius:99px;">{{ $pendingApprovals->count() }}</span>
            </div>
            @foreach($pendingApprovals as $ap)
            <div style="padding:12px 14px; background:rgba(239,68,68,0.05); border-radius:12px; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-weight:800; color:white; font-size:.8rem;">{{ \Illuminate\Support\Str::limit($ap->description, 40) }}</div>
                    <div style="font-size:.7rem; color:rgba(255,255,255,0.5); font-weight:700;">{{ \Carbon\Carbon::parse($ap->date)->format('d/m/Y') }}</div>
                </div>
                <span style="font-weight:900; color:#dc2626; font-size:.875rem;">-R$ {{ number_format($ap->amount,2,',','.') }}</span>
            </div>
            @endforeach
            <a href="{{ url('/manager/approvals') }}" style="display:block; text-align:center; padding:10px; background:#ef4444; color:white; border-radius:12px; font-weight:800; font-size:.8rem; text-decoration:none; margin-top:12px;">
                Revisar Aprovações
            </a>
        </div>
        @endif

        {{-- Bruce AI Insight --}}
        <div style="background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%); border-radius: 28px; padding: 30px; border: 1px solid rgba(99,102,241,0.2); box-shadow: 0 10px 40px rgba(0,0,0,0.2); margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
                <div style="width: 36px; height: 36px; background: rgba(99,102,241,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-brain" style="color: #818cf8; font-size: 1rem;"></i>
                </div>
                <div>
                    <div style="font-weight: 900; color: white; font-size: 0.9rem;">Bruce AI</div>
                    <div style="font-size: 0.65rem; color: #818cf8; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Insight Gerencial</div>
                </div>
            </div>
            <div id="bruce-manager-insight">
                <p class="mb-0" style="font-size:0.8rem;color:rgba(255,255,255,0.4);">
                    <i class="fas fa-circle-notch fa-spin me-2"></i> Analisando dados operacionais...
                </p>
            </div>
        </div>

        <div style="background: #0f172a; border-radius: 28px; padding: 30px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <h4 style="font-weight: 950; color: white; margin-bottom: 22px; font-size:1.1rem;">⚡ Comando Rápido</h4>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="{{ url('/manager/schedule') }}" class="btn-action-pro" style="background: rgba(255,255,255,0.03); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.05); justify-content: space-between; padding:12px 18px;">
                    <span><i class="fas fa-calendar-alt me-2" style="color: #818cf8;"></i> Agenda Integrada</span>
                    <i class="fas fa-chevron-right" style="font-size: 0.7rem; opacity: 0.3;"></i>
                </a>
                <a href="{{ url('/transactions') }}" class="btn-action-pro" style="background: rgba(255,255,255,0.03); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.05); justify-content: space-between; padding:12px 18px;">
                    <span><i class="fas fa-wallet me-2" style="color: #34d399;"></i> Fluxo de Caixa</span>
                    <i class="fas fa-chevron-right" style="font-size: 0.7rem; opacity: 0.3;"></i>
                </a>
                <a href="{{ url('/manager/team') }}" class="btn-action-pro" style="background: rgba(255,255,255,0.03); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.05); justify-content: space-between; padding:12px 18px;">
                    <span><i class="fas fa-user-friends me-2" style="color: #fbbf24;"></i> Gestão de Equipe</span>
                    <i class="fas fa-chevron-right" style="font-size: 0.7rem; opacity: 0.3;"></i>
                </a>
                <button type="button" data-bs-toggle="modal" data-bs-target="#quickTaskModal" class="btn-action-pro" style="background: rgba(99,102,241,0.08); color: #c7d2fe; border: 1px solid rgba(99,102,241,0.2); justify-content: space-between; padding:12px 18px; width:100%; text-align:left;">
                    <span><i class="fas fa-circle-plus me-2" style="color: #818cf8;"></i> Criar Tarefa Rápida</span>
                    <i class="fas fa-chevron-right" style="font-size: 0.7rem; opacity: 0.3;"></i>
                </button>
                <a href="{{ url('/smart-analysis') }}" class="btn-action-pro" style="background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.3); color: white; justify-content: space-between; padding:12px 18px; margin-top:4px;">
                    <span><i class="fas fa-brain me-2" style="color: #818cf8;"></i> Smart AI Analysis</span>
                    <i class="fas fa-bolt" style="color: #f59e0b; font-size: 0.7rem;"></i>
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(async function() {
    try {
        const res  = await fetch('/api/bruce/insight', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
        const data = await res.json();
        const el   = document.getElementById('bruce-manager-insight');
        if (el && data.insight) {
            el.innerHTML = `<p class="mb-0" style="font-size:0.85rem;color:#e2e8f0;line-height:1.6;">${data.insight.replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>').replace(/\n/g,'<br>')}</p>`;
        }
    } catch(e) {}
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctxRadar = document.getElementById('healthRadarChart').getContext('2d');
        new Chart(ctxRadar, {
            type: 'radar',
            data: {
                labels: @json($radarData['labels']),
                datasets: [{
                    label: 'Score Operacional',
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
                plugins: { legend: { display: false } },
                maintainAspectRatio: true
            }
        });

        const ctxUsage = document.getElementById('usageChart').getContext('2d');
        new Chart(ctxUsage, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [
                    {
                        label: 'Projetos',
                        data: @json($chartProjects),
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.15)',
                        tension: 0.35,
                        fill: true,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#0f172a',
                        pointBorderColor: '#6366f1',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Tarefas Concluídas',
                        data: @json($chartTasks),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.12)',
                        tension: 0.35,
                        fill: true,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: '#0f172a',
                        pointBorderColor: '#10b981',
                        pointBorderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, labels: { color: 'rgba(255,255,255,0.7)', font: { weight: '700' } } },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#e2e8f0',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        padding: 12
                    }
                },
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { color: 'rgba(255,255,255,0.4)', font: { size: 11, family: "'Outfit', sans-serif" } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.06)', drawBorder: false },
                        ticks: { color: 'rgba(255,255,255,0.4)' }
                    }
                }
            }
        });

        @if(!empty($leadsBreakdown) && ($leadsBreakdown['total'] ?? 0) > 0)
        (function () {
            const horizontalOpts = {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        callbacks: {
                            label: (ctx) => ` ${ctx.parsed.x} cadastro(s)`
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { color: 'rgba(255,255,255,0.4)', precision: 0 },
                        grid: { color: 'rgba(255,255,255,0.06)', drawBorder: false }
                    },
                    y: {
                        ticks: { color: 'rgba(255,255,255,0.7)', font: { size: 11, weight: '700' } },
                        grid: { display: false, drawBorder: false }
                    }
                }
            };

            const ctxCity = document.getElementById('leadsCityChart');
            if (ctxCity) {
                new Chart(ctxCity.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: @json($leadsBreakdown['cities']['labels']),
                        datasets: [{
                            data: @json($leadsBreakdown['cities']['values']),
                            backgroundColor: '#6366f1',
                            borderRadius: 6,
                            borderWidth: 0,
                            barThickness: 16
                        }]
                    },
                    options: horizontalOpts
                });
            }

            const ctxTags = document.getElementById('leadsTagsChart');
            if (ctxTags) {
                new Chart(ctxTags.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: @json($leadsBreakdown['tags']['labels']),
                        datasets: [{
                            data: @json($leadsBreakdown['tags']['values']),
                            backgroundColor: '#10b981',
                            borderRadius: 6,
                            borderWidth: 0,
                            barThickness: 16
                        }]
                    },
                    options: horizontalOpts
                });
            }
        })();
        @endif

        const ctxEngagement = document.getElementById('engagementChart').getContext('2d');
        new Chart(ctxEngagement, {
            type: 'bar',
            data: {
                labels: @json($waDailyLabels),
                datasets: [
                    {
                        label: 'Enviadas',
                        data: @json($waDailySent),
                        backgroundColor: '#6366f1',
                        borderRadius: 6,
                        borderWidth: 0,
                        barThickness: 12
                    },
                    {
                        label: 'Recebidas',
                        data: @json($waDailyReceived),
                        backgroundColor: '#f59e0b',
                        borderRadius: 6,
                        borderWidth: 0,
                        barThickness: 12
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
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff'
                    }
                },
                scales: {
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { color: 'rgba(255,255,255,0.4)', font: { size: 10 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false },
                        ticks: { color: 'rgba(255,255,255,0.4)', font: { size: 10 } }
                    }
                }
            }
        });
    });
</script>
@endpush

<!-- Add Person Global Modal -->
<div class="modal fade" id="addPersonGlobalModal" role="dialog" aria-modal="true" aria-labelledby="addPersonGlobalModalLabel" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(10px);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 overflow-hidden" style="border-radius: 32px; box-shadow: 0 50px 100px rgba(0,0,0,0.2);">
            <div class="modal-header border-0 py-4 px-5 text-white" style="background: #1e293b;">
                <div>
                    <h4 class="modal-title fw-900 mb-1" id="addPersonGlobalModalLabel">Cadastrar Nova Pessoa</h4>
                    <p class="m-0 opacity-50 small fw-bold text-uppercase">Vincular contato a um projeto</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('projects.people.store.global') }}" method="POST">
                @csrf
                <div class="modal-body p-5">
                    <div class="mb-4">
                        <label for="project_id" class="fw-800 text-uppercase mb-2 small text-muted">Projeto Destino</label>
                        <select name="project_id" class="form-select form-select-lg border-0 bg-light rounded-4 py-3 fw-700" required id="project_id">
                            <option value="">Selecione um projeto...</option>
                            @foreach($projects as $proj)
                                <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="name" class="fw-800 text-uppercase mb-2 small text-muted">Nome Completo</label>
                        <input name="name" type="text" class="form-control form-control-lg border-0 bg-light rounded-4 py-3 fw-700" required id="name">
                    </div>
                    <div class="mb-4">
                        <label for="phone" class="fw-800 text-uppercase mb-2 small text-muted">WhatsApp (com DDD)</label>
                        <input name="phone" type="text" class="form-control form-control-lg border-0 bg-light rounded-4 py-3 fw-700" placeholder="Ex: 11999999999" id="phone">
                    </div>
                    <div class="mb-4">
                        <label for="address" class="fw-800 text-uppercase mb-2 small text-muted">Endereço</label>
                        <input name="address" type="text" class="form-control border-0 bg-light rounded-4 py-3 fw-700" id="address">
                    </div>
                    <div class="mb-0">
                        <label for="city" class="fw-800 text-uppercase mb-2 small text-muted">Cidade</label>
                        <input name="city" type="text" class="form-control border-0 bg-light rounded-4 py-3 fw-700" id="city">
                    </div>
                </div>
                <div class="p-5 pt-0">
                    <button type="submit" class="btn-premium btn-premium-shine w-100 border-0 py-4 fs-5 fw-900">Salvar Contato no Projeto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Person Global Modal -->
<div class="modal fade" id="importPersonGlobalModal" role="dialog" aria-modal="true" aria-labelledby="importPersonGlobalModalLabel" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(10px);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 overflow-hidden" style="border-radius: 32px; box-shadow: 0 50px 100px rgba(0,0,0,0.2);">
            <div class="modal-header border-0 py-4 px-5 text-white" style="background: #1e293b;">
                <div>
                    <h4 class="modal-title fw-900 mb-1">Importar Contatos (CSV)</h4>
                    <p class="m-0 opacity-50 small fw-bold text-uppercase">Vincular lote de contatos a um projeto</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('projects.people.import.global') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-5">
                    <div class="mb-4">
                        <label for="project_id" class="fw-800 text-uppercase mb-2 small text-muted">Projeto Destino</label>
                        <select name="project_id" class="form-select form-select-lg border-0 bg-light rounded-4 py-3 fw-700" required id="project_id">
                            <option value="">Selecione um projeto...</option>
                            @foreach($projects as $proj)
                                <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-4 p-4 rounded-4" style="background: rgba(99,102,241,0.05); border: 1px dashed rgba(99,102,241,0.3);">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <i class="fas fa-file-csv fs-3 text-primary"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Padrão do Arquivo CSV</h6>
                                <p class="small text-muted mb-0">Use as colunas: <strong>Nome, Telefone, Endereço, Cidade</strong></p>
                            </div>
                        </div>
                        <input type="file" name="csv_file" class="form-control bg-white border-0 py-2" accept=".csv, .txt" required id="csv_file">
                    </div>
                </div>
                <div class="p-5 pt-0">
                    <button type="submit" class="btn-premium btn-premium-shine w-100 border-0 py-4 fs-5 fw-900" style="background: #10b981;">
                        <i class="fas fa-cloud-upload-alt me-2"></i> Importar Contatos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL: Criar Tarefa Rápida ===== --}}
<div class="modal fade" id="quickTaskModal" role="dialog" aria-modal="true" aria-labelledby="quickTaskModalLabel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content" style="background: #0f172a; border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; overflow: hidden;">
            <div style="padding: 28px 32px 0;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                    <div>
                        <h5 style="color: white; font-weight: 950; font-size: 1.2rem; margin: 0; letter-spacing: -0.5px;">Nova Tarefa</h5>
                        <p style="color: rgba(255,255,255,0.4); font-size: 0.8rem; margin: 4px 0 0;">Criação rápida sem sair do painel</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
            </div>
            <div style="padding: 0 32px 32px;">
                <div id="quickTaskFeedback" style="display:none; margin-bottom:12px;"></div>

                <div style="margin-bottom: 16px;">
                    <label for="qtTitle" style="color: rgba(255,255,255,0.6); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">Título da Tarefa</label>
                    <input type="text" id="qtTitle" placeholder="Ex: Revisar relatório mensal" maxlength="255"
                        style="width:100%; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:12px 16px; color:white; font-size:0.9rem; outline:none;"
                        onfocus="this.style.borderColor='rgba(99,102,241,0.5)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <div>
                        <label for="qtProject" style="color: rgba(255,255,255,0.6); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">Projeto</label>
                        <select id="qtProject" style="width:100%; background:#1e293b; border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:12px 16px; color:white; font-size:0.85rem; outline:none;">
                            <option value="">— Sem projeto</option>
                            @foreach($projects as $proj)
                                <option value="{{ $proj->id }}">{{ \Illuminate\Support\Str::limit($proj->name, 28) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="qtPriority" style="color: rgba(255,255,255,0.6); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">Prioridade</label>
                        <select id="qtPriority" style="width:100%; background:#1e293b; border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:12px 16px; color:white; font-size:0.85rem; outline:none;">
                            <option value="low">Baixa</option>
                            <option value="medium" selected>Média</option>
                            <option value="high">Alta</option>
                            <option value="critical">Crítica</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label for="qtDueDate" style="color: rgba(255,255,255,0.6); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">Prazo (opcional)</label>
                    <input type="date" id="qtDueDate"
                        style="width:100%; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:12px 16px; color:white; font-size:0.9rem; outline:none;"
                        onfocus="this.style.borderColor='rgba(99,102,241,0.5)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
                </div>

                <button type="button" id="qtSubmit" onclick="submitQuickTask()" style="width:100%; background:linear-gradient(135deg,#6366f1,#4f46e5); color:white; border:none; border-radius:14px; padding:14px; font-weight:900; font-size:0.95rem; cursor:pointer; box-shadow:0 8px 24px rgba(99,102,241,0.25); transition:.2s;" onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                    <i class="fas fa-plus me-2"></i> Criar Tarefa
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
async function submitQuickTask() {
    const title = document.getElementById('qtTitle').value.trim();
    if (!title) {
        document.getElementById('qtTitle').style.borderColor = 'rgba(239,68,68,0.6)';
        return;
    }

    const btn = document.getElementById('qtSubmit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i> Criando...';

    const payload = {
        title,
        status: 'todo',
        priority: document.getElementById('qtPriority').value,
        project_id: document.getElementById('qtProject').value || null,
        due_date: document.getElementById('qtDueDate').value || null,
    };

    try {
        const res = await fetch('/api/tasks/create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify(payload),
        });
        const data = await res.json();

        const fb = document.getElementById('quickTaskFeedback');
        if (data.success) {
            fb.style.display = 'block';
            fb.innerHTML = '<div style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:10px;padding:10px 14px;color:#34d399;font-size:0.8rem;font-weight:800;"><i class="fas fa-check me-2"></i> Tarefa criada com sucesso!</div>';
            document.getElementById('qtTitle').value = '';
            document.getElementById('qtDueDate').value = '';
            setTimeout(() => { bootstrap.Modal.getInstance(document.getElementById('quickTaskModal')).hide(); fb.style.display='none'; }, 1800);
        } else {
            fb.style.display = 'block';
            fb.innerHTML = '<div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:10px;padding:10px 14px;color:#f87171;font-size:0.8rem;font-weight:800;"><i class="fas fa-xmark me-2"></i> Erro ao criar tarefa.</div>';
        }
    } catch(e) {
        const fb = document.getElementById('quickTaskFeedback');
        fb.style.display = 'block';
        fb.innerHTML = '<div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:10px;padding:10px 14px;color:#f87171;font-size:0.8rem;">Erro de conexão. Tente novamente.</div>';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus me-2"></i> Criar Tarefa';
    }
}
</script>
@endpush

@endsection
