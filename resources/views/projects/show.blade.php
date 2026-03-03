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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px;">
                <div>
                    <h4 style="margin: 0; font-weight: 900; color: #1e293b; letter-spacing: -0.5px;">Dossiê Financeiro</h4>
                    <p style="margin: 5px 0 0 0; color: #94a3b8; font-weight: 600; font-size: 0.85rem;">Últimas movimentações vinculadas a este registro.</p>
                </div>
                @if($isManager)
                    <a href="{{ $basePath . '/transactions/create?project_id='.$project->id }}" class="btn-premium btn-premium-shine" style="border: none; padding: 12px 25px; font-weight: 800; font-size: 0.85rem;">
                        <i class="fas fa-plus me-2"></i> Lançar Movimentação
                    </a>
                @endif
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
                    <a href="{{ $basePath . '/manager/schedule' }}" class="btn-premium" style="background: #f8fafc; color: #475569; border: 1px solid #f1f5f9; text-decoration: none; display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fas fa-calendar-alt me-2 text-primary"></i> Agenda da Missão</span>
                        <i class="fas fa-chevron-right style='font-size: 0.7rem; opacity: 0.3;"></i>
                    </a>
                    <a href="{{ $basePath . '/manager/approvals' }}" class="btn-premium" style="background: #f8fafc; color: #475569; border: 1px solid #f1f5f9; text-decoration: none; display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fas fa-check-double me-2 text-success"></i> Central de Aprovações</span>
                        <i class="fas fa-chevron-right style='font-size: 0.7rem; opacity: 0.3;"></i>
                    </a>
                    <a href="{{ $basePath . '/manager/reconciliation' }}" class="btn-premium" style="background: #f8fafc; color: #475569; border: 1px solid #f1f5f9; text-decoration: none; display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fas fa-sync-alt me-2 text-info"></i> Conciliação Bancária</span>
                        <i class="fas fa-chevron-right style='font-size: 0.7rem; opacity: 0.3;"></i>
                    </a>
                    <a href="{{ $basePath . '/manager/contracts' }}" class="btn-premium" style="background: #f8fafc; color: #475569; border: 1px solid #f1f5f9; text-decoration: none; display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fas fa-file-signature me-2 text-indigo"></i> Contratos Digitais</span>
                        <i class="fas fa-chevron-right style='font-size: 0.7rem; opacity: 0.3;"></i>
                    </a>
                    <a href="{{ $basePath . '/smart-analysis' }}" class="btn-premium" style="background: #f8fafc; color: #475569; border: 1px solid #f1f5f9; text-decoration: none; display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fas fa-brain me-2 text-primary"></i> Smart Analysis</span>
                        <i class="fas fa-chevron-right style='font-size: 0.7rem; opacity: 0.3;"></i>
                    </a>
                    <button class="btn-premium mt-3" style="background: #fff1f2; color: #e11d48; border: 1px solid #fee2e2; border-radius: 12px; font-weight: 800; font-size: 0.8rem; text-transform: uppercase;">
                        Arquivar Registro
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Team Modal Refined -->
@if($isManager)
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(10px);">
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
                                    <div class="p-4 border rounded-4 text-center cursor-pointer" onclick="document.getElementById('rV').click()">
                                        <input type="radio" name="access_level" id="rV" value="viewer" class="d-none">
                                        <div class="fw-900 mb-1">Viewer</div>
                                        <div class="small opacity-50 fw-bold">Auditagem</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-4 border rounded-4 text-center cursor-pointer border-primary" onclick="document.getElementById('rE').click()">
                                        <input type="radio" name="access_level" id="rE" value="editor" class="d-none" checked>
                                        <div class="fw-900 mb-1 text-primary">Editor</div>
                                        <div class="small opacity-50 fw-bold">Operacional</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-4 border rounded-4 text-center cursor-pointer" onclick="document.getElementById('rA').click()">
                                        <input type="radio" name="access_level" id="rA" value="admin" class="d-none">
                                        <div class="fw-900 mb-1">Admin</div>
                                        <div class="small opacity-50 fw-bold">Total</div>
                                    </div>
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
                                    <div class="p-4 border rounded-4 text-center cursor-pointer" onclick="document.getElementById('nrV').click()">
                                        <input type="radio" name="access_level" id="nrV" value="viewer" class="d-none">
                                        <div class="fw-900 mb-1">Viewer</div>
                                        <div class="small opacity-50 fw-bold">Auditagem</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-4 border rounded-4 text-center cursor-pointer border-primary" onclick="document.getElementById('nrE').click()">
                                        <input type="radio" name="access_level" id="nrE" value="editor" class="d-none" checked>
                                        <div class="fw-900 mb-1 text-primary">Editor</div>
                                        <div class="small opacity-50 fw-bold">Operacional</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-4 border rounded-4 text-center cursor-pointer" onclick="document.getElementById('nrA').click()">
                                        <input type="radio" name="access_level" id="nrA" value="admin" class="d-none">
                                        <div class="fw-900 mb-1">Admin</div>
                                        <div class="small opacity-50 fw-bold">Total</div>
                                    </div>
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
@endsection

