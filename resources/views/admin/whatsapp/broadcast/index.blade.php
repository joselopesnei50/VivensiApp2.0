@extends('layouts.app')

@section('title', 'WhatsApp - Disparo em Massa')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Disparo em Massa (Broadcast)</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('whatsapp.chat') }}">WhatsApp</a></li>
                    <li class="breadcrumb-item active">Disparo</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <!-- Coluna de Configuração e Importação -->
    <div class="col-md-4">
        <!-- Status da Instância -->
        <div class="card mb-4" style="border-radius: 12px; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);">
            <div class="card-body">
                <h5 class="card-title mb-3 d-flex align-items-center">
                    <i class="fas fa-server text-primary me-2"></i> Status da Instância
                </h5>
                @if(isset($config) && $config->client_token_hash)
                    <div class="alert alert-info py-2 mb-0" style="font-size: 0.9rem;">
                        <i class="fas fa-check-circle me-1"></i> Configurado
                    </div>
                @else
                    <div class="alert alert-warning py-2 mb-0" style="font-size: 0.9rem;">
                        <i class="fas fa-exclamation-triangle me-1"></i> Instância não configurada. <br>
                        <a href="{{ route('whatsapp.settings') }}" style="font-weight: bold; text-decoration: underline;">Configurar Agora</a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Estatísticas da Base de Contatos -->
        <div class="card mb-4 bg-primary text-white" style="border-radius: 12px; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <div class="card-body text-center py-4">
                <i class="fas fa-users fa-3x mb-3 text-white-50"></i>
                <h2 class="text-white fw-bold mb-1">{{ number_format($contactsCount, 0, ',', '.') }}</h2>
                <p class="mb-0 text-white-50">Contatos Salvos no CRM</p>
            </div>
        </div>

        <!-- Importar Contatos -->
        <div class="card mb-4" style="border-radius: 12px; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);">
            <div class="card-body">
                <h5 class="card-title mb-3 d-flex align-items-center">
                    <i class="fas fa-file-csv text-success me-2"></i> Importar Contatos
                </h5>
                <p class="text-muted small mb-3">
                    Suba um arquivo CSV com as colunas <strong>Nome</strong> e <strong>Telefone</strong> (com DDI e DDD, apenas números).
                </p>
                
                <form action="{{ route('whatsapp.broadcast.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <input class="form-control form-control-sm" type="file" name="csv_file" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-success w-100">
                        <i class="fas fa-upload me-1"></i> Importar
                    </button>
                    @error('csv_file')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </form>
                
                <div class="mt-3 p-2 bg-light rounded" style="font-size: 0.8rem;">
                    <strong>Exemplo de CSV:</strong><br>
                    <code class="text-dark">
                        Nome,Telefone<br>
                        João Silva,5511999999999<br>
                        Maria Souza,5521988888888
                    </code>
                </div>
            </div>
        </div>
    </div>

    <!-- Coluna Principal: Composição do Disparo -->
    <div class="col-md-8">
        <div class="card" style="border-radius: 12px; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);">
            <div class="card-header bg-white border-bottom pb-0 pt-4 px-4">
                <h5 class="card-title mb-3 d-flex align-items-center">
                    <i class="fas fa-paper-plane text-primary me-2"></i> Nova Campanha de Mensagens
                </h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('whatsapp.broadcast.send') }}" method="POST">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Público Alvo</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="audience" id="audienceAll" value="all" checked onchange="toggleManualPhones(false)">
                                <label class="form-check-label" for="audienceAll">
                                    Todos os contatos do CRM ({{ $contactsCount }})
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="audience" id="audienceSelected" value="selected" onchange="toggleManualPhones(true)">
                                <label class="form-check-label" for="audienceSelected">
                                    Números Específicos
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4 d-none" id="manualPhonesWrapper">
                        <label class="form-label fw-bold text-muted small">Digite os números separados por vírgula (ex: 5511999999999, 5521988888888)</label>
                        <textarea name="phones" class="form-control" rows="2" placeholder="Ex: 5511999999999, 5521988888888"></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold d-flex justify-content-between align-items-center">
                            Mensagem
                            <span class="badge bg-light text-dark fw-normal border" style="font-size: 0.75rem;">Suporta Spintax: {Olá|Oi} {Tudo bem?|Como vai?}</span>
                        </label>
                        <textarea name="message" class="form-control" rows="8" placeholder="Digite sua mensagem aqui. Para evitar bloqueios, use variáveis Spintax como {Promoção|Oferta Especial}." required></textarea>
                        @error('message')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="alert alert-warning d-flex mb-4" style="border-radius: 8px;">
                        <i class="fas fa-exclamation-triangle flex-shrink-0 me-2 mt-1"></i>
                        <div style="font-size: 0.85rem;">
                            <strong>Aviso Anti-Spam:</strong> Disparos em massa podem resultar no bloqueio permanente do seu número pelo WhatsApp. 
                            Certifique-se de que os contatos autorizaram o recebimento destas mensagens e utilize o formato Spintax (Ex: {textoA|textoB}) para gerar variações na mensagem.
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-bold" onclick="return confirm('Deseja realmente iniciar este disparo em massa? Esta ação não pode ser desfeita e pode demorar alguns minutos.')">
                            <i class="fas fa-rocket me-2"></i> Iniciar Disparo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleManualPhones(show) {
        const wrapper = document.getElementById('manualPhonesWrapper');
        if (show) {
            wrapper.classList.remove('d-none');
        } else {
            wrapper.classList.add('d-none');
        }
    }
</script>
@endpush
@endsection
