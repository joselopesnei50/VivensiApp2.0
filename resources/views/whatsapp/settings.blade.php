@extends('layouts.app')

@section('content')
<div class="d-flex align-items-start gap-3 mb-4 flex-wrap">
    <div class="flex-1">
        <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;margin-bottom:4px;">
            WhatsApp / Configurações
        </div>
        <h2 class="fw-800 mb-0" style="font-size:1.6rem;color:#0f172a;line-height:1.2;">Treinamento da IA & WhatsApp Oficial</h2>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">Infraestrutura direta da Meta — máxima segurança e blindagem contra bloqueios.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap mt-1">
        <a href="{{ route('whatsapp.automations.index') }}" class="btn btn-sm btn-outline-secondary rounded-3" style="font-size:.8rem;">
            <i class="fas fa-robot me-1"></i> Automações
        </a>
        <a href="{{ route('whatsapp.templates') }}" class="btn btn-sm btn-outline-secondary rounded-3" style="font-size:.8rem;">
            <i class="fas fa-layer-group me-1"></i> Templates
        </a>
        <a href="{{ route('whatsapp.broadcast.index') }}" class="btn btn-sm btn-outline-secondary rounded-3" style="font-size:.8rem;">
            <i class="fas fa-rocket me-1"></i> Disparo
        </a>
        <a href="{{ route('whatsapp.chat') }}" class="btn btn-sm rounded-3 fw-bold" style="font-size:.8rem;background:#25d366;color:#fff;">
            <i class="fab fa-whatsapp me-1"></i> Chat
        </a>
    </div>
</div>

