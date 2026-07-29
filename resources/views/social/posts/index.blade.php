@extends('layouts.app')
@section('title', 'Redes Sociais — Posts')

@push('styles')
<style>
    .sp-page { padding: 24px 0 60px; }

    /* Header */
    .sp-hero {
        display:flex; align-items:center; justify-content:space-between;
        gap:20px; margin-bottom:24px; flex-wrap:wrap;
    }
    .sp-hero h1 { font-size:1.7rem; font-weight:800; color:#0f172a; margin:0 0 4px; }
    .sp-hero p { color:#64748b; margin:0; font-size:.92rem; }

    .sp-hero-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .sp-btn { display:inline-flex; align-items:center; gap:8px; padding:9px 18px; border-radius:10px;
        font-size:.88rem; font-weight:600; text-decoration:none; border:0; cursor:pointer;
        transition:all .15s; white-space:nowrap; }
    .sp-btn-primary { background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; box-shadow:0 6px 16px rgba(79,70,229,.25); }
    .sp-btn-primary:hover { transform:translateY(-1px); color:#fff; }
    .sp-btn-ghost { background:#fff; color:#475569; border:1px solid #e2e8f0; }
    .sp-btn-ghost:hover { background:#f8fafc; color:#0f172a; }

    /* Filtros por status */
    .sp-filters { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:24px; }
    .sp-filter { padding:8px 16px; border-radius:20px; font-size:.83rem; font-weight:600;
        text-decoration:none; background:#fff; color:#475569; border:1px solid #e2e8f0;
        display:inline-flex; align-items:center; gap:6px; transition:all .15s; }
    .sp-filter:hover { background:#f8fafc; color:#0f172a; }
    .sp-filter.active { background:#0f172a; color:#fff; border-color:#0f172a; }
    .sp-filter .sp-count { background:rgba(148,163,184,.2); color:inherit; padding:1px 8px;
        border-radius:10px; font-size:.72rem; font-weight:700; }
    .sp-filter.active .sp-count { background:rgba(255,255,255,.2); }

    /* Calendário collapse */
    .sp-cal-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px;
        overflow:hidden; margin-bottom:24px; }
    .sp-cal-toggle { display:flex; justify-content:space-between; align-items:center;
        padding:14px 20px; cursor:pointer; user-select:none; }
    .sp-cal-toggle strong { color:#0f172a; font-size:.95rem; font-weight:700; }
    .sp-cal-toggle i.sp-chev { color:#94a3b8; transition:transform .2s; }
    .sp-cal-body { padding:16px; display:none; border-top:1px solid #f1f5f9; }
    .sp-cal-body.open { display:block; }
    .sp-cal-toggle.open i.sp-chev { transform:rotate(180deg); }

    /* Grid de cards */
    .sp-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:18px; }

    /* Card individual */
    .sp-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; overflow:hidden;
        display:flex; flex-direction:column; transition:all .18s; }
    .sp-card:hover { border-color:#cbd5e1; box-shadow:0 8px 24px rgba(15,23,42,.06); transform:translateY(-2px); }

    /* Thumbnail */
    .sp-thumb { position:relative; height:170px; background:#f1f5f9;
        display:flex; align-items:center; justify-content:center; overflow:hidden; }
    .sp-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
    .sp-thumb-placeholder { color:#cbd5e1; font-size:2.4rem; }
    .sp-thumb-video-badge { position:absolute; top:10px; right:10px; background:rgba(15,23,42,.75);
        color:#fff; font-size:.7rem; padding:4px 8px; border-radius:6px; font-weight:600;
        display:inline-flex; align-items:center; gap:5px; }
    /* Badge de status na thumb */
    .sp-thumb-status { position:absolute; top:10px; left:10px; padding:4px 10px; border-radius:20px;
        font-size:.68rem; font-weight:800; letter-spacing:.04em; text-transform:uppercase;
        display:inline-flex; align-items:center; gap:5px; backdrop-filter:blur(6px);
        background:rgba(255,255,255,.9); }
    .sp-thumb-status.published { color:#059669; }
    .sp-thumb-status.scheduled { color:#4f46e5; }
    .sp-thumb-status.failed { color:#dc2626; }
    .sp-thumb-status.draft { color:#64748b; }
    .sp-thumb-status i { font-size:.65rem; }
    .sp-thumb-status .dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

    /* Corpo do card */
    .sp-card-body { padding:14px 16px; flex:1; display:flex; flex-direction:column; }

    .sp-platforms { display:flex; gap:6px; margin-bottom:10px; }
    .sp-plat-badge { display:inline-flex; align-items:center; gap:5px; font-size:.72rem;
        font-weight:700; padding:3px 8px; border-radius:6px; }
    .sp-plat-badge.fb { background:rgba(24,119,242,.1); color:#1877f2; }
    .sp-plat-badge.ig { background:rgba(228,64,95,.1); color:#e4405f; }

    .sp-caption { color:#334155; font-size:.87rem; line-height:1.45;
        display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden;
        margin:0 0 12px; min-height:56px; }

    .sp-meta { display:flex; align-items:center; gap:10px; color:#64748b; font-size:.78rem;
        margin-bottom:10px; padding-top:10px; border-top:1px solid #f1f5f9; }
    .sp-meta i { color:#94a3b8; }
    .sp-account-avatar { width:22px; height:22px; border-radius:50%; background:#f1f5f9;
        display:inline-flex; align-items:center; justify-content:center; font-size:.75rem;
        color:#64748b; font-weight:700; overflow:hidden; }
    .sp-account-avatar img { width:100%; height:100%; object-fit:cover; }

    /* Erro discreto pra failed */
    .sp-error { background:#fef2f2; color:#991b1b; padding:8px 10px; border-radius:8px;
        font-size:.75rem; margin-bottom:10px; display:flex; align-items:flex-start; gap:6px; }
    .sp-error i { flex-shrink:0; margin-top:2px; }

    /* Ações */
    .sp-actions { display:flex; gap:6px; margin-top:auto; padding-top:10px;
        border-top:1px solid #f1f5f9; flex-wrap:wrap; }
    .sp-action { flex:1; min-width:60px; padding:6px 10px; border-radius:8px;
        font-size:.75rem; font-weight:600; text-decoration:none; text-align:center;
        display:inline-flex; align-items:center; justify-content:center; gap:5px;
        background:#f8fafc; color:#475569; border:1px solid #e2e8f0; cursor:pointer;
        transition:all .15s; }
    .sp-action:hover { background:#f1f5f9; color:#0f172a; }
    .sp-action.primary { background:rgba(79,70,229,.08); color:#4f46e5; border-color:rgba(79,70,229,.15); }
    .sp-action.primary:hover { background:rgba(79,70,229,.15); }
    .sp-action.danger { color:#dc2626; }
    .sp-action.danger:hover { background:#fef2f2; color:#991b1b; border-color:#fecaca; }
    .sp-action-form { display:inline-flex; flex:1; margin:0; }

    /* Empty state */
    .sp-empty { background:#fff; border:2px dashed #e2e8f0; border-radius:16px;
        padding:60px 24px; text-align:center; }
    .sp-empty i { font-size:3rem; color:#cbd5e1; margin-bottom:16px; }
    .sp-empty h3 { color:#0f172a; font-weight:700; margin:0 0 6px; }
    .sp-empty p { color:#64748b; margin:0 0 20px; }

    /* ─── Modal do evento do calendário (substitui o alert() feio) ────── */
    .sp-modal-overlay { position:fixed; inset:0; background:rgba(15,23,42,.55);
        backdrop-filter:blur(4px); z-index:9998; display:none; align-items:center;
        justify-content:center; padding:20px; }
    .sp-modal-overlay.open { display:flex; }
    .sp-modal { background:#fff; border-radius:16px; max-width:480px; width:100%;
        max-height:90vh; overflow-y:auto; box-shadow:0 24px 60px rgba(15,23,42,.25);
        animation:spModalIn .18s ease-out; }
    @keyframes spModalIn { from { transform:translateY(20px); opacity:0; } to { transform:translateY(0); opacity:1; } }

    .sp-modal-head { padding:18px 20px 12px; display:flex; justify-content:space-between;
        align-items:flex-start; gap:14px; border-bottom:1px solid #f1f5f9; }
    .sp-modal-title { color:#0f172a; font-weight:800; font-size:1rem; margin:0; }
    .sp-modal-subtitle { color:#64748b; font-size:.8rem; margin-top:2px; }
    .sp-modal-close { background:none; border:0; color:#94a3b8; font-size:1.2rem;
        cursor:pointer; padding:0 6px; line-height:1; }
    .sp-modal-close:hover { color:#0f172a; }

    .sp-modal-body { padding:16px 20px; }
    .sp-modal-thumb { width:100%; max-height:220px; object-fit:cover; border-radius:10px;
        margin-bottom:14px; background:#f1f5f9; }
    .sp-modal-caption { color:#334155; font-size:.9rem; line-height:1.55; margin:0 0 14px;
        padding:12px; background:#f8fafc; border-radius:10px; }
    .sp-modal-meta { display:grid; grid-template-columns:repeat(2, 1fr); gap:10px 16px;
        font-size:.83rem; margin-bottom:14px; }
    .sp-modal-meta-item span { display:block; color:#94a3b8; font-size:.72rem;
        font-weight:700; letter-spacing:.06em; text-transform:uppercase; margin-bottom:2px; }
    .sp-modal-meta-item strong { color:#0f172a; font-weight:600; }
    .sp-modal-error { background:#fef2f2; color:#991b1b; padding:10px 12px; border-radius:8px;
        font-size:.82rem; margin-bottom:14px; display:flex; align-items:flex-start; gap:8px; }

    .sp-modal-actions { padding:14px 20px 18px; display:flex; gap:8px; flex-wrap:wrap;
        border-top:1px solid #f1f5f9; }
    .sp-modal-actions .sp-action { flex:1; padding:10px 14px; font-size:.85rem; }
</style>
@endpush

@section('content')
<div class="container-fluid sp-page">

    {{-- Hero --}}
    <div class="sp-hero">
        <div>
            <h1><i class="fas fa-share-nodes" style="color:#4f46e5;"></i> Redes Sociais</h1>
            <p>Crie, agende e acompanhe seus posts no Facebook e Instagram.</p>
        </div>
        <div class="sp-hero-actions">
            <a href="{{ route('social.accounts') }}" class="sp-btn sp-btn-ghost">
                <i class="fas fa-plug"></i> Contas conectadas
            </a>
            <a href="{{ route('social-ai.index') }}" class="sp-btn sp-btn-ghost">
                <i class="fas fa-wand-magic-sparkles" style="color:#f59e0b;"></i> Criar com IA
            </a>
            <a href="{{ route('social.posts.create') }}" class="sp-btn sp-btn-primary">
                <i class="fas fa-plus"></i> Novo Post
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 rounded-3 mb-3"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 rounded-3 mb-3"><i class="fas fa-triangle-exclamation me-2"></i>{{ session('error') }}</div>
    @endif

    {{-- Filtros por status --}}
    <div class="sp-filters">
        <a href="{{ route('social.posts.index') }}" class="sp-filter {{ !$filter ? 'active' : '' }}">
            Todos <span class="sp-count">{{ $counts['all'] ?? 0 }}</span>
        </a>
        <a href="{{ route('social.posts.index', ['status' => 'scheduled']) }}" class="sp-filter {{ $filter === 'scheduled' ? 'active' : '' }}">
            <i class="fas fa-calendar-check" style="color:#4f46e5;"></i> Agendados <span class="sp-count">{{ $counts['scheduled'] ?? 0 }}</span>
        </a>
        <a href="{{ route('social.posts.index', ['status' => 'published']) }}" class="sp-filter {{ $filter === 'published' ? 'active' : '' }}">
            <i class="fas fa-check-circle" style="color:#10b981;"></i> Publicados <span class="sp-count">{{ $counts['published'] ?? 0 }}</span>
        </a>
        <a href="{{ route('social.posts.index', ['status' => 'failed']) }}" class="sp-filter {{ $filter === 'failed' ? 'active' : '' }}">
            <i class="fas fa-triangle-exclamation" style="color:#dc2626;"></i> Falhas <span class="sp-count">{{ $counts['failed'] ?? 0 }}</span>
        </a>
        @if(!empty($counts['draft']))
        <a href="{{ route('social.posts.index', ['status' => 'draft']) }}" class="sp-filter {{ $filter === 'draft' ? 'active' : '' }}">
            <i class="fas fa-file-lines" style="color:#64748b;"></i> Rascunhos <span class="sp-count">{{ $counts['draft'] ?? 0 }}</span>
        </a>
        @endif
    </div>

    {{-- Calendário colapsável --}}
    <div class="sp-cal-card">
        <div class="sp-cal-toggle" onclick="spToggleCal(this)">
            <strong><i class="fas fa-calendar-alt me-2" style="color:#4f46e5;"></i> Ver calendário editorial</strong>
            <i class="fas fa-chevron-down sp-chev"></i>
        </div>
        <div class="sp-cal-body">
            <div id="socialCalendar"></div>
        </div>
    </div>

    {{-- Grid de posts --}}
    @if($posts->isEmpty())
        <div class="sp-empty">
            <i class="fas fa-image"></i>
            <h3>Nenhum post {{ $filter ? 'nesse filtro' : 'ainda' }}</h3>
            <p>{{ $filter ? 'Ajuste o filtro ou crie um novo.' : 'Crie seu primeiro post e agende ou publique agora mesmo.' }}</p>
            <a href="{{ route('social.posts.create') }}" class="sp-btn sp-btn-primary">
                <i class="fas fa-plus"></i> Criar meu primeiro post
            </a>
        </div>
    @else
        <div class="sp-grid">
            @foreach($posts as $post)
                @php
                    $statusLabels = [
                        'scheduled' => ['Agendado', 'calendar-check'],
                        'published' => ['Publicado', 'check-circle'],
                        'failed'    => ['Falhou',    'triangle-exclamation'],
                        'draft'     => ['Rascunho',  'file-lines'],
                        'cancelled' => ['Cancelado', 'ban'],
                    ];
                    [$stLabel, $stIcon] = $statusLabels[$post->status] ?? [$post->status, 'circle'];
                    $accountName = $post->account?->page_name ?? 'Sem conta';
                    $accountInitial = mb_strtoupper(mb_substr($accountName, 0, 1));
                    $fbUrl = ($post->status === 'published' && $post->facebook_post_id && $post->account)
                        ? 'https://www.facebook.com/' . $post->facebook_post_id
                        : null;
                    $igUrl = ($post->status === 'published' && $post->instagram_post_id)
                        ? 'https://www.instagram.com/p/' . $post->instagram_post_id . '/'
                        : null;
                @endphp
                <div class="sp-card">
                    {{-- Thumbnail --}}
                    @php
                        $mediaItems = $post->mediaList();
                        $isCarousel = count($mediaItems) >= 2;
                        $firstMedia = $mediaItems[0] ?? null;
                    @endphp
                    <div class="sp-thumb">
                        @if($firstMedia && ($firstMedia['type'] ?? 'image') === 'image')
                            <img loading="lazy" src="{{ $firstMedia['url'] }}" alt="Mídia do post">
                        @elseif($firstMedia && ($firstMedia['type'] ?? '') === 'video')
                            <img loading="lazy" src="{{ $firstMedia['url'] }}" alt="Frame do vídeo" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                            <div class="sp-thumb-placeholder" style="display:none;"><i class="fas fa-film"></i></div>
                            <span class="sp-thumb-video-badge"><i class="fas fa-video"></i> VÍDEO</span>
                        @else
                            <div class="sp-thumb-placeholder"><i class="fas fa-image"></i></div>
                        @endif

                        @if($isCarousel)
                            <span class="sp-thumb-video-badge" style="background:rgba(79,70,229,.9);">
                                <i class="fas fa-images"></i> {{ count($mediaItems) }} mídias
                            </span>
                        @endif

                        <span class="sp-thumb-status {{ $post->status }}">
                            <span class="dot"></span> {{ $stLabel }}
                        </span>
                    </div>

                    {{-- Corpo --}}
                    <div class="sp-card-body">
                        {{-- Plataformas --}}
                        <div class="sp-platforms">
                            @if(in_array($post->platform, ['facebook', 'both']))
                                <span class="sp-plat-badge fb"><i class="fab fa-facebook"></i> Facebook</span>
                            @endif
                            @if(in_array($post->platform, ['instagram', 'both']))
                                <span class="sp-plat-badge ig"><i class="fab fa-instagram"></i> Instagram</span>
                            @endif
                        </div>

                        {{-- Legenda --}}
                        <p class="sp-caption">{{ $post->caption }}</p>

                        {{-- Erro (só failed) --}}
                        @if($post->status === 'failed' && $post->error_message)
                            <div class="sp-error">
                                <i class="fas fa-triangle-exclamation"></i>
                                <span>{{ Str::limit($post->error_message, 100) }}</span>
                            </div>
                        @endif

                        {{-- Meta: conta + data --}}
                        <div class="sp-meta">
                            <div class="sp-account-avatar">
                                @if($post->account?->page_picture)
                                    <img loading="lazy" src="{{ $post->account->page_picture }}" alt="">
                                @else
                                    {{ $accountInitial }}
                                @endif
                            </div>
                            <span class="text-truncate" style="max-width:100px;">{{ $accountName }}</span>
                            <span style="margin-left:auto;">
                                <i class="fas fa-clock"></i>
                                {{ $post->scheduled_at->setTimezone('America/Sao_Paulo')->format('d/m H:i') }}
                            </span>
                        </div>

                        {{-- Ações --}}
                        <div class="sp-actions">
                            @if($post->status === 'scheduled')
                                <a href="{{ route('social.posts.edit', $post) }}" class="sp-action primary">
                                    <i class="fas fa-pen"></i> Editar
                                </a>
                                <form action="{{ route('social.posts.destroy', $post) }}" method="POST" class="sp-action-form"
                                      onsubmit="return confirm('Remover este post agendado?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="sp-action danger" style="width:100%;">
                                        <i class="fas fa-trash"></i> Remover
                                    </button>
                                </form>
                            @elseif($post->status === 'published')
                                @if($fbUrl)
                                    <a href="{{ $fbUrl }}" target="_blank" rel="noopener" class="sp-action primary">
                                        <i class="fab fa-facebook"></i> Ver
                                    </a>
                                @endif
                                @if($igUrl)
                                    <a href="{{ $igUrl }}" target="_blank" rel="noopener" class="sp-action primary">
                                        <i class="fab fa-instagram"></i> Ver
                                    </a>
                                @endif
                                @if(!$fbUrl && !$igUrl)
                                    <span class="sp-action" style="cursor:default;">Publicado</span>
                                @endif
                            @elseif($post->status === 'failed')
                                <a href="{{ route('social.posts.create') }}" class="sp-action primary">
                                    <i class="fas fa-rotate-right"></i> Tentar de novo
                                </a>
                                <form action="{{ route('social.posts.destroy', $post) }}" method="POST" class="sp-action-form"
                                      onsubmit="return confirm('Remover este post?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="sp-action danger" style="width:100%;">
                                        <i class="fas fa-trash"></i> Remover
                                    </button>
                                </form>
                            @else
                                <span class="sp-action" style="cursor:default;">{{ $stLabel }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($posts->hasPages())
            <div class="mt-4 d-flex justify-content-center">{{ $posts->links() }}</div>
        @endif
    @endif

</div>

{{-- Modal Vivensi para detalhes do evento do calendário --}}
<div class="sp-modal-overlay" id="spEventModal" onclick="if(event.target===this) spCloseModal()">
    <div class="sp-modal">
        <div class="sp-modal-head">
            <div>
                <p class="sp-modal-title" id="spEvTitle">Post</p>
                <div class="sp-modal-subtitle" id="spEvSubtitle"></div>
            </div>
            <button type="button" class="sp-modal-close" onclick="spCloseModal()" aria-label="Fechar">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="sp-modal-body">
            <img id="spEvThumb" class="sp-modal-thumb" src="" alt="" style="display:none;">
            <div id="spEvCaption" class="sp-modal-caption"></div>
            <div id="spEvError" class="sp-modal-error" style="display:none;">
                <i class="fas fa-triangle-exclamation"></i> <span id="spEvErrorText"></span>
            </div>
            <div class="sp-modal-meta">
                <div class="sp-modal-meta-item">
                    <span>Conta</span>
                    <strong id="spEvAccount">—</strong>
                </div>
                <div class="sp-modal-meta-item">
                    <span>Plataforma</span>
                    <strong id="spEvPlatform">—</strong>
                </div>
                <div class="sp-modal-meta-item">
                    <span>Status</span>
                    <strong id="spEvStatus">—</strong>
                </div>
                <div class="sp-modal-meta-item">
                    <span>Data</span>
                    <strong id="spEvDate">—</strong>
                </div>
            </div>
        </div>
        <div class="sp-modal-actions" id="spEvActions"></div>
    </div>
</div>

{{-- FullCalendar --}}
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales/pt-br.global.min.js"></script>
<script>
    let __spCalRendered = false;
    function spToggleCal(el) {
        const body = el.nextElementSibling;
        const isOpen = body.classList.toggle('open');
        el.classList.toggle('open', isOpen);
        if (isOpen && !__spCalRendered) {
            spRenderCal();
            __spCalRendered = true;
        }
    }

    function spRenderCal() {
        const cal = new FullCalendar.Calendar(document.getElementById('socialCalendar'), {
            initialView: 'dayGridMonth', locale: 'pt-br',
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
            buttonText: { today:'Hoje', month:'Mês', week:'Semana', day:'Dia', list:'Lista' },
            allDayText: 'Dia inteiro', noEventsText: 'Nenhum post agendado neste período.',
            moreLinkText: n => '+ mais ' + n,
            events: '{{ route('social.posts.calendar') }}',
            eventClick: info => spOpenEventModal(info.event),
            height: 'auto',
        });
        cal.render();
    }

    // ── Modal Vivensi (substitui alert()) ─────────────────────────────
    const spStatusLabels = {
        scheduled: '📅 Agendado', published: '✅ Publicado',
        failed:    '⚠️ Falhou',  draft:     '📝 Rascunho', cancelled: '⊘ Cancelado',
    };
    const spPlatformLabels = {
        facebook: 'Facebook', instagram: 'Instagram', both: 'Facebook + Instagram',
    };

    function spOpenEventModal(ev) {
        const p = ev.extendedProps || {};
        document.getElementById('spEvTitle').innerText  = 'Detalhes do post';
        document.getElementById('spEvSubtitle').innerText = spStatusLabels[p.status] || p.status || '';
        document.getElementById('spEvCaption').innerText  = p.caption || ev.title || '(sem legenda)';

        const thumb = document.getElementById('spEvThumb');
        if (p.media_url && p.media_type === 'image') {
            thumb.src = p.media_url; thumb.style.display = 'block';
        } else {
            thumb.style.display = 'none';
        }

        const err = document.getElementById('spEvError');
        if (p.status === 'failed' && p.error_message) {
            document.getElementById('spEvErrorText').innerText = p.error_message;
            err.style.display = 'flex';
        } else {
            err.style.display = 'none';
        }

        document.getElementById('spEvAccount').innerText  = p.account || '—';
        document.getElementById('spEvPlatform').innerText = spPlatformLabels[p.platform] || p.platform || '—';
        document.getElementById('spEvStatus').innerText   = spStatusLabels[p.status] || p.status || '—';
        document.getElementById('spEvDate').innerText     = ev.start
            ? new Date(ev.start).toLocaleString('pt-BR', { timeZone: 'America/Sao_Paulo',
                day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' })
            : '—';

        const actions = document.getElementById('spEvActions');
        actions.innerHTML = '';
        if (p.edit_url) {
            actions.insertAdjacentHTML('beforeend',
                `<a href="${p.edit_url}" class="sp-action primary"><i class="fas fa-pen"></i> Editar</a>`);
        }
        if (p.facebook_url) {
            actions.insertAdjacentHTML('beforeend',
                `<a href="${p.facebook_url}" target="_blank" rel="noopener" class="sp-action primary"><i class="fab fa-facebook"></i> Ver no Facebook</a>`);
        }
        if (p.instagram_url) {
            actions.insertAdjacentHTML('beforeend',
                `<a href="${p.instagram_url}" target="_blank" rel="noopener" class="sp-action primary"><i class="fab fa-instagram"></i> Ver no Instagram</a>`);
        }
        if (!actions.children.length) {
            actions.insertAdjacentHTML('beforeend',
                `<button type="button" class="sp-action" onclick="spCloseModal()">Fechar</button>`);
        }

        document.getElementById('spEventModal').classList.add('open');
    }

    function spCloseModal() {
        document.getElementById('spEventModal').classList.remove('open');
    }

    // ESC fecha o modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') spCloseModal();
    });
</script>
@endsection
