@extends('layouts.app')

@section('title', 'Identidade Visual — Vivensi')

@section('content')
<div style="background-color: #0b1120; border-radius: 20px; padding: 40px; margin: -10px 10px 30px 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">

    <div style="margin-bottom: 30px;">
        <div style="font-size: 0.65rem; font-weight: 900; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 6px;">Configurações</div>
        <h1 style="font-size: 1.8rem; font-weight: 900; color: white; margin: 0; letter-spacing: -0.5px;">Identidade Visual</h1>
        <p style="color: rgba(255,255,255,0.5); font-size: 0.95rem; margin: 8px 0 0;">Personalize a logo, cor e nome exibidos no seu painel de comando.</p>
    </div>

    @if(session('success'))
        <div style="background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); color: #34d399; padding: 14px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i> {{ session('success') }}
        </div>
    @endif

    <div class="row g-4 align-items-start">

        {{-- Coluna Principal: Formulário --}}
        <div class="col-lg-8">
            <form action="{{ route('settings.branding.update') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Quadro: Logo --}}
                <div style="background: #0f172a; border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 30px; margin-bottom: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                    <h3 style="font-size: 1.1rem; font-weight: 800; color: white; margin: 0 0 8px;">
                        <i class="fas fa-image" style="color: #818cf8; margin-right: 8px;"></i> Logotipo da Organização
                    </h3>
                    <p style="color: rgba(255,255,255,0.4); font-size: 0.85rem; margin: 0 0 24px;">Formatos aceitos: PNG, JPG ou SVG. Tamanho máximo: 2 MB. Recomendado: 300×80px.</p>

                    <div style="display: flex; align-items: center; gap: 24px; flex-wrap: wrap;">
                        {{-- Preview atual --}}
                        <div id="logo-preview-box" style="width: 140px; height: 70px; background: rgba(255,255,255,0.03); border: 1px dashed rgba(255,255,255,0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; padding: 10px;">
                            @if($tenant->brand_logo)
                                <img id="logo-preview" src="{{ Storage::url($tenant->brand_logo) }}" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                            @else
                                <img id="logo-preview" src="{{ asset('img/novalogo.png') }}" alt="Logo padrão" style="max-width: 100%; max-height: 100%; object-fit: contain; opacity: 0.3;">
                            @endif
                        </div>

                        <div style="flex:1; min-width: 200px;">
                            <label for="brand_logo" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.4); border-radius: 12px; cursor: pointer; color: #818cf8; font-size: 0.9rem; font-weight: 700; transition: all 0.2s; box-shadow: 0 4px 12px rgba(99,102,241,0.15);"
                                   onmouseover="this.style.background='rgba(99,102,241,0.25)'"
                                   onmouseout="this.style.background='rgba(99,102,241,0.15)'">
                                <i class="fas fa-cloud-upload-alt"></i> Escolher novo arquivo
                            </label>
                            <input type="file" id="brand_logo" name="brand_logo" accept="image/*" style="display:none;" onchange="previewLogo(this)">
                            
                            <p style="font-size: 0.75rem; color: rgba(255,255,255,0.3); margin: 10px 0 0;" id="logo-filename">
                                {{ $tenant->brand_logo ? basename($tenant->brand_logo) : 'Nenhum arquivo recém-selecionado' }}
                            </p>

                            @if($tenant->brand_logo)
                            <button type="button" onclick="removeLogo()"
                                    style="margin-top: 12px; background: none; border: none; color: #ef4444; font-size: 0.8rem; cursor: pointer; font-weight: 600; padding: 6px 12px; border-radius: 8px; transition: background 0.2s; display: inline-flex; align-items: center; gap: 6px;"
                                    onmouseover="this.style.background='rgba(239,68,68,0.1)'"
                                    onmouseout="this.style.background='transparent'">
                                <i class="fas fa-trash-alt"></i> Remover arquivo atual
                            </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Quadro: Nome e Cor --}}
                <div style="background: #0f172a; border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 30px; margin-bottom: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                    <h3 style="font-size: 1.1rem; font-weight: 800; color: white; margin: 0 0 24px;">
                        <i class="fas fa-paint-roller" style="color: #f472b6; margin-right: 8px;"></i> Paleta e Nomenclatura
                    </h3>

                    <div class="row">
                        <div class="col-md-7 mb-4 mb-md-0">
                            <label style="font-size: 0.8rem; font-weight: 800; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 10px;">Nome de Exibição no Painel</label>
                            <input type="text" name="brand_name" value="{{ old('brand_name', $tenant->brand_name) }}"
                                   placeholder="Ex: Fundação Vida Nova"
                                   style="width: 100%; padding: 14px 18px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; font-size: 0.95rem; outline: none; transition: border 0.2s;"
                                   onfocus="this.style.borderColor='#818cf8'; this.style.boxShadow='0 0 0 3px rgba(129,140,248,0.1)'"
                                   onblur="this.style.borderColor='rgba(255,255,255,0.1)'; this.style.boxShadow='none'">
                            <p style="font-size: 0.75rem; color: rgba(255,255,255,0.35); margin: 8px 0 0;">Se deixado em branco, o nome padrão do seu contrato será usado.</p>
                        </div>

                        <div class="col-md-5">
                            <label style="font-size: 0.8rem; font-weight: 800; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 10px;">Cor de Destaque</label>
                            <div style="display: flex; align-items: center; gap: 16px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); padding: 12px; border-radius: 12px;">
                                <input type="color" name="brand_color" id="brand_color"
                                       value="{{ old('brand_color', $tenant->brand_color ?? '#4F46E5') }}"
                                       oninput="updateColorPreview(this.value)"
                                       style="width: 44px; height: 44px; border: none; border-radius: 10px; cursor: pointer; background: none; padding: 0;">
                                <div>
                                    <div id="color-hex-display" style="font-size: 1rem; font-weight: 800; color: white; font-family: 'JetBrains Mono', monospace;">{{ $tenant->brand_color ?? '#4F46E5' }}</div>
                                    <div style="font-size: 0.75rem; color: rgba(255,255,255,0.4);">Aplica-se ao menu</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Quadro: Relatório Semanal --}}
                <div style="background: #0f172a; border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 30px; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                    <h3 style="font-size: 1.1rem; font-weight: 800; color: white; margin: 0 0 8px;">
                        <i class="fas fa-robot" style="color: #38bdf8; margin-right: 8px;"></i> Relatório Semanal Inteligente
                    </h3>
                    <p style="color: rgba(255,255,255,0.4); font-size: 0.85rem; margin: 0 0 24px; line-height: 1.6;">O <strong>Bruce AI</strong> pode compilar seus indicadores (receitas, despesas, tarefas) e enviar um resumo narrativo da semana direto no seu e-mail, todo domingo de manhã.</p>

                    <div style="background: rgba(56,189,248,0.05); border: 1px solid rgba(56,189,248,0.15); border-radius: 12px; padding: 16px 20px; margin-bottom: 20px;">
                        <label style="display: flex; align-items: center; gap: 14px; cursor: pointer; margin: 0;">
                            <input type="checkbox" name="weekly_report_enabled" value="1" {{ old('weekly_report_enabled', $tenant->weekly_report_enabled) ? 'checked' : '' }} style="width: 20px; height: 20px; cursor: pointer; accent-color: #38bdf8;">
                            <span style="font-size: 0.95rem; font-weight: 700; color: white;">Habilitar envios dominicais automáticos</span>
                        </label>
                    </div>

                    <div>
                        <label style="font-size: 0.8rem; font-weight: 800; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 10px;">E-mail do Destinatário</label>
                        <input type="email" name="report_email" value="{{ old('report_email', $tenant->report_email) }}"
                               placeholder="Ex: diretor@suaong.com.br"
                               style="width: 100%; max-width: 400px; padding: 14px 18px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; font-size: 0.95rem; outline: none; transition: border 0.2s;"
                               onfocus="this.style.borderColor='#38bdf8'"
                               onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
                        <p style="font-size: 0.75rem; color: rgba(255,255,255,0.35); margin: 8px 0 0;">Se vazio, enviaremos para o e-mail do dono da conta.</p>
                    </div>
                </div>

                {{-- Quadro: Dados de Recebimento (PIX) --}}
                <div style="background: #0f172a; border: 1px solid rgba(16,185,129,0.15); border-radius: 20px; padding: 30px; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); position: relative; overflow: hidden;">
                    <div style="position: absolute; top: 0; right: 0; width: 100px; height: 100px; background: radial-gradient(circle at top right, rgba(16,185,129,0.1) 0%, transparent 70%);"></div>
                    
                    <h3 style="font-size: 1.1rem; font-weight: 800; color: white; margin: 0 0 8px;">
                        <i class="fas fa-money-bill-wave" style="color: #10b981; margin-right: 8px;"></i> Recebimento via PIX
                    </h3>
                    <p style="color: rgba(255,255,255,0.4); font-size: 0.85rem; margin: 0 0 24px; line-height: 1.6;">Configure sua chave oficial para arrecadação automática em <strong>Rifas</strong> e <strong>Doações</strong>.</p>

                    <div class="row g-3">
                        <div class="col-md-5">
                            <label style="font-size: 0.8rem; font-weight: 800; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 10px;">Tipo de Chave</label>
                            <select name="pix_key_type" 
                                    style="width: 100%; padding: 14px 18px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; font-size: 0.95rem; outline: none; transition: border 0.2s; cursor: pointer;">
                                <option value="" style="background: #0f172a;">Selecione o tipo...</option>
                                <option value="cpf_cnpj" {{ $tenant->pix_key_type == 'cpf_cnpj' ? 'selected' : '' }} style="background: #0f172a;">CPF ou CNPJ</option>
                                <option value="email" {{ $tenant->pix_key_type == 'email' ? 'selected' : '' }} style="background: #0f172a;">E-mail</option>
                                <option value="phone" {{ $tenant->pix_key_type == 'phone' ? 'selected' : '' }} style="background: #0f172a;">Celular (WhatsApp)</option>
                                <option value="random" {{ $tenant->pix_key_type == 'random' ? 'selected' : '' }} style="background: #0f172a;">Chave Aleatória (EVP)</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label style="font-size: 0.8rem; font-weight: 800; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 10px;">Chave PIX Oficial</label>
                            <input type="text" name="pix_key" value="{{ old('pix_key', $tenant->pix_key) }}"
                                   placeholder="Cole sua chave aqui..."
                                   style="width: 100%; padding: 14px 18px; background: rgba(255,255,255,0.03); border: 1px solid rgba(16,185,129,0.2); border-radius: 12px; color: white; font-size: 0.95rem; font-weight: 700; outline: none; transition: all 0.2s;"
                                   onfocus="this.style.borderColor='#10b981'; this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.1)'"
                                   onblur="this.style.borderColor='rgba(16,185,129,0.2)'; this.style.boxShadow='none'">
                            <div style="display: flex; align-items: center; gap: 6px; margin-top: 10px; color: #34d399; font-size: 0.75rem; font-weight: 600;">
                                <i class="fas fa-lock"></i> Seus dados de recebimento estão seguros e criptografados.
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-3">
                        <div class="col-md-5">
                            <label style="font-size: 0.8rem; font-weight: 800; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 10px;">WhatsApp de Suporte (Comprovantes)</label>
                            <input type="text" name="whatsapp_support" value="{{ old('whatsapp_support', $tenant->whatsapp_support) }}"
                                   placeholder="Ex: 5511999999999"
                                   style="width: 100%; padding: 14px 18px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; font-size: 0.95rem; outline: none; transition: border 0.2s;"
                                   onfocus="this.style.borderColor='#10b981'"
                                   onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
                            <p style="font-size: 0.75rem; color: rgba(255,255,255,0.35); margin: 8px 0 0;">Número para onde os compradores enviarão comprovantes.</p>
                        </div>
                        <div class="col-md-7">
                            <label style="font-size: 0.8rem; font-weight: 800; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 10px;">OpenPix App ID (Opcional - Automático)</label>
                            <input type="password" name="openpix_app_id" value="{{ old('openpix_app_id', $tenant->openpix_app_id) }}"
                                   placeholder="Seu App ID da OpenPix"
                                   style="width: 100%; padding: 14px 18px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; font-size: 0.95rem; outline: none; transition: border 0.2s;"
                                   onfocus="this.style.borderColor='#10b981'"
                                   onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
                            <p style="font-size: 0.75rem; color: rgba(255,255,255,0.35); margin: 8px 0 0;">Somente se possuir CNPJ e conta na OpenPix.</p>
                        </div>
                    </div>
                </div>

                {{-- Botões de Ação --}}
                <div style="display: flex; justify-content: flex-end; gap: 16px;">
                    <button type="button" onclick="window.history.back()" style="padding: 14px 28px; border: 1px solid rgba(255,255,255,0.15); border-radius: 14px; background: transparent; color: rgba(255,255,255,0.6); font-weight: 700; font-size: 0.9rem; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='transparent'">Cancelar</button>
                    
                    <button type="submit"
                            style="padding: 14px 36px; background: linear-gradient(135deg, #4f46e5, #818cf8); border: none; border-radius: 14px; color: white; font-weight: 800; font-size: 0.95rem; cursor: pointer; transition: all 0.2s; box-shadow: 0 8px 25px rgba(79,70,229,0.35); display: flex; align-items: center; gap: 8px;"
                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 12px 30px rgba(79,70,229,0.5)'"
                            onmouseout="this.style.transform='none'; this.style.boxShadow='0 8px 25px rgba(79,70,229,0.35)'">
                        <i class="fas fa-check-double"></i> Salvar Modificações
                    </button>
                </div>
            </form>
        </div>

        {{-- Coluna Secundária: Preview Ao Vivo --}}
        <div class="col-lg-4">
            <div style="background: #0f172a; border: 1px solid rgba(255,255,255,0.07); border-radius: 20px; padding: 20px;">
                <div style="font-size: 0.65rem; font-weight: 900; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 16px;">Preview do Sidebar</div>

                {{-- Mini sidebar preview --}}
                <div style="background: #070d1a; border-radius: 14px; padding: 20px; border: 1px solid rgba(255,255,255,0.05);">
                    {{-- Logo area preview --}}
                    <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <div style="height: 48px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px;">
                            <img id="sidebar-preview-logo" src="{{ $tenant->brand_logo ? Storage::url($tenant->brand_logo) : asset('img/novalogo.png') }}"
                                 alt="Preview" style="max-width: 108px; max-height: 48px; object-fit: contain; {{ !$tenant->brand_logo ? 'opacity:0.4' : '' }}">
                        </div>
                        @if($tenant->brand_name)
                        <div id="sidebar-preview-name" style="font-size: 0.7rem; color: rgba(255,255,255,0.35); font-weight: 700; text-align: center;">{{ $tenant->brand_name }}</div>
                        @else
                        <div id="sidebar-preview-name" style="font-size: 0.7rem; color: rgba(255,255,255,0.15); font-weight: 700; text-align: center;">{{ auth()->user()->tenant->name }}</div>
                        @endif
                    </div>

                    {{-- Fake menu items --}}
                    <div style="display: flex; flex-direction: column; gap: 4px;">
                        <div id="sidebar-active-item" style="display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: 10px; background: {{ $tenant->brand_color ?? '#4F46E5' }}22; border-left: 3px solid {{ $tenant->brand_color ?? '#4F46E5' }};">
                            <i class="fas fa-gauge" style="color: {{ $tenant->brand_color ?? '#4F46E5' }}; font-size: 0.8rem;"></i>
                            <span style="font-size: 0.78rem; font-weight: 700; color: white;">Dashboard</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: 10px;">
                            <i class="fas fa-folder" style="color: rgba(255,255,255,0.25); font-size: 0.8rem;"></i>
                            <span style="font-size: 0.78rem; font-weight: 600; color: rgba(255,255,255,0.35);">Projetos</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: 10px;">
                            <i class="fas fa-chart-bar" style="color: rgba(255,255,255,0.25); font-size: 0.8rem;"></i>
                            <span style="font-size: 0.78rem; font-weight: 600; color: rgba(255,255,255,0.35);">Relatórios</span>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 16px; padding: 12px; background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.1); border-radius: 10px;">
                    <p style="font-size: 0.72rem; color: rgba(255,255,255,0.3); margin: 0; font-weight: 600; line-height: 1.5;">
                        <i class="fas fa-info-circle" style="color: #818cf8; margin-right: 4px;"></i>
                        A cor de destaque afeta todos os botões, ícones ativos e elementos de UI do painel.
                    </p>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

<script>
function previewLogo(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('logo-preview').src = e.target.result;
            document.getElementById('logo-preview').style.opacity = '1';
            document.getElementById('sidebar-preview-logo').src = e.target.result;
            document.getElementById('sidebar-preview-logo').style.opacity = '1';
            document.getElementById('logo-filename').textContent = input.files[0].name;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function updateColorPreview(hex) {
    document.getElementById('color-hex-display').textContent = hex;

    // Update sidebar preview active item
    const activeItem = document.getElementById('sidebar-active-item');
    if (activeItem) {
        activeItem.style.background = hex + '22';
        activeItem.style.borderLeftColor = hex;
        activeItem.querySelector('i').style.color = hex;
    }
}

function removeLogo() {
    if (!confirm('Remover a logo personalizada e usar o logo padrão do Vivensi?')) return;

    fetch('{{ route("settings.branding.remove-logo") }}', {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            window.location.reload();
        }
    });
}

// Inicializa preview com a cor atual
updateColorPreview(document.getElementById('brand_color').value);
</script>
@endsection
