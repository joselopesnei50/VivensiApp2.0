@extends('layouts.app')

@section('content')
<div style="max-width: 540px; margin: 0 auto;">
    <div style="margin-bottom: 28px;">
        <a href="{{ url('/profile') }}" style="color:#6366f1; font-size:0.8rem; font-weight:700; text-decoration:none;">
            <i class="fas fa-arrow-left me-1"></i> Voltar ao Perfil
        </a>
        <h2 style="margin:12px 0 4px; font-weight:950; font-size:1.8rem; letter-spacing:-1px;">
            Autenticação em Dois Fatores
        </h2>
        <p style="color:#64748b; font-size:0.9rem;">Proteja sua conta com um segundo fator via app autenticador.</p>
    </div>

    {{-- Status --}}
    <div style="background:white; border-radius:20px; padding:28px; border:1px solid #e2e8f0; box-shadow:0 2px 15px rgba(0,0,0,0.05); margin-bottom:20px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:44px; height:44px; border-radius:14px; background:{{ $confirmed ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.08)' }}; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fas {{ $confirmed ? 'fa-shield-check' : 'fa-shield-xmark' }}" style="color:{{ $confirmed ? '#10b981' : '#ef4444' }}; font-size:1.2rem;"></i>
            </div>
            <div>
                <div style="font-weight:900; font-size:1rem; color:#1e293b;">
                    2FA está <span style="color:{{ $confirmed ? '#10b981' : '#ef4444' }}">{{ $confirmed ? 'ATIVADO' : 'DESATIVADO' }}</span>
                </div>
                <div style="font-size:0.8rem; color:#94a3b8; margin-top:2px;">
                    {{ $confirmed ? 'Sua conta está protegida com autenticação em dois fatores.' : 'Adicione uma camada extra de segurança à sua conta.' }}
                </div>
            </div>
        </div>
    </div>

    @if(!$confirmed && !$setupSecret)
    {{-- Enable 2FA --}}
    <div style="background:white; border-radius:20px; padding:28px; border:1px solid #e2e8f0; box-shadow:0 2px 15px rgba(0,0,0,0.05); margin-bottom:20px;">
        <h4 style="font-weight:900; color:#1e293b; margin:0 0 12px;">Como funciona</h4>
        <ol style="color:#64748b; font-size:0.85rem; line-height:2; padding-left:20px; margin-bottom:20px;">
            <li>Instale um app como <strong>Google Authenticator</strong> ou <strong>Authy</strong></li>
            <li>Clique em "Ativar 2FA" e escaneie o QR Code</li>
            <li>Digite o código de 6 dígitos para confirmar</li>
            <li>Guarde os códigos de recuperação em local seguro</li>
        </ol>
        <form method="POST" action="{{ route('2fa.enable') }}">
            @csrf
            <button type="submit" style="width:100%; background:linear-gradient(135deg,#6366f1,#4f46e5); color:white; border:none; border-radius:14px; padding:14px; font-weight:900; font-size:0.95rem; cursor:pointer; box-shadow:0 8px 24px rgba(99,102,241,0.2);">
                <i class="fas fa-shield-plus me-2"></i> Ativar 2FA
            </button>
        </form>
    </div>

    @elseif(!$confirmed && $setupSecret)
    {{-- QR Code setup --}}
    <div style="background:white; border-radius:20px; padding:28px; border:1px solid #e2e8f0; box-shadow:0 2px 15px rgba(0,0,0,0.05); margin-bottom:20px;">
        <h4 style="font-weight:900; color:#1e293b; margin:0 0 16px;"><i class="fas fa-qrcode me-2" style="color:#6366f1;"></i>Escaneie o QR Code</h4>
        <p style="color:#64748b; font-size:0.85rem; margin-bottom:16px;">Abra seu app autenticador e escaneie o código abaixo:</p>
        <div style="text-align:center; margin-bottom:20px;">
            <img loading="lazy" src="{{ $qrCodeUrl }}" alt="QR Code 2FA" style="border-radius:12px; border:3px solid #e2e8f0; width:180px; height:180px;">
        </div>
        <div style="background:#f8fafc; border-radius:12px; padding:14px; margin-bottom:20px; text-align:center;">
            <div style="font-size:0.7rem; color:#94a3b8; font-weight:700; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px;">Chave manual</div>
            <code style="font-size:0.85rem; color:#1e293b; font-weight:800; letter-spacing:2px;">{{ $setupSecret }}</code>
        </div>
        <form method="POST" action="{{ route('2fa.confirm') }}">
            @csrf
            <div style="margin-bottom:14px;">
                <label for="code" style="display:block; margin-bottom:8px; color:#475569; font-weight:700; font-size:0.85rem;">Código de verificação</label>
                <input type="text" name="code" required placeholder="000 000" maxlength="7" inputmode="numeric" autocomplete="one-time-code"
                    style="width:100%; padding:14px 18px; border:2px solid #e2e8f0; border-radius:14px; font-size:1.4rem; font-weight:900; text-align:center; letter-spacing:6px; color:#1e293b;"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'" id="code">
            </div>
            <button type="submit" style="width:100%; background:linear-gradient(135deg,#10b981,#059669); color:white; border:none; border-radius:14px; padding:14px; font-weight:900; font-size:0.95rem; cursor:pointer;">
                <i class="fas fa-check me-2"></i> Confirmar e Ativar
            </button>
        </form>
    </div>

    @elseif($confirmed)
    {{-- Recovery codes + disable --}}
    @php $codes = auth()->user()->two_factor_recovery_codes ?? []; @endphp
    @if(!empty($codes))
    <div style="background:#fefce8; border-radius:20px; padding:28px; border:1px solid #fde047; margin-bottom:20px;">
        <h4 style="font-weight:900; color:#854d0e; margin:0 0 8px;"><i class="fas fa-key me-2"></i>Códigos de Recuperação</h4>
        <p style="color:#854d0e; font-size:0.82rem; margin-bottom:16px;">Guarde estes códigos em local seguro. Cada código só pode ser usado uma vez.</p>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
            @foreach($codes as $code)
            <code style="background:white; border:1px solid #fde047; border-radius:8px; padding:8px 12px; font-size:0.8rem; font-weight:800; color:#1e293b; text-align:center;">{{ $code }}</code>
            @endforeach
        </div>
    </div>
    @endif

    <div style="background:white; border-radius:20px; padding:28px; border:1px solid #fecaca; box-shadow:0 2px 15px rgba(0,0,0,0.05);">
        <h4 style="font-weight:900; color:#dc2626; margin:0 0 12px;">Desativar 2FA</h4>
        <p style="color:#64748b; font-size:0.85rem; margin-bottom:16px;">Para desativar, confirme sua senha atual:</p>
        <form method="POST" action="{{ route('2fa.disable') }}" onsubmit="return confirm('Tem certeza? Sua conta ficará menos segura.')">
            @csrf @method('DELETE')
            <input type="password" name="password" required placeholder="Senha atual"
                style="width:100%; padding:12px 16px; border:2px solid #e2e8f0; border-radius:12px; font-size:0.9rem; margin-bottom:12px; color:#1e293b;"
                onfocus="this.style.borderColor='#ef4444'" onblur="this.style.borderColor='#e2e8f0'" id="password">
            <button type="submit" style="width:100%; background:rgba(239,68,68,0.1); color:#dc2626; border:1px solid rgba(239,68,68,0.3); border-radius:12px; padding:12px; font-weight:900; cursor:pointer;">
                <i class="fas fa-shield-xmark me-2"></i> Desativar 2FA
            </button>
        </form>
    </div>
    @endif
</div>
@endsection
