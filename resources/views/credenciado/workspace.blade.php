@extends('layouts.app')
@section('title', $project->name)

@section('content')
@php
    $statusColors = [
        'todo'        => ['bg' => '#eff6ff', 'color' => '#1d4ed8', 'label' => 'A fazer'],
        'in_progress' => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Em progresso'],
        'completed'   => ['bg' => '#dcfce7', 'color' => '#166534', 'label' => 'Concluida'],
        'done'        => ['bg' => '#dcfce7', 'color' => '#166534', 'label' => 'Concluida'],
        'cancelled'   => ['bg' => '#f1f5f9', 'color' => '#475569', 'label' => 'Cancelada'],
    ];
    $projectStatusLabel = match($project->status ?? 'active') {
        'active'    => 'Ativo',
        'paused'    => 'Pausado',
        'completed' => 'Concluido',
        'cancelled' => 'Cancelado',
        default     => ucfirst($project->status ?? 'active'),
    };
@endphp

<div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
    @if(auth()->user()->projectMemberships()->count() > 1)
        <a href="{{ route('credenciado.index') }}" style="color:#6366f1; font-weight:700; font-size:0.82rem; text-decoration:none;">
            <i class="fas fa-arrow-left me-1"></i>Meus projetos
        </a>
    @endif
</div>

<div style="margin-bottom:24px;">
    <h2 style="margin:0; color:#1e293b; font-weight:900; font-size:1.6rem; letter-spacing:-0.5px; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
        <i class="fas fa-folder-open" style="color:#6366f1;"></i>{{ $project->name }}
        <span style="background:#eff6ff; color:#1d4ed8; font-size:0.65rem; font-weight:800; padding:3px 10px; border-radius:10px; text-transform:uppercase; letter-spacing:0.5px;">
            {{ $projectStatusLabel }}
        </span>
    </h2>
    @if($project->start_date || $project->end_date)
        <p style="color:#64748b; margin:8px 0 0; font-size:0.88rem;">
            <i class="far fa-calendar me-1"></i>
            {{ $project->start_date?->format('d/m/Y') ?? '—' }}
            <i class="fas fa-arrow-right mx-2" style="font-size:0.7rem; color:#cbd5e1;"></i>
            {{ $project->end_date?->format('d/m/Y') ?? '—' }}
        </p>
    @endif
</div>

@if(session('success'))
    <div style="background:#f0fdf4; border:1px solid #86efac; color:#166534; border-radius:12px; padding:12px 16px; margin-bottom:20px; font-size:0.88rem;">
        <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
    </div>
@endif

