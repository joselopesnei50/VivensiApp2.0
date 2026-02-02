@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div style="display: flex; align-items: center;">
        <a href="{{ route('admin.team.index') }}" class="btn-outline" style="padding: 10px; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; margin-right: 20px;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h6 style="color: #6366f1; font-weight: 700; text-transform: uppercase; margin: 0 0 5px 0;">Perfil do Colaborador</h6>
            <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">{{ $user->name }}</h2>
        </div>
    </div>
</div>

<div class="row">
    <!-- Bio & Stats -->
    <div class="col-md-4">
        <div class="vivensi-card text-center" style="padding: 40px 25px;">
            <div style="width: 100px; height: 100px; background: #6366f1; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 800; margin: 0 auto 20px;">
                {{ substr($user->name, 0, 1) }}
            </div>
            <h3 style="margin: 0; color: #1e293b; font-weight: 800;">{{ $user->name }}</h3>
            <p style="color: #64748b; margin-top: 5px;">{{ $user->email }}</p>
            
            <div style="display: flex; justify-content: center; gap: 10px; margin-top: 15px;">
                <span style="background: #e0e7ff; color: #6366f1; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;">
                    {{ $user->department }}
                </span>
                <span style="background: #f0fdf4; color: #16a34a; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;">
                    Ativo
                </span>
            </div>

            <hr style="margin: 30px 0; border-color: #f1f5f9;">

            <div class="row g-2">
                <div class="col-6">
                    <div style="background: #f8fafc; padding: 15px; border-radius: 12px;">
                        <div style="font-size: 1.2rem; font-weight: 800; color: #1e293b;">{{ count($tasks) }}</div>
                        <div style="font-size: 0.7rem; color: #94a3b8; font-weight: 600; text-transform: uppercase;">Tarefas</div>
                    </div>
                </div>
                <div class="col-6">
                    <div style="background: #f8fafc; padding: 15px; border-radius: 12px;">
                        <div style="font-size: 1.2rem; font-weight: 800; color: #1e293b;">{{ is_array($tickets) ? count($tickets) : 0 }}</div>
                        <div style="font-size: 0.7rem; color: #94a3b8; font-weight: 600; text-transform: uppercase;">Tickets</div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 30px; text-align: left;">
                <h5 style="font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 15px;">Informações do Cargo</h5>
                <div style="font-size: 0.9rem; color: #64748b; margin-bottom: 10px;">
                    <i class="fas fa-id-badge me-2" style="width: 20px;"></i> {{ $user->role }}
                </div>
                <div style="font-size: 0.9rem; color: #64748b;">
                    <i class="fas fa-user-shield me-2" style="width: 20px;"></i> Superior: {{ $user->supervisor->name ?? 'Diretoria' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Diário do Membro -->
    <div class="col-md-8">
        <!-- Tarefas Diárias -->
        <div class="vivensi-card" style="padding: 25px; margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h4 style="margin: 0; font-size: 1.1rem; color: #1e293b; font-weight: 800;">
                    <i class="fas fa-check-double me-2 text-primary"></i> Tarefas Atribuídas
                </h4>
                <button class="btn btn-sm btn-light rounded-pill px-3" style="font-size: 0.75rem; font-weight: 700;">Ver Agenda Completa</button>
            </div>

            @forelse($tasks as $task)
            <div style="padding: 15px; background: #f8fafc; border-radius: 12px; border-left: 4px solid {{ $task->status === 'completed' ? '#10b981' : ($task->priority === 'high' ? '#ef4444' : '#6366f1') }}; margin-bottom: 10px; display: flex; align-items: center;">
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: #334155; font-size: 0.95rem;">{{ $task->title }}</div>
                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">{{ Str::limit($task->description, 80) }}</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.7rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Prazo</div>
                    <div style="font-size: 0.8rem; color: #1e293b; font-weight: 600;">{{ $task->due_date ? $task->due_date->format('d/m/Y') : 'S/P' }}</div>
                </div>
            </div>
            @empty
            <div style="text-align: center; padding: 40px; color: #94a3b8;">
                <i class="fas fa-tasks d-block mb-3" style="font-size: 2rem; opacity: 0.5;"></i>
                Nenhuma tarefa pendente para hoje.
            </div>
            @endforelse
        </div>

        <!-- Suporte ao Cliente (Condicional) -->
        @if($user->department === 'suporte')
        <div class="vivensi-card" style="padding: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h4 style="margin: 0; font-size: 1.1rem; color: #1e293b; font-weight: 800;">
                    <i class="fas fa-headset me-2 text-success"></i> Chamados Sob Minha Responsabilidade
                </h4>
            </div>

            <div class="table-responsive">
                <table class="table" style="font-size: 0.85rem;">
                    <thead>
                        <tr style="color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.7rem;">
                            <th>Ticket</th>
                            <th>Cliente</th>
                            <th>Status</th>
                            <th class="text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                        <tr>
                            <td class="align-middle"><strong>#{{ $ticket->id }}</strong><br><span class="text-muted">{{ $ticket->subject }}</span></td>
                            <td class="align-middle">{{ $ticket->user->name ?? 'N/A' }}</td>
                            <td class="align-middle">
                                <span style="background: #e0f2fe; color: #0284c7; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem; font-weight: 700;">
                                    {{ strtoupper($ticket->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ url('/admin/support') }}" class="btn btn-sm btn-outline-primary" style="border-radius: 8px;">Responder</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">Sem chamados pendentes.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
