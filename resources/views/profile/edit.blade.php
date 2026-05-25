@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 40px; position: relative;">
    <div style="background: linear-gradient(135deg, rgba(71, 85, 105, 0.1) 0%, rgba(30, 41, 59, 0.05) 100%); position: absolute; top: -30px; left: -30px; right: -30px; bottom: 0; z-index: -1;"></div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap:wrap; gap:12px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: #475569; width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: #475569; font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Configurações de Identidade</h6>
            </div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.5rem; letter-spacing: -1.5px;">Centro de Perfil</h2>
            <p style="color: #64748b; margin: 8px 0 0 0; font-size: 1.1rem; font-weight: 500;">Gerencie suas credenciais e preferências de segurança.</p>
        </div>
        <a href="{{ route('2fa.show') }}" style="display:inline-flex; align-items:center; gap:8px; background:{{ auth()->user()->hasTwoFactorEnabled() ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.08)' }}; border:1px solid {{ auth()->user()->hasTwoFactorEnabled() ? 'rgba(16,185,129,0.3)' : 'rgba(239,68,68,0.2)' }}; color:{{ auth()->user()->hasTwoFactorEnabled() ? '#10b981' : '#dc2626' }}; border-radius:12px; padding:10px 18px; font-size:0.8rem; font-weight:800; text-decoration:none;">
            <i class="fas fa-shield-halved me-1"></i>
            2FA {{ auth()->user()->hasTwoFactorEnabled() ? 'Ativo' : 'Desativado' }}
        </a>
    </div>
</div>

@if(session('success'))
    <div style="background: #ecfdf5; color: #065f46; padding: 20px; border-radius: 16px; margin-bottom: 30px; border: 1px solid #a7f3d0; font-weight: 700; display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-user-check" style="font-size: 1.2rem;"></i> {{ session('success') }}
    </div>
@endif

