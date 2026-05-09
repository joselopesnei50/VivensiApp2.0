@extends('emails.layout')

@section('title', 'Alerta de Deadline — Edital')

@section('content')
<tr>
    <td style="padding: 36px 40px 0;">
        @if($daysLeft <= 1)
        <div style="width:56px;height:56px;background:linear-gradient(135deg,#dc2626,#b91c1c);border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:24px;">
            <span style="font-size:1.6rem;">🚨</span>
        </div>
        <div style="display:inline-block;background:#fee2e2;color:#dc2626;font-size:0.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:5px 14px;border-radius:20px;margin-bottom:16px;">
            URGENTE — Vence amanhã!
        </div>
        @else
        <div style="width:56px;height:56px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:24px;">
            <span style="font-size:1.6rem;">⚠️</span>
        </div>
        <div style="display:inline-block;background:#fef3c7;color:#d97706;font-size:0.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:5px 14px;border-radius:20px;margin-bottom:16px;">
            Prazo se aproximando — {{ $daysLeft }} dias restantes
        </div>
        @endif

        <h1 style="margin:0 0 10px;font-size:1.3rem;font-weight:800;color:#0f172a;line-height:1.3;">
            Deadline do edital se aproxima
        </h1>
        <p style="margin:0 0 24px;font-size:0.95rem;color:#475569;line-height:1.65;">
            Olá, <strong>{{ $manager->name }}</strong>! O edital abaixo vence
            @if($daysLeft <= 1)
                <strong style="color:#dc2626;">amanhã</strong>.
            @else
                em <strong>{{ $daysLeft }} dias</strong>.
            @endif
            Verifique se toda a documentação está em ordem.
        </p>
    </td>
</tr>

{{-- Grant details --}}
<tr>
    <td style="padding: 0 40px;">
        <table style="width:100%;border-collapse:collapse;background:#f8fafc;border-radius:14px;overflow:hidden;border:1px solid #e2e8f0;">
            <tr>
                <td style="padding:14px 18px;border-bottom:1px solid #e2e8f0;">
                    <div style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px;">Edital</div>
                    <div style="font-size:1rem;font-weight:700;color:#1e293b;">{{ $grant->title }}</div>
                </td>
            </tr>
            <tr>
                <td style="padding:14px 18px;border-bottom:1px solid #e2e8f0;">
                    <div style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px;">Órgão Concedente</div>
                    <div style="font-size:0.95rem;font-weight:600;color:#1e293b;">{{ $grant->agency }}</div>
                </td>
            </tr>
            <tr>
                <td style="padding:14px 18px;border-bottom:1px solid #e2e8f0;">
                    <div style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px;">Valor do Edital</div>
                    <div style="font-size:1.1rem;font-weight:900;color:#059669;">
                        R$ {{ number_format($grant->value ?? 0, 2, ',', '.') }}
                    </div>
                </td>
            </tr>
            <tr>
                <td style="padding:14px 18px;">
                    <div style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px;">Prazo Final</div>
                    <div style="font-size:0.95rem;font-weight:700;color:{{ $daysLeft <= 1 ? '#dc2626' : '#d97706' }};">
                        {{ $grant->deadline?->format('d/m/Y') ?? '—' }}
                        ({{ $daysLeft <= 1 ? 'amanhã' : "em {$daysLeft} dias" }})
                    </div>
                </td>
            </tr>
        </table>
    </td>
</tr>

{{-- CTA --}}
<tr>
    <td style="padding: 28px 40px 36px;">
        <a href="{{ url('/ngo/grants') }}"
           style="display:inline-block;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;text-decoration:none;font-weight:700;font-size:0.95rem;padding:14px 28px;border-radius:12px;box-shadow:0 4px 16px rgba(79,70,229,0.35);">
            📋 Ver edital no sistema
        </a>
        <p style="margin:18px 0 0;font-size:0.82rem;color:#94a3b8;line-height:1.55;">
            Acesse <a href="{{ url('/ngo/grants') }}" style="color:#4f46e5;text-decoration:none;">Editais & Grants</a>
            para verificar documentos, status e próximos passos.
        </p>
    </td>
</tr>
@endsection
