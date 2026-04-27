@extends('layouts.app', ['title' => 'Prospecção Inteligente'])

@section('content')
<div class="container-fluid py-4">

    {{-- ── HEADER ──────────────────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-start mb-5 flex-wrap gap-3">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1" style="font-family:'Outfit',sans-serif;">
                <i class="fas fa-crosshairs text-primary me-2"></i> Prospecção Inteligente
            </h1>
            <p class="text-muted mb-0">Encontre doadores e parceiros via Google Maps ou Busca Web. A Bruce AI analisa e gera o pitch automaticamente.</p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            @if($stats['raw'] > 0)
                <form action="{{ route('prospecting.analyze-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">
                        <i class="fas fa-wand-magic-sparkles me-2"></i> Analisar {{ $stats['raw'] }} Raw
                    </button>
                </form>
                <a href="{{ request()->fullUrl() }}" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">
                    <i class="fas fa-sync-alt me-2"></i> Atualizar Resultados
                </a>
            @endif
            <button class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#searchModal">
                <i class="fas fa-search me-2"></i> Nova Busca
            </button>
        </div>
    </div>

    {{-- ── ALERTS ──────────────────────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center gap-2">
            <i class="fas fa-check-circle fa-lg"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center gap-2">
            <i class="fas fa-exclamation-circle fa-lg"></i> {{ session('error') }}
        </div>
    @endif

    {{-- ── STATS CARDS ─────────────────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3" style="background:#eff6ff;">
                        <i class="fas fa-users fa-lg text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Total Identificados</div>
                        <div class="h4 fw-bold mb-0">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3" style="background:#fefce8;">
                        <i class="fas fa-clock fa-lg text-warning"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Aguardando IA</div>
                        <div class="h4 fw-bold mb-0">{{ $stats['raw'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3" style="background:#f0fdf4;">
                        <i class="fas fa-brain fa-lg text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Analisados</div>
                        <div class="h4 fw-bold mb-0">{{ $stats['analyzed'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3" style="background:#fff7ed;">
                        <i class="fas fa-fire fa-lg text-danger"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Hot Leads (≥80%)</div>
                        <div class="h4 fw-bold mb-0">{{ $stats['hot'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── FILTRO POR STATUS ────────────────────────────────────────────────── --}}
    <div class="mb-4 d-flex gap-2 flex-wrap">
        @php
            $filters = [
                null         => ['label' => 'Todos',     'icon' => 'list', 'count' => $stats['total']],
                'raw'        => ['label' => 'Aguardando','icon' => 'clock','count' => $stats['raw']],
                'analyzed'   => ['label' => 'Analisados','icon' => 'check-circle','count' => $stats['analyzed']],
                'contacted'  => ['label' => 'Contatados','icon' => 'phone','count' => $stats['contacted']],
            ];
        @endphp
        @foreach($filters as $val => $f)
            <a href="{{ route('prospecting.index', $val ? ['status' => $val] : []) }}"
               class="btn {{ $status == $val ? 'btn-primary' : 'btn-outline-secondary' }} btn-sm rounded-pill px-3 fw-semibold">
                <i class="fas fa-{{ $f['icon'] }} me-1"></i> {{ $f['label'] }}
                <span class="badge {{ $status == $val ? 'bg-white text-primary' : 'bg-light text-dark' }} ms-1">{{ $f['count'] }}</span>
            </a>
        @endforeach
    </div>

    {{-- ── TABELA DE LEADS ─────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 border-0">Empresa / Contato</th>
                        <th class="py-3 border-0 text-center" style="width:130px;">Score IA</th>
                        <th class="py-3 border-0" style="width:150px;">Categoria</th>
                        <th class="py-3 border-0" style="width:130px;">Status</th>
                        <th class="py-3 border-0 text-end px-4" style="width:80px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prospects as $prospect)
                        <tr>
                            {{-- Empresa --}}
                            <td class="px-4 py-3">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="rounded-3 bg-light text-primary d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                         style="width:42px;height:42px;font-size:1.1rem;">
                                        {{ mb_strtoupper(mb_substr($prospect->company_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark mb-1">
                                            {{ $prospect->company_name }}
                                            @if($prospect->lead_score >= 80 && $prospect->status === 'analyzed')
                                                <span class="badge bg-danger ms-1 hot-lead-badge">
                                                    <i class="fas fa-fire me-1"></i>HOT
                                                </span>
                                            @endif
                                        </div>
                                        <div class="mt-1">
                                            @if(($prospect->source ?? 'maps') === 'web')
                                                <span class="source-badge-web"><i class="fas fa-globe me-1"></i>Web</span>
                                            @else
                                                <span class="source-badge-maps"><i class="fas fa-map-marker-alt me-1"></i>Maps</span>
                                            @endif
                                        </div>
                                        @if($prospect->address)
                                            <div class="text-muted small"><i class="fas fa-map-marker-alt me-1 text-muted"></i>{{ $prospect->address }}</div>
                                        @endif
                                        @if($prospect->phone)
                                            <div class="text-success small mt-1 fw-semibold"><i class="fab fa-whatsapp me-1"></i>{{ $prospect->phone }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Score --}}
                            <td class="text-center">
                                @if($prospect->status === 'analyzed')
                                    @php $sc = $prospect->lead_score; $cls = $sc >= 80 ? 'success' : ($sc >= 50 ? 'warning' : 'danger'); @endphp
                                    <div class="fw-bold text-{{ $cls }} fs-5">{{ $sc }}%</div>
                                    <div class="progress mt-1 mx-auto" style="height:4px;width:60px;">
                                        <div class="progress-bar bg-{{ $cls }}" style="width:{{ $sc }}%"></div>
                                    </div>
                                @elseif($prospect->status === 'raw')
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning fw-normal p-2">
                                        <i class="fas fa-spinner fa-spin me-1"></i> Analisando...
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>

                            {{-- Categoria --}}
                            <td>
                                <span class="badge bg-light text-dark fw-normal p-2 border">{{ $prospect->category }}</span>
                            </td>

                            {{-- Status --}}
                            <td>
                                @if($prospect->status === 'raw')
                                    <span class="badge p-2 rounded-3" style="background:#fefce8;color:#92400e;">
                                        <i class="fas fa-clock me-1"></i> Raw
                                    </span>
                                @elseif($prospect->status === 'analyzed')
                                    <span class="badge p-2 rounded-3" style="background:#f0fdf4;color:#166534;">
                                        <i class="fas fa-check-circle me-1"></i> Analisado
                                    </span>
                                @else
                                    <span class="badge p-2 rounded-3" style="background:#eff6ff;color:#1e40af;">
                                        <i class="fas fa-phone me-1"></i> Contatado
                                    </span>
                                @endif
                            </td>

                            {{-- Ações --}}
                            <td class="text-end px-4">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm rounded-circle shadow-sm" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-3" style="min-width:190px;">
                                        <li>
                                            <a class="dropdown-item py-2" href="#"
                                               data-bs-toggle="modal" data-bs-target="#detailModal{{ $prospect->id }}">
                                                <i class="fas fa-eye me-2 text-primary"></i> Ver Análise / Pitch
                                            </a>
                                        </li>
                                        <li>
                                            <form action="{{ route('prospecting.convert', $prospect->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item py-2 fw-bold text-success">
                                                    <i class="fas fa-chart-line me-2"></i> Enviar para Funil
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="{{ route('prospecting.analyze', $prospect->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item py-2">
                                                    <i class="fas fa-wand-magic-sparkles me-2 text-warning"></i> Re-analisar IA
                                                </button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('prospecting.destroy', $prospect->id) }}" method="POST"
                                                  onsubmit="return confirm('Excluir este lead permanentemente?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item py-2 text-danger">
                                                    <i class="fas fa-trash-alt me-2"></i> Remover
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>

                        {{-- Modal Detalhes / Pitch ─────────────────────────────────── --}}
                        <div class="modal fade" id="detailModal{{ $prospect->id }}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content border-0 rounded-4 overflow-hidden shadow-lg">
                                    <div class="modal-header p-4" style="background:linear-gradient(135deg,#1e293b,#0f172a);">
                                        <div>
                                            <h5 class="modal-title fw-bold text-white mb-1">
                                                {{ $prospect->company_name }}
                                            </h5>
                                            <span class="badge bg-white bg-opacity-10 text-white fw-normal">
                                                {{ $prospect->category }} · {{ $prospect->address }}
                                            </span>
                                        </div>
                                        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body p-4">
                                        @if($prospect->status === 'raw')
                                            <div class="text-center py-4 text-muted">
                                                <i class="fas fa-spinner fa-spin fa-2x text-warning mb-3 d-block"></i>
                                                Bruce AI está analisando este lead. Aguarde alguns instantes e recarregue a página.
                                            </div>
                                        @else
                                        <div class="row g-4">
                                            {{-- Coluna Score --}}
                                            <div class="col-md-4">
                                                <div class="text-center mb-4">
                                                    @php $sc = $prospect->lead_score; $cls = $sc >= 80 ? '#16a34a' : ($sc >= 50 ? '#d97706' : '#dc2626'); @endphp
                                                    <div style="font-size:3.5rem;font-weight:900;color:{{ $cls }};line-height:1;">{{ $sc }}</div>
                                                    <div class="small text-muted fw-bold text-uppercase">Fit Score</div>
                                                </div>
                                                <div class="mb-3 p-3 rounded-3 bg-light">
                                                    <div class="small fw-bold text-dark mb-1"><i class="fab fa-whatsapp text-success me-1"></i>Contato</div>
                                                    <p class="small text-muted mb-0">{{ $prospect->phone ?? 'Não disponível' }}</p>
                                                </div>
                                                @if($prospect->website)
                                                    <div class="mb-3 p-3 rounded-3 bg-light">
                                                        <div class="small fw-bold text-dark mb-1"><i class="fas fa-globe text-primary me-1"></i>Website</div>
                                                        <a href="{{ $prospect->website }}" target="_blank" class="small text-primary text-truncate d-block">{{ $prospect->website }}</a>
                                                    </div>
                                                @endif
                                                @if($prospect->google_rating)
                                                    <div class="p-3 rounded-3 bg-light">
                                                        <div class="small fw-bold text-dark mb-1"><i class="fab fa-google text-danger me-1"></i>Google</div>
                                                        <p class="small text-muted mb-0">⭐ {{ $prospect->google_rating }} · {{ number_format($prospect->total_reviews) }} avaliações</p>
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Coluna Análise + Pitch --}}
                                            <div class="col-md-8">
                                                <div class="mb-4">
                                                    <h6 class="fw-bold text-dark mb-2">
                                                        <i class="fas fa-stethoscope text-primary me-2"></i>Alinhamento Detectado pela Bruce AI
                                                    </h6>
                                                    <div class="p-3 bg-light rounded-3 fst-italic text-muted small lh-lg">
                                                        "{{ $prospect->ai_analysis ?? '—' }}"
                                                    </div>
                                                </div>

                                                <div>
                                                    <h6 class="fw-bold text-dark mb-2">
                                                        <i class="fab fa-whatsapp text-success me-2"></i>Pitch Personalizado de Abordagem
                                                    </h6>
                                                    <div class="p-3 border rounded-3 bg-white position-relative mb-3">
                                                        <p id="pitchText{{ $prospect->id }}" class="mb-0 small lh-lg">
                                                            {{ $prospect->personalized_pitch ?? 'Pitch não gerado.' }}
                                                        </p>
                                                        @if($prospect->personalized_pitch)
                                                            <button class="btn btn-sm btn-light position-absolute top-0 end-0 m-2 shadow-sm"
                                                                    onclick="copyPitch({{ $prospect->id }})" title="Copiar pitch">
                                                                <i class="fas fa-copy"></i>
                                                            </button>
                                                        @endif
                                                    </div>

                                                    <div class="d-flex gap-2">
                                                        @if($prospect->phone && $prospect->personalized_pitch)
                                                            @php
                                                                $waText  = urlencode($prospect->personalized_pitch);
                                                                $waPhone = '55' . preg_replace('/\D/', '', $prospect->phone);
                                                            @endphp
                                                            <a href="https://wa.me/{{ $waPhone }}?text={{ $waText }}"
                                                               target="_blank"
                                                               class="btn btn-success flex-grow-1 rounded-3 fw-bold py-2">
                                                                <i class="fab fa-whatsapp me-2"></i> WhatsApp
                                                            </a>
                                                        @endif

                                                        <form action="{{ route('prospecting.convert', $prospect->id) }}" method="POST" class="flex-grow-1">
                                                            @csrf
                                                            <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold py-2">
                                                                <i class="fas fa-chart-line me-2"></i> Enviar p/ Funil
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                    @empty
                        <tr>
                            <td colspan="5" class="py-5 text-center text-muted">
                                <i class="fas fa-crosshairs fa-3x mb-3 d-block opacity-25"></i>
                                <span class="fw-semibold">Nenhum lead prospectado ainda.</span><br>
                                <small>Clique em <strong>Nova Busca</strong> para iniciar sua prospecção com a Bruce AI.</small>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($prospects->hasPages())
            <div class="card-footer bg-white border-0 py-3 px-4">
                {{ $prospects->links() }}
            </div>
        @endif
    </div>

</div>

{{-- ── MODAL NOVA BUSCA ─────────────────────────────────────────────────── --}}
<div class="modal fade" id="searchModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <div>
                    <h5 class="modal-title fw-bold">Nova Prospecção Inteligente</h5>
                    <p class="text-muted small mb-0">Bruce AI analisa e gera o pitch automaticamente após a busca.</p>
                </div>
                <button type="button" class="btn-close ms-3" data-bs-dismiss="modal"></button>
            </div>

            <form action="{{ route('prospecting.search') }}" method="POST" id="searchForm">
                @csrf
                <input type="hidden" name="mode" id="searchMode" value="maps">

                <div class="modal-body px-4 py-3">
                    {{-- Tabs de modo --}}
                    <div class="d-flex gap-2 mb-4 p-1 rounded-3" style="background:#f1f5f9;">
                        <button type="button" id="tabMaps" onclick="setMode('maps')"
                                class="btn btn-sm fw-bold rounded-2 flex-grow-1 active-mode-tab">
                            <i class="fas fa-map-marker-alt me-1"></i> Google Maps
                        </button>
                        <button type="button" id="tabWeb" onclick="setMode('web')"
                                class="btn btn-sm fw-bold rounded-2 flex-grow-1 inactive-mode-tab">
                            <i class="fas fa-globe me-1"></i> Busca Web
                        </button>
                    </div>

                    {{-- Descrição do modo --}}
                    <div id="descMaps" class="alert alert-light border small py-2 px-3 mb-3">
                        <i class="fas fa-map-pin text-success me-1"></i>
                        <strong>Google Maps:</strong> Encontra estabelecimentos físicos com endereço, telefone e avaliação. Ideal para negócios locais (restaurantes, clínicas, escolas etc.).
                    </div>
                    <div id="descWeb" class="alert alert-light border small py-2 px-3 mb-3" style="display:none;">
                        <i class="fas fa-globe text-primary me-1"></i>
                        <strong>Busca Web:</strong> Encontra empresas e organizações por resultados orgânicos do Google. Ideal para negócios digitais, e-commerces, ONGs e associações.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">
                            O que você procura? <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="term" class="form-control form-control-lg rounded-3"
                               id="termInput"
                               placeholder="Ex: Restaurantes, Escolas, Farmácias..." required>
                        <div class="form-text" id="termHint">Categoria / nicho a ser buscado.</div>
                    </div>

                    <div id="locationField" class="mb-0">
                        <label class="form-label fw-bold small">
                            Onde? <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="location" class="form-control form-control-lg rounded-3"
                               placeholder="Ex: Araraquara SP, São Paulo capital...">
                        <div class="form-text">Cidade e estado para refinar a busca.</div>
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">
                        <i class="fas fa-wand-magic-sparkles me-2"></i> Buscar e Analisar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .hot-lead-badge {
        font-size: 0.62rem;
        padding: 3px 7px;
        animation: hotGlow 2s infinite alternate;
    }
    @keyframes hotGlow {
        from { box-shadow: 0 0 4px #f87171; }
        to   { box-shadow: 0 0 14px #f87171; }
    }
    .active-mode-tab {
        background: #ffffff;
        color: #1e40af;
        box-shadow: 0 1px 4px rgba(0,0,0,.12);
    }
    .inactive-mode-tab {
        background: transparent;
        color: #64748b;
        box-shadow: none;
    }
    .inactive-mode-tab:hover {
        background: rgba(255,255,255,.6);
        color: #334155;
    }
    .source-badge-maps {
        font-size: 0.68rem;
        background: #f0fdf4;
        color: #166534;
        border: 1px solid #bbf7d0;
        border-radius: 6px;
        padding: 2px 7px;
    }
    .source-badge-web {
        font-size: 0.68rem;
        background: #eff6ff;
        color: #1e40af;
        border: 1px solid #bfdbfe;
        border-radius: 6px;
        padding: 2px 7px;
    }
</style>

<script>
    function setMode(mode) {
        document.getElementById('searchMode').value = mode;

        // Tab appearance
        document.getElementById('tabMaps').className =
            'btn btn-sm fw-bold rounded-2 flex-grow-1 ' + (mode === 'maps' ? 'active-mode-tab' : 'inactive-mode-tab');
        document.getElementById('tabWeb').className =
            'btn btn-sm fw-bold rounded-2 flex-grow-1 ' + (mode === 'web'  ? 'active-mode-tab' : 'inactive-mode-tab');

        // Descriptions
        document.getElementById('descMaps').style.display = mode === 'maps' ? '' : 'none';
        document.getElementById('descWeb').style.display  = mode === 'web'  ? '' : 'none';

        // Location field – required for Maps, hidden + not-required for Web
        const locationField = document.getElementById('locationField');
        const locationInput = locationField.querySelector('input[name="location"]');
        if (mode === 'maps') {
            locationField.style.display = '';
            locationInput.required = true;
            locationInput.placeholder = 'Ex: Araraquara SP, São Paulo capital...';
        } else {
            locationField.style.display = 'none';
            locationInput.required = false;
            locationInput.value = '';
        }

        // Term placeholder hint
        const hints = {
            maps: { ph: 'Ex: Restaurantes, Escolas, Farmácias...', hint: 'Categoria / nicho a ser buscado.' },
            web:  { ph: 'Ex: construtoras, e-commerce, clínicas de estética...', hint: 'Nicho ou palavra-chave para busca orgânica no Google.' },
        };
        document.getElementById('termInput').placeholder  = hints[mode].ph;
        document.getElementById('termHint').textContent    = hints[mode].hint;
    }

    function copyPitch(id) {
        const text = document.getElementById('pitchText' + id).innerText.trim();
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Pitch copiado! Cole no WhatsApp.');
            });
        } else {
            // iOS / non-secure fallback
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.cssText = 'position:fixed;opacity:0;';
            document.body.appendChild(ta);
            ta.focus(); ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            alert('Pitch copiado! Cole no WhatsApp.');
        }
    }
</script>
@endsection