<div class="row g-5">
    
    <!-- Info Card -->
    <div class="col-md-6">
        <div class="vivensi-card" style="padding: 40px; border-radius: 28px; background: white; border: 1px solid #f1f5f9; box-shadow: 0 15px 45px rgba(0,0,0,0.02);">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 30px;">
                <div style="width: 48px; height: 48px; background: #f8fafc; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #475569; font-size: 1.2rem; border: 1px solid #e2e8f0;">
                    <i class="fas fa-id-badge"></i>
                </div>
                <h3 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 1.3rem; letter-spacing: -0.5px;">Dados Corporativos</h3>
            </div>
            
            <form action="{{ url('/profile/update') }}" method="POST">
                @csrf
                
                <div class="form-group" style="margin-bottom: 25px;">
                    <label for="name" style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Nome Completo</label>
                    <div style="position: relative;">
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                               style="width: 100%; padding: 15px 20px 15px 50px; border: 2px solid #f1f5f9; border-radius: 14px; background: #f8fafc; font-weight: 700; color: #1e293b; transition: all 0.3s;"
                               onfocus="this.style.borderColor='var(--primary-color)'; this.style.background='white';">
                        <i class="fas fa-user" style="position: absolute; left: 20px; top: 18px; color: #cbd5e1; font-size: 0.9rem;"></i>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 25px;">
                    <label for="email" style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Endereço de E-mail</label>
                    <div style="position: relative;">
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                               style="width: 100%; padding: 15px 20px 15px 50px; border: 2px solid #f1f5f9; border-radius: 14px; background: #f8fafc; font-weight: 700; color: #1e293b; transition: all 0.3s;"
                               onfocus="this.style.borderColor='var(--primary-color)'; this.style.background='white';">
                        <i class="fas fa-envelope" style="position: absolute; left: 20px; top: 18px; color: #cbd5e1; font-size: 0.9rem;"></i>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 35px;">
                    <label for="phone" style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">
                        WhatsApp / Telefone
                        <span style="font-weight: 500; color: #94a3b8; font-size: 0.78rem; margin-left: 6px;">usado para notificações automáticas</span>
                    </label>
                    <div style="position: relative;">
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                               placeholder="Ex: 5511999998888 (com DDI)"
                               style="width: 100%; padding: 15px 20px 15px 50px; border: 2px solid #f1f5f9; border-radius: 14px; background: #f8fafc; font-weight: 700; color: #1e293b; transition: all 0.3s;"
                               onfocus="this.style.borderColor='#25d366'; this.style.background='white';">
                        <i class="fab fa-whatsapp" style="position: absolute; left: 20px; top: 18px; color: #25d366; font-size: 1rem;"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-premium btn-premium-shine" style="width: 100%; border: none; padding: 18px; font-weight: 800; border-radius: 14px;">
                    Atualizar Registro <i class="fas fa-save ms-2"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Security Card -->
    <div class="col-md-6">
        <div class="vivensi-card" style="padding: 40px; border-radius: 28px; background: white; border: 1px solid #f1f5f9; box-shadow: 0 15px 45px rgba(0,0,0,0.02);">
             <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 30px;">
                <div style="width: 48px; height: 48px; background: #fef2f2; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 1.2rem; border: 1px solid #fee2e2;">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <h3 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 1.3rem; letter-spacing: -0.5px;">Criptografia & Acesso</h3>
            </div>
            
            <form action="{{ url('/profile/password') }}" method="POST">
                @csrf
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="current_password" style="display: block; margin-bottom: 8px; color: #1e293b; font-weight: 700; font-size: 0.85rem;">Senha Vigente</label>
                    <input type="password" name="current_password" id="current_password" required
                           style="width: 100%; padding: 14px 20px; border: 2px solid #f1f5f9; border-radius: 14px; background: #f8fafc; font-weight: 700; color: #1e293b;">
                    @error('current_password') <span style="color: #ef4444; font-size: 0.75rem; font-weight: 700; margin-top: 5px; display: block;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="password" style="display: block; margin-bottom: 8px; color: #1e293b; font-weight: 700; font-size: 0.85rem;">Nova Chave de Acesso</label>
                    <input type="password" name="password" id="password" required
                           style="width: 100%; padding: 14px 20px; border: 2px solid #f1f5f9; border-radius: 14px; background: #f8fafc; font-weight: 700; color: #1e293b;">
                    @error('password') <span style="color: #ef4444; font-size: 0.75rem; font-weight: 700; margin-top: 5px; display: block;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group" style="margin-bottom: 30px;">
                    <label for="password_confirmation" style="display: block; margin-bottom: 8px; color: #1e293b; font-weight: 700; font-size: 0.85rem;">Ratificar Nova Senha</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                           style="width: 100%; padding: 14px 20px; border: 2px solid #f1f5f9; border-radius: 14px; background: #f8fafc; font-weight: 700; color: #1e293b;">
                </div>
                
                <button type="submit" class="btn-premium" style="width: 100%; border: none; padding: 18px; font-weight: 800; border-radius: 14px; background: #1e293b; color: white;">
                    Redefinir Segurança <i class="fas fa-lock ms-2"></i>
                </button>
            </form>
        </div>
    </div>

</div>

