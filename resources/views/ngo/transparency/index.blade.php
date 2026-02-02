@extends('layouts.app')

@section('content')
<style>
    :root {
        --premium-bg: #f3f4f6;
        --accent: #2563eb;
        --accent-glow: rgba(37, 99, 235, 0.1);
        --text-main: #1e293b;
        --text-muted: #64748b;
        --card-radius: 24px;
        --input-radius: 14px;
    }

    /* Overall Spacing & Grid */
    .dashboard-wrapper {
        padding: 40px;
        background-color: var(--premium-bg);
        min-height: 100vh;
    }

    .main-grid {
        display: grid;
        grid-template-columns: 400px 1fr;
        gap: 32px; /* Gutters */
    }

    /* Hero Section Refined */
    .premium-hero {
        background: linear-gradient(120deg, #020617 0%, #1e3a8a 100%);
        border-radius: var(--card-radius);
        padding: 56px;
        color: white;
        margin-bottom: 40px;
        position: relative;
        box-shadow: 0 25px 50px -12px rgba(30, 58, 138, 0.25);
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .premium-hero::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle at top right, rgba(59, 130, 246, 0.3), transparent 60%);
        pointer-events: none;
    }

    .premium-hero h1 {
        font-weight: 800;
        letter-spacing: -1.5px;
        color: #ffffff;
        text-shadow: 0 4px 10px rgba(0,0,0,0.3);
    }

    /* Card Styling */
    .glass-card {
        background: white;
        border-radius: var(--card-radius);
        border: 1px solid rgba(0,0,0,0.05);
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        padding: 32px;
        margin-bottom: 32px;
    }

    .card-title-premium {
        font-weight: 800;
        font-size: 1.25rem;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 32px; /* Spacing below title */
    }

    /* Form Spacing */
    .form-section {
        margin-bottom: 24px;
    }

    .form-label-premium {
        display: block;
        font-weight: 700;
        font-size: 0.85rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 12px; /* Spacing between label and input */
    }

    .input-premium {
        width: 100%;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: var(--input-radius);
        padding: 14px 20px;
        font-size: 1rem;
        color: var(--text-main);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .input-premium:focus {
        background: white;
        border-color: var(--accent);
        box-shadow: 0 0 0 5px var(--accent-glow);
        outline: none;
    }

    .textarea-premium {
        min-height: 120px;
        resize: vertical;
    }

    /* Custom Checkbox/Switch */
    .switch-container {
        background: #f1f5f9;
        padding: 24px;
        border-radius: 18px;
        margin: 32px 0; /* Breathing room for the switch */
        display: flex;
        align-items: center;
        gap: 16px;
    }

    /* Button Styling */
    .btn-action-premium {
        background: #0f172a;
        color: white;
        font-weight: 800;
        padding: 18px 40px;
        border-radius: 100px;
        border: none;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 0.9rem;
        width: 100%;
        margin-top: 16px; /* Spacing from the element above */
    }

    .btn-action-premium:hover {
        background: var(--accent);
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
    }

    /* Tables */
    .premium-table {
        width: 100%;
        border-spacing: 0;
    }

    .premium-table th {
        text-align: left;
        padding: 16px 24px;
        color: var(--text-muted);
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        border-bottom: 2px solid #f1f5f9;
    }

    .premium-table td {
        padding: 24px;
        border-bottom: 1px solid #f8fafc;
        vertical-align: middle;
    }

    /* Documentation Pills */
    .doc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
        margin-top: 24px;
    }

    .doc-item {
        background: white;
        border: 2px solid #f1f5f9;
        border-radius: 20px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: all 0.3s ease;
    }

    .doc-item:hover {
        border-color: var(--accent);
        transform: translateY(-4px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }

    @media (max-width: 1200px) {
        .main-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="dashboard-wrapper">
    
    <!-- Hero Header -->
    <div class="premium-hero d-flex justify-content-between align-items-center">
        <div class="position-relative z-index-1">
            <span class="badge rounded-pill border border-white border-opacity-25 px-3 py-2 fw-bold mb-3 d-inline-flex align-items-center" style="background: rgba(255, 255, 255, 0.1); color: #93c5fd; letter-spacing: 1px;">
                <i class="fas fa-shield-alt me-2"></i> VIVENSI COMPLIANCE HUB
            </span>
            <h1 class="display-4 fw-bolder text-white mb-2">Painel de Transparência</h1>
            <p class="text-blue-100 lead mb-0 fw-light" style="color: #bfdbfe;">Gestão de governança, diretoria e parcerias públicas com excelência.</p>
        </div>
        <div class="position-relative z-index-1 d-none d-md-block">
            <a href="{{ url('/transparencia/'.$portal->slug) }}" target="_blank" class="btn btn-light btn-lg rounded-pill px-5 py-3 shadow-lg text-primary fw-bold" style="transition: transform 0.2s;">
                <i class="fas fa-external-link-alt me-2"></i> ACESSAR PORTAL
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 p-4 mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle me-3 fa-2x"></i>
                <div>
                    <h6 class="mb-0 fw-bold">Operação realizada!</h6>
                    <small>{{ session('success') }}</small>
                </div>
            </div>
        </div>
    @endif

    <div class="main-grid">
        <!-- Sidebar: Settings -->
        <aside>
            <div class="glass-card h-100">
                <h5 class="card-title-premium">
                    <div style="width: 40px; height: 40px; background: #eff6ff; color: #2563eb; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-cogs"></i>
                    </div>
                    Configurações Gerais
                </h5>
                
                <form action="{{ url('/ngo/transparencia/portal') }}" method="POST">
                    @csrf
                    <div class="form-section">
                        <label class="form-label-premium">Título do Portal</label>
                        <input type="text" name="title" class="input-premium" value="{{ $portal->title }}" placeholder="Ex: Portal de Transparência da ONG">
                    </div>

                    <div class="form-section">
                        <label class="form-label-premium">CNPJ da Instituição</label>
                        <input type="text" name="cnpj" class="input-premium" value="{{ $portal->cnpj }}" placeholder="00.000.000/0001-00">
                    </div>

                    <div class="form-section">
                        <label class="form-label-premium">Missão e Impacto</label>
                        <textarea name="mission" class="input-premium textarea-premium">{{ $portal->mission }}</textarea>
                    </div>

                    <div class="form-section">
                        <label class="form-label-premium">Canal de Ouvidoria (SIC)</label>
                        <input type="email" name="sic_email" class="input-premium" value="{{ $portal->sic_email }}" placeholder="ouvidoria@exemplo.org">
                    </div>

                    <div class="switch-container">
                        <div class="form-check form-switch p-0 m-0">
                            <input class="form-check-input" type="checkbox" name="is_published" value="1" id="pubSwitch" style="width: 50px; height: 25px; cursor: pointer;" {{ $portal->is_published ? 'checked' : '' }}>
                        </div>
                        <div>
                            <label class="fw-bold mb-0 d-block" for="pubSwitch" style="color: var(--text-main);">Visibilidade Pública</label>
                            <small class="text-muted">Tornar o portal acessível na web.</small>
                        </div>
                    </div>

                    <button type="submit" class="btn-action-premium">
                        <i class="fas fa-save me-2"></i> Atualizar Configurações
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Core -->
        <div class="content-stack">
            <!-- Diretoria Section -->
            <div class="glass-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title-premium m-0">
                        <div style="width: 40px; height: 40px; background: #fef2f2; color: #dc2626; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        Membros da Diretoria
                    </h5>
                    <button class="btn btn-dark rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalBoard">
                        <i class="fas fa-plus-circle me-2"></i> Adicionar
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>Membro / Colaborador</th>
                                <th>Cargo</th>
                                <th>Mandato</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($board as $member)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" style="width: 38px; height: 38px; font-size: 0.8rem;">
                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                        </div>
                                        <span class="fw-bold fs-6">{{ $member->name }}</span>
                                    </div>
                                </td>
                                <td><span class="badge rounded-pill px-3 py-2" style="background: #eff6ff; color: #2563eb; font-weight: 700;">{{ $member->position }}</span></td>
                                <td class="text-muted fw-500">{{ $member->tenure_start ? date('Y', strtotime($member->tenure_start)) : 'N/A' }} - {{ $member->tenure_end ? date('Y', strtotime($member->tenure_end)) : 'Ativo' }}</td>
                                <td class="text-end">
                                    <form action="{{ url('/ngo/transparencia/board/'.$member->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm border-0 rounded-circle" style="width: 35px; height: 35px;"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-5 text-muted opacity-50">Nenhum diretor registrado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Parcerias Section -->
            <div class="glass-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title-premium m-0">
                        <div style="width: 40px; height: 40px; background: #ecfdf5; color: #059669; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-handshake"></i>
                        </div>
                        Parcerias Públicas (MROSC)
                    </h5>
                    <button class="btn btn-dark rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalPartnership">
                        <i class="fas fa-plus-circle me-2"></i> Novo Termo
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>Órgão e Projeto</th>
                                <th>Valor do Repasse</th>
                                <th>Status Jurídico</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($partnerships as $p)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $p->project_name }}</div>
                                    <div class="text-muted small">{{ $p->agency_name }}</div>
                                </td>
                                <td><span class="fw-800 text-dark">R$ {{ number_format($p->value, 2, ',', '.') }}</span></td>
                                <td><span class="badge bg-success rounded-pill px-3">{{ strtoupper($p->status) }}</span></td>
                                <td class="text-end">
                                    <form action="{{ url('/ngo/transparencia/partnerships/'.$p->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm border-0 rounded-circle" style="width: 35px; height: 35px;"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-5 text-muted opacity-50">Nenhuma parceria governamental ativa.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Documentos Section -->
            <div class="glass-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title-premium m-0">
                        <div style="width: 40px; height: 40px; background: #fff7ed; color: #ea580c; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-file-archive"></i>
                        </div>
                        Repositório de Compliance
                    </h5>
                    <button class="btn btn-dark rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalDoc">
                        <i class="fas fa-upload me-2"></i> Subir Documento
                    </button>
                </div>

                <div class="doc-grid">
                    @forelse($docs as $doc)
                    <div class="doc-item">
                        <div style="width: 50px; height: 50px; background: #fee2e2; color: #dc2626; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fw-bold text-truncate">{{ $doc->title }}</div>
                            <div class="text-muted small">{{ $doc->year }} • PDF Document</div>
                        </div>
                        <form action="{{ url('/ngo/transparencia/documents/'.$doc->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-link text-danger p-0"><i class="fas fa-times-circle"></i></button>
                        </form>
                    </div>
                    @empty
                    <div class="col-12 text-center py-5 text-muted">Aguardando envio de documentos institucionais.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Premium Modals -->