<div class="row g-4">
    {{-- Descricao do projeto --}}
    @if($project->description)
    <div class="col-12">
        <div class="vivensi-card" style="padding:24px; border-radius:16px;">
            <h5 style="margin:0 0 12px; font-weight:800; color:#1e293b; font-size:0.95rem;">
                <i class="fas fa-align-left me-2" style="color:#6366f1;"></i>Sobre o projeto
            </h5>
            <p style="color:#475569; font-size:0.9rem; line-height:1.6; margin:0; white-space:pre-wrap;">{{ $project->description }}</p>
        </div>
    </div>
    @endif

    {{-- Suas tarefas --}}
    <div class="col-lg-7">
        <div class="vivensi-card" style="padding:24px; border-radius:16px; height:100%;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                <h5 style="margin:0; font-weight:800; color:#1e293b; font-size:0.95rem;">
                    <i class="fas fa-list-check me-2" style="color:#6366f1;"></i>Minhas tarefas
                </h5>
                <span style="color:#94a3b8; font-size:0.75rem;">{{ $tasks->count() }} atribuida(s)</span>
            </div>

            @forelse($tasks as $t)
                @php $s = $statusColors[$t->status] ?? $statusColors['todo']; @endphp
                <div style="padding:14px; border:1px solid #f1f5f9; border-radius:12px; margin-bottom:10px;">
                    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:8px;">
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:700; color:#1e293b; font-size:0.9rem;">{{ $t->title }}</div>
                            @if($t->description)
                                <div style="color:#64748b; font-size:0.78rem; margin-top:4px; line-height:1.5;">{{ Str::limit($t->description, 140) }}</div>
                            @endif
                        </div>
                        <span style="background:{{ $s['bg'] }}; color:{{ $s['color'] }}; font-size:0.68rem; font-weight:800; padding:3px 8px; border-radius:8px; text-transform:uppercase; letter-spacing:0.5px; flex-shrink:0;">
                            {{ $s['label'] }}
                        </span>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                        <div style="color:#94a3b8; font-size:0.72rem;">
                            @if($t->due_date)
                                <i class="far fa-calendar me-1"></i>Prazo: {{ $t->due_date->format('d/m/Y') }}
                            @else
                                Sem prazo
                            @endif
                        </div>
                        <form action="{{ route('credenciado.task.status', ['id' => $project->id, 'taskId' => $t->id]) }}"
                              method="POST" style="margin:0; display:flex; gap:6px;">
                            @csrf
                            <select name="status" class="form-select form-select-sm" style="font-size:0.75rem; padding:4px 8px; border-radius:8px; width:auto;">
                                <option value="todo"        @selected($t->status === 'todo')>A fazer</option>
                                <option value="in_progress" @selected($t->status === 'in_progress')>Em progresso</option>
                                <option value="completed"   @selected(in_array($t->status, ['completed', 'done']))>Concluida</option>
                            </select>
                            <button type="submit" style="padding:4px 10px; border-radius:8px; border:none; background:#6366f1; color:white; font-size:0.72rem; font-weight:700; cursor:pointer;">
                                Salvar
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div style="text-align:center; padding:32px 20px; color:#94a3b8;">
                    <i class="fas fa-clipboard-list" style="font-size:1.8rem; margin-bottom:10px; display:block; opacity:0.5;"></i>
                    <p style="margin:0; font-size:0.85rem;">Nenhuma tarefa atribuida a voce neste projeto.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Aulas / chamadas --}}
    <div class="col-lg-5">
        <div class="vivensi-card" style="padding:24px; border-radius:16px; height:100%;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                <h5 style="margin:0; font-weight:800; color:#1e293b; font-size:0.95rem;">
                    <i class="fas fa-chalkboard-user me-2" style="color:#6366f1;"></i>Aulas do projeto
                </h5>
                <span style="color:#94a3b8; font-size:0.75rem;">{{ $sessions->count() }} recente(s)</span>
            </div>

            @forelse($sessions as $sess)
                <div style="padding:12px 14px; border:1px solid #f1f5f9; border-radius:12px; margin-bottom:8px;">
                    <div style="font-weight:700; color:#1e293b; font-size:0.85rem;">{{ $sess->title ?? 'Aula sem titulo' }}</div>
                    <div style="color:#64748b; font-size:0.72rem; margin-top:4px;">
                        <i class="far fa-calendar me-1"></i>{{ $sess->date?->format('d/m/Y') ?? '—' }}
                        @if($sess->start_time)
                            <span style="margin-left:8px;"><i class="far fa-clock me-1"></i>{{ substr($sess->start_time, 0, 5) }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <div style="text-align:center; padding:32px 20px; color:#94a3b8;">
                    <i class="fas fa-chalkboard" style="font-size:1.8rem; margin-bottom:10px; display:block; opacity:0.5;"></i>
                    <p style="margin:0; font-size:0.85rem;">Nenhuma aula cadastrada ainda.</p>
                </div>
            @endforelse
            <div style="margin-top:12px; padding:10px 12px; background:#f8fafc; border-radius:8px; font-size:0.72rem; color:#64748b; line-height:1.5;">
                <i class="fas fa-info-circle me-1"></i>Marcacao de presenca via link publico enviado pela coordenacao.
            </div>
        </div>
    </div>
</div>
@endsection
