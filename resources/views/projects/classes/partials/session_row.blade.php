{{-- Linha de chamada na tela da turma. $s vem com registros_count/presentes_count. --}}
@php
    $isToday = $s->date && $s->date->isToday();
    $feita   = (int) $s->registros_count > 0;
@endphp
<div class="d-flex align-items-center justify-content-between gap-2 py-2 border-bottom">
    <div>
        <span class="fw-semibold">{{ $s->date ? $s->date->locale('pt_BR')->translatedFormat('D, d/m') : '—' }}</span>
        @if($isToday)<span class="badge bg-primary ms-1">hoje</span>@endif
        @if($s->start_time)<span class="text-muted small ms-1">{{ substr($s->start_time, 0, 5) }}</span>@endif
    </div>
    <div class="d-flex align-items-center gap-2">
        @if($feita)
            <span class="badge bg-success-subtle text-success-emphasis" title="Chamada feita">
                <i class="fas fa-check me-1"></i>{{ (int) $s->presentes_count }} de {{ (int) $s->registros_count }} presentes
            </span>
        @elseif(!$futura || $isToday)
            <span class="badge bg-warning-subtle text-warning-emphasis">chamada pendente</span>
        @endif
        <a href="{{ url('/projects/' . $s->project_id . '/class-sessions/' . $s->id) }}"
           class="btn btn-sm {{ (!$feita && (!$futura || $isToday)) ? 'btn-primary' : 'btn-outline-primary' }}">
            {{ $feita ? 'Abrir' : 'Fazer chamada' }}
        </a>
    </div>
</div>