<div class="modal fade" id="modalDoc" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ url('/ngo/transparencia/documents') }}" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            @csrf
            <div class="modal-header bg-dark text-white p-4">
                <h5 class="fw-bold mb-0">Subir Arquivo Profissional</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4">
                    <label class="form-label-premium">Nome do Documento</label>
                    <input type="text" name="title" class="input-premium" required>
                </div>
                <div class="mb-4">
                    <label class="form-label-premium">Categoria Jurídica</label>
                    <select name="type" class="input-premium">
                        <option value="statute">Estatuto Social</option>
                        <option value="election_minutes">Atas de Eleição</option>
                        <option value="financial_balance">Balanço Patrimonial</option>
                        <option value="activity_report">Relatório de Atividades</option>
                        <option value="tax_certificate">Certidões Federais</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label-premium">Ano / Exercício</label>
                    <input type="number" name="year" class="input-premium" value="{{ date('Y') }}">
                </div>
                <div class="mb-2">
                    <label class="form-label-premium">Arquivo (PDF)</label>
                    <input type="file" name="file" class="form-control border-2 p-3 rounded-4" required>
                </div>
            </div>
            <div class="p-4 bg-light border-top">
                <button type="submit" class="btn btn-primary w-100 rounded-pill py-3 fw-bold">ENVIAR AGORA</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Board -->
