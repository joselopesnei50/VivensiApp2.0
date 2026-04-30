@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h6 style="color: #6366f1; font-weight: 700; text-transform: uppercase; margin: 0 0 5px 0; letter-spacing: 1px;">Omnichannel Oficial</h6>
        <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">Treinamento da IA & WhatsApp Oficial</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Utilizamos a infraestrutura direta da Meta para máxima segurança de dados e blindagem contra bloqueios.</p>
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
            <div class="vivensi-card" style="padding: 25px; margin-bottom: 30px; border-top: 4px solid #a855f7;">
                <h4 style="margin: 0 0 6px 0; font-size: 1.1rem; color: #334155; font-weight: 700;">
                    <i class="fas fa-brain me-2" style="color: #a855f7;"></i> Treinamento Bruce AI
                </h4>
                <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 20px;">
                    Preencha as seções abaixo. O sistema monta o prompt automaticamente para o Bruce.
                </p>

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
                        <span><i class="fas fa-building me-2" style="color: #6366f1;"></i> Organização</span>
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

                <div style="margin-top: 20px;">
                    <label class="fw-700 mb-2 small">Motor Cognitivo (IA)</label>
                    <select name="ai_provider" class="form-control-vivensi">
                        <option value="gemini" {{ ($config->ai_provider ?? 'gemini') == 'gemini' ? 'selected' : '' }}>Google Gemini (Rápido)</option>
                        <option value="deepseek" {{ ($config->ai_provider ?? '') == 'deepseek' ? 'selected' : '' }}>DeepSeek Chat (V3)</option>
                    </select>
                </div>

                <div style="margin-top: 20px; display: flex; align-items: center; background: #f8fafc; padding: 15px; border-radius: 12px;">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="ai_enabled" value="1" id="aiEnabled" {{ $config->ai_enabled ? 'checked' : '' }}>
                        <label class="form-check-label fw-700" for="aiEnabled">Habilitar Robô de Atendimento</label>
                    </div>
                </div>
            </div>
            
            <!-- Send Policies -->
            <div class="vivensi-card" style="padding: 25px; margin-bottom: 30px; border-top: 4px solid #f59e0b;">
                <h4 style="margin: 0 0 14px 0; font-size: 1.1rem; color: #334155; font-weight: 700;">
                    <i class="fas fa-gavel me-2" style="color: #f59e0b;"></i> Políticas de Envio da Meta
                </h4>
                
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="outbound_enabled" value="1" id="outboundEnabled" {{ ($config->outbound_enabled ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label fw-700" for="outboundEnabled">Habilitar Envio de Mensagens</label>
                    <div class="small text-muted mt-1">Ativa o envio de mensagens pelo sistema. Desativar bloqueia todos os disparos.</div>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="enforce_24h_window" value="1" id="enforce24h" {{ ($config->enforce_24h_window ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label fw-700" for="enforce24h">Respeitar Janela Restrita de 24h (Custos reduzidos)</label>
                    <div class="small text-muted mt-1">Garante que a IA só envia textos livres enquanto o WhatsApp permite gratuitamente.</div>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="allow_templates_outside_window" value="1" id="allowTemplates" {{ ($config->allow_templates_outside_window ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label fw-700" for="allowTemplates">Permitir disparos pagos em Massa (Templates)</label>
                    <div class="small text-muted mt-1">Ao ativar, relatórios e campanhas usarão modelos aprovados pela Meta fora da janela de atendimento. O cartão atrelado no Facebook Business será cobrado.</div>
                </div>
            </div>

        </div>

        <div class="col-md-5">
            <!-- Meta API Official Connection Panel -->
            <div class="card-vivensi p-4" style="border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: #fff;">
                <!-- BOTÃO FIXO DE AJUDA -->
                <div class="mb-4 text-end">
                    <button type="button" class="btn btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#metaHelpModal" style="background: #eff6ff; color: #1877f2; border-radius: 8px; border: 1px solid #dbeafe;">
                        <i class="fas fa-question-circle me-1"></i> Guia: Como conectar WhatsApp Oficial?
                    </button>
                </div>

                <div class="text-center">
                    <div class="integration-badge mb-4">
                        <i class="fab fa-facebook" style="color: #1877f2; font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="fw-800 mb-2">WhatsApp Business API</h4>
                    <p class="text-muted small px-3">Conexão oficial via Meta Cloud API. Sem necessidade de celular ligado.</p>

                    <div id="meta_status_container" class="mt-4">
                        @if(!empty($contextModel->meta_waba_id) && !empty($contextModel->meta_phone_number_id))
                            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                                <i class="fas fa-check-circle fa-3x" style="color: #22c55e; margin-bottom: 10px;"></i>
                                <h5 style="color: #166534; font-weight: 800; font-size: 1rem; margin: 0;">SISTEMA ONLINE (APROVADO)</h5>
                                <hr style="border-color: #d1fae5; margin: 15px 0;">
                                <div style="text-align: left; font-size: 0.75rem; color: #166534;">
                                    <b>ID WhatsApp Business:</b> {{ $contextModel->meta_waba_id }}<br>
                                    <b>Telefone Cloud ID:</b> {{ $contextModel->meta_phone_number_id }}
                                </div>
                            </div>
                            
                            <button type="button" onclick="disconnectMeta()" class="btn btn-outline-danger w-100 fw-bold" style="border-radius: 8px; padding: 10px;">
                                <i class="fas fa-unlink me-2"></i> Desconectar e Mudar Credenciais
                            </button>
                        @else
                            <div style="background: #f8fafc; border: 1px dashed #cbd5e1; padding: 25px 20px; border-radius: 12px; margin-bottom: 20px; text-align: left;">
                                <h5 style="color: #475569; font-weight: 700; font-size: 0.9rem; margin-bottom: 15px; text-align: center;">Configuração Oficial Meta API</h5>
                                
                                <div class="mb-3">
                                    <label class="small fw-700 text-muted">WhatsApp Business Account (WABA ID)</label>
                                    <input type="text" id="manual_waba_id" class="form-control-vivensi mt-1" placeholder="Ex: 109283746554321">
                                </div>

                                <div class="mb-3">
                                    <label class="small fw-700 text-muted">Phone Number ID (Cloud API)</label>
                                    <input type="text" id="manual_phone_id" class="form-control-vivensi mt-1" placeholder="Ex: 223344556677889">
                                </div>

                                <div class="mb-4">
                                    <label class="small fw-700 text-muted">Access Token Permanente</label>
                                    <input type="password" id="manual_access_token" class="form-control-vivensi mt-1" placeholder="EAABxb...">
                                </div>

                                <button type="button" onclick="saveManualMeta()" class="btn w-100 fw-bold" style="background: #1877f2; color: #fff; padding: 12px; border-radius: 8px;">
                                    <i class="fab fa-facebook-f me-2"></i> Salvar e Conectar Meta
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Evolution API Connection Panel -->
            <div class="card-vivensi p-4 mt-4" style="border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: #fff;">
                <div class="text-center mb-4">
                    <div class="integration-badge mb-3">
                        <i class="fas fa-qrcode" style="color: #10b981; font-size: 2.5rem;"></i>
                    </div>
                    <h4 class="fw-800 mb-2">Aparelhos Conectados (Evolution)</h4>
                    <p class="text-muted small px-3">Conexões autônomas de WhatsApp para disparos em massa nativos e fallback do Cloud API.</p>
                </div>

                <!-- Lista de Instâncias -->
                <div id="instances_list_container">
                    @if($instances->isEmpty())
                        <div style="background: #f8fafc; border: 1px dashed #cbd5e1; padding: 25px 20px; border-radius: 12px; text-align: center;">
                            <h5 style="color: #475569; font-weight: 700; font-size: 0.95rem; margin-bottom: 10px;">Nenhum WhatsApp Conectado</h5>
                            <p style="color: #64748b; font-size: 0.8rem; margin-bottom: 20px;">Adicione um aparelho celular escaneando o QR Code para habilitar disparos assíncronos.</p>
                            <button type="button" onclick="openNewInstanceModal()" class="btn w-100 fw-bold" style="background: #10b981; color: #fff; padding: 12px; border-radius: 8px;">
                                <i class="fas fa-plus me-2"></i> Conectar Novo Aparelho
                            </button>
                        </div>
                    @else
                        @foreach($instances as $instance)
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 15px; text-align: left;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fab fa-whatsapp" style="color: #10b981; font-size: 1.5rem;"></i>
                                        <div>
                                            <h6 style="margin: 0; font-weight: 700; color: #334155;">{{ $instance->instance_name }}</h6>
                                            <small style="color: #64748b; font-family: monospace;">{{ $instance->phone_number ?: 'Aguardando Número...' }}</small>
                                        </div>
                                    </div>
                                    <div>
                                        @if($instance->status === 'open')
                                            <span class="badge" style="background: #dcfce7; color: #166534; font-size: 0.7rem; border: 1px solid #bbf7d0;">CONECTADO</span>
                                        @elseif($instance->status === 'connecting')
                                            <span class="badge" style="background: #fef3c7; color: #92400e; font-size: 0.7rem; border: 1px solid #fde68a;"><i class="fas fa-spinner fa-spin"></i> CONECTANDO</span>
                                        @else
                                            <span class="badge" style="background: #fee2e2; color: #b91c1c; font-size: 0.7rem; border: 1px solid #fecaca;">DESCONECTADO</span>
                                        @endif
                                    </div>
                                </div>

                                <div style="background: #fff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 10px; margin-bottom: 15px;">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small class="fw-bold" style="color: #475569; font-size: 0.7rem;">Aquecimento Diário</small>
                                        <small class="fw-bold" style="color: #10b981; font-size: 0.7rem;">{{ $instance->messages_sent_today }} / {{ $instance->daily_limit }}</small>
                                    </div>
                                    <div class="progress" style="height: 6px; background: #e2e8f0;">
                                        @php $percent = $instance->daily_limit > 0 ? min(100, intval(($instance->messages_sent_today / $instance->daily_limit) * 100)) : 0; @endphp
                                        <div class="progress-bar" style="background: {{ $percent > 80 ? '#ef4444' : ($percent > 50 ? '#f59e0b' : '#10b981') }}; width: {{ $percent }}%;"></div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    @if($instance->status !== 'open')
                                        <button type="button" onclick="checkStatus('{{ $instance->id }}')" class="btn btn-sm btn-outline-primary fw-bold" style="flex: 1; border-radius: 8px;">
                                            <i class="fas fa-qrcode"></i> Scan QR
                                        </button>
                                    @else
                                        <a href="{{ route('whatsapp.broadcast.index') }}" class="btn btn-sm btn-outline-success fw-bold" style="flex: 1; border-radius: 8px;">
                                            <i class="fas fa-paper-plane"></i> Disparar
                                        </a>
                                    @endif
                                    <button type="button" onclick="confirmDelete('{{ $instance->id }}')" class="btn btn-sm btn-outline-danger" style="border-radius: 8px;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                        
                        <button type="button" onclick="openNewInstanceModal()" class="btn btn-outline-secondary w-100 fw-bold" style="border-radius: 8px; padding: 10px; border-style: dashed;">
                            <i class="fas fa-plus"></i> Adicionar Outro Aparelho
                        </button>
                    @endif
                </div>
            </div>

            <button type="submit" class="btn-premium" style="width: 100%; padding: 15px; margin-top: 20px;">
                <i class="fas fa-save me-2"></i> Salvar Treinamento Bruce AI
            </button>
        </div>
    </div>
</form>

<!-- Modal de Ajuda Meta -->
<div class="modal fade" id="metaHelpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: #1877f2; color: white; padding: 25px; border: none;">
                <h5 class="modal-title fw-800"><i class="fab fa-whatsapp me-2"></i> Passo a Passo: Conectar WhatsApp Oficial</h5>
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
.form-control-vivensi {
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 10px 14px; width: 100%; transition: all 0.2s; font-size: 0.85rem;
}
.form-control-vivensi:focus { border-color: #6366f1; background: white; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); outline: none; }

/* Structured Training Editor */
.training-section { border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 10px; overflow: hidden; }
.training-section-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 12px 16px; background: #f8fafc; cursor: pointer;
    font-weight: 700; font-size: 0.88rem; color: #334155;
    user-select: none; transition: background 0.15s;
}
.training-section-header:hover { background: #f1f5f9; }
.training-section-body { padding: 16px; background: #fff; }
.training-chevron { font-size: 0.75rem; color: #94a3b8; transition: transform 0.2s; }
.training-chevron.open { transform: rotate(180deg); }
.training-label { display: block; font-size: 0.8rem; font-weight: 600; color: #64748b; margin-bottom: 6px; }

/* FAQ rows */
.faq-row { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 12px; padding: 12px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0; }
.faq-index { min-width: 24px; height: 24px; background: #a855f7; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 800; margin-top: 4px; flex-shrink: 0; }
.faq-fields { flex: 1; }
.faq-remove { background: none; border: none; color: #cbd5e1; padding: 4px; cursor: pointer; flex-shrink: 0; margin-top: 2px; transition: color 0.15s; }
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
                qrWindow.document.write('<html><head><title>QR Code Evolution API</title></head><body style="text-align:center;padding:20px;"><h3>Escaneie este QR Code</h3><img src="' + data.qrcode + '" style="max-width:300px;"/><p>Abra o WhatsApp no celular > Configurações > WhatsApp Web > Escanear código QR</p></body></html>');
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
<div class="modal fade" id="newInstanceModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
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
                            <img id="qr-code-image" src="" alt="QR Code" style="width: 220px; height: 220px; display: none;">
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
                alert('Erro ao criar instância: ' + (data.error || 'Desconhecido'));
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
