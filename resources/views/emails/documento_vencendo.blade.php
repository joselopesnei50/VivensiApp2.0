@extends('emails.layout')

@section('title', 'Alerta de Documento — Conformidade')

@section('content')
<tr>
    <td style="padding: 36px 40px 0;">
        @if($diasRestantes <= 0)
        <div style="width:56px;height:56px;background:linear-gradient(135deg,#dc2626,#b91c1c);border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:24px;">
            <span style="font-size:1.6rem;">🚨</span>
        </div>
        <div style="display:inline-block;background:#fee2e2;color:#dc2626;font-size:0.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:5px 14px;border-radius:20px;margin-bottom:16px;">
            VENCIDO — Ação imediata necessária
        </div>
        @elseif($diasRestantes <= 7)
        <div style="width:56px;height:56px;background:linear-gradient(135deg,#dc2626,#b91c1c);border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:24px;">
            <span style="font-size:1.6rem;">🚨</span>
        </div>
        <div style="display:inline-block;background:#fee2e2;color:#dc2626;font-size:0.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:5px 14px;border-radius:20px;margin-bottom:16px;">
            URGENTE — Vence em {{ $diasRestantes }} dia(s)
        </div>
        @else
        <div style="width:56px;height:56px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:24px;">
            <span style="font-size:1.6rem;">⚠️</span>
        </div>
        <div style="display:inline-block;background:#fef3c7;color:#d97706;font-size:0.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:5px 14px;border-radius:20px;margin-bottom:16px;">
            Atenção — Vence em {{ $diasRestantes }} dias
        </div>
        @endif

        <h1 style="margin:0 0 10px;font-size:1.3rem;font-weight:800;color:#0f172a;line-height:1.3;">
            Documento de conformidade precisa de renovação
        </h1>
        <p style="margin:0 0 24px;font-size:0.95rem;color:#475569;line-height:1.65;">
            Olá, <strong>{{ $admin->name }}</strong>! Um documento exigido para conformidade legal
            @if($diasRestantes <= 0)
                <strong style="color:#dc2626;">está vencido</strong>.
            @else
                vence em <strong>{{ $diasRestantes }} dia(s)</strong>.
            @endif
            Faça o upload da versão atualizada para manter o índice de conformidade verde.
        </p>
    </td>
</tr>

<tr>
    <td style="padding: 0 40px;">
        <table style="width:100%;border-collapse:collapse;background:#f8fafc;border-radius:14px;overflow:hidden;border:1px solid #e2e8f0;">
            <tr>
                <td style="padding:14px 18px;border-bottom:1px solid #e2e8f0;">
                    <div style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px;">Tipo de Documento</div>
                    <div style="font-size:1rem;font-weight:700;color:#1e293b;">{{ $regra->tipo_documento_obrigatorio }}</div>
                </td>
            </tr>
            <tr>
                <td style="padding:14px 18px;border-bottom:1px solid #e2e8f0;">
                    <div style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px;">Arquivo atual</div>
                    <div style="font-size:0.95rem;font-weight:600;color:#1e293b;">{{ $documento->original_name }}</div>
                </td>
            </tr>
            <tr>
                <td style="padding:14px 18px;">
                    <div style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px;">Validade</div>
                    <div style="font-size:0.95rem;font-weight:700;color:{{ $diasRestantes <= 7 ? '#dc2626' : '#d97706' }};">
                        {{ $documento->valid_until?->format('d/m/Y') }}
                        ({{ $diasRestantes <= 0 ? 'vencido' : "em {$diasRestantes} dias" }})
                    </div>
                </td>
            </tr>
        </table>
    </td>
</tr>

<tr>
    <td style="padding: 28px 40px 36px;">
        <a href="{{ url('/ngo/conformidade') }}"
           style="display:inline-block;background:linear-gradient(135deg,#10b981,#059669);color:#fff;text-decoration:none;font-weight:700;font-size:0.95rem;padding:14px 28px;border-radius:12px;box-shadow:0 4px 16px rgba(16,185,129,0.35);">
            Renovar documento agora
        </a>
        <p style="margin:18px 0 0;font-size:0.82rem;color:#94a3b8;line-height:1.55;">
            Acesse <a href="{{ url('/ngo/conformidade') }}" style="color:#10b981;text-decoration:none;">Conformidade Contínua</a>
            e faça o upload do documento atualizado para regularizar a situação.
        </p>
    </td>
</tr>
@endsection