<div class="modal fade" id="modalBoard" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ url('/ngo/transparencia/board') }}" method="POST" class="modal-content border-0 shadow-lg rounded-4">
            @csrf
            <div class="modal-header bg-dark text-white p-4">
                <h5 class="fw-bold mb-0">Adicionar à Diretoria</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4"><label class="form-label-premium">Nome Completo</label><input type="text" name="name" class="input-premium" required></div>
                <div class="mb-4"><label class="form-label-premium">Cargo Oficial</label><input type="text" name="position" class="input-premium" required></div>
                <div class="row">
                    <div class="col-6"><label class="form-label-premium">Início Mandato</label><input type="date" name="tenure_start" class="input-premium"></div>
                    <div class="col-6"><label class="form-label-premium">Fim Mandato</label><input type="date" name="tenure_end" class="input-premium"></div>
                </div>
            </div>
            <div class="p-4 bg-light border-top"><button type="submit" class="btn btn-primary w-100 rounded-pill py-3 fw-bold">SALVAR DIRETOR</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalPartnership" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ url('/ngo/transparencia/partnerships') }}" method="POST" class="modal-content border-0 shadow-lg rounded-4">
            @csrf
            <div class="modal-header bg-dark text-white p-4">
                <h5 class="fw-bold mb-0">Nova Parceria Governamental</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4"><label class="form-label-premium">Órgão Concedente</label><input type="text" name="agency_name" class="input-premium" required></div>
                <div class="mb-4"><label class="form-label-premium">Nome do Projeto</label><input type="text" name="project_name" class="input-premium" required></div>
                <div class="mb-4"><label class="form-label-premium">Valor do Repasse (R$)</label><input type="number" step="0.01" name="value" class="input-premium" required></div>
                <div class="mb-0"><label class="form-label-premium">Link Diário Oficial (LAI)</label><input type="url" name="gazette_link" class="input-premium"></div>
            </div>
            <div class="p-4 bg-light border-top"><button type="submit" class="btn btn-primary w-100 rounded-pill py-3 fw-bold">REGISTRAR TERMO</button></div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Move modals to body to prevent z-index/transform issues
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            document.body.appendChild(modal);
        });
    });
</script>
@endsection

<style>
    /* Glassmorphism Backdrop for Modals */
    .modal-backdrop.show {
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        background-color: rgba(15, 23, 42, 0.6) !important;
        opacity: 1 !important;
    }

    .modal-content {
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
</style>

