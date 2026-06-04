@extends('layouts.app')

@section('content')
@php
    $basePath = rtrim(request()->getBaseUrl(), '/');
    $isManager = in_array(auth()->user()->role, ['manager', 'super_admin'], true);
@endphp

@if($isManager && session('invite_link'))
    <div class="alert alert-warning d-flex align-items-start justify-content-between gap-3" style="border-radius: 18px; border: 1px solid #fde68a; background: #fffbeb; padding: 16px 18px; margin-bottom: 18px;">
        <div style="flex: 1;">
            <div style="font-weight: 900; color:#92400e; margin-bottom: 6px;">Link para definir senha do novo membro</div>
            <div style="color:#92400e; font-weight: 700; font-size: .9rem; margin-bottom: 10px;">
                Enviamos por e-mail para <span style="font-weight:900;">{{ session('invite_email') }}</span>. Se não chegar (ex.: ambiente local), copie e envie este link:
            </div>
            <div style="display:flex; gap:10px; flex-wrap: wrap;">
                <input id="inviteLinkInput" type="text" readonly value="{{ session('invite_link') }}" class="form-control" style="flex: 1; min-width: min(520px, 90vw); border-radius: 14px; border: 1px solid #fde68a; background: #fff;">
                <button type="button" class="btn btn-dark" style="border-radius: 14px; font-weight: 900;" onclick="(async () => { const v = document.getElementById('inviteLinkInput')?.value; if (!v) return; try { await navigator.clipboard.writeText(v); } catch (e) { document.getElementById('inviteLinkInput')?.select(); } })();">
                    Copiar link
                </button>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<style>
    .project-hero-premium {
        background: #0f172a;
        border-radius: 32px;
        padding: 50px;
        color: white;
        position: relative;
        overflow: hidden;
        margin-bottom: 32px;
        border: 1px solid rgba(255,255,255,0.05);
        box-shadow: 0 40px 100px rgba(0,0,0,0.2);
    }
    .project-hero-premium::before {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: radial-gradient(circle at 20% 30%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                    radial-gradient(circle at 80% 70%, rgba(16, 185, 129, 0.1) 0%, transparent 50%);
        z-index: 1;
    }
    .stat-pill-premium {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        padding: 24px;
        border-radius: 24px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 140px;
    }
    .stat-pill-premium:hover {
        background: rgba(255, 255, 255, 0.06);
        transform: translateY(-5px);
        border-color: rgba(99, 102, 241, 0.4);
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }
    .btn-action-pro {
        padding: 12px 24px;
        border-radius: 14px;
        font-weight: 800;
        font-size: 0.85rem;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }
    .btn-action-pro:hover {
        transform: scale(1.02);
    }
    .project-table-card {
        background: white;
        border-radius: 28px;
        padding: 40px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 10px 40px rgba(0,0,0,0.02);
    }
    .stakeholder-card {
        background: #f8fafc;
        border-radius: 20px;
        padding: 15px;
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 12px;
        border: 1px solid #f1f5f9;
        transition: all 0.2s;
    }
    .stakeholder-card:hover {
        background: white;
        border-color: #6366f1;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.05);
    }
    .avatar-placeholder {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: #1e293b;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.1rem;
    }

    /* Timeline Styles */
    .impact-timeline {
        position: relative;
        padding-left: 50px;
    }
    .impact-timeline::before {
        content: '';
        position: absolute;
        left: 20px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e2e8f0;
    }
    .timeline-item {
        position: relative;
        margin-bottom: 40px;
    }
    .timeline-dot {
        position: absolute;
        left: -42px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: white;
        border: 4px solid #6366f1;
        z-index: 1;
    }
    .timeline-content-card {
        background: white;
        border-radius: 20px;
        padding: 24px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        transition: all 0.3s ease;
    }
    .timeline-content-card:hover {
        transform: translateX(10px);
        border-color: #6366f1;
    }
    .timeline-media {
        border-radius: 16px;
        overflow: hidden;
        margin-top: 15px;
        border: 1px solid #f1f5f9;
    }
    .timeline-type-badge {
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 4px 12px;
        border-radius: 50px;
        margin-bottom: 10px;
        display: inline-block;
    }
    /* Role selection cards */
    .role-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 20px 12px;
        border: 2px solid #e2e8f0;
        border-radius: 16px;
        text-align: center;
        cursor: pointer;
        transition: all .18s;
        background: #fff;
        user-select: none;
        margin: 0;
    }
    .role-card:hover {
        border-color: #a5b4fc;
        background: #f5f3ff;
    }
    .role-card.role-card-active {
        border-color: #6366f1;
        background: #eef2ff;
        box-shadow: 0 0 0 3px rgba(99,102,241,.15);
    }
    .role-card.role-card-active .fw-900 { color: #4f46e5; }
    .role-card.role-card-active i { color: #6366f1 !important; }
</style>

<div class="project-hero-premium">
    <div style="position: relative; z-index: 10;">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px;">
             <span style="background: #6366f1; color: white; padding: 6px 18px; border-radius: 50px; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 1.5px;">
                <i class="fas fa-rocket me-2"></i>
                {{ $project->status == 'active'
                    ? 'Em Missão'
                    : ($project->status == 'paused'
                        ? 'Em Pausa'
                        : ($project->status == 'completed' ? 'Concluído' : 'Cancelado'))
                }}
            </span>
            <span style="color: rgba(255,255,255,0.4); font-weight: 700; font-size: 0.8rem;">REGISTRO #{{ str_pad($project->id, 5, '0', STR_PAD_LEFT) }}</span>
        </div>
        
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 48px;">
            <div style="flex: 1;">
                <h1 style="font-size: 3.5rem; font-weight: 950; margin: 0; letter-spacing: -3px; line-height: 1; color: white;">{{ $project->name }}</h1>
                <div style="display: flex; align-items: center; gap: 15px; margin-top: 15px;">
                    <span style="font-size: 1rem; color: rgba(255,255,255,0.4); font-weight: 600;">{{ $project->description ?: 'Gestão de alta performance Vivensi' }}</span>
                </div>
            </div>
            <div style="display: flex; gap: 12px;">
                @if($isManager)
                    <a href="{{ $basePath . '/projects/'.$project->id.'/edit' }}" class="btn-action-pro" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white;">
                        <i class="fas fa-cog" style="color: #94a3b8;"></i> Ajustes
                    </a>
                    <button id="btn-project-pdf" onclick="generateProjectPdf(this)" class="btn-action-pro" style="background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: white;" title="Exportar Relatório PDF">
                        <i class="fas fa-file-pdf" style="color: #f87171;"></i> Relatório PDF
                    </button>
                @endif
                <a href="{{ $basePath . '/projects/'.$project->id.'/kanban' }}" class="btn-action-pro" style="background: white; color: #0f172a;">
                    <i class="fas fa-tasks" style="color: #6366f1;"></i> Quadros Kanban
                </a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-3">
                <div class="stat-pill-premium">
                    <div>
                        <span style="display: block; font-size: 0.65rem; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 12px;">Budget Destinado</span>
                        <div style="font-size: 1.8rem; font-weight: 950; letter-spacing: -1px;">R$ {{ number_format($project->budget, 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div style="margin-top: 20px; height: 3px; background: rgba(255,255,255,0.05); border-radius: 2px;">
                            <div style="width: 100%; height: 100%; background: #10b981; border-radius: 2px; box-shadow: 0 0 10px rgba(16, 185, 129, 0.4);"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-pill-premium">
                    <div>
                        <span style="display: block; font-size: 0.65rem; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 12px;">Aporte Realizado</span>
                        <div style="font-size: 1.8rem; font-weight: 950; color: #f43f5e; letter-spacing: -1px;">R$ {{ number_format($totalSpent, 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div style="margin-top: 20px; height: 3px; background: rgba(255,255,255,0.05); border-radius: 2px;">
                            <div style="width: {{ min($percentUsed, 100) }}%; height: 100%; background: #f43f5e; border-radius: 2px; box-shadow: 0 0 10px rgba(244, 63, 94, 0.4);"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-pill-premium">
                    <div>
                        <span style="display: block; font-size: 0.65rem; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 12px;">Saldo em Caixa</span>
                        <div style="font-size: 1.8rem; font-weight: 950; color: #6366f1; letter-spacing: -1px;">R$ {{ number_format($project->budget - $totalSpent, 0, ',', '.') }}</div>
                    </div>
                    <div style="margin-top: 20px; font-size: 0.7rem; font-weight: 800; color: rgba(255,255,255,0.4);">
                         {{ number_format(100 - $percentUsed, 1) }}% disponível
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-pill-premium">
                    <div>
                        <span style="display: block; font-size: 0.65rem; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 12px;">Performance</span>
                        <div style="font-size: 1.8rem; font-weight: 950; color: #10b981; letter-spacing: -1px;">{{ number_format(100 - min($percentUsed, 100), 0) }}%</div>
                    </div>
                    <div style="margin-top: 20px; font-size: 0.7rem; font-weight: 800; color: rgba(255,255,255,0.4);">
                        Taxa de Eficiência
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Operations Column -->
    <div class="col-lg-8">
        <div class="project-table-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h4 style="margin: 0; font-weight: 900; color: #1e293b; letter-spacing: -0.5px;">Dossiê Financeiro</h4>
                    <p style="margin: 5px 0 0 0; color: #94a3b8; font-weight: 600; font-size: 0.85rem;">Últimas movimentações vinculadas a este registro.</p>
                </div>
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <a href="{{ url('/transactions?project_id=' . $project->id) }}" class="btn-ds btn-ds-outline" style="padding: 10px 16px; font-weight: 700; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-filter"></i> Ver todas com filtros
                    </a>
                    @if($isManager)
                        <a href="{{ $basePath . '/transactions/create?project_id='.$project->id }}" class="btn-premium btn-premium-shine" style="border: none; padding: 12px 25px; font-weight: 800; font-size: 0.85rem;">
                            <i class="fas fa-plus me-2"></i> Lançar Movimentação
                        </a>
                    @endif
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover" style="vertical-align: middle;">
                    <thead>
                        <tr style="border-bottom: 2px solid #f1f5f9;">
                            <th style="padding: 15px; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Registro</th>
                            <th style="padding: 15px; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Descrição</th>
                            <th style="padding: 15px; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Data</th>
                            <th style="padding: 15px; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; text-align: right;">Montante</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $t)
                        <tr style="border-bottom: 1px solid #f8fafc;">
                            <td style="padding: 20px 15px; font-weight: 800; color: #6366f1;">#{{ str_pad($t->id, 4, '0', STR_PAD_LEFT) }}</td>
                            <td style="padding: 20px 15px;">
                                <div style="font-weight: 800; color: #1e293b; font-size: 0.95rem;">{{ $t->description }}</div>
                                <div style="font-size: 0.7rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">{{ $t->category->name ?? 'Geral' }}</div>
                            </td>
                            <td style="padding: 20px 15px; font-weight: 600; color: #64748b; font-size: 0.85rem;">{{ \Carbon\Carbon::parse($t->date)->format('d/m/Y') }}</td>
                            <td style="padding: 20px 15px; text-align: right;">
                                <div style="font-weight: 900; font-size: 1.1rem; color: {{ $t->type == 'expense' ? '#f43f5e' : '#10b981' }}">
                                    {{ $t->type == 'expense' ? '-' : '+' }} R$ {{ number_format($t->amount, 2, ',', '.') }}
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="padding: 60px; text-align: center;">
                                <i class="fas fa-receipt style='font-size: 3rem; color: #f1f5f9; margin-bottom: 20px; display: block;"></i>
                                <span style="font-weight: 700; color: #cbd5e1;">Aguardando o primeiro registro de tesouraria.</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Governance Column -->
    <div class="col-lg-4">
        <div style="display: flex; flex-direction: column; gap: 30px;">
            
            <!-- Stakeholders -->
            <div class="project-table-card" style="padding: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h5 style="margin: 0; font-weight: 900; color: #1e293b;">Stakeholders</h5>
                    @if($isManager)
                        <button class="btn btn-light rounded-circle" style="width: 36px; height: 36px; padding: 0;" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                            <i class="fas fa-user-plus text-primary"></i>
                        </button>
                    @endif
                </div>

                <div class="stakeholder-card" style="border-left: 4px solid #1e293b;">
                    <div class="avatar-placeholder">{{ substr(auth()->user()->name, 0, 1) }}</div>
                    <div>
                        <div style="font-weight: 800; color: #1e293b; font-size: 0.9rem;">{{ auth()->user()->name }}</div>
                        <div style="font-size: 0.65rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Responsável Geral</div>
                    </div>
                </div>

                @foreach($members as $member)
                <div class="stakeholder-card">
                    <div class="avatar-placeholder" style="background: #f1f5f9; color: #1e293b;">{{ substr($member->user->name, 0, 1) }}</div>
                    <div style="flex-grow: 1;">
                        <div style="font-weight: 800; color: #1e293b; font-size: 0.9rem;">{{ $member->user->name }}</div>
                        <div style="font-size: 0.65rem; color: #94a3b8; font-weight: 800; text-transform: uppercase;">{{ strtoupper($member->access_level) }}</div>
                    </div>
                    @if($isManager)
                        <form action="{{ $basePath . '/projects/'.$project->id.'/members/'.$member->id }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-link btn-sm text-danger p-0"><i class="fas fa-times-circle"></i></button>
                        </form>
                    @endif
                </div>
                @endforeach
            </div>

            <!-- Actions -->
            <div class="project-table-card" style="padding: 30px;">
                <h5 style="margin: 0 0 25px 0; font-weight: 900; color: #1e293b;">Toolkit Estratégico</h5>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <a href="{{ $basePath . '/manager/schedule' }}" class="btn-ds btn-ds-outline" style="text-decoration: none; display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fas fa-calendar-alt me-2 text-primary"></i> Agenda da Missão</span>
                        <i class="fas fa-chevron-right" style="font-size: 0.7rem; opacity: 0.3;"></i>
                    </a>
                    <a href="{{ $basePath . '/manager/approvals' }}" class="btn-ds btn-ds-outline" style="text-decoration: none; display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fas fa-check-double me-2 text-success"></i> Central de Aprovações</span>
                        <i class="fas fa-chevron-right" style="font-size: 0.7rem; opacity: 0.3;"></i>
                    </a>
                    <a href="{{ $basePath . '/manager/reconciliation' }}" class="btn-ds btn-ds-outline" style="text-decoration: none; display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fas fa-sync-alt me-2 text-info"></i> Conciliação Bancária</span>
                        <i class="fas fa-chevron-right" style="font-size: 0.7rem; opacity: 0.3;"></i>
                    </a>
                    <a href="{{ $basePath . '/manager/contracts' }}" class="btn-ds btn-ds-outline" style="text-decoration: none; display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fas fa-file-signature me-2 text-indigo"></i> Contratos Digitais</span>
                        <i class="fas fa-chevron-right" style="font-size: 0.7rem; opacity: 0.3;"></i>
                    </a>
                    <a href="{{ $basePath . '/smart-analysis' }}" class="btn-ds btn-ds-outline" style="text-decoration: none; display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fas fa-brain me-2 text-primary"></i> Smart Analysis</span>
                        <i class="fas fa-chevron-right" style="font-size: 0.7rem; opacity: 0.3;"></i>
                    </a>
                    @if(config('bruce.context_project_enabled'))
                        <button type="button" data-bruce-project-id="{{ $project->id }}" data-bruce-project-name="{{ $project->name }}" id="btn-open-bruce-project" class="btn-ds btn-ds-outline" style="text-decoration: none; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg,#f0f9ff,#e0f2fe); border: 1px solid #bae6fd;">
                            <span><i class="fas fa-robot me-2" style="color:#0369a1;"></i> Perguntar ao Bruce sobre este projeto</span>
                            <i class="fas fa-chevron-right" style="font-size: 0.7rem; opacity: 0.3;"></i>
                        </button>
                    @endif
                    @php
                        $isArchived = !empty($project) && method_exists($project, 'isArchived') && $project->isArchived();
                    @endphp
                    @if($isArchived)
                        <form method="POST" action="{{ url('/projects/' . $project->id . '/unarchive') }}" onsubmit="return confirm('Reativar este projeto e voltar para a listagem ativa?');" style="margin-top: 12px;">
                            @csrf
                            <button type="submit" class="btn-ds btn-ds-success" style="width: 100%; font-size: 0.8rem; text-transform: uppercase;">
                                <i class="fas fa-box-open me-2"></i> Reativar Projeto
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ url('/projects/' . $project->id . '/archive') }}" onsubmit="return confirm('Arquivar este projeto? Ele sai da listagem ativa mas mantém todo o histórico (transações, timeline, beneficiários, tarefas). Você pode reativar a qualquer momento.');" style="margin-top: 12px;">
                            @csrf
                            <button type="submit" class="btn-ds btn-ds-danger" style="width: 100%; font-size: 0.8rem; text-transform: uppercase;">
                                <i class="fas fa-archive me-2"></i> Arquivar Registro
                            </button>
                        </form>
                    @endif
                </div>
            </div>

        </div>
</div>

<!-- Pessoas & Contatos Section -->
<div class="project-table-card mt-4">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h4 style="margin: 0; font-weight: 900; color: #1e293b; letter-spacing: -0.5px;">Pessoas & Contatos</h4>
            <p style="margin: 5px 0 0 0; color: #94a3b8; font-weight: 600; font-size: 0.85rem;">Gerencie o cadastro de pessoas relacionadas a este projeto.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="button" id="btn-open-add-person" onclick="openProjectModal('addPersonModal')" class="btn-ds btn-ds-outline" style="padding: 12px 20px; font-weight: 800; font-size: 0.85rem;">
                <i class="fas fa-user-plus me-2" style="color: #6366f1;"></i> Nova Pessoa
            </button>
            <button type="button" id="btn-open-import-person" onclick="openProjectModal('importPersonProjectModal')" class="btn-ds btn-ds-outline" style="padding: 12px 20px; font-weight: 800; font-size: 0.85rem;">
                <i class="fas fa-file-csv me-2" style="color: #f59e0b;"></i> Importar CSV
            </button>
            @if($project->people->count() > 0)
                <form action="{{ route('projects.broadcast.create', $project->id) }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-premium btn-premium-shine" style="background: #10b981; color: white; border: none; padding: 12px 20px; font-weight: 800; font-size: 0.85rem;">
                        <i class="fab fa-whatsapp me-2"></i> Criar Lista de Disparo
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover" style="vertical-align: middle;">
            <thead>
                <tr style="border-bottom: 2px solid #f1f5f9;">
                    <th style="padding: 15px; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Nome</th>
                    <th style="padding: 15px; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Telefone</th>
                    <th style="padding: 15px; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Endereço/Cidade</th>
                    <th style="padding: 15px; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($project->people as $person)
                <tr style="border-bottom: 1px solid #f8fafc;">
                    <td style="padding: 15px;">
                        <div style="font-weight: 800; color: #1e293b;">{{ $person->name }}</div>
                    </td>
                    <td style="padding: 15px;">
                        <div style="font-weight: 600; color: #64748b;">
                            <i class="fab fa-whatsapp" style="color: #10b981;"></i> {{ $person->phone ?: '-' }}
                        </div>
                    </td>
                    <td style="padding: 15px;">
                        <div style="font-size: 0.85rem; color: #64748b; font-weight: 600;">
                            {{ $person->address ?: '-' }} <br>
                            <span style="color: #94a3b8;">{{ $person->city ?: '-' }}</span>
                        </div>
                    </td>
                    <td style="padding: 15px; text-align: right;">
                        <form action="{{ route('projects.people.destroy', [$project->id, $person->id]) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Excluir esta pessoa?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light text-danger" style="border-radius: 8px;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="padding: 40px; text-align: center;">
                        <i class="fas fa-users-slash" style="font-size: 3rem; color: #f1f5f9; margin-bottom: 20px; display: block;"></i>
                        <span style="font-weight: 700; color: #cbd5e1;">Nenhuma pessoa cadastrada neste projeto.</span>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Impact Timeline Section -->
<div class="project-table-card mt-4">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px;">
        <div>
            <h4 style="margin: 0; font-weight: 900; color: #1e293b; letter-spacing: -0.5px;">Linha do Tempo de Impacto</h4>
            <p style="margin: 5px 0 0 0; color: #94a3b8; font-weight: 600; font-size: 0.85rem;">Evidências e marcos históricos da execução do projeto.</p>
        </div>
        @if($isManager)
            <button class="btn-premium btn-premium-shine" style="border: none; padding: 12px 25px; font-weight: 800; font-size: 0.85rem;" data-bs-toggle="modal" data-bs-target="#addTimelineModal">
                <i class="fas fa-magic me-2"></i> Registrar Impacto
            </button>
        @endif
    </div>

    <div class="impact-timeline">
        @forelse($project->timelineRecords as $record)
            <div class="timeline-item">
                <div class="timeline-dot" style="border-color: {{ $record->type == 'milestone' ? '#10b981' : ($record->type == 'photo' ? '#6366f1' : '#f59e0b') }}"></div>
                <div class="timeline-content-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <span class="timeline-type-badge" style="background: {{ $record->type == 'milestone' ? '#ecfdf5' : ($record->type == 'photo' ? '#eef2ff' : '#fff7ed') }}; color: {{ $record->type == 'milestone' ? '#059669' : ($record->type == 'photo' ? '#4f46e5' : '#d97706') }}">
                                {{ strtoupper($record->type) }}
                            </span>
                            <h5 style="font-weight: 950; color: #1e293b; margin-bottom: 5px;">{{ $record->title }}</h5>
                            <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 700; margin-bottom: 15px;">
                                <i class="far fa-calendar-alt me-1"></i> {{ $record->date->format('d/m/Y') }}
                            </div>
                        </div>
                        @if($isManager)
                            <form action="{{ route('projects.timeline.destroy', [$project->id, $record->id]) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-link text-danger p-0" onclick="return confirm('Excluir este registro?')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                    
                    <p style="color: #64748b; font-weight: 500; font-size: 0.95rem; margin-bottom: 0;">{{ $record->content }}</p>

                    @if($record->media_path)
                        <div class="timeline-media">
                            <img loading="lazy" src="{{ asset('storage/' . $record->media_path) }}" alt="Impacto" style="width: 100%; max-height: 400px; object-fit: cover;">
                        </div>
                    @endif

                    @if($record->external_url)
                        <div class="mt-3">
                            <a href="{{ $record->external_url }}" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-4 fw-bold">
                                <i class="fas fa-external-link-alt me-2"></i> Ver Conteúdo Externo
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div style="padding: 60px; text-align: center;">
                <i class="fas fa-stream" style="font-size: 3rem; color: #f1f5f9; margin-bottom: 20px; display: block;"></i>
                <span style="font-weight: 700; color: #cbd5e1;">Ainda não há registros na linha do tempo.</span>
            </div>
        @endforelse
    </div>
</div>

<!-- ── Diário de Evolução do Projeto ──────────────────────────────────────── -->
<div class="project-table-card mt-4" id="project-diary">

    {{-- Header --}}
    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:28px; flex-wrap:wrap; gap:12px;">
        <div>
            <h4 style="margin:0; font-weight:900; color:#1e293b; letter-spacing:-0.5px;">
                <i class="fas fa-book-open me-2" style="color:#6366f1;"></i> Diário de Evolução
            </h4>
            <p style="margin:5px 0 0; color:#94a3b8; font-weight:600; font-size:.85rem;">
                Atualizações diárias da equipe · {{ $logs->count() }} entr{{ $logs->count() === 1 ? 'ada' : 'adas' }}
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            @if($logs->count() >= 3)
            <button id="btn-generate-summary" onclick="generateAiSummary()"
                class="btn btn-sm fw-bold"
                style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:12px;padding:10px 20px;">
                <i class="fas fa-wand-magic-sparkles me-2"></i> Gerar Relatório IA
            </button>
            @endif
        </div>
    </div>

    {{-- Relatório IA (se existir) --}}
    @if($project->ai_summary)
    <div id="ai-summary-box" style="background:#f5f3ff;border:1px solid #c4b5fd;border-radius:16px;padding:24px;margin-bottom:28px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <span style="font-weight:800;color:#6d28d9;font-size:.85rem;text-transform:uppercase;letter-spacing:.5px;">
                <i class="fas fa-robot me-2"></i> Relatório Gerado por IA
            </span>
            <span style="font-size:.75rem;color:#a78bfa;font-weight:600;">
                {{ $project->ai_summary_at?->format('d/m/Y H:i') }}
            </span>
        </div>
        <div id="ai-summary-content" style="font-size:.88rem;color:#1e293b;line-height:1.75;white-space:pre-wrap;">{{ $project->ai_summary }}</div>
    </div>
    @else
    <div id="ai-summary-box" style="display:none;background:#f5f3ff;border:1px solid #c4b5fd;border-radius:16px;padding:24px;margin-bottom:28px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <span style="font-weight:800;color:#6d28d9;font-size:.85rem;text-transform:uppercase;letter-spacing:.5px;">
                <i class="fas fa-robot me-2"></i> Relatório Gerado por IA
            </span>
            <span id="ai-summary-date" style="font-size:.75rem;color:#a78bfa;font-weight:600;"></span>
        </div>
        <div id="ai-summary-content" style="font-size:.88rem;color:#1e293b;line-height:1.75;white-space:pre-wrap;"></div>
    </div>
    @endif

    {{-- Formulário de nova entrada --}}
    <form action="{{ route('projects.logs.store', $project->id) }}" method="POST" class="mb-4">
        @csrf
        @if(session('log_success'))
            <div class="alert alert-success rounded-3 border-0 mb-3 py-2 px-3" style="font-size:.85rem;">
                {{ session('log_success') }}
            </div>
        @endif
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;padding:18px;">
            <label style="font-weight:700;font-size:.8rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:10px;">
                Nova Entrada
            </label>
            <textarea name="body" rows="3" required maxlength="3000"
                placeholder="Descreva o que foi feito hoje, dificuldades encontradas, próximas ações..."
                style="width:100%;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:12px 14px;font-size:.88rem;resize:vertical;outline:none;line-height:1.6;"
                onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
            <div style="display:flex;justify-content:flex-end;margin-top:10px;">
                <button type="submit"
                    style="background:#6366f1;color:#fff;border:none;border-radius:12px;padding:10px 24px;font-weight:700;font-size:.85rem;cursor:pointer;">
                    <i class="fas fa-plus me-2"></i> Registrar
                </button>
            </div>
        </div>
    </form>

    {{-- Lista de entradas --}}
    @if($logs->isEmpty())
        <div style="text-align:center;padding:40px;color:#94a3b8;">
            <i class="fas fa-journal-whills" style="font-size:2.5rem;margin-bottom:12px;display:block;color:#e2e8f0;"></i>
            <span style="font-weight:600;">Nenhuma entrada ainda. Comece registrando o progresso de hoje.</span>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:12px;">
            @foreach($logs as $log)
            <div style="background:#fff;border:1px solid #f1f5f9;border-radius:14px;padding:18px 20px;position:relative;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.8rem;flex-shrink:0;">
                            {{ strtoupper(substr($log->user?->name ?? 'U', 0, 1)) }}
                        </div>
                        <div>
                            <div style="font-weight:700;color:#1e293b;font-size:.88rem;">{{ $log->user?->name ?? 'Usuário' }}</div>
                            <div style="font-size:.75rem;color:#94a3b8;">
                                <i class="far fa-clock me-1"></i>{{ $log->created_at->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    </div>
                    @if(in_array(auth()->user()->role, ['manager','super_admin']) || $log->user_id === auth()->id())
                    <form action="{{ route('projects.logs.destroy', [$project->id, $log->id]) }}" method="POST"
                          onsubmit="return confirm('Remover esta entrada?')">
                        @csrf @method('DELETE')
                        <button type="submit" style="background:none;border:none;color:#cbd5e1;cursor:pointer;padding:4px;"
                            onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#cbd5e1'">
                            <i class="fas fa-trash-alt" style="font-size:.85rem;"></i>
                        </button>
                    </form>
                    @endif
                </div>
                <p style="margin:0;color:#475569;font-size:.88rem;line-height:1.65;white-space:pre-wrap;">{{ $log->body }}</p>
            </div>
            @endforeach
        </div>
    @endif
</div>

<script>
const _summaryStatusUrl = '{{ route("projects.logs.summary-status", $project->id) }}';
let _summaryPollBtn = null;
let _summaryPollOrig = null;

function pollProjectSummary(attempts) {
    attempts = attempts || 0;
    if (attempts > 36) {
        alert('A IA demorou demais. Tente novamente.');
        if (_summaryPollBtn) { _summaryPollBtn.innerHTML = _summaryPollOrig; _summaryPollBtn.disabled = false; }
        return;
    }
    fetch(_summaryStatusUrl, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(function(data) {
            if (data.status === 'done') {
                const box = document.getElementById('ai-summary-box');
                document.getElementById('ai-summary-content').textContent = data.summary;
                if (document.getElementById('ai-summary-date')) {
                    document.getElementById('ai-summary-date').textContent = data.summary_at;
                }
                box.style.display = 'block';
                box.scrollIntoView({ behavior: 'smooth', block: 'start' });
                if (_summaryPollBtn) {
                    _summaryPollBtn.innerHTML = '<i class="fas fa-check me-2"></i> Relatório Gerado';
                    setTimeout(() => { _summaryPollBtn.innerHTML = _summaryPollOrig; _summaryPollBtn.disabled = false; }, 3000);
                }
            } else if (data.status === 'failed') {
                alert('A IA encontrou um erro. Verifique as chaves de API e tente novamente.');
                if (_summaryPollBtn) { _summaryPollBtn.innerHTML = _summaryPollOrig; _summaryPollBtn.disabled = false; }
            } else {
                setTimeout(function() { pollProjectSummary(attempts + 1); }, 5000);
            }
        })
        .catch(function() {
            setTimeout(function() { pollProjectSummary(attempts + 1); }, 8000);
        });
}

async function generateAiSummary() {
    const btn = document.getElementById('btn-generate-summary');
    if (!btn) return;
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Gerando...';
    btn.disabled = true;
    _summaryPollBtn = btn;
    _summaryPollOrig = orig;

    try {
        const res = await fetch('{{ route("projects.logs.summary", $project->id) }}', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        const data = await res.json();

        if (!res.ok) {
            alert(data.error ?? 'Erro ao gerar relatório.');
            btn.innerHTML = orig;
            btn.disabled = false;
            return;
        }

        if (data.status === 'processing') {
            // Job dispatched — start polling
            pollProjectSummary(0);
        } else if (data.summary) {
            // Synchronous fallback (shouldn't happen but safe)
            const box = document.getElementById('ai-summary-box');
            document.getElementById('ai-summary-content').textContent = data.summary;
            box.style.display = 'block';
            btn.innerHTML = '<i class="fas fa-check me-2"></i> Relatório Gerado';
            setTimeout(() => { btn.innerHTML = orig; btn.disabled = false; }, 3000);
        }
    } catch (e) {
        alert('Falha na comunicação com o servidor.');
        btn.innerHTML = orig;
        btn.disabled = false;
    }
}

let _pdfPollBtn = null;
let _pdfPollOrig = null;
let _pdfStatusUrl = null;

function pollProjectPdf(attempts) {
    attempts = attempts || 0;
    if (attempts > 36) {
        alert('A geração do PDF demorou demais. Tente novamente.');
        if (_pdfPollBtn) { _pdfPollBtn.innerHTML = _pdfPollOrig; _pdfPollBtn.disabled = false; }
        return;
    }
    fetch(_pdfStatusUrl).then(r => r.json()).then(function(data) {
        if (data.status === 'done' && data.download_url) {
            window.location.href = data.download_url;
            if (_pdfPollBtn) { _pdfPollBtn.innerHTML = _pdfPollOrig; _pdfPollBtn.disabled = false; }
        } else if (data.status === 'failed') {
            alert('Falha ao gerar o PDF. Tente novamente.');
            if (_pdfPollBtn) { _pdfPollBtn.innerHTML = _pdfPollOrig; _pdfPollBtn.disabled = false; }
        } else {
            setTimeout(function() { pollProjectPdf(attempts + 1); }, 5000);
        }
    }).catch(function() {
        setTimeout(function() { pollProjectPdf(attempts + 1); }, 8000);
    });
}

async function generateProjectPdf(btn) {
    _pdfPollOrig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="color:#f87171;"></i> Gerando...';
    btn.disabled = true;
    _pdfPollBtn = btn;
    try {
        const resp = await fetch('{{ url("/projects/".$project->id."/export-pdf") }}', {
            method: 'GET', headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
        });
        const data = await resp.json();
        if (data.status_url) {
            _pdfStatusUrl = data.status_url;
            pollProjectPdf(0);
        } else {
            alert('Erro ao iniciar geração do PDF.');
            btn.innerHTML = _pdfPollOrig; btn.disabled = false;
        }
    } catch(e) {
        alert('Falha na comunicação com o servidor.');
        btn.innerHTML = _pdfPollOrig; btn.disabled = false;
    }
}
</script>

<!-- Timeline Modal -->
@if($isManager)
<div class="modal fade" id="addTimelineModal" role="dialog" aria-modal="true" aria-labelledby="addTimelineModalLabel" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(10px);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 overflow-hidden" style="border-radius: 32px; box-shadow: 0 50px 100px rgba(0,0,0,0.2);">
            <div class="modal-header border-0 py-4 px-5 text-white" style="background: #1e293b;">
                <div>
                    <h4 class="modal-title fw-900 mb-1" id="addTimelineModalLabel">Registrar Impacto</h4>
                    <p class="m-0 opacity-50 small fw-bold text-uppercase">Evidências para o Dossiê</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('projects.timeline.store', $project->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-5">
                    <div class="mb-4">
                        <label class="fw-800 text-uppercase mb-2 small text-muted">Título do Marco</label>
                        <input name="title" type="text" class="form-control form-control-lg border-0 bg-light rounded-4 py-3 fw-700" placeholder="Ex: Entrega de cestas básicas" required>
                    </div>

                    <div class="mb-4">
                        <label class="fw-800 text-uppercase mb-2 small text-muted">Tipo de Registro</label>
                        <select name="type" class="form-select form-select-lg border-0 bg-light rounded-4 py-3 fw-700">
                            <option value="status">Atualização de Status</option>
                            <option value="milestone">Marco Histórico (Milestone)</option>
                            <option value="photo">Galeria de Fotos</option>
                            <option value="video">Documentário / Vídeo</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="fw-800 text-uppercase mb-2 small text-muted">Data da Ocorrência</label>
                        <input name="date" type="date" class="form-control form-control-lg border-0 bg-light rounded-4 py-3 fw-700" value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="mb-4">
                        <label class="fw-800 text-uppercase mb-2 small text-muted">Descrição</label>
                        <textarea name="content" class="form-control border-0 bg-light rounded-4 py-3 fw-700" rows="4" placeholder="Descreva os detalhes deste marco de impacto..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="fw-800 text-uppercase mb-2 small text-muted">Mídia (Foto)</label>
                        <input name="media" type="file" class="form-control border-0 bg-light rounded-4 py-3 fw-700">
                    </div>

                    <div class="mb-0">
                        <label class="fw-800 text-uppercase mb-2 small text-muted">Link Externo (Vídeo/Drive)</label>
                        <input name="external_url" type="url" class="form-control form-control-lg border-0 bg-light rounded-4 py-3 fw-700" placeholder="https://youtube.com/...">
                    </div>
                </div>
                <div class="p-5 pt-0">
                    <button type="submit" class="btn-premium btn-premium-shine w-100 border-0 py-4 fs-5 fw-900">Salvar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Team Modal Refined -->
@if($isManager)
<div class="modal fade" id="addMemberModal" role="dialog" aria-modal="true" aria-labelledby="addMemberModalLabel" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(10px);">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 overflow-hidden" style="border-radius: 32px; box-shadow: 0 50px 100px rgba(0,0,0,0.2);">
            <div class="modal-header border-0 py-5 px-5 text-white" style="background: #1e293b;">
                <div>
                    <h4 class="modal-title fw-900 mb-1">Expandir Stakeholders</h4>
                    <p class="m-0 opacity-50 small fw-bold text-uppercase">Gestão de Equipe e Protocolos de Acesso</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="px-5 pt-4">
                    <ul class="nav nav-pills nav-fill bg-light rounded-4 p-1" id="teamModalTabs" role="tablist">
                        <li class="nav-item"><button class="nav-link active rounded-4 fw-800 py-3" id="select-tab" data-bs-toggle="pill" data-bs-target="#selectMember" type="button">Vincular Ativo</button></li>
                        <li class="nav-item"><button class="nav-link rounded-4 fw-800 py-3" id="new-tab" data-bs-toggle="pill" data-bs-target="#newMember" type="button">Novo Credenciamento</button></li>
                    </ul>
                </div>
                <div class="tab-content">
                    <div class="tab-pane fade show active p-5" id="selectMember">
                        <form action="{{ $basePath . '/projects/'.$project->id.'/members' }}" method="POST">
                            @csrf
                            <label class="fw-800 text-uppercase mb-3" style="font-size: 0.7rem; letter-spacing: 1px; color: #94a3b8;">Selecionar Colaborador</label>
                            <select name="user_id" class="form-select form-select-lg border-0 bg-light rounded-4 py-3 mb-4 fw-700" style="font-size: 1rem; color: #1e293b;" required>
                                <option value="">Busque por nome ou email...</option>
                                @foreach($availableUsers as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </select>

                            <label class="fw-800 text-uppercase mb-3" style="font-size: 0.7rem; letter-spacing: 1px; color: #94a3b8;">Protocolo de Acesso</label>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="rV" class="role-card w-100" id="card-rV">
                                        <input type="radio" name="access_level" id="rV" value="viewer" class="d-none" onchange="highlightRoleCard('r',this.id)">
                                        <i class="fas fa-eye mb-2" style="font-size:1.3rem;color:#64748b;"></i>
                                        <div class="fw-900 mb-1">Viewer</div>
                                        <div class="small fw-bold" style="color:#94a3b8;">Auditagem</div>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <label for="rE" class="role-card role-card-active w-100" id="card-rE">
                                        <input type="radio" name="access_level" id="rE" value="editor" class="d-none" checked onchange="highlightRoleCard('r',this.id)">
                                        <i class="fas fa-pen-to-square mb-2" style="font-size:1.3rem;color:#6366f1;"></i>
                                        <div class="fw-900 mb-1">Editor</div>
                                        <div class="small fw-bold" style="color:#94a3b8;">Operacional</div>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <label for="rA" class="role-card w-100" id="card-rA">
                                        <input type="radio" name="access_level" id="rA" value="admin" class="d-none" onchange="highlightRoleCard('r',this.id)">
                                        <i class="fas fa-shield-halved mb-2" style="font-size:1.3rem;color:#64748b;"></i>
                                        <div class="fw-900 mb-1">Admin</div>
                                        <div class="small fw-bold" style="color:#94a3b8;">Total</div>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn-premium btn-premium-shine w-100 mt-5 border-0 py-4 fs-5 fw-900">Efetivar Vinculação</button>
                        </form>
                    </div>

                    <div class="tab-pane fade p-5" id="newMember">
                        <form action="{{ $basePath . '/projects/'.$project->id.'/members/credential' }}" method="POST">
                            @csrf

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="fw-800 text-uppercase mb-3" style="font-size: 0.7rem; letter-spacing: 1px; color: #94a3b8;">Nome</label>
                                    <input name="name" type="text" class="form-control form-control-lg border-0 bg-light rounded-4 py-3 fw-700" placeholder="Ex: Maria Silva" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-800 text-uppercase mb-3" style="font-size: 0.7rem; letter-spacing: 1px; color: #94a3b8;">Email (login)</label>
                                    <input name="email" type="email" class="form-control form-control-lg border-0 bg-light rounded-4 py-3 fw-700" placeholder="ex: maria@empresa.com" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-800 text-uppercase mb-3" style="font-size: 0.7rem; letter-spacing: 1px; color: #94a3b8;">Telefone (opcional)</label>
                                    <input name="phone" type="text" class="form-control form-control-lg border-0 bg-light rounded-4 py-3 fw-700" placeholder="(11) 99999-9999">
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-800 text-uppercase mb-3" style="font-size: 0.7rem; letter-spacing: 1px; color: #94a3b8;">Perfil</label>
                                    <div class="p-4 bg-light rounded-4 border-0 fw-800" style="color:#1e293b;">
                                        Colaborador (Employee)
                                        <div class="small opacity-50 fw-bold mt-1">A senha será definida via link de redefinição enviado por email.</div>
                                    </div>
                                </div>
                            </div>

                            <label class="fw-800 text-uppercase mt-5 mb-3" style="font-size: 0.7rem; letter-spacing: 1px; color: #94a3b8;">Protocolo de Acesso</label>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="nrV" class="role-card w-100" id="card-nrV">
                                        <input type="radio" name="access_level" id="nrV" value="viewer" class="d-none" onchange="highlightRoleCard('nr',this.id)">
                                        <i class="fas fa-eye mb-2" style="font-size:1.3rem;color:#64748b;"></i>
                                        <div class="fw-900 mb-1">Viewer</div>
                                        <div class="small fw-bold" style="color:#94a3b8;">Auditagem</div>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <label for="nrE" class="role-card role-card-active w-100" id="card-nrE">
                                        <input type="radio" name="access_level" id="nrE" value="editor" class="d-none" checked onchange="highlightRoleCard('nr',this.id)">
                                        <i class="fas fa-pen-to-square mb-2" style="font-size:1.3rem;color:#6366f1;"></i>
                                        <div class="fw-900 mb-1">Editor</div>
                                        <div class="small fw-bold" style="color:#94a3b8;">Operacional</div>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <label for="nrA" class="role-card w-100" id="card-nrA">
                                        <input type="radio" name="access_level" id="nrA" value="admin" class="d-none" onchange="highlightRoleCard('nr',this.id)">
                                        <i class="fas fa-shield-halved mb-2" style="font-size:1.3rem;color:#64748b;"></i>
                                        <div class="fw-900 mb-1">Admin</div>
                                        <div class="small fw-bold" style="color:#94a3b8;">Total</div>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn-premium btn-premium-shine w-100 mt-5 border-0 py-4 fs-5 fw-900">Criar Credencial e Vincular</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<script>
    function highlightRoleCard(prefix, selectedId) {
        const ids = [prefix + 'V', prefix + 'E', prefix + 'A'];
        ids.forEach(function(id) {
            const card = document.getElementById('card-' + id);
            if (!card) return;
            const isSelected = (id === selectedId);
            card.classList.toggle('role-card-active', isSelected);
            const icon = card.querySelector('i');
            if (icon) icon.style.color = isSelected ? '#6366f1' : '#64748b';
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const triggers = document.querySelectorAll('#teamModalTabs button');
        triggers.forEach(btn => {
            btn.addEventListener('click', function() {
                triggers.forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('show', 'active'));
                this.classList.add('active');
                const target = this.getAttribute('data-bs-target');
                const pane = document.querySelector(target);
                if(pane) pane.classList.add('show', 'active');
            });
        });
    });
</script>

<!-- Add Person Modal -->
{{-- Modal precisa renderizar para qualquer um que possa ver o botao Nova Pessoa
     (storePerson permite manager/employee/super_admin/ngo). --}}
<div class="modal fade" id="addPersonModal" role="dialog" aria-modal="true" aria-labelledby="addPersonModalLabel" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(10px);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 overflow-hidden" style="border-radius: 32px; box-shadow: 0 50px 100px rgba(0,0,0,0.2);">
            <div class="modal-header border-0 py-4 px-5 text-white" style="background: #1e293b;">
                <div>
                    <h4 class="modal-title fw-900 mb-1">Cadastrar Pessoa</h4>
                    <p class="m-0 opacity-50 small fw-bold text-uppercase">Vincular contato ao projeto</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('projects.people.store', $project->id) }}" method="POST">
                @csrf
                <div class="modal-body p-5">
                    <div class="mb-4">
                        <label class="fw-800 text-uppercase mb-2 small text-muted">Nome Completo</label>
                        <input name="name" type="text" class="form-control form-control-lg border-0 bg-light rounded-4 py-3 fw-700" required>
                    </div>
                    <div class="mb-4">
                        <label class="fw-800 text-uppercase mb-2 small text-muted">WhatsApp (com DDD)</label>
                        <input name="phone" type="text" class="form-control form-control-lg border-0 bg-light rounded-4 py-3 fw-700" placeholder="Ex: 11999999999">
                    </div>
                    <div class="mb-4">
                        <label class="fw-800 text-uppercase mb-2 small text-muted">Endereço</label>
                        <input name="address" type="text" class="form-control border-0 bg-light rounded-4 py-3 fw-700">
                    </div>
                    <div class="mb-0">
                        <label class="fw-800 text-uppercase mb-2 small text-muted">Cidade</label>
                        <input name="city" type="text" class="form-control border-0 bg-light rounded-4 py-3 fw-700">
                    </div>
                </div>
                <div class="p-5 pt-0">
                    <button type="submit" class="btn-premium btn-premium-shine w-100 border-0 py-4 fs-5 fw-900">Cadastrar Pessoa</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Import Person Project Modal -->
<div class="modal fade" id="importPersonProjectModal" role="dialog" aria-modal="true" aria-labelledby="importPersonProjectModalLabel" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(10px);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 overflow-hidden" style="border-radius: 32px; box-shadow: 0 50px 100px rgba(0,0,0,0.2);">
            <div class="modal-header border-0 py-4 px-5 text-white" style="background: #1e293b;">
                <div>
                    <h4 class="modal-title fw-900 mb-1">Importar Contatos (CSV)</h4>
                    <p class="m-0 opacity-50 small fw-bold text-uppercase">Importar lote para {{ $project->name }}</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('projects.people.import.global') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <div class="modal-body p-5">
                    <div class="mb-4 p-4 rounded-4" style="background: rgba(99,102,241,0.05); border: 1px dashed rgba(99,102,241,0.3);">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <i class="fas fa-file-csv fs-3 text-primary"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Padrão do Arquivo CSV</h6>
                                <p class="small text-muted mb-0">Use as colunas: <strong>Nome, Telefone, Endereço, Cidade</strong></p>
                            </div>
                        </div>
                        <input type="file" name="csv_file" class="form-control bg-white border-0 py-2" accept=".csv, .txt" required>
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

@if(config('bruce.context_project_enabled'))
{{-- ════════════════════════════════════════════════════════════════
     Modal: Bruce contextual (feature flag bruce.context_project_enabled)
     ──────────────────────────────────────────────────────────────── --}}
<div class="modal fade" id="bruceProjectChatModal" tabindex="-1" aria-labelledby="bruceProjectChatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 640px;">
        <div class="modal-content" style="border: none; border-radius: 24px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg,#0369a1,#0c4a6e); color:#fff; border-bottom: none; padding: 20px 24px;">
                <h5 class="modal-title" id="bruceProjectChatModalLabel" style="font-weight: 900; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-robot"></i>
                    <span>Bruce</span>
                    <span id="bpc_project_badge" style="background: rgba(255,255,255,0.18); padding: 4px 12px; border-radius: 999px; font-size: 0.75rem; font-weight: 700;"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" style="padding: 0;">
                <div id="bpc_messages" style="padding: 20px 24px; height: 380px; overflow-y: auto; background: #f8fafc;">
                    <div id="bpc_empty" style="text-align: center; color: #94a3b8; padding: 60px 20px;">
                        <i class="fas fa-comments" style="font-size: 2rem; margin-bottom: 12px; opacity: 0.5;"></i>
                        <p style="margin: 0; font-weight: 700; color: #475569;">Pergunte algo sobre este projeto</p>
                        <p style="margin: 8px 0 0 0; font-size: 0.85rem;">Ex: <em>"como está o orçamento?"</em>, <em>"o que devo priorizar essa semana?"</em>, <em>"resuma o diário dos últimos dias"</em></p>
                    </div>
                </div>
                <div style="padding: 16px 20px; background: #fff; border-top: 1px solid #f1f5f9;">
                    <form id="bpc_form" onsubmit="return bruceProjectSend(event);" style="display: flex; gap: 8px;">
                        <input type="text" id="bpc_input" class="form-control" placeholder="Digite sua pergunta sobre este projeto..." autocomplete="off"
                               style="flex:1; padding: 12px 16px; border-radius: 12px; border: 1px solid #e2e8f0; font-weight: 600; color: #0f172a;">
                        <button type="submit" id="bpc_send" class="btn-premium" style="padding: 12px 18px !important; font-size: 0.85rem !important; background: #0369a1 !important; color: #fff !important;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 12px 24px; justify-content: space-between;">
                <button type="button" id="bpc_clear" onclick="bruceProjectClear()" class="btn btn-light" style="font-weight: 700; font-size: 0.8rem;"><i class="fas fa-trash-alt me-1"></i> Limpar conversa</button>
                <small style="color: #94a3b8;">As respostas são geradas por IA e podem conter imprecisões.</small>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    let bpcProjectId = null;
    let bpcProjectName = '';
    let bpcSending = false;

    // Liga o botão do Toolkit ao modal via addEventListener
    // (evita aspas inline no onclick, que quebravam o JS da página)
    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('btn-open-bruce-project');
        if (btn) {
            btn.addEventListener('click', function() {
                openBruceProjectChat(
                    parseInt(this.dataset.bruceProjectId, 10),
                    this.dataset.bruceProjectName || ''
                );
            });
        }
    });

    // openProjectModal — abre qualquer modal por id, com fallback caso o
    // Bootstrap JS nao esteja disponivel. Garante que os botoes Nova
    // Pessoa, Importar CSV e similares funcionem mesmo se algum outro
    // script da pagina engolir o data-bs-toggle ou quebrar o objeto
    // `bootstrap` global.
    window.openProjectModal = function(modalId) {
        const el = document.getElementById(modalId);
        if (!el) { console.warn('openProjectModal: modal nao encontrado:', modalId); return; }

        // Tentativa 1: Bootstrap nativo
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            try {
                bootstrap.Modal.getOrCreateInstance(el).show();
                return;
            } catch (e) {
                console.warn('openProjectModal: Bootstrap falhou, usando fallback', e);
            }
        }

        // Tentativa 2: fallback puro CSS/JS — abre o modal manualmente
        ensureProjectModalFallbackStyles();
        document.body.classList.add('proj-modal-open');
        el.classList.add('proj-modal-shown');
        el.style.display    = 'block';
        el.style.zIndex     = '100000';
        el.setAttribute('aria-hidden', 'false');

        // Backdrop manual
        let bd = document.getElementById('proj-modal-backdrop');
        if (!bd) {
            bd = document.createElement('div');
            bd.id = 'proj-modal-backdrop';
            bd.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:99999;';
            document.body.appendChild(bd);
        }
        const close = () => closeProjectModal(modalId);
        bd.onclick = close;

        // Botoes [data-bs-dismiss] dentro do modal tambem fecham
        el.querySelectorAll('[data-bs-dismiss="modal"]').forEach(b => { b.onclick = close; });
    };

    window.closeProjectModal = function(modalId) {
        const el = document.getElementById(modalId);
        if (!el) return;
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const inst = bootstrap.Modal.getInstance(el);
            if (inst) { try { inst.hide(); return; } catch (e) { /* fallthrough */ } }
        }
        el.classList.remove('proj-modal-shown');
        el.style.display = 'none';
        el.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('proj-modal-open');
        const bd = document.getElementById('proj-modal-backdrop');
        if (bd) bd.remove();
    };

    function ensureProjectModalFallbackStyles() {
        if (document.getElementById('proj-modal-fallback-styles')) return;
        const s = document.createElement('style');
        s.id = 'proj-modal-fallback-styles';
        s.textContent = `
            body.proj-modal-open { overflow: hidden; }
            .modal.proj-modal-shown {
                position: fixed; inset: 0; overflow-y: auto;
                padding: 40px 16px; box-sizing: border-box;
            }
            .modal.proj-modal-shown .modal-dialog {
                margin: 0 auto; max-width: 600px;
            }
        `;
        document.head.appendChild(s);
    }

    window.openBruceProjectChat = function(projectId, projectName) {
        bpcProjectId   = projectId;
        bpcProjectName = projectName || ('Projeto #' + projectId);
        document.getElementById('bpc_project_badge').textContent = bpcProjectName;
        document.getElementById('bpc_input').value = '';
        document.getElementById('bpc_messages').innerHTML = `
            <div id="bpc_empty" style="text-align: center; color: #94a3b8; padding: 60px 20px;">
                <i class="fas fa-comments" style="font-size: 2rem; margin-bottom: 12px; opacity: 0.5;"></i>
                <p style="margin: 0; font-weight: 700; color: #475569;">Pergunte algo sobre <strong>${bpcProjectName}</strong></p>
                <p style="margin: 8px 0 0 0; font-size: 0.85rem;">Ex: <em>"como está o orçamento?"</em>, <em>"o que devo priorizar essa semana?"</em></p>
            </div>`;
        const modal = new bootstrap.Modal(document.getElementById('bruceProjectChatModal'));
        modal.show();
        setTimeout(() => document.getElementById('bpc_input').focus(), 250);
    };

    function bpcAppend(role, text) {
        const empty = document.getElementById('bpc_empty');
        if (empty) empty.remove();
        const box   = document.getElementById('bpc_messages');
        const isUser = role === 'user';
        const html  = `
            <div style="display: flex; gap: 10px; margin-bottom: 16px; flex-direction: ${isUser ? 'row-reverse' : 'row'};">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: ${isUser ? '#0369a1' : '#10b981'}; color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.85rem;">
                    <i class="fas fa-${isUser ? 'user' : 'robot'}"></i>
                </div>
                <div style="background: ${isUser ? '#0369a1' : '#fff'}; color: ${isUser ? '#fff' : '#0f172a'}; padding: 12px 16px; border-radius: 16px; max-width: 78%; font-size: 0.9rem; line-height: 1.5; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid ${isUser ? 'transparent' : '#e2e8f0'};">
                    ${text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>')}
                </div>
            </div>`;
        box.insertAdjacentHTML('beforeend', html);
        box.scrollTop = box.scrollHeight;
    }

    function bpcTyping(on) {
        let el = document.getElementById('bpc_typing');
        if (on && !el) {
            const box = document.getElementById('bpc_messages');
            box.insertAdjacentHTML('beforeend', `
                <div id="bpc_typing" style="display: flex; gap: 10px; margin-bottom: 16px; align-items: center;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #10b981; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;"><i class="fas fa-robot"></i></div>
                    <div style="background: #fff; color: #475569; padding: 12px 16px; border-radius: 16px; font-size: 0.85rem; border: 1px solid #e2e8f0;"><i class="fas fa-circle-notch fa-spin me-2"></i> Pensando...</div>
                </div>`);
            box.scrollTop = box.scrollHeight;
        } else if (!on && el) {
            el.remove();
        }
    }

    window.bruceProjectSend = async function(ev) {
        ev.preventDefault();
        if (bpcSending || !bpcProjectId) return false;

        const input = document.getElementById('bpc_input');
        const msg = input.value.trim();
        if (!msg) return false;

        bpcSending = true;
        document.getElementById('bpc_send').disabled = true;
        bpcAppend('user', msg);
        input.value = '';
        bpcTyping(true);

        try {
            const r = await fetch('{{ url("/api/bruce/chat") }}', {
                method: 'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept':           'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    message:      msg,
                    context_type: 'project',
                    context_id:   bpcProjectId,
                }),
            });
            const data = await r.json();
            bpcTyping(false);
            if (data.reply) {
                bpcAppend('assistant', data.reply);
            } else {
                bpcAppend('assistant', '⚠️ ' + (data.error || 'Não consegui responder agora. Tenta de novo em instantes.'));
            }
        } catch (e) {
            bpcTyping(false);
            bpcAppend('assistant', '⚠️ Falha de conexão. Verifique sua internet e tente novamente.');
        } finally {
            bpcSending = false;
            document.getElementById('bpc_send').disabled = false;
            input.focus();
        }
        return false;
    };

    window.bruceProjectClear = async function() {
        if (!confirm('Limpar a conversa atual sobre este projeto?')) return;
        try {
            await fetch('{{ url("/api/bruce/chat/history") }}', {
                method: 'DELETE',
                headers: {
                    'Content-Type':     'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept':           'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ context_type: 'project', context_id: bpcProjectId }),
            });
        } catch (e) { /* silencioso */ }
        openBruceProjectChat(bpcProjectId, bpcProjectName);
    };
})();
</script>
@endif

@endsection

