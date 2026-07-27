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

    {{-- Diario de Evolucao --}}
    <div class="col-lg-7">
        <div class="vivensi-card" style="padding:24px; border-radius:16px; height:100%;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                <h5 style="margin:0; font-weight:800; color:#1e293b; font-size:0.95rem;">
                    <i class="fas fa-book-open me-2" style="color:#6366f1;"></i>Diario de evolucao
                </h5>
                <span style="color:#94a3b8; font-size:0.75rem;">{{ $logs->count() }} recente(s)</span>
            </div>

            <form action="{{ route('credenciado.log.store', $project->id) }}" method="POST" style="margin-bottom:16px;">
                @csrf
                <textarea name="body" rows="3" required maxlength="3000"
                          placeholder="Como foi hoje? Registre presenca, aprendizados, dificuldades, materiais que faltaram..."
                          style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:0.85rem; box-sizing:border-box; resize:vertical; line-height:1.55;"></textarea>
                @error('body')
                    <div style="color:#dc2626; font-size:0.75rem; margin-top:4px;">{{ $message }}</div>
                @enderror
                <div style="display:flex; justify-content:flex-end; margin-top:8px;">
                    <button type="submit" style="padding:8px 18px; border-radius:10px; border:none; background:#6366f1; color:white; font-weight:700; font-size:0.82rem; cursor:pointer;">
                        <i class="fas fa-paper-plane me-1"></i>Registrar
                    </button>
                </div>
            </form>

            @forelse($logs as $log)
                <div style="padding:12px 14px; background:#f8fafc; border-left:3px solid #6366f1; border-radius:8px; margin-bottom:8px;">
                    <div style="display:flex; justify-content:space-between; gap:10px; margin-bottom:6px; flex-wrap:wrap;">
                        <div style="font-weight:700; color:#1e293b; font-size:0.8rem;">
                            {{ $log->user->name ?? 'Anonimo' }}
                            @if($log->user_id === auth()->id())
                                <span style="background:#eef2ff; color:#4338ca; font-size:0.65rem; font-weight:700; padding:1px 6px; border-radius:6px; margin-left:4px;">Voce</span>
                            @endif
                        </div>
                        <div style="color:#94a3b8; font-size:0.72rem;">{{ $log->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <div style="color:#475569; font-size:0.85rem; line-height:1.55; white-space:pre-wrap;">{{ $log->body }}</div>
                </div>
            @empty
                <div style="text-align:center; padding:24px 20px; color:#94a3b8;">
                    <i class="fas fa-pen-clip" style="font-size:1.6rem; margin-bottom:8px; display:block; opacity:0.5;"></i>
                    <p style="margin:0; font-size:0.82rem;">Nenhuma entrada ainda. Registre a primeira acima.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Marcos do projeto --}}
    <div class="col-lg-5">
        <div class="vivensi-card" style="padding:24px; border-radius:16px; height:100%;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                <h5 style="margin:0; font-weight:800; color:#1e293b; font-size:0.95rem;">
                    <i class="fas fa-flag me-2" style="color:#6366f1;"></i>Marcos do projeto
                </h5>
                <span style="color:#94a3b8; font-size:0.75rem;">{{ $timeline->count() }} recente(s)</span>
            </div>

            <form action="{{ route('credenciado.timeline.store', $project->id) }}" method="POST" enctype="multipart/form-data" style="margin-bottom:16px;">
                @csrf
                <input type="text" name="title" required maxlength="255"
                       placeholder="Titulo do marco (ex: Finalizamos o modulo 1)"
                       style="width:100%; padding:10px 12px; border:2px solid #e2e8f0; border-radius:10px; font-size:0.85rem; box-sizing:border-box; margin-bottom:8px;">
                <textarea name="content" rows="2" maxlength="3000"
                          placeholder="Descricao (opcional)"
                          style="width:100%; padding:10px 12px; border:2px solid #e2e8f0; border-radius:10px; font-size:0.82rem; box-sizing:border-box; resize:vertical; line-height:1.5; margin-bottom:8px;"></textarea>
                <div class="row g-2" style="margin-bottom:8px;">
                    <div class="col-6">
                        <select name="type" required
                                style="width:100%; padding:8px 10px; border:2px solid #e2e8f0; border-radius:10px; font-size:0.8rem; box-sizing:border-box; background:white;">
                            <option value="milestone">Marco / entrega</option>
                            <option value="status">Status / atualizacao</option>
                            <option value="photo">Foto / evidencia</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <input type="date" name="date" value="{{ now()->format('Y-m-d') }}"
                               style="width:100%; padding:8px 10px; border:2px solid #e2e8f0; border-radius:10px; font-size:0.8rem; box-sizing:border-box;">
                    </div>
                </div>
                <label style="display:block; font-size:0.75rem; color:#64748b; margin-bottom:4px;">Foto (opcional, ate 10MB)</label>
                <input type="file" name="media" accept="image/*"
                       style="width:100%; padding:6px; border:2px dashed #e2e8f0; border-radius:10px; font-size:0.78rem; box-sizing:border-box; background:#f8fafc; margin-bottom:8px;">
                @error('media')
                    <div style="color:#dc2626; font-size:0.75rem; margin-bottom:6px;">{{ $message }}</div>
                @enderror
                <div style="display:flex; justify-content:flex-end;">
                    <button type="submit" style="padding:8px 18px; border-radius:10px; border:none; background:#6366f1; color:white; font-weight:700; font-size:0.82rem; cursor:pointer;">
                        <i class="fas fa-flag-checkered me-1"></i>Registrar marco
                    </button>
                </div>
            </form>

            @php
                $typeLabels = [
                    'milestone' => ['label' => 'Marco',      'bg' => '#eef2ff', 'color' => '#4338ca', 'icon' => 'fa-flag-checkered'],
                    'status'    => ['label' => 'Status',     'bg' => '#dbeafe', 'color' => '#1e40af', 'icon' => 'fa-circle-info'],
                    'photo'     => ['label' => 'Foto',       'bg' => '#dcfce7', 'color' => '#166534', 'icon' => 'fa-camera'],
                    'video'     => ['label' => 'Video',      'bg' => '#fef3c7', 'color' => '#92400e', 'icon' => 'fa-video'],
                ];
            @endphp
            @forelse($timeline as $tr)
                @php $tl = $typeLabels[$tr->type] ?? $typeLabels['status']; @endphp
                <div style="padding:12px 14px; border:1px solid #f1f5f9; border-radius:12px; margin-bottom:8px;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px; flex-wrap:wrap;">
                        <span style="background:{{ $tl['bg'] }}; color:{{ $tl['color'] }}; font-size:0.66rem; font-weight:800; padding:2px 8px; border-radius:6px; text-transform:uppercase; letter-spacing:0.5px;">
                            <i class="fas {{ $tl['icon'] }} me-1"></i>{{ $tl['label'] }}
                        </span>
                        <span style="color:#94a3b8; font-size:0.72rem; margin-left:auto;">{{ optional($tr->date)->format('d/m/Y') }}</span>
                    </div>
                    <div style="font-weight:700; color:#1e293b; font-size:0.88rem;">{{ $tr->title }}</div>
                    @if($tr->content)
                        <div style="color:#64748b; font-size:0.78rem; margin-top:4px; line-height:1.5;">{{ Str::limit($tr->content, 140) }}</div>
                    @endif
                    @if($tr->media_path)
                        <a href="{{ asset('storage/' . $tr->media_path) }}" target="_blank" rel="noopener"
                           style="display:block; margin-top:8px;">
                            <img src="{{ asset('storage/' . $tr->media_path) }}" alt="{{ $tr->title }}"
                                 style="width:100%; max-height:180px; object-fit:cover; border-radius:8px;">
                        </a>
                    @endif
                </div>
            @empty
                <div style="text-align:center; padding:24px 20px; color:#94a3b8;">
                    <i class="fas fa-flag" style="font-size:1.6rem; margin-bottom:8px; display:block; opacity:0.5;"></i>
                    <p style="margin:0; font-size:0.82rem;">Nenhum marco ainda. Comemore o primeiro passo acima.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
