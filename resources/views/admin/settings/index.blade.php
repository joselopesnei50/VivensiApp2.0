@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1" style="font-family: 'Outfit', sans-serif;">Configurações do Sistema</h1>
            <p class="text-muted mb-0">Gerencie as integrações, chaves de API e preferências globais do SaaS.</p>
        </div>
        <div>
           <!-- Optional Header Actions -->
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 rounded-3 shadow-sm">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 rounded-3 shadow-sm">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-warning border-0 rounded-3 shadow-sm">
            <i class="fas fa-triangle-exclamation me-2"></i> {{ $errors->first() }}
        </div>
    @endif

    <!-- Main Content -->
    <form action="{{ url('/admin/settings') }}" method="POST">
        @csrf
        
        <div class="row g-4">
            
            <!-- 1. Artificial Intelligence -->
            <div class="col-12 col-xl-6">
                <div class="card border-0 shadow-sm h-100 overflow-hidden">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-indigo-50 text-indigo rounded-3 p-3 me-3">
                                <i class="fas fa-brain fa-lg text-primary"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Inteligência Artificial</h5>
                                <p class="text-muted small mb-0">Motores de IA para chat e análise.</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-4">
                            <label class="form-label fw-600 text-dark">DeepSeek API Key <span class="badge bg-light text-dark border ms-2">Chatbot</span>
                                @if(!empty($deepseek_configured))
                                    <span class="badge bg-success ms-2">Configurada</span>
                                @endif
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-key text-muted"></i></span>
                                <input type="password" name="deepseek_api_key" value="" class="form-control border-start-0 ps-0 form-control-lg" placeholder="Cole aqui para definir / atualizar" autocomplete="off">
                            </div>
                            <div class="form-text">Motor principal para o assistente virtual Bruce AI.</div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-600 text-dark">Google Gemini API Key <span class="badge bg-light text-dark border ms-2">Visão Computacional</span>
                                @if(!empty($gemini_configured))
                                    <span class="badge bg-success ms-2">Configurada</span>
                                @endif
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fab fa-google text-muted"></i></span>
                                <input type="password" name="gemini_api_key" value="" class="form-control border-start-0 ps-0 form-control-lg" placeholder="Cole aqui para definir / atualizar" autocomplete="off">
                            </div>
                            <div class="form-text">Utilizado para leitura de PDFs e análise de imagens.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Payment Gateway (Asaas) -->
            <div class="col-12 col-xl-6">
                <div class="card border-0 shadow-sm h-100 overflow-hidden">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-emerald-50 text-emerald rounded-3 p-3 me-3">
                                <i class="fas fa-wallet fa-lg text-success"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Pagamentos & Assinaturas</h5>
                                <p class="text-muted small mb-0">Integração com Asaas (v3).</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                         <div class="mb-4">
                            <label class="form-label fw-600 text-dark">Ambiente</label>
                            <div class="d-flex gap-3">
                                <div class="form-check card-radio">
                                    <input class="form-check-input" type="radio" name="asaas_environment" id="envSandbox" value="sandbox" {{ $asaas_env == 'sandbox' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="envSandbox">
                                        <i class="fas fa-flask me-1 text-warning"></i> Sandbox (Testes)
                                    </label>
                                </div>
                                <div class="form-check card-radio">
                                    <input class="form-check-input" type="radio" name="asaas_environment" id="envProd" value="production" {{ $asaas_env == 'production' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="envProd">
                                        <i class="fas fa-rocket me-1 text-success"></i> Produção
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-600 text-dark">API Key</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-key text-muted"></i></span>
                                <input type="password" name="asaas_api_key" value="" class="form-control border-start-0 ps-0 form-control-lg" placeholder="{{ !empty($asaas_configured) ? 'Configurada (cole para atualizar)' : 'Cole aqui para definir' }}" autocomplete="off">
                            </div>
                        </div>

                        <div class="alert alert-light border border-info border-opacity-25 d-flex align-items-center mb-0 p-3 rounded-3" role="alert">
                            <i class="fas fa-info-circle text-info me-3 fs-4"></i>
                            <div class="small text-muted">
                                Configure o Webhook no Asaas para: <strong>{{ url('/api/webhook/asaas') }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Email Service -->
            <div class="col-12 col-xl-6">
                <div class="card border-0 shadow-sm h-100 overflow-hidden">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-blue-50 text-blue rounded-3 p-3 me-3">
                                <i class="fas fa-envelope-open-text fa-lg text-info"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">E-mail Transacional</h5>
                                <p class="text-muted small mb-0">Configuração do Brevo API.</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-4">
                            <label class="form-label fw-600 text-dark">Brevo API Key (v3)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-key text-muted"></i></span>
                                <input type="password" name="brevo_api_key" value="" class="form-control border-start-0 ps-0 form-control-lg" placeholder="{{ !empty($brevo_configured) ? 'Configurada (cole para atualizar)' : 'Cole aqui para definir' }}" autocomplete="off">
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-600 text-dark">Remetente (E-mail)</label>
                                <input type="email" name="email_from" value="{{ $email_from }}" class="form-control form-control-lg" placeholder="noreply@vivensi.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-600 text-dark">Remetente (Nome)</label>
                                <input type="text" name="email_from_name" value="{{ $email_from_name }}" class="form-control form-control-lg" placeholder="Vivensi System">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Marketing & System -->
            <div class="col-12 col-xl-6">
                <div class="card border-0 shadow-sm h-100 overflow-hidden">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-purple-50 text-purple rounded-3 p-3 me-3">
                                <i class="fas fa-photo-video fa-lg text-secondary"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Marketing & Personalização</h5>
                                <p class="text-muted small mb-0">Assets visuais e links.</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-600 text-dark">URL do Vídeo (Embed)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fab fa-youtube text-danger"></i></span>
                                <input type="url" name="home_video_url" value="{{ $home_video_url }}" class="form-control border-start-0 ps-0 form-control-lg" placeholder="https://www.youtube.com/embed/...">
                            </div>
                            <div class="form-text">Vídeo exibido no painel de boas-vindas dos usuários.</div>
                        </div>
                        
                        <div class="mt-4 p-3 bg-light rounded-3 border border-dashed">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="d-block fw-bold text-dark">Modo de Manutenção</span>
                                    <span class="small text-muted">Desativa o acesso de usuários não-admin.</span>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" disabled checked>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>

        <!-- Sticky Footer for Save -->
        <div class="fixed-bottom p-3 bg-white border-top shadow-lg" style="left: var(--sidebar-width, 250px); transition: 0.3s;">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    <i class="fas fa-lock me-1"></i> Suas chaves são armazenadas com criptografia.
                </div>
                <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold rounded-pill shadow-sm hover-scale">
                    <i class="fas fa-save me-2"></i> Salvar Alterações
                </button>
            </div>
        </div>
        <div style="height: 80px;"></div> <!-- Spacer for fixed footer -->

    </form>
</div>

<style>
    .icon-box {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .fw-600 { font-weight: 600; }
    
    .cursor-pointer { cursor: pointer; }

    .card-radio {
        border: 1px solid #e2e8f0;
        padding: 10px 15px;
        border-radius: 8px;
        cursor: pointer;
        transition: 0.2s;
        flex: 1;
    }
    .card-radio:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }
    .card-radio:has(input:checked) {
        background: #eff6ff;
        border-color: #4f46e5;
        color: #4f46e5;
        font-weight: 500;
    }

    .hover-scale { transition: transform 0.2s; }
    .hover-scale:hover { transform: translateY(-2px); }

    /* Custom Form Control to remove default borders mostly */
    .input-group-text { border-color: #e2e8f0; }
    .form-control { border-color: #e2e8f0; box-shadow: none !important; }
    .form-control:focus { border-color: #4F46E5 !important; }
    
    @media (max-width: 768px) {
        .fixed-bottom { left: 0 !important; }
    }
</style>
@endsection