<form action="{{ url('/whatsapp/settings') }}" method="POST" id="mainSettingsForm">
    @csrf
    
    <!-- Hidden fields for Meta OAuth Credentials -->
    <input type="hidden" name="meta_waba_id" id="input_waba_id" value="{{ $contextModel->meta_waba_id ?? '' }}">
    <input type="hidden" name="meta_phone_number_id" id="input_phone_id" value="{{ $contextModel->meta_phone_number_id ?? '' }}">
    <input type="hidden" name="meta_access_token" id="input_access_token" value="{{ $contextModel->meta_access_token ?? '' }}">
    
    <!-- Hidden fields for Evolution API Credentials -->
    <input type="hidden" name="evolution_instance_name" id="input_evolution_instance_name" value="{{ $contextModel->evolution_instance_name ?? '' }}">
    <input type="hidden" name="evolution_instance_token" id="input_evolution_instance_token" value="{{ $contextModel->evolution_instance_token ?? '' }}">

    <div class="row">
        <div class="col-md-7">
            <!-- AI Training Section -->
            @php $ts = $config->ai_training_structured ?? []; @endphp
            <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-top: 3px solid #a855f7 !important;">
                <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span style="width:34px;height:34px;background:rgba(168,85,247,.1);border-radius:10px;display:inline-flex;align-items:center;justify-content:center;">
                        <i class="fas fa-brain" style="color:#a855f7;font-size:.9rem;"></i>
                    </span>
                    <div>
                        <div class="fw-bold" style="font-size:.95rem;color:#0f172a;line-height:1.2;">Treinamento Bruce AI</div>
                        <div style="font-size:.72rem;color:#94a3b8;">O sistema monta o prompt automaticamente com base nas seções abaixo.</div>
                    </div>
                </div>

                <!-- Seção 1: Identidade do Bot -->
                <div class="training-section">
                    <div class="training-section-header" onclick="toggleSection('identity')">
                        <span><i class="fas fa-robot me-2" style="color: #a855f7;"></i> Identidade do Assistente</span>
                        <i class="fas fa-chevron-down training-chevron" id="chevron-identity"></i>
                    </div>
                    <div id="section-identity" class="training-section-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="training-label">Nome do Assistente</label>
                                <input type="text" name="bot_name" class="form-control-vivensi" placeholder="Ex: Bruce" value="{{ $ts['bot_name'] ?? '' }}" maxlength="100">
                            </div>
                            <div class="col-sm-6">
                                <label class="training-label">Tom de Voz</label>
                                <select name="bot_tone" class="form-control-vivensi">
                                    @foreach(['amigável' => 'Amigável', 'formal' => 'Formal', 'empático' => 'Empático', 'animado' => 'Animado'] as $val => $label)
                                        <option value="{{ $val }}" {{ ($ts['bot_tone'] ?? 'amigável') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção 2: Organização -->
                <div class="training-section">
                    <div class="training-section-header" onclick="toggleSection('org')">
                        <span><i class="fas fa-building me-2" style="color: var(--ds-brand);"></i> Organização</span>
                        <i class="fas fa-chevron-down training-chevron" id="chevron-org"></i>
                    </div>
                    <div id="section-org" class="training-section-body">
                        <div class="mb-3">
                            <label class="training-label">Nome da Organização</label>
                            <input type="text" name="org_name" class="form-control-vivensi" placeholder="Ex: ONG Esperança" value="{{ $ts['org_name'] ?? '' }}" maxlength="255">
                        </div>
                        <div>
                            <label class="training-label">Missão / Descrição</label>
                            <textarea name="org_mission" rows="3" class="form-control-vivensi" placeholder="Ex: Apoiamos famílias em situação de vulnerabilidade social..." maxlength="1000">{{ $ts['org_mission'] ?? '' }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Seção 3: Serviços -->
                <div class="training-section">
                    <div class="training-section-header" onclick="toggleSection('services')">
                        <span><i class="fas fa-concierge-bell me-2" style="color: #10b981;"></i> Serviços Oferecidos</span>
                        <i class="fas fa-chevron-down training-chevron" id="chevron-services"></i>
                    </div>
                    <div id="section-services" class="training-section-body">
                        <label class="training-label">Descreva os serviços disponíveis</label>
                        <textarea name="services" rows="4" class="form-control-vivensi" placeholder="Ex: Distribuição de cestas básicas (toda terça), psicólogo voluntário (agendamento), oficinas de costura..." maxlength="2000">{{ $ts['services'] ?? '' }}</textarea>
                    </div>
                </div>

                <!-- Seção 4: Manual de Atendimento -->
                <div class="training-section">
                    <div class="training-section-header" onclick="toggleSection('manual')">
                        <span><i class="fas fa-book-open me-2" style="color:#7c3aed;"></i> Manual de Atendimento & Base de Conhecimento</span>
                        <i class="fas fa-chevron-down training-chevron" id="chevron-manual"></i>
                    </div>
                    <div id="section-manual" class="training-section-body">
                        <p style="font-size:0.8rem;color:#64748b;margin-bottom:10px;">
                            Cole aqui textos densos sobre o negócio: procedimentos internos, scripts de atendimento, políticas, contexto institucional, regras de captação, instruções específicas, etc. Quanto mais detalhado, mais preciso o bot.
                        </p>
                        <textarea name="training_manual" rows="10" class="form-control-vivensi"
                            style="font-size:0.82rem;line-height:1.6;font-family:monospace;"
                            placeholder="Exemplo:
— Quando o cliente perguntar sobre doação, sempre enviar o link da campanha ativa antes de qualquer outra informação.
— Não confirmar datas de eventos sem consultar o calendário oficial (sempre dizer 'vou verificar e retorno').
— Se perguntarem sobre boletos vencidos, orientar a acessar o portal financeiro em financeiro.vivensi.app.br.
— Nosso processo de cadastro tem 3 etapas: triagem social, entrevista e aprovação pelo coordenador.
— Parceiros institucionais devem ser direcionados ao e-mail parcerias@suaong.org.br..."
                            maxlength="8000">{{ $ts['training_manual'] ?? '' }}</textarea>
                        <div class="d-flex justify-content-end mt-1">
                            <small class="text-muted" id="manual-char-count">0 / 8000 caracteres</small>
                        </div>
                    </div>
                </div>

                <!-- Seção 5: Horários -->
                <div class="training-section">
                    <div class="training-section-header" onclick="toggleSection('hours')">
                        <span><i class="fas fa-clock me-2" style="color: #f59e0b;"></i> Horários de Atendimento</span>
                        <i class="fas fa-chevron-down training-chevron" id="chevron-hours"></i>
                    </div>
                    <div id="section-hours" class="training-section-body">
                        <label class="training-label">Horários e dias</label>
                        <textarea name="working_hours" rows="3" class="form-control-vivensi" placeholder="Ex: Seg–Sex das 8h às 17h. Sáb das 8h às 12h. Feriados: fechado." maxlength="500">{{ $ts['working_hours'] ?? '' }}</textarea>
                    </div>
                </div>

                <!-- Seção 5: Contato -->
                <div class="training-section">
                    <div class="training-section-header" onclick="toggleSection('contact')">
                        <span><i class="fas fa-address-card me-2" style="color: #0ea5e9;"></i> Informações de Contato</span>
                        <i class="fas fa-chevron-down training-chevron" id="chevron-contact"></i>
                    </div>
                    <div id="section-contact" class="training-section-body">
                        <label class="training-label">Endereço, telefone, site, e-mail</label>
                        <textarea name="contact_info" rows="3" class="form-control-vivensi" placeholder="Ex: Rua das Flores 123, Bairro Luz, SP. Tel: (11) 99999-0000. Site: www.ongexemplo.org.br" maxlength="500">{{ $ts['contact_info'] ?? '' }}</textarea>
                    </div>
                </div>

                <!-- Seção 6: Perguntas Frequentes -->
                <div class="training-section" style="margin-bottom: 0;">
                    <div class="training-section-header" onclick="toggleSection('faq')">
                        <span><i class="fas fa-question-circle me-2" style="color: #ef4444;"></i> Perguntas Frequentes (FAQ)</span>
                        <i class="fas fa-chevron-down training-chevron" id="chevron-faq"></i>
                    </div>
                    <div id="section-faq" class="training-section-body">
                        <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 12px;">Adicione perguntas e respostas que o Bruce deve saber responder.</p>
                        <div id="faq-list">
                            @php $faqs = $ts['faq'] ?? [['question'=>'','answer'=>'']]; @endphp
                            @foreach($faqs as $i => $faq)
                            <div class="faq-row" data-index="{{ $i }}">
                                <div class="faq-index">{{ $i + 1 }}</div>
                                <div class="faq-fields">
                                    <input type="text" name="faq[{{ $i }}][question]" class="form-control-vivensi mb-2" placeholder="Pergunta: Ex: Como me cadastrar?" value="{{ $faq['question'] ?? '' }}">
                                    <textarea name="faq[{{ $i }}][answer]" rows="2" class="form-control-vivensi" placeholder="Resposta: Ex: Compareça à sede com RG e comprovante de renda.">{{ $faq['answer'] ?? '' }}</textarea>
                                </div>
                                <button type="button" class="faq-remove" onclick="removeFaq(this)" title="Remover">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" onclick="addFaq()" class="btn btn-sm btn-outline-secondary mt-2" style="border-radius: 8px; font-size: 0.8rem; border-style: dashed;">
                            <i class="fas fa-plus me-1"></i> Adicionar Pergunta
                        </button>
                    </div>
                </div>

                <div class="settings-robot-config mt-4">
                    <input type="hidden" name="ai_provider" value="deepseek">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm-7">
                            <label class="training-label mb-1">Motor Cognitivo</label>
                            <div class="p-2 rounded-3 d-flex align-items-center gap-2" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                                <i class="fas fa-brain" style="color:#16a34a;font-size:.9rem;"></i>
                                <span style="font-size:.85rem;font-weight:700;color:#166534;">DeepSeek (V4 Flash)</span>
                            </div>
                        </div>
                        <div class="col-sm-5">
                            <label class="training-label mb-1">Robô Ativo</label>
                            <div class="d-flex align-items-center gap-2 p-2 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="ai_enabled" value="1" id="aiEnabled" {{ $config->ai_enabled ? 'checked' : '' }}>
                                </div>
                                <label for="aiEnabled" class="mb-0 fw-bold" style="font-size:.83rem;color:#334155;cursor:pointer;">
                                    {{ $config->ai_enabled ? 'Habilitado' : 'Desabilitado' }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                </div>{{-- card-body --}}
            </div>{{-- card --}}

            <!-- Send Policies -->
            <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-top: 3px solid #f59e0b !important;">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3" style="font-size:.95rem;color:#334155;">
                        <i class="fas fa-gavel me-2" style="color:#f59e0b;"></i> Políticas de Envio Meta
                    </h5>

                    <div class="d-flex align-items-start gap-3 py-3 border-bottom">
                        <div class="form-check form-switch mb-0 mt-1">
                            <input class="form-check-input" type="checkbox" name="outbound_enabled" value="1" id="outboundEnabled" {{ ($config->outbound_enabled ?? false) ? 'checked' : '' }}>
                        </div>
                        <label for="outboundEnabled" class="mb-0" style="cursor:pointer;">
                            <div class="fw-bold" style="font-size:.85rem;color:#334155;">Habilitar Envio de Mensagens</div>
                            <div class="text-muted" style="font-size:.75rem;line-height:1.5;margin-top:2px;">Desativar bloqueia todos os disparos do sistema.</div>
                        </label>
                    </div>

                    <div class="d-flex align-items-start gap-3 py-3 border-bottom">
                        <div class="form-check form-switch mb-0 mt-1">
                            <input class="form-check-input" type="checkbox" name="enforce_24h_window" value="1" id="enforce24h" {{ ($config->enforce_24h_window ?? true) ? 'checked' : '' }}>
                        </div>
                        <label for="enforce24h" class="mb-0" style="cursor:pointer;">
                            <div class="fw-bold" style="font-size:.85rem;color:#334155;">Respeitar Janela de 24h (Custos reduzidos)</div>
                            <div class="text-muted" style="font-size:.75rem;line-height:1.5;margin-top:2px;">A IA só envia textos livres enquanto o WhatsApp permite gratuitamente.</div>
                        </label>
                    </div>

                    <div class="d-flex align-items-start gap-3 py-3">
                        <div class="form-check form-switch mb-0 mt-1">
                            <input class="form-check-input" type="checkbox" name="allow_templates_outside_window" value="1" id="allowTemplates" {{ ($config->allow_templates_outside_window ?? true) ? 'checked' : '' }}>
                        </div>
                        <label for="allowTemplates" class="mb-0" style="cursor:pointer;">
                            <div class="fw-bold" style="font-size:.85rem;color:#334155;">Permitir Templates pagos fora da janela</div>
                            <div class="text-muted" style="font-size:.75rem;line-height:1.5;margin-top:2px;">Campanhas usarão modelos Meta aprovados. O cartão no Facebook Business será cobrado.</div>
                        </label>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-md-5">
            <!-- Meta API Official Connection Panel -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span style="width:34px;height:34px;background:#eff6ff;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;">
                                <i class="fab fa-facebook" style="color:#1877f2;font-size:1rem;"></i>
                            </span>
                            <div>
                                <div class="fw-bold" style="font-size:.9rem;color:#0f172a;line-height:1.2;">WhatsApp Business API</div>
                                <div style="font-size:.7rem;color:#94a3b8;">Meta Cloud API — sem celular ligado</div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#metaHelpModal"
                                style="background:#eff6ff;color:#1877f2;border:none;border-radius:8px;font-size:.72rem;font-weight:700;padding:5px 10px;">
                            <i class="fas fa-question-circle me-1"></i> Guia
                        </button>
                    </div>

                    <div id="meta_status_container">
                        @if(!empty($contextModel->meta_waba_id) && !empty($contextModel->meta_phone_number_id))
                            <div class="rounded-3 p-3 mb-3" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="fas fa-check-circle" style="color:#22c55e;font-size:1rem;"></i>
                                    <span class="fw-bold" style="color:#166534;font-size:.85rem;">Conectado e Aprovado</span>
                                </div>
                                <div style="font-size:.72rem;color:#166534;line-height:1.8;">
                                    <div><span class="fw-bold">WABA ID:</span> <code style="background:rgba(0,0,0,.04);padding:1px 6px;border-radius:4px;">{{ $contextModel->meta_waba_id }}</code></div>
                                    <div><span class="fw-bold">Phone ID:</span> <code style="background:rgba(0,0,0,.04);padding:1px 6px;border-radius:4px;">{{ $contextModel->meta_phone_number_id }}</code></div>
                                </div>
                            </div>
                            <button type="button" onclick="disconnectMeta()" class="btn btn-outline-danger w-100 fw-bold rounded-3" style="font-size:.83rem;padding:9px;">
                                <i class="fas fa-unlink me-2"></i> Desconectar e Mudar Credenciais
                            </button>
                        @else
                            <div class="rounded-3 p-3 mb-3" style="background:#f8fafc;border:1px dashed #cbd5e1;">
                                <div class="mb-2">
                                    <label class="training-label">WABA ID</label>
                                    <input type="text" id="manual_waba_id" class="form-control-vivensi" placeholder="Ex: 109283746554321">
                                </div>
                                <div class="mb-2">
                                    <label class="training-label">Phone Number ID</label>
                                    <input type="text" id="manual_phone_id" class="form-control-vivensi" placeholder="Ex: 223344556677889">
                                </div>
                                <div class="mb-3">
                                    <label class="training-label">Access Token Permanente</label>
                                    <input type="password" id="manual_access_token" class="form-control-vivensi" placeholder="EAABxb...">
                                </div>
                                <button type="button" onclick="saveManualMeta()" class="btn w-100 fw-bold rounded-3" style="background:#1877f2;color:#fff;padding:11px;font-size:.85rem;">
                                    <i class="fab fa-facebook-f me-2"></i> Salvar e Conectar
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Evolution API Connection Panel -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span style="width:34px;height:34px;background:#ecfdf5;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;">
                            <i class="fas fa-qrcode" style="color:#10b981;font-size:.9rem;"></i>
                        </span>
                        <div>
                            <div class="fw-bold" style="font-size:.9rem;color:#0f172a;line-height:1.2;">Aparelhos Conectados</div>
                            <div style="font-size:.7rem;color:#94a3b8;">Conexões Evolution para disparo nativo</div>
                        </div>
                    </div>

                    <div id="instances_list_container">
                        @if($instances->isEmpty())
                            <div class="rounded-3 p-4 text-center" style="background:#f8fafc;border:1px dashed #cbd5e1;">
                                <div style="width:44px;height:44px;background:rgba(16,185,129,.1);border-radius:12px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
                                    <i class="fab fa-whatsapp" style="color:#10b981;font-size:1.2rem;"></i>
                                </div>
                                <p class="fw-bold mb-1" style="color:#334155;font-size:.88rem;">Nenhum aparelho conectado</p>
                                <p class="text-muted mb-3" style="font-size:.78rem;">Escaneie o QR Code para habilitar disparos assíncronos.</p>
                                <button type="button" onclick="openNewInstanceModal()" class="btn w-100 fw-bold rounded-3" style="background:#10b981;color:#fff;padding:11px;font-size:.85rem;">
                                    <i class="fas fa-plus me-2"></i> Conectar Novo Aparelho
                                </button>
                            </div>
                        @else
                            @foreach($instances as $instance)
                            @php
                                $percent = $instance->daily_limit > 0
                                    ? min(100, intval(($instance->messages_sent_today / $instance->daily_limit) * 100)) : 0;
                            @endphp
                            <div class="rounded-3 p-3 mb-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span style="width:32px;height:32px;background:#ecfdf5;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;">
                                            <i class="fab fa-whatsapp" style="color:#10b981;font-size:.9rem;"></i>
                                        </span>
                                        <div>
                                            <div class="fw-bold" style="font-size:.85rem;color:#334155;">{{ $instance->instance_name }}</div>
                                            <div style="font-size:.7rem;color:#94a3b8;font-family:monospace;">{{ $instance->phone_number ?: 'Aguardando...' }}</div>
                                        </div>
                                    </div>
                                    @if($instance->status === 'open')
                                        <span class="badge rounded-pill" style="background:#dcfce7;color:#166534;font-size:.65rem;border:1px solid #bbf7d0;">CONECTADO</span>
                                    @elseif($instance->status === 'connecting')
                                        <span class="badge rounded-pill" style="background:#fef3c7;color:#92400e;font-size:.65rem;border:1px solid #fde68a;">
                                            <i class="fas fa-spinner fa-spin me-1"></i>CONECTANDO
                                        </span>
                                    @else
                                        <span class="badge rounded-pill" style="background:#fee2e2;color:#b91c1c;font-size:.65rem;border:1px solid #fecaca;">DESCONECTADO</span>
                                    @endif
                                </div>

                                <div class="mb-2 p-2 rounded-2" style="background:#fff;border:1px solid #f1f5f9;">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small class="fw-bold" style="color:#64748b;font-size:.68rem;">Aquecimento diário</small>
                                        <small class="fw-bold" style="color:{{ $percent > 80 ? '#ef4444' : '#10b981' }};font-size:.68rem;">{{ $instance->messages_sent_today }} / {{ $instance->daily_limit }}</small>
                                    </div>
                                    <div class="progress" style="height:5px;background:#e2e8f0;border-radius:99px;">
                                        <div class="progress-bar" style="background:{{ $percent > 80 ? '#ef4444' : ($percent > 50 ? '#f59e0b' : '#10b981') }};width:{{ $percent }}%;border-radius:99px;"></div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    @if($instance->status !== 'open')
                                        <button type="button" onclick="checkStatus('{{ $instance->id }}')" class="btn btn-sm btn-outline-primary fw-bold rounded-3" style="flex:1;font-size:.78rem;">
                                            <i class="fas fa-qrcode me-1"></i> Scan QR
                                        </button>
                                    @else
                                        <a href="{{ route('whatsapp.broadcast.index') }}" class="btn btn-sm btn-outline-success fw-bold rounded-3" style="flex:1;font-size:.78rem;">
                                            <i class="fas fa-paper-plane me-1"></i> Disparar
                                        </a>
                                    @endif
                                    <button type="button" onclick="confirmDelete('{{ $instance->id }}')" class="btn btn-sm btn-outline-danger rounded-3" style="font-size:.78rem;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            @endforeach

                            <button type="button" onclick="openNewInstanceModal()" class="btn btn-outline-secondary w-100 fw-bold rounded-3" style="font-size:.82rem;padding:9px;border-style:dashed;">
                                <i class="fas fa-plus me-1"></i> Adicionar Outro Aparelho
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <button type="submit" class="btn btn-primary w-100 fw-bold rounded-3 d-flex align-items-center justify-content-center gap-2" style="padding:14px;font-size:.95rem;">
                <i class="fas fa-save"></i> Salvar Configurações
            </button>
        </div>
    </div>
</form>

<!-- Modal de Ajuda Meta -->
<div class="modal fade" id="metaHelpModal" role="dialog" aria-modal="true" aria-labelledby="metaHelpModalLabel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: #1877f2; color: white; padding: 25px; border: none;">
                <h5 class="modal-title fw-800" id="metaHelpModalLabel"><i class="fab fa-whatsapp me-2"></i> Passo a Passo: Conectar WhatsApp Oficial</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 30px; background: #fff;">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex mb-4">
                            <div style="background: #eff6ff; color: #1877f2; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; margin-right: 15px; flex-shrink: 0;">1</div>
                            <div>
                                <h6 class="fw-700 mb-1">Criar App na Meta</h6>
                                <p class="small text-muted">Acesse <a href="https://developers.facebook.com" target="_blank">Meta for Developers</a>, crie um App do tipo <b>Empresa</b> e adicione o produto <b>WhatsApp</b>.</p>
                            </div>
                        </div>
                        <div class="d-flex">
                            <div style="background: #eff6ff; color: #1877f2; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; margin-right: 15px; flex-shrink: 0;">2</div>
                            <div>
                                <h6 class="fw-700 mb-1">Pegar IDs (WABA e Phone)</h6>
                                <p class="small text-muted">No menu <b>WhatsApp > Configuração de API</b>, copie o "ID da conta do WhatsApp Business" e o "ID do número de telefone".</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex mb-4">
                            <div style="background: #fef2f2; color: #dc2626; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; margin-right: 15px; flex-shrink: 0;">3</div>
                            <div>
                                <h6 class="fw-700 mb-1">Token Permanente (Crítico)</h6>
                                <p class="small text-muted">Não use o token temporário! Vá em <b>Configurações de Negócio > Usuários do Sistema</b>, crie um usuário técnico e gere um token com as permissões <b>whatsapp_business_management</b> e <b>messaging</b>.</p>
                            </div>
                        </div>
                        <div class="d-flex">
                            <div style="background: #ecfdf5; color: #059669; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; margin-right: 15px; flex-shrink: 0;">4</div>
                            <div>
                                <h6 class="fw-700 mb-1">Configurar Webhook</h6>
                                <p class="small text-muted">Na Meta, vá em <b>Configurações de Webhook</b>:<br>
                                • URL: <code>https://vivensi.app.br/whatsapp/webhook</code><br>
                                • Token: <code>vivensi_seguro_2026</code><br>
                                • Campo: Assine a opção <b>messages</b>.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #f1f5f9; padding: 20px;">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal" style="border-radius: 10px; padding: 10px 25px;">Entendi, vou configurar!</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.fw-800 { font-weight: 800; }
.flex-1  { flex: 1; }

.form-control-vivensi {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 9px 13px;
    width: 100%;
    transition: border-color .2s, box-shadow .2s;
    font-size: 0.85rem;
    color: #334155;
}
.form-control-vivensi:focus {
    border-color: var(--ds-brand);
    background: white;
    box-shadow: 0 0 0 3px rgba(99,102,241,.12);
    outline: none;
}

/* Training accordions */
.training-section { border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 8px; overflow: hidden; }
.training-section-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 11px 15px; background: #f8fafc; cursor: pointer;
    font-weight: 700; font-size: .85rem; color: #334155;
    user-select: none; transition: background .15s;
}
.training-section-header:hover { background: #f1f5f9; }
.training-section-body { padding: 14px 15px; background: #fff; }
.training-chevron { font-size: .72rem; color: #94a3b8; transition: transform .2s; }
.training-chevron.open { transform: rotate(180deg); }
.training-label { display: block; font-size: .76rem; font-weight: 600; color: #64748b; margin-bottom: 5px; letter-spacing: .02em; }

/* Robot config section separator */
.settings-robot-config {
    border-top: 1px solid #e2e8f0;
    padding-top: 16px;
    margin-top: 8px;
}

/* FAQ rows */
.faq-row { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 10px; padding: 11px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0; }
.faq-index { min-width: 22px; height: 22px; background: #a855f7; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .68rem; font-weight: 800; margin-top: 4px; flex-shrink: 0; }
.faq-fields { flex: 1; }
.faq-remove { background: none; border: none; color: #cbd5e1; padding: 4px; cursor: pointer; flex-shrink: 0; margin-top: 2px; transition: color .15s; }
.faq-remove:hover { color: #ef4444; }
</style>
@endpush

<script>
    function saveManualMeta() {
        const waba = document.getElementById('manual_waba_id').value;
        const phone = document.getElementById('manual_phone_id').value;
        const token = document.getElementById('manual_access_token').value;

        if(!waba || !phone || !token) {
            alert('Por favor, preencha todos os campos da Meta API.');
            return;
        }

        document.getElementById('input_waba_id').value = waba;
        document.getElementById('input_phone_id').value = phone;
        document.getElementById('input_access_token').value = token;
        
        document.getElementById('mainSettingsForm').submit();
    }

    function disconnectMeta() {
        if(confirm("Tem certeza que deseja desvincular seu WhatsApp Oficial?")) {
            document.getElementById('input_waba_id').value = '';
            document.getElementById('input_phone_id').value = '';
            document.getElementById('input_access_token').value = '';
            document.getElementById('mainSettingsForm').submit();
        }
    }

    function saveEvolutionConfig() {
        const instanceName = document.getElementById('evolution_instance_name').value;
        const apiKey = document.getElementById('evolution_api_key').value;

        if(!instanceName || !apiKey) {
            alert('Por favor, preencha o nome da instância e a API Key.');
            return;
        }

        document.getElementById('input_evolution_instance_name').value = instanceName;
        document.getElementById('input_evolution_instance_token').value = apiKey;
        
        document.getElementById('mainSettingsForm').submit();
    }

    function getEvolutionQrCode() {
        fetch('/whatsapp/qr-code', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if(data.error) {
                alert('Erro: ' + data.error);
                return;
            }
            if(data.qrcode) {
                const qrWindow = window.open('', 'QR Code Evolution', 'width=400,height=500');
                qrWindow.document.write('<html><head><title>QR Code Evolution API</title></head><body style="text-align:center;padding:20px;"><h3>Escaneie este QR Code</h3><img loading="lazy" src="' + data.qrcode + '" style="max-width:300px;"/><p>Abra o WhatsApp no celular > Configurações > WhatsApp Web > Escanear código QR</p></body></html>');
            } else {
                alert('QR Code não disponível. Verifique a configuração da instância.');
            }
        })
        .catch(error => {
            console.error(error);
            alert('Erro ao obter QR Code.');
        });
    }

    function getEvolutionPairingCode() {
        const phoneNumber = prompt('Digite o número de telefone (com DDD, ex: 11999999999):');
        if(!phoneNumber) return;
        
        fetch('/whatsapp/pairing-code', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ phone_number: phoneNumber })
        })
        .then(response => response.json())
        .then(data => {
            if(data.error) {
                alert('Erro: ' + data.error);
                return;
            }
            if(data.pairing_code) {
                alert('Pairing Code: ' + data.pairing_code + '\n\nNo WhatsApp, vá em Configurações > WhatsApp Web > Vincular dispositivo e insira este código.');
            } else {
                alert('Pairing Code não disponível. Verifique a configuração.');
            }
        })
        .catch(error => {
            console.error(error);
            alert('Erro ao obter Pairing Code.');
        });
    }

    function checkEvolutionStatus() {
        fetch('/whatsapp/status', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if(data.error) {
                document.getElementById('evolution_status_text').textContent = 'Erro: ' + data.error;
            } else {
                const status = data.state || data.status || 'Desconhecido';
                document.getElementById('evolution_status_text').textContent = status;
                alert('Status da conexão: ' + status);
            }
        })
        .catch(error => {
            console.error(error);
            document.getElementById('evolution_status_text').textContent = 'Erro na verificação';
        });
    }

    function disconnectEvolution() {
        if(confirm("Tem certeza que deseja desconectar a Evolution API?")) {
            document.getElementById('input_evolution_instance_name').value = '';
            document.getElementById('input_evolution_instance_token').value = '';
            document.getElementById('mainSettingsForm').submit();
        }
    }
</script>
<!-- Modal Criar/Escanear Instância -->
<div class="modal fade" id="newInstanceModal" role="dialog" aria-modal="true" aria-labelledby="newInstanceModalLabel" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content" style="background: #fff; border: none; border-radius: 20px; box-shadow: 0 25px 50px rgba(0,0,0,0.1);">
            <div class="modal-header" style="border-bottom: 1px solid #f8fafc; padding: 20px 24px;">
                <h5 class="modal-title" style="color: #334155; font-weight: 800; font-size: 1.1rem;"><i class="fab fa-whatsapp" style="color: #10b981; margin-right: 8px;"></i> Conectar Aparelho</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 24px; text-align: center;">
                
                <!-- Criar Form -->
                <div id="create-instance-form">
                    <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 20px; text-align: left;">Dê um nome e opcionalmente insira o número para conectar via <b>Código de Pareamento</b>.</p>
                    
                    <div class="form-group text-start mb-3">
                        <label style="color: #475569; font-weight: 600; font-size: 0.8rem; margin-bottom: 6px;">Nome da Instância</label>
                        <input type="text" id="instanceName" class="form-control" placeholder="Ex: Financeiro" style="background: #f8fafc; border: 1px solid #e2e8f0; color: #334155; border-radius: 10px; padding: 12px;">
                    </div>

                    <div class="form-group text-start mb-4">
                        <label style="color: #475569; font-weight: 600; font-size: 0.8rem; margin-bottom: 6px;">Número do WhatsApp (Opcional)</label>
                        <input type="text" id="instanceNumber" class="form-control" placeholder="5511999999999" style="background: #f8fafc; border: 1px solid #e2e8f0; color: #334155; border-radius: 10px; padding: 12px;">
                        <small style="color: #94a3b8; font-size: 0.75rem;">Se preencher, geraremos um código para digitar no celular.</small>
                    </div>

                    <button onclick="createInstance()" class="btn btn-primary w-100" style="background: #10b981; color: #fff; border: none; border-radius: 10px; font-weight: 700; padding: 14px;">
                        Iniciar Conexão
                    </button>
                </div>

                <!-- QR Code / Pairing Code View -->
                <div id="qr-code-view" style="display: none;">
                    <div id="pairing-code-display" style="display: none; margin-bottom: 20px;">
                        <p style="color: #475569; font-size: 0.9rem; margin-bottom: 10px; font-weight: 600;">Código de Pareamento:</p>
                        <div style="background: #f1f5f9; color: #0f172a; font-size: 2rem; font-weight: 900; letter-spacing: 5px; padding: 15px; border-radius: 12px; border: 2px dashed #cbd5e1; display: inline-block; min-width: 200px;">
                            <span id="pairing-code-value">--------</span>
                        </div>
                        <p style="color: #64748b; font-size: 0.8rem; margin-top: 10px;">Vá em <b>Aparelhos Conectados > Conectar com número de telefone</b> no seu WhatsApp e digite este código.</p>
                    </div>

                    <div id="qr-code-display">
                        <p style="color: #475569; font-size: 0.9rem; margin-bottom: 15px; font-weight: 600;">Abra o WhatsApp no celular e escaneie o código abaixo:</p>
                        
                        <div style="background: #f8fafc; padding: 15px; border-radius: 16px; border: 1px solid #e2e8f0; display: inline-block; margin-bottom: 20px; min-width: 250px; min-height: 250px; display: flex; align-items: center; justify-content: center;">
                            <img loading="lazy" id="qr-code-image" src="" alt="QR Code" style="width: 220px; height: 220px; display: none;">
                            <div id="qr-code-loading" style="color: #64748b;">
                                <i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>Gerando código...
                            </div>
                        </div>
                    </div>

                    <p style="color: #f59e0b; font-size: 0.85rem; font-weight: 600; margin-bottom: 0;">
                        <i class="fas fa-sync fa-spin me-1"></i> Aguardando conexão...
                    </p>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    let pollInterval = null;
    let currentInstanceId = null;

    function openNewInstanceModal() {
        document.getElementById('create-instance-form').style.display = 'block';
        document.getElementById('qr-code-view').style.display = 'none';
        document.getElementById('instanceName').value = '';
        document.getElementById('instanceNumber').value = '';
        document.getElementById('pairing-code-display').style.display = 'none';
        document.getElementById('qr-code-display').style.display = 'block';
        var modal = new bootstrap.Modal(document.getElementById('newInstanceModal'));
        modal.show();
    }

    async function createInstance() {
        const name = document.getElementById('instanceName').value;
        const number = document.getElementById('instanceNumber').value;
        if (!name) {
            alert('Por favor, informe um nome para a instância.');
            return;
        }

        const btn = document.querySelector('#create-instance-form button');
        const oldText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Iniciando...';
        btn.disabled = true;

        try {
            const response = await fetch(`/whatsapp/instances`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ name: name, number: number })
            });

            const data = await response.json();
            
            if (response.ok && data.instance) {
                currentInstanceId = data.instance.id;
                document.getElementById('create-instance-form').style.display = 'none';
                document.getElementById('qr-code-view').style.display = 'block';
                
                // Exibição Instantânea de Pairing Code ou QR Code
                if (data.pairingCode) {
                    document.getElementById('qr-code-display').style.display = 'none';
                    document.getElementById('pairing-code-display').style.display = 'block';
                    document.getElementById('pairing-code-value').textContent = data.pairingCode;
                } else if (data.qrcode) {
                    let qrBase64 = data.qrcode;
                    if (!qrBase64.startsWith('data:image')) {
                        qrBase64 = 'data:image/png;base64,' + qrBase64;
                    }
                    document.getElementById('qr-code-display').style.display = 'block';
                    document.getElementById('pairing-code-display').style.display = 'none';
                    document.getElementById('qr-code-loading').style.display = 'none';
                    document.getElementById('qr-code-image').style.display = 'block';
                    document.getElementById('qr-code-image').src = qrBase64;
                } else {
                    document.getElementById('qr-code-display').style.display = 'block';
                    document.getElementById('pairing-code-display').style.display = 'none';
                    document.getElementById('qr-code-image').style.display = 'none';
                    document.getElementById('qr-code-loading').style.display = 'block';
                }
                
                fetchQrCode();
                pollInterval = setInterval(fetchQrCode, 5000);
            } else {
                const errMsg = data.error || data.message || 'Sem resposta do servidor';
                const errDet = data.details ? '\n\nDetalhes: ' + data.details : '';
                alert('Não foi possível criar a instância:\n' + errMsg + errDet);
                btn.innerHTML = oldText;
                btn.disabled = false;
            }
        } catch (err) {
            console.error(err);
            alert('Falha na comunicação com o servidor.');
            btn.innerHTML = oldText;
            btn.disabled = false;
        }
    }

    async function fetchQrCode() {
        if (!currentInstanceId) return;

        const loadingText = document.getElementById('qr-code-loading');
        const qrImage = document.getElementById('qr-code-image');

        try {
            const response = await fetch(`/whatsapp/instances/${currentInstanceId}/connect`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const data = await response.json();

            if (data.error) {
                loadingText.innerHTML = `<i class="fas fa-exclamation-triangle text-warning mb-2"></i><br><small>${data.error}</small>`;
                loadingText.style.display = 'block';
                qrImage.style.display = 'none';
                return;
            }

            let qrBase64 = data.qrcode || data.base64;
            
            if (qrBase64) {
                if (!qrBase64.startsWith('data:image')) {
                    qrBase64 = 'data:image/png;base64,' + qrBase64;
                }
                loadingText.style.display = 'none';
                qrImage.style.display = 'block';
                qrImage.src = qrBase64;
            } else if (data.status === 'open' || data.state === 'open' || (data.instance && data.instance.state === 'open')) {
                clearInterval(pollInterval);
                document.getElementById('qr-code-view').innerHTML = `
                    <div style="color: #10b981; font-size: 3rem; margin-bottom: 15px;"><i class="fas fa-check-circle"></i></div>
                    <h5 style="color: #334155; font-weight: 800;">Conectado com Sucesso!</h5>
                    <p style="color: #64748b;">A página será recarregada.</p>
                `;
                setTimeout(() => window.location.reload(), 2000);
            } else {
                loadingText.innerHTML = `<i class="fas fa-sync fa-spin mb-2"></i><br>Gerando QR Code...<br><small style="font-size:0.7rem">Aguardando resposta da Evolution</small>`;
            }
        } catch (err) {
            console.error('Erro buscando QR Code', err);
            loadingText.innerHTML = `<i class="fas fa-network-wired text-danger mb-2"></i><br><small>Erro de conexão local</small>`;
        }
    }

    document.getElementById('newInstanceModal').addEventListener('hidden.bs.modal', function () {
        if (pollInterval) clearInterval(pollInterval);
        currentInstanceId = null;
    });

    function confirmDelete(id) {
        if (confirm('Tem certeza que deseja excluir esta instância? Esta ação é irreversível.')) {
            fetch(`/whatsapp/instances/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).then(() => window.location.reload());
        }
    }

    function checkStatus(id) {
        currentInstanceId = id;
        document.getElementById('create-instance-form').style.display = 'none';
        document.getElementById('qr-code-view').style.display = 'block';
        document.getElementById('qr-code-image').style.display = 'none';
        document.getElementById('qr-code-loading').style.display = 'block';
        var modal = new bootstrap.Modal(document.getElementById('newInstanceModal'));
        modal.show();
        fetchQrCode();
        pollInterval = setInterval(fetchQrCode, 5000);
    }
</script>
@push('scripts')
<script>
// --- Training Editor Accordion ---
function toggleSection(id) {
    const body = document.getElementById('section-' + id);
    const chevron = document.getElementById('chevron-' + id);
    const isOpen = body.style.display !== 'none';
    body.style.display = isOpen ? 'none' : 'block';
    chevron.classList.toggle('open', !isOpen);
}

// Open identity section by default on load
document.addEventListener('DOMContentLoaded', function () {
    ['identity', 'org', 'services', 'manual', 'hours', 'contact', 'faq'].forEach(function(id) {
        const body = document.getElementById('section-' + id);
        const chevron = document.getElementById('chevron-' + id);
        if (body) {
            body.style.display = 'block';
            chevron.classList.add('open');
        }
    });
    reindexFaq();

    // Contador de caracteres do manual
    const manualTA = document.querySelector('textarea[name="training_manual"]');
    const manualCount = document.getElementById('manual-char-count');
    if (manualTA && manualCount) {
        const update = () => manualCount.textContent = manualTA.value.length + ' / 8000 caracteres';
        update();
        manualTA.addEventListener('input', update);
    }
});

// --- FAQ Management ---
let faqCounter = document.querySelectorAll('.faq-row').length;

function addFaq() {
    const list = document.getElementById('faq-list');
    const idx = faqCounter++;
    const row = document.createElement('div');
    row.className = 'faq-row';
    row.dataset.index = idx;
    row.innerHTML = `
        <div class="faq-index">${list.children.length + 1}</div>
        <div class="faq-fields">
            <input type="text" name="faq[${idx}][question]" class="form-control-vivensi mb-2" placeholder="Pergunta: Ex: Como me cadastrar?">
            <textarea name="faq[${idx}][answer]" rows="2" class="form-control-vivensi" placeholder="Resposta: Ex: Compareça à sede com RG e comprovante de renda."></textarea>
        </div>
        <button type="button" class="faq-remove" onclick="removeFaq(this)" title="Remover">
            <i class="fas fa-times"></i>
        </button>`;
    list.appendChild(row);
    reindexFaq();
}

function removeFaq(btn) {
    const row = btn.closest('.faq-row');
    if (document.querySelectorAll('.faq-row').length <= 1) {
        row.querySelector('input').value = '';
        row.querySelector('textarea').value = '';
        return;
    }
    row.remove();
    reindexFaq();
}

function reindexFaq() {
    document.querySelectorAll('.faq-row').forEach(function(row, i) {
        row.querySelector('.faq-index').textContent = i + 1;
    });
}
</script>
@endpush

@endsection
