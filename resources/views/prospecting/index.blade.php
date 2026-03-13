@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1" style="font-family: 'Outfit', sans-serif;">Prospecção Inteligente</h1>
            <p class="text-muted mb-0">Encontre doadores e clientes no Google Maps com auxílio da Bruce AI.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#searchModal">
                <i class="fas fa-search me-2"></i> Nova Busca
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        </div>
    @endif

    <!-- Stats Row -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-blue-50 text-primary rounded-3 p-3 me-3">
                        <i class="fas fa-users fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Identificados</div>
                        <div class="h4 fw-bold mb-0">{{ $prospects->total() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-emerald-50 text-success rounded-3 p-3 me-3">
                        <i class="fas fa-brain fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Analisados pela IA</div>
                        <div class="h4 fw-bold mb-0">{{ $prospects->where('status', 'analyzed')->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Prospects List -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 border-0">Empresa / Contato</th>
                        <th class="py-3 border-0 text-center">Bruce AI Score</th>
                        <th class="py-3 border-0">Categoria</th>
                        <th class="py-3 border-0">Status</th>
                        <th class="py-3 border-0 text-end px-4">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prospects as $prospect)
                        <tr>
                            <td class="px-4 py-4">
                                <div class="d-flex align-items-start">
                                    <div class="avatar-md bg-light rounded-3 text-primary d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px; font-weight: bold;">
                                        {{ substr($prospect->company_name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark mb-1">{{ $prospect->company_name }}</div>
                                        <div class="text-muted small"><i class="fas fa-map-marker-alt me-1"></i> {{ $prospect->address }}</div>
                                        @if($prospect->phone)
                                            <div class="text-primary small mt-1"><i class="fab fa-whatsapp me-1"></i> {{ $prospect->phone }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                @if($prospect->status === 'analyzed')
                                    <div class="d-inline-flex align-items-center justify-content-center flex-column">
                                        <div class="h5 fw-bold mb-0 @if($prospect->lead_score >= 80) text-success @elseif($prospect->lead_score >= 50) text-warning @else text-danger @endif">
                                            {{ $prospect->lead_score }}%
                                        </div>
                                        <div class="progress mt-1" style="height: 4px; width: 60px;">
                                            <div class="progress-bar @if($prospect->lead_score >= 80) bg-success @elseif($prospect->lead_score >= 50) bg-warning @else bg-danger @endif" style="width: {{ $prospect->lead_score }}%"></div>
                                        </div>
                                    </div>
                                @else
                                    <span class="badge bg-light text-muted fw-normal p-2 border">Pendente</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark fw-normal p-2 border">{{ $prospect->category }}</span>
                            </td>
                            <td>
                                @if($prospect->status === 'raw')
                                    <span class="badge bg-soft-warning text-warning p-2 rounded-3"><i class="fas fa-clock me-1"></i> Raw</span>
                                @elseif($prospect->status === 'analyzed')
                                    <span class="badge bg-soft-success text-success p-2 rounded-3"><i class="fas fa-check-circle me-1"></i> Analisado</span>
                                @else
                                    <span class="badge bg-soft-primary text-primary p-2 rounded-3">Contatado</span>
                                @endif
                            </td>
                            <td class="text-end px-4">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm rounded-circle" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-3">
                                        <li><a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#detailModal{{ $prospect->id }}"><i class="fas fa-eye me-2 text-primary"></i> Ver Detalhes / Pitch</a></li>
                                        <li>
                                            <form action="{{ route('prospecting.analyze', $prospect->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item py-2"><i class="fas fa-wand-magic-sparkles me-2 text-warning"></i> Re-analisar IA</button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('prospecting.destroy', $prospect->id) }}" method="POST" onsubmit="return confirm('Excluir este potencial cliente?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item py-2 text-danger"><i class="fas fa-trash-alt me-2"></i> Remover</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>

                        <!-- Modal Detalhes -->
                        <div class="modal fade" id="detailModal{{ $prospect->id }}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content border-0 rounded-4 overflow-hidden">
                                    <div class="modal-header bg-dark text-white p-4">
                                        <h5 class="modal-title fw-bold">Análise da Bruce AI</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <div class="row">
                                            <div class="col-md-4 border-end">
                                                <div class="text-center mb-4">
                                                    <div class="h1 fw-bold mb-0 @if($prospect->lead_score >= 80) text-success @elseif($prospect->lead_score >= 50) text-warning @else text-danger @endif">
                                                        {{ $prospect->lead_score }}%
                                                    </div>
                                                    <div class="text-muted small uppercase fw-bold">Fit do Cliente</div>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="small fw-bold text-dark mb-1">Contato</div>
                                                    <p class="small text-muted mb-0">{{ $prospect->phone ?? 'Não disponível' }}</p>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="small fw-bold text-dark mb-1">Localização</div>
                                                    <p class="small text-muted mb-0">{{ $prospect->address }}</p>
                                                </div>
                                            </div>
                                            <div class="col-md-8 px-4">
                                                <div class="mb-4">
                                                    <h6 class="fw-bold text-dark"><i class="fas fa-stethoscope text-primary me-2"></i> Dor Detectada pela IA</h6>
                                                    <div class="p-3 bg-light rounded-3 italic">
                                                        "{{ $prospect->ai_analysis ?? 'Aguardando processamento...' }}"
                                                    </div>
                                                </div>
                                                <div>
                                                    <h6 class="fw-bold text-dark"><i class="fab fa-whatsapp text-success me-2"></i> Pitch Personalizado de Abordagem</h6>
                                                    <div class="p-3 border rounded-3 bg-white position-relative">
                                                        <p id="pitchText{{ $prospect->id }}" class="mb-0 small line-height-lg">
                                                            {{ $prospect->personalized_pitch ?? 'O Bruce AI está gerando o seu pitch...' }}
                                                        </p>
                                                        @if($prospect->personalized_pitch)
                                                            <button class="btn btn-sm btn-light position-absolute top-0 end-0 m-2" onclick="copyPitch({{ $prospect->id }})">
                                                                <i class="fas fa-copy"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                    @if($prospect->phone && $prospect->personalized_pitch)
                                                        @php
                                                            $waText = urlencode($prospect->personalized_pitch);
                                                            $waPhone = preg_replace('/\D/', '', $prospect->phone);
                                                        @endphp
                                                        <a href="https://wa.me/55{{ $waPhone }}?text={{ $waText }}" target="_blank" class="btn btn-success w-100 mt-3 rounded-3 fw-bold py-2">
                                                            <i class="fab fa-whatsapp me-2"></i> Abrir WhatsApp para Conversão
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="5" class="py-5 text-center text-muted">
                                <img src="{{ asset('img/empty-leads.svg') }}" style="width: 150px; opacity: 0.5;" alt="Sem leads">
                                <p class="mt-3">Nenhum potencial cliente prospectado ainda. Comece sua busca agora!</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0 py-3">
            {{ $prospects->links() }}
        </div>
    </div>
</div>

<!-- Modal de Busca -->
<div class="modal fade" id="searchModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Nova Prospecção Inteligente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('prospecting.search') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <p class="small text-muted mb-4">A Bruce AI buscará no Google Maps e analisará os melhores leads para você.</p>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">O que você procura? (Nicho)</label>
                        <input type="text" name="term" class="form-control form-control-lg rounded-3" placeholder="Ex: Restaurantes, Escolas, Marcenarias..." required>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-bold">Onde? (Localização)</label>
                        <input type="text" name="location" class="form-control form-control-lg rounded-3" placeholder="Ex: Araraquara, SP..." required>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-toggle="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                        <i class="fas fa-wand-magic-sparkles me-2"></i> Iniciar Prospecção
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .bg-soft-success { background-color: #ecfdf5; }
    .bg-soft-warning { background-color: #fffbeb; }
    .bg-soft-primary { background-color: #eff6ff; }
    .bg-indigo-50 { background-color: #eef2ff; }
    .line-height-lg { line-height: 1.7; }
    .italic { font-style: italic; }
</style>

<script>
    function copyPitch(id) {
        let text = document.getElementById('pitchText' + id).innerText;
        navigator.clipboard.writeText(text).then(() => {
            alert('Pitch copiado para a área de transferência!');
        });
    }
</script>
@endsection
