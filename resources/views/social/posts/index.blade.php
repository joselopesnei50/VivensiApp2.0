@extends('layouts.app')
@section('title', 'Calendário Editorial')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">Calendário Editorial</h1>
            <p class="text-muted small mb-0">Visualize e gerencie seus posts agendados.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('social.accounts') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-plug me-1"></i> Contas
            </a>
            <a href="{{ route('marketing.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-wand-magic-sparkles me-1"></i> Criar com IA
            </a>
            <a href="{{ route('social.posts.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i> Novo Post
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 rounded-3"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</div>
    @endif

    <!-- Calendário FullCalendar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div id="socialCalendar"></div>
        </div>
    </div>

    <!-- Lista de posts -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white pt-3 pb-0 px-4 border-bottom-0">
            <h6 class="fw-bold mb-0">Todos os Posts</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Legenda</th>
                            <th>Conta</th>
                            <th>Plataforma</th>
                            <th>Agendado para</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($posts as $post)
                        <tr>
                            <td class="ps-4" style="max-width:260px;">
                                <p class="mb-0 text-truncate fw-500">{{ $post->caption }}</p>
                            </td>
                            <td>{{ $post->account?->page_name ?? '—' }}</td>
                            <td>
                                @if($post->platform === 'facebook')
                                    <span class="badge bg-primary bg-opacity-15 text-primary"><i class="fab fa-facebook me-1"></i>Facebook</span>
                                @elseif($post->platform === 'instagram')
                                    <span class="badge bg-danger bg-opacity-15 text-danger"><i class="fab fa-instagram me-1"></i>Instagram</span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-15 text-secondary"><i class="fas fa-share-nodes me-1"></i>Ambos</span>
                                @endif
                            </td>
                            <td>{{ $post->scheduled_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @match($post->status)
                                    'scheduled'  => '<span class="badge bg-primary bg-opacity-15 text-primary">Agendado</span>',
                                    'published'  => '<span class="badge bg-success bg-opacity-15 text-success">Publicado</span>',
                                    'failed'     => '<span class="badge bg-danger bg-opacity-15 text-danger" title="'.$post->error_message.'">Falhou</span>',
                                    'cancelled'  => '<span class="badge bg-secondary bg-opacity-15 text-secondary">Cancelado</span>',
                                    default      => '<span class="badge bg-light text-muted">'.$post->status.'</span>',
                                @endmatch
                            </td>
                            <td class="text-end pe-4">
                                @if($post->status === 'scheduled')
                                <form action="{{ route('social.posts.destroy', $post) }}" method="POST"
                                      onsubmit="return confirm('Remover este post?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-outline-danger" style="font-size:.72rem;padding:3px 8px;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum post agendado ainda.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($posts->hasPages())
                <div class="px-4 py-3 border-top">{{ $posts->links() }}</div>
            @endif
        </div>
    </div>
</div>

{{-- FullCalendar --}}
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales/pt-br.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cal = new FullCalendar.Calendar(document.getElementById('socialCalendar'), {
        initialView: 'dayGridMonth',
        locale: 'pt-br',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        buttonText: {
            today:    'Hoje',
            month:    'Mês',
            week:     'Semana',
            day:      'Dia',
            list:     'Lista',
        },
        views: {
            timeGridWeek: { buttonText: 'Semana' },
            listWeek:     { buttonText: 'Lista'  },
        },
        allDayText: 'Dia inteiro',
        noEventsText: 'Nenhum post agendado neste período.',
        moreLinkText: function(n) { return '+ mais ' + n; },
        events: '{{ route('social.posts.calendar') }}',
        eventClick: function(info) {
            const p = info.event.extendedProps;
            alert(`${info.event.title}\n\nConta: ${p.account}\nPlataforma: ${p.platform}\nStatus: ${p.status}`);
        },
        height: 'auto',
    });
    cal.render();
});
</script>
@endsection