{{-- ── LGPD: Direitos do Titular ──────────────────────────────────────── --}}
<div class="row g-5 mt-2">
    <div class="col-12">
        <div class="vivensi-card" style="padding: 40px; border-radius: 28px; background: white; border: 1px solid #fef3c7; box-shadow: 0 15px 45px rgba(0,0,0,0.02);">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 8px;">
                <div style="width: 48px; height: 48px; background: #fffbeb; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #d97706; font-size: 1.2rem; border: 1px solid #fde68a;">
                    <i class="fas fa-scale-balanced"></i>
                </div>
                <div>
                    <h3 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 1.2rem; letter-spacing: -0.5px;">Privacidade & Direitos LGPD</h3>
                    <p style="margin: 2px 0 0 0; color: #64748b; font-size: 0.82rem;">Lei Geral de Proteção de Dados — Art. 18, Lei 13.709/2018</p>
                </div>
            </div>

            @if(session('lgpd_success'))
                <div style="background: #ecfdf5; color: #065f46; padding: 16px 20px; border-radius: 12px; margin: 20px 0; border: 1px solid #a7f3d0; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle"></i> {{ session('lgpd_success') }}
                </div>
            @endif
            @if(session('lgpd_info'))
                <div style="background: #eff6ff; color: #1d4ed8; padding: 16px 20px; border-radius: 12px; margin: 20px 0; border: 1px solid #bfdbfe; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-info-circle"></i> {{ session('lgpd_info') }}
                </div>
            @endif

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 28px;">

                {{-- Exportar dados --}}
                <div style="background: #f8fafc; border-radius: 18px; padding: 28px; border: 1px solid #e2e8f0;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
                        <div style="width: 40px; height: 40px; background: #eff6ff; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #3b82f6; font-size: 1rem;">
                            <i class="fas fa-download"></i>
                        </div>
                        <strong style="color: #1e293b; font-size: 0.95rem;">Exportar meus dados</strong>
                    </div>
                    <p style="color: #64748b; font-size: 0.82rem; line-height: 1.6; margin: 0 0 20px 0;">
                        Baixe uma cópia completa de todos os seus dados pessoais armazenados na plataforma (portabilidade — Art. 18, V).
                    </p>
                    <a href="{{ route('profile.export') }}"
                       style="display: inline-flex; align-items: center; gap: 8px; background: #3b82f6; color: white; padding: 12px 20px; border-radius: 10px; font-weight: 700; font-size: 0.85rem; text-decoration: none; transition: background 0.2s;"
                       onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
                        <i class="fas fa-file-arrow-down"></i> Baixar JSON com meus dados
                    </a>
                </div>

                {{-- Solicitar exclusão --}}
                <div style="background: #fff5f5; border-radius: 18px; padding: 28px; border: 1px solid #fee2e2;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
                        <div style="width: 40px; height: 40px; background: #fef2f2; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 1rem;">
                            <i class="fas fa-user-slash"></i>
                        </div>
                        <strong style="color: #1e293b; font-size: 0.95rem;">Solicitar exclusão da conta</strong>
                    </div>
                    <p style="color: #64748b; font-size: 0.82rem; line-height: 1.6; margin: 0 0 16px 0;">
                        Solicite o apagamento permanente dos seus dados pessoais (Art. 18, VI). Processado em até 15 dias úteis.
                    </p>
                    <button onclick="document.getElementById('lgpd-delete-modal').style.display='flex'"
                            style="display: inline-flex; align-items: center; gap: 8px; background: #ef4444; color: white; padding: 12px 20px; border-radius: 10px; font-weight: 700; font-size: 0.85rem; border: none; cursor: pointer; transition: background 0.2s;"
                            onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                        <i class="fas fa-trash-alt"></i> Solicitar exclusão
                    </button>
                </div>
            </div>

            <p style="color: #94a3b8; font-size: 0.75rem; margin: 20px 0 0 0; text-align: center;">
                Dúvidas sobre privacidade? Entre em contato com nosso DPO: <strong>privacidade@vivensi.com.br</strong>
            </p>
        </div>
    </div>
</div>

