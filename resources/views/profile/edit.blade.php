@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 40px; position: relative;">
    <div style="background: linear-gradient(135deg, rgba(71, 85, 105, 0.1) 0%, rgba(30, 41, 59, 0.05) 100%); position: absolute; top: -30px; left: -30px; right: -30px; bottom: 0; z-index: -1;"></div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: #475569; width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: #475569; font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Configurações de Identidade</h6>
            </div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.5rem; letter-spacing: -1.5px;">Centro de Perfil</h2>
            <p style="color: #64748b; margin: 8px 0 0 0; font-size: 1.1rem; font-weight: 500;">Gerencie suas credenciais e preferências de segurança.</p>
        </div>
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
                    <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Nome Completo</label>
                    <div style="position: relative;">
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required 
                               style="width: 100%; padding: 15px 20px 15px 50px; border: 2px solid #f1f5f9; border-radius: 14px; background: #f8fafc; font-weight: 700; color: #1e293b; transition: all 0.3s;"
                               onfocus="this.style.borderColor='var(--primary-color)'; this.style.background='white';">
                        <i class="fas fa-user" style="position: absolute; left: 20px; top: 18px; color: #cbd5e1; font-size: 0.9rem;"></i>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 35px;">
                    <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Endereço de E-mail</label>
                    <div style="position: relative;">
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required 
                               style="width: 100%; padding: 15px 20px 15px 50px; border: 2px solid #f1f5f9; border-radius: 14px; background: #f8fafc; font-weight: 700; color: #1e293b; transition: all 0.3s;"
                               onfocus="this.style.borderColor='var(--primary-color)'; this.style.background='white';">
                        <i class="fas fa-envelope" style="position: absolute; left: 20px; top: 18px; color: #cbd5e1; font-size: 0.9rem;"></i>
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
                    <label style="display: block; margin-bottom: 8px; color: #1e293b; font-weight: 700; font-size: 0.85rem;">Senha Vigente</label>
                    <input type="password" name="current_password" required 
                           style="width: 100%; padding: 14px 20px; border: 2px solid #f1f5f9; border-radius: 14px; background: #f8fafc; font-weight: 700; color: #1e293b;">
                    @error('current_password') <span style="color: #ef4444; font-size: 0.75rem; font-weight: 700; margin-top: 5px; display: block;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; color: #1e293b; font-weight: 700; font-size: 0.85rem;">Nova Chave de Acesso</label>
                    <input type="password" name="password" required 
                           style="width: 100%; padding: 14px 20px; border: 2px solid #f1f5f9; border-radius: 14px; background: #f8fafc; font-weight: 700; color: #1e293b;">
                    @error('password') <span style="color: #ef4444; font-size: 0.75rem; font-weight: 700; margin-top: 5px; display: block;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group" style="margin-bottom: 30px;">
                    <label style="display: block; margin-bottom: 8px; color: #1e293b; font-weight: 700; font-size: 0.85rem;">Ratificar Nova Senha</label>
                    <input type="password" name="password_confirmation" required 
                           style="width: 100%; padding: 14px 20px; border: 2px solid #f1f5f9; border-radius: 14px; background: #f8fafc; font-weight: 700; color: #1e293b;">
                </div>
                
                <button type="submit" class="btn-premium" style="width: 100%; border: none; padding: 18px; font-weight: 800; border-radius: 14px; background: #1e293b; color: white;">
                    Redefinir Segurança <i class="fas fa-lock ms-2"></i>
                </button>
            </form>
        </div>
    </div>

</div>
@endsection

