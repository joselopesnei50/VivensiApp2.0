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

                        <div class="mb-0 mt-4">
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

                        <div class="mb-0 mt-4">
                            <label class="form-label fw-600 text-dark">Together AI API Key <span class="badge bg-light text-dark border ms-2">Geração de Imagem</span>
                                @if(!empty($together_ai_configured))
                                    <span class="badge bg-success ms-2">Configurada</span>
                                @endif
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-magic text-muted"></i></span>
                                <input type="password" name="together_ai_api_key" value="" class="form-control border-start-0 ps-0 form-control-lg" placeholder="Cole aqui para definir / atualizar" autocomplete="off">
                            </div>
                            <div class="form-text">Necessário para o módulo de criação de posts para redes sociais (FLUX.1).</div>
                        </div>

                        <div class="mb-0 mt-4">
                            <label class="form-label fw-600 text-dark">Unsplash Access Key <span class="badge bg-light text-dark border ms-2">Imagens Marketing</span>
                                @if(!empty($unsplash_configured))
                                    <span class="badge bg-success ms-2">Configurada</span>
                                @endif
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-camera text-muted"></i></span>
                                <input type="password" name="unsplash_access_key" value="" class="form-control border-start-0 ps-0 form-control-lg" placeholder="Cole aqui para definir / atualizar" autocomplete="off">
                            </div>
                            <div class="form-text">Necessário para buscar imagens automáticas na estratégia de marketing.</div>
                        </div>

                        <div class="mb-0 mt-4">
                            <label class="form-label fw-600 text-dark">Serper.dev API Key <span class="badge bg-light text-dark border ms-2">Lead Search</span>
                                @if(!empty($serper_configured))
                                    <span class="badge bg-success ms-2">Configurada</span>
                                @endif
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search-location text-muted"></i></span>
                                <input type="password" name="serper_api_key" value="" class="form-control border-start-0 ps-0 form-control-lg" placeholder="Cole aqui para definir / atualizar" autocomplete="off">
                            </div>
                            <div class="form-text">Usada para extrair endereços e contatos do Google Maps para prospecção.</div>
                        </div>

                        <div class="mb-0 mt-4">
                            <label class="form-label fw-600 text-dark">Google Maps API Key <span class="badge bg-light text-dark border ms-2">Geocodificação</span>
                                @if(!empty($google_maps_configured))
                                    <span class="badge bg-success ms-2">Configurada</span>
                                @endif
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-map-marked-alt text-muted"></i></span>
                                <input type="password" name="google_maps_api_key" value="" class="form-control border-start-0 ps-0 form-control-lg" placeholder="Cole aqui para definir / atualizar" autocomplete="off">
                            </div>
                            <div class="form-text">Utilizada para transformar endereços em coordenadas geográficas automaticamente.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Payment Gateway (PagSeguro & OpenPix) -->
            <div class="col-12 col-xl-6">
                <div class="card border-0 shadow-sm h-100 overflow-hidden">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-emerald-50 text-emerald rounded-3 p-3 me-3">
                                <i class="fas fa-wallet fa-lg text-success"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Pagamentos & Assinaturas</h5>
                                <p class="text-muted small mb-0">Configurações de Gateways.</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                         <div class="mb-4">
                            <label class="form-label fw-600 text-dark">PagSeguro Ambiente</label>
                            <div class="d-flex gap-3">
                                <div class="form-check card-radio">
                                    <input class="form-check-input" type="radio" name="pagseguro_environment" id="envSandbox" value="sandbox" {{ $pagseguro_env == 'sandbox' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="envSandbox">
                                        <i class="fas fa-flask me-1 text-warning"></i> Sandbox
                                    </label>
                                </div>
                                <div class="form-check card-radio">
                                    <input class="form-check-input" type="radio" name="pagseguro_environment" id="envProd" value="production" {{ $pagseguro_env == 'production' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="envProd">
                                        <i class="fas fa-rocket me-1 text-success"></i> Produção
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-600 text-dark">PagSeguro E-mail</label>
                                <input type="email" name="pagseguro_email" value="{{ $pagseguro_email }}" class="form-control" placeholder="email@loja.com.br">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-600 text-dark">PagSeguro Token
                                    @if(!empty($pagseguro_configured)) <span class="badge bg-success small">OK</span> @endif
                                </label>
                                <input type="password" name="pagseguro_token" class="form-control" placeholder="Token">
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="mb-4">
                            <label class="form-label fw-600 text-dark">OpenPix App ID
                                @if(!empty($openpix_configured))
                                    <span class="badge bg-success ms-2">Configurado</span>
                                @endif
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-key text-muted"></i></span>
                                <input type="password" name="openpix_app_id" value="" class="form-control border-start-0 ps-0" placeholder="Insira o App ID aqui">
                            </div>
                        </div>

                        <div class="alert alert-light border border-info border-opacity-25 d-flex align-items-center mb-0 p-3 rounded-3">
                            <i class="fas fa-info-circle text-info me-3 fs-4"></i>
                            <div class="small text-muted">
                                Webhook OpenPix: <strong>{{ url('/openpix/webhook') }}</strong>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- AbacatePay --}}
                        <div class="d-flex align-items-center mb-3">
                            <span class="fw-bold text-dark me-2">🥑 AbacatePay</span>
                            @if(!empty($abacatepay_configured))
                                <span class="badge bg-success">Configurada</span>
                            @else
                                <span class="badge bg-secondary">Não configurada</span>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-600 text-dark">Ambiente AbacatePay</label>
                            <div class="d-flex gap-3">
                                <div class="form-check card-radio">
                                    <input class="form-check-input" type="radio" name="abacatepay_environment" id="abacateSandbox" value="sandbox" {{ ($abacatepay_env ?? 'sandbox') == 'sandbox' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="abacateSandbox">
                                        <i class="fas fa-flask me-1 text-warning"></i> Dev Mode
                                    </label>
                                </div>
                                <div class="form-check card-radio">
                                    <input class="form-check-input" type="radio" name="abacatepay_environment" id="abacateProd" value="production" {{ ($abacatepay_env ?? '') == 'production' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="abacateProd">
                                        <i class="fas fa-rocket me-1 text-success"></i> Produção
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-600 text-dark">API Key (Bearer Token)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-key text-muted"></i></span>
                                <input type="password" name="abacatepay_api_key" value=""
                                       class="form-control border-start-0 ps-0"
                                       placeholder="{{ !empty($abacatepay_configured) ? 'Configurada (cole para atualizar)' : 'sk_dev_... ou sk_live_...' }}"
                                       autocomplete="off">
                            </div>
                            <div class="form-text">Encontre no painel AbacatePay → Configurações → API Keys.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-600 text-dark">Webhook Secret</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-shield-alt text-muted"></i></span>
                                <input type="password" name="abacatepay_webhook_secret" value=""
                                       class="form-control border-start-0 ps-0"
                                       placeholder="Chave secreta para autenticar o webhook"
                                       autocomplete="off">
                            </div>
                        </div>

                        <div class="alert alert-light border border-success border-opacity-25 d-flex align-items-start p-3 rounded-3 mb-0">
                            <span class="me-2">🥑</span>
                            <div class="small text-muted">
                                Configure o Webhook no painel AbacatePay para:<br>
                                <strong>{{ url('/api/abacatepay/webhook') }}?webhookSecret=<em>SEU_SECRET</em></strong>
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
                            <label class="form-label fw-600 text-dark">Brevo API Key (v3)
                                @if(!empty($brevo_configured))
                                    <span class="badge bg-success ms-2">Configurada</span>
                                @endif
                            </label>
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

                        <div class="mb-3">
                            <label class="form-label fw-600 text-dark">WhatsApp de Suporte</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fab fa-whatsapp text-success"></i></span>
                                <input type="text" name="support_whatsapp" value="{{ $support_whatsapp }}" class="form-control border-start-0 ps-0 form-control-lg" placeholder="16997618695">
                            </div>
                            <div class="form-text">Número para o botão do WhatsApp na página pública.</div>
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

            <!-- 5. WhatsApp Messaging (Z-API) -->
            <div class="col-12 col-xl-6">
                <div class="card border-0 shadow-sm h-100 overflow-hidden">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-success-50 text-success rounded-3 p-3 me-3">
                                <i class="fab fa-whatsapp fa-lg text-success"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Mensageria WhatsApp</h5>
                                <p class="text-muted small mb-0">Integração com Z-API.</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-4">
                            <label class="form-label fw-600 text-dark">Instance ID <span class="badge bg-light text-dark border ms-2">Visível</span>
                                @if(!empty($zapi_configured))
                                    <span class="badge bg-success ms-2">Configurada</span>
                                @endif
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-hashtag text-muted"></i></span>
                                <input type="text" name="zapi_instance_id" value="{{ $zapi_instance }}" class="form-control border-start-0 ps-0 form-control-lg" placeholder="Ex: 3C12345678901234567890AB" autocomplete="off">
                            </div>
                            <div class="form-text">ID da instância do WhatsApp no painel Z-API.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-600 text-dark">Token</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-key text-muted"></i></span>
                                <input type="password" name="zapi_token" value="" class="form-control border-start-0 ps-0 form-control-lg" placeholder="{{ !empty($zapi_configured) ? 'Configurado (cole para atualizar)' : 'Cole o Token aqui' }}" autocomplete="off">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-600 text-dark">Client Token</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-shield-alt text-muted"></i></span>
                                <input type="password" name="zapi_client_token" value="" class="form-control border-start-0 ps-0 form-control-lg" placeholder="{{ !empty($zapi_configured) ? 'Configurado (cole para atualizar)' : 'Cole o Client Token aqui' }}" autocomplete="off">
                            </div>
                        </div>

                        <div class="alert alert-light border border-success border-opacity-25 d-flex align-items-center mb-0 p-3 rounded-3" role="alert">
                            <i class="fab fa-whatsapp text-success me-3 fs-4"></i>
                            <div class="small text-muted">
                                Configure o Webhook no Z-API para: <strong>{{ url('/api/webhooks/zapi') }}</strong>
                            </div>
                        </div>

                        {{-- Meta App Secret (Webhook Signature) --}}
                        <div class="mt-4 pt-4 border-top">
                            <label class="form-label fw-600 text-dark">
                                Meta App Secret
                                <span class="badge ms-2" style="background:#e7f3ff;color:#1877f2;">Cloud API</span>
                                @if(!empty($meta_app_secret_configured))
                                    <span class="badge bg-success ms-1">Configurado</span>
                                @endif
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fab fa-facebook text-muted"></i>
                                </span>
                                <input type="password" name="meta_app_secret" value=""
                                       class="form-control border-start-0 ps-0 form-control-lg"
                                       placeholder="Cole aqui para definir / atualizar" autocomplete="off">
                            </div>
                            <div class="form-text">
                                Segredo do App Meta (Facebook Developers) usado para verificar a assinatura
                                HMAC-SHA256 dos webhooks recebidos. Encontre em:
                                <strong>Facebook Developers → Seu App → Configurações → Básico → App Secret</strong>.
                                <br>URL do webhook Meta: <code>{{ url('/api/whatsapp/webhook') }}</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- 6. Real-Time Notifications (Soketi/Pusher) -->
            <div class="col-12">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-orange-50 text-orange rounded-3 p-3 me-3">
                                <i class="fas fa-bolt fa-lg text-warning"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Notificações em Tempo Real</h5>
                                <p class="text-muted small mb-0">Configurações do Servidor WebSocket (Soketi ou Pusher).</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label fw-600 text-dark">Pusher App ID</label>
                                <input type="text" name="pusher_app_id" value="{{ $pusher_app_id }}" class="form-control form-control-lg" placeholder="Ex: 123456">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-600 text-dark">Pusher Key</label>
                                <input type="text" name="pusher_app_key" value="{{ $pusher_app_key }}" class="form-control form-control-lg" placeholder="Ex: vivensi-key">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-600 text-dark">Pusher Secret
                                    @if(!empty($pusher_configured))
                                        <span class="badge bg-success ms-2">Configurado</span>
                                    @endif
                                </label>
                                <input type="password" name="pusher_app_secret" value="" class="form-control form-control-lg" placeholder="Cole para atualizar">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label fw-600 text-dark">Host (IP/Domínio)</label>
                                <input type="text" name="pusher_host" value="{{ $pusher_host }}" class="form-control form-control-lg" placeholder="127.0.0.1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-600 text-dark">Porta</label>
                                <input type="number" name="pusher_port" value="{{ $pusher_port }}" class="form-control form-control-lg" placeholder="6001">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-600 text-dark">Esquema (Protocolo)</label>
                                <select name="pusher_scheme" class="form-select form-select-lg">
                                    <option value="http" {{ $pusher_scheme == 'http' ? 'selected' : '' }}>HTTP (Local/Sem SSL)</option>
                                    <option value="https" {{ $pusher_scheme == 'https' ? 'selected' : '' }}>HTTPS (Produção com SSL)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning border-0 bg-warning bg-opacity-10 mt-4 d-flex align-items-center mb-0" role="alert">
                            <i class="fas fa-exclamation-triangle text-warning me-3 fs-4"></i>
                            <div class="small">
                                <strong>Atenção:</strong> Alterar estas chaves afetará o funcionamento das notificações instantâneas e do Bruce AI para todos os usuários.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- META SOCIAL (Facebook Pages + Instagram) -->
            <div class="col-12">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                        <div class="d-flex align-items-center">
                            <div class="icon-box rounded-3 p-3 me-3" style="background:#e7f3ff;">
                                <i class="fab fa-facebook fa-lg" style="color:#1877f2;"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Meta Social — Facebook &amp; Instagram</h5>
                                <p class="text-muted small mb-0">Credenciais do Meta App para agendamento e publicação de posts nas páginas dos clientes.</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-600 text-dark">
                                    App ID (Meta Developers)
                                    @if(!empty($meta_social_app_id_configured))
                                        <span class="badge bg-success ms-2">Configurado</span>
                                    @endif
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fab fa-facebook text-muted"></i></span>
                                    <input type="text" name="meta_social_app_id" value="{{ $meta_social_app_id }}"
                                           class="form-control border-start-0 ps-0 form-control-lg"
                                           placeholder="Ex: 123456789012345">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-600 text-dark">
                                    App Secret (Meta Developers)
                                    @if(!empty($meta_social_app_secret_configured))
                                        <span class="badge bg-success ms-2">Configurado</span>
                                    @endif
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-key text-muted"></i></span>
                                    <input type="password" name="meta_social_app_secret" value=""
                                           class="form-control border-start-0 ps-0 form-control-lg"
                                           placeholder="Cole aqui para definir / atualizar" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-light border border-info border-opacity-25 d-flex align-items-start mt-4 mb-0 p-3 rounded-3">
                            <i class="fas fa-info-circle text-info me-3 mt-1 fs-5"></i>
                            <div class="small text-muted">
                                Crie um App em <strong>developers.facebook.com</strong> do tipo <em>Business</em>.
                                Adicione os produtos <strong>Facebook Login</strong> e <strong>Instagram Graph API</strong>.
                                URL de callback OAuth: <code>{{ url('/social/facebook/callback') }}</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ESTATÍSTICAS DA PÁGINA PÚBLICA -->
            <div class="col-12">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                        <div class="d-flex align-items-center">
                            <div class="icon-box bg-indigo-50 text-indigo rounded-3 p-3 me-3">
                                <i class="fas fa-chart-bar fa-lg text-primary"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1">Estatísticas da Página Pública</h5>
                                <p class="text-muted small mb-0">Números exibidos na landing page. Deixe em branco para usar os valores reais do banco de dados.</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-600 text-dark small">Contagem de Organizações</label>
                                <input type="number" name="stat_orgs_count" class="form-control"
                                    value="{{ \App\Models\SystemSetting::getValue('stat_orgs_count', '') }}"
                                    placeholder="Auto (conta do banco)">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-600 text-dark small">Label de Organizações</label>
                                <input type="text" name="stat_orgs_label" class="form-control"
                                    value="{{ \App\Models\SystemSetting::getValue('stat_orgs_label', 'organizações já na plataforma') }}"
                                    placeholder="organizações já na plataforma">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-600 text-dark small">Contagem de Projetos</label>
                                <input type="number" name="stat_projects_count" class="form-control"
                                    value="{{ \App\Models\SystemSetting::getValue('stat_projects_count', '') }}"
                                    placeholder="Auto (conta do banco)">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-600 text-dark small">Label de Projetos</label>
                                <input type="text" name="stat_projects_label" class="form-control"
                                    value="{{ \App\Models\SystemSetting::getValue('stat_projects_label', 'projetos gerenciados') }}"
                                    placeholder="projetos gerenciados">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-600 text-dark small">Contagem de Usuários</label>
                                <input type="number" name="stat_users_count" class="form-control"
                                    value="{{ \App\Models\SystemSetting::getValue('stat_users_count', '') }}"
                                    placeholder="Auto (conta do banco)">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-600 text-dark small">Label de Usuários</label>
                                <input type="text" name="stat_users_label" class="form-control"
                                    value="{{ \App\Models\SystemSetting::getValue('stat_users_label', 'usuários ativos') }}"
                                    placeholder="usuários ativos">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-600 text-dark small">Nota (Rating)</label>
                                <input type="text" name="stat_rating_score" class="form-control"
                                    value="{{ \App\Models\SystemSetting::getValue('stat_rating_score', '5.0') }}"
                                    placeholder="5.0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-600 text-dark small">Label do Rating</label>
                                <input type="text" name="stat_rating_label" class="form-control"
                                    value="{{ \App\Models\SystemSetting::getValue('stat_rating_label', 'avaliação média') }}"
                                    placeholder="avaliação média">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-600 text-dark small">Badge do Hero (texto acima do título)</label>
                                <input type="text" name="stat_hero_badge" class="form-control"
                                    value="{{ \App\Models\SystemSetting::getValue('stat_hero_badge', 'Novo — Em fase de lançamento') }}"
                                    placeholder="Novo — Em fase de lançamento">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-600 text-dark small">Label de Crescimento (subtexto dos cards)</label>
                                <input type="text" name="stat_impact_label" class="form-control"
                                    value="{{ \App\Models\SystemSetting::getValue('stat_impact_label', 'Crescendo a cada dia') }}"
                                    placeholder="Crescendo a cada dia">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AGENDA DE REUNIÕES -->
            <div class="col-12">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="icon-box bg-indigo-50 text-indigo rounded-3 p-3 me-3">
                                    <i class="fas fa-calendar-check fa-lg text-primary"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-1">Agenda de Reuniões</h5>
                                    <p class="text-muted small mb-0">Configure os horários disponíveis para agendamento público em <code>/agendar</code>.</p>
                                </div>
                            </div>
                            <a href="{{ route('booking.index') }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                                <i class="fas fa-external-link-alt me-1"></i> Ver página pública
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">

                            {{-- Meses disponíveis --}}
                            <div class="col-12">
                                <label class="form-label fw-600 text-dark">Meses disponíveis para agendamento</label>
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    @php
                                        $activeMonths = explode(',', $booking_months ?? '1,2,3,4,5,6,7,8,9,10,11,12');
                                        $monthLabels  = [1=>'Jan',2=>'Fev',3=>'Mar',4=>'Abr',5=>'Mai',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Set',10=>'Out',11=>'Nov',12=>'Dez'];
                                    @endphp
                                    @foreach($monthLabels as $num => $label)
                                    <label class="booking-day-chip {{ in_array((string)$num, $activeMonths) ? 'active' : '' }}">
                                        <input type="checkbox" name="booking_months[]" value="{{ $num }}"
                                               {{ in_array((string)$num, $activeMonths) ? 'checked' : '' }}
                                               class="d-none" onchange="this.closest('label').classList.toggle('active', this.checked)">
                                        {{ $label }}
                                    </label>
                                    @endforeach
                                </div>
                                <div class="form-text">Meses em que a agenda estará aberta. Desmarque meses de férias, recesso ou baixa demanda.</div>
                            </div>

                            {{-- Dias disponíveis --}}
                            <div class="col-12">
                                <label class="form-label fw-600 text-dark">Dias da semana disponíveis</label>
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    @php
                                        $activeDays = explode(',', $booking_days);
                                        $dayLabels = [1=>'Segunda',2=>'Terça',3=>'Quarta',4=>'Quinta',5=>'Sexta',6=>'Sábado',0=>'Domingo'];
                                    @endphp
                                    @foreach($dayLabels as $num => $label)
                                    <label class="booking-day-chip {{ in_array((string)$num, $activeDays) ? 'active' : '' }}">
                                        <input type="checkbox" name="booking_days[]" value="{{ $num }}"
                                               {{ in_array((string)$num, $activeDays) ? 'checked' : '' }}
                                               class="d-none" onchange="this.closest('label').classList.toggle('active', this.checked)">
                                        {{ $label }}
                                    </label>
                                    @endforeach
                                </div>
                                <div class="form-text">Dias em que a agenda estará aberta para novos agendamentos.</div>
                            </div>

                            {{-- Horário início/fim --}}
                            <div class="col-md-3">
                                <label class="form-label fw-600 text-dark">Horário de início</label>
                                <input type="time" name="booking_start_time" value="{{ $booking_start_time }}" class="form-control form-control-lg">
                                <div class="form-text">Primeiro slot do dia.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-600 text-dark">Horário de término</label>
                                <input type="time" name="booking_end_time" value="{{ $booking_end_time }}" class="form-control form-control-lg">
                                <div class="form-text">Último slot começa antes deste horário.</div>
                            </div>

                            {{-- Duração do slot --}}
                            <div class="col-md-3">
                                <label class="form-label fw-600 text-dark">Duração de cada reunião</label>
                                <select name="booking_slot_duration" class="form-select form-select-lg">
                                    @foreach([15=>'15 minutos',30=>'30 minutos',45=>'45 minutos',60=>'1 hora'] as $min => $lbl)
                                    <option value="{{ $min }}" {{ $booking_slot_duration == $min ? 'selected' : '' }}>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Antecedência mínima --}}
                            <div class="col-md-3">
                                <label class="form-label fw-600 text-dark">Antecedência mínima</label>
                                <select name="booking_min_advance" class="form-select form-select-lg">
                                    @foreach([0=>'Sem restrição',1=>'1 hora',2=>'2 horas',4=>'4 horas',24=>'1 dia',48=>'2 dias'] as $h => $lbl)
                                    <option value="{{ $h }}" {{ $booking_min_advance == $h ? 'selected' : '' }}>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Mínimo de horas de antecedência para agendar.</div>
                            </div>

                            {{-- Resumo de agendamentos --}}
                            <div class="col-12">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <span class="fw-600 text-dark">Próximas reuniões</span>
                                    <span class="badge bg-primary rounded-pill">{{ $booking_total }} confirmadas</span>
                                </div>
                                @if($booking_upcoming->isEmpty())
                                    <p class="text-muted small mb-0">Nenhum agendamento confirmado no momento.</p>
                                @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0" style="font-size:.85rem;">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nome</th>
                                                <th>E-mail</th>
                                                <th>Data</th>
                                                <th>Horário</th>
                                                <th>Observações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($booking_upcoming as $bk)
                                            <tr>
                                                <td class="fw-600">{{ $bk->name }}</td>
                                                <td class="text-muted">{{ $bk->email }}</td>
                                                <td>{{ \Carbon\Carbon::parse($bk->meeting_date)->locale('pt_BR')->isoFormat('ddd, D MMM') }}</td>
                                                <td><span class="badge bg-light text-dark border">{{ substr($bk->meeting_time,0,5) }}</span></td>
                                                <td class="text-muted" style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $bk->notes ?: '—' }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @endif
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Dev Portal Password -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="icon-box bg-dark text-white rounded-3 me-3">
                        <i class="fas fa-terminal"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">Dev Portal</h5>
                        <p class="text-muted small mb-0">Senha de acesso à página de documentação técnica do sistema.</p>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-600 text-dark">
                        Senha do Dev Portal
                        @if($dev_page_password_configured)
                            <span class="badge bg-success ms-2">Configurada</span>
                        @else
                            <span class="badge bg-warning text-dark ms-2">Não definida</span>
                        @endif
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                        <input type="password" name="dev_page_password" value=""
                               class="form-control border-start-0 ps-0 form-control-lg"
                               placeholder="Mínimo 8 caracteres — deixe em branco para manter" autocomplete="new-password">
                    </div>
                    <div class="form-text">Armazenada como hash bcrypt. Nunca em texto puro. Expira automaticamente em 30 min de sessão.</div>
                </div>
                @if($dev_page_password_configured)
                <a href="{{ route('admin.dev.gate') }}" class="btn btn-outline-dark btn-sm" target="_blank">
                    <i class="fas fa-external-link-alt me-1"></i>Abrir Dev Portal
                </a>
                @endif
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

    /* Booking day chips */
    .booking-day-chip {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 7px 16px;
        border: 1.5px solid #e2e8f0;
        border-radius: 20px;
        font-size: .82rem;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        transition: all .15s;
        user-select: none;
    }
    .booking-day-chip:hover { border-color: #4f46e5; color: #4f46e5; background: #eff6ff; }
    .booking-day-chip.active { background: #4f46e5; border-color: #4f46e5; color: #fff; }

    /* Custom Form Control to remove default borders mostly */
    .input-group-text { border-color: #e2e8f0; }
    .form-control { border-color: #e2e8f0; box-shadow: none !important; }
    .form-control:focus { border-color: #4F46E5 !important; }
    
    @media (max-width: 768px) {
        .fixed-bottom { left: 0 !important; }
    }
</style>
@endsection