{{-- Modal de confirmação de exclusão --}}
<div id="lgpd-delete-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:white; border-radius:24px; padding:40px; max-width:480px; width:100%; box-shadow:0 25px 60px rgba(0,0,0,0.25);">
        <div style="text-align:center; margin-bottom:24px;">
            <div style="width:64px; height:64px; background:#fef2f2; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; color:#ef4444; font-size:1.5rem;">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <h3 style="margin:0 0 8px; color:#1e293b; font-weight:900; font-size:1.3rem;">Confirmar exclusão permanente</h3>
            <p style="color:#64748b; font-size:0.88rem; line-height:1.6; margin:0;">
                Esta ação é <strong>irreversível</strong>. Todos os seus dados pessoais serão permanentemente removidos em até 15 dias úteis após confirmação da nossa equipe.
            </p>
        </div>

        <form action="{{ route('profile.delete') }}" method="POST">
            @csrf
            <div style="margin-bottom:20px;">
                <label for="confirm_phrase" style="display:block; margin-bottom:8px; font-weight:700; font-size:0.85rem; color:#1e293b;">
                    Digite exatamente: <code style="background:#fef2f2; color:#ef4444; padding:2px 6px; border-radius:4px;">EXCLUIR MINHA CONTA</code>
                </label>
                <input type="text" name="confirm_phrase" id="confirm_phrase" required autocomplete="off"
                       placeholder="EXCLUIR MINHA CONTA"
                       style="width:100%; padding:14px 18px; border:2px solid #fee2e2; border-radius:12px; font-size:0.9rem; font-weight:700; color:#1e293b; box-sizing:border-box;"
                       onfocus="this.style.borderColor='#ef4444'" onblur="this.style.borderColor='#fee2e2'">
                @error('confirm_phrase')<span style="color:#ef4444;font-size:0.75rem;font-weight:700;display:block;margin-top:5px;">{{ $message }}</span>@enderror
            </div>
            <div style="margin-bottom:24px;">
                <label for="reason" style="display:block; margin-bottom:8px; font-weight:700; font-size:0.85rem; color:#1e293b;">Motivo (opcional)</label>
                <textarea name="reason" id="reason" rows="2" placeholder="Ex: Não utilizo mais a plataforma."
                          style="width:100%; padding:12px 16px; border:2px solid #f1f5f9; border-radius:12px; font-size:0.85rem; resize:vertical; box-sizing:border-box;"></textarea>
            </div>
            <div style="display:flex; gap:12px;">
                <button type="button" onclick="document.getElementById('lgpd-delete-modal').style.display='none'"
                        style="flex:1; padding:14px; border-radius:12px; border:2px solid #e2e8f0; background:white; font-weight:700; font-size:0.9rem; cursor:pointer; color:#64748b;">
                    Cancelar
                </button>
                <button type="submit"
                        style="flex:1; padding:14px; border-radius:12px; border:none; background:#ef4444; color:white; font-weight:800; font-size:0.9rem; cursor:pointer;">
                    Confirmar solicitação
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===== Atividade de Login ===== --}}
@if($loginActivities->isNotEmpty())
<div style="background: white; border-radius: 20px; padding: 32px; border: 1px solid #e2e8f0; box-shadow: 0 2px 15px rgba(0,0,0,0.06); margin-top: 28px;">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:22px;">
        <div style="width:36px; height:36px; background:rgba(99,102,241,0.1); border-radius:10px; display:flex; align-items:center; justify-content:center;">
            <i class="fas fa-shield-halved" style="color:#6366f1;"></i>
        </div>
        <div>
            <h4 style="margin:0; font-weight:900; font-size:1rem; color:#1e293b;">Atividade de Acesso</h4>
            <p style="margin:0; font-size:0.75rem; color:#94a3b8;">Últimos 10 acessos à sua conta</p>
        </div>
    </div>
    <div style="display:flex; flex-direction:column; gap:8px;">
        @foreach($loginActivities as $activity)
        @php
            $icons = ['Desktop'=>'fa-desktop','Mobile'=>'fa-mobile-alt','Tablet'=>'fa-tablet-alt'];
            $icon  = $icons[$activity->device] ?? 'fa-desktop';
            $isRecent = $activity->logged_in_at->diffInHours(now()) < 1;
        @endphp
        <div style="display:flex; align-items:center; gap:14px; padding:12px 16px; background:{{ $isRecent ? 'rgba(16,185,129,0.05)' : '#f8fafc' }}; border-radius:12px; border:1px solid {{ $isRecent ? 'rgba(16,185,129,0.2)' : '#e2e8f0' }};">
            <div style="width:32px; height:32px; background:rgba(99,102,241,0.08); border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fas {{ $icon }}" style="color:#6366f1; font-size:0.85rem;"></i>
            </div>
            <div style="flex:1; min-width:0;">
                <div style="font-size:0.82rem; font-weight:700; color:#1e293b;">
                    {{ $activity->browser }} em {{ $activity->platform }}
                    @if($isRecent) <span style="color:#10b981; font-size:0.7rem; font-weight:800; margin-left:6px;">● Recente</span> @endif
                </div>
                <div style="font-size:0.72rem; color:#94a3b8; margin-top:2px;">
                    <i class="fas fa-map-marker-alt me-1"></i>{{ $activity->ip_address ?? '—' }}
                    <span style="margin:0 6px;">·</span>
                    {{ $activity->logged_in_at->diffForHumans() }}
                </div>
            </div>
            <div style="font-size:0.65rem; color:{{ $activity->success ? '#10b981' : '#ef4444' }}; font-weight:800; text-transform:uppercase; flex-shrink:0;">
                {{ $activity->success ? 'Sucesso' : 'Falha' }}
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection

