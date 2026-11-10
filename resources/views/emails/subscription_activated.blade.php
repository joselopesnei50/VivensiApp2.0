@extends('emails.layout')

@section('title', 'Pagamento confirmado')

@section('content')
<tr>
    <td style="padding: 36px 40px 0;">
        <div style="display:inline-block;background:#dcfce7;color:#15803d;font-size:0.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:5px 14px;border-radius:20px;margin-bottom:16px;">
            Pagamento confirmado
        </div>
        <h1 style="margin:0 0 10px;font-size:1.3rem;font-weight:800;color:#0f172a;line-height:1.3;">
            Sua assinatura Vivensi está ativa 🎉
        </h1>
        <p style="margin:0 0 18px;font-size:0.95rem;color:#475569;line-height:1.65;">
            Olá, <strong>{{ $recipientName }}</strong>! Confirmamos o pagamento de <strong>{{ $tenant->brand_name ?: ($tenant->razao_social ?: $tenant->name) }}</strong>.
            O acesso completo ao painel já está liberado.
        </p>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin:0 0 22px;">
            <tr><td style="padding:16px 18px;font-size:0.9rem;color:#334155;line-height:1.6;">
                @if($plan)<strong>Plano:</strong> {{ $plan->name }}<br>@endif
                @if($amount)<strong>Valor pago:</strong> R$ {{ number_format($amount, 2, ',', '.') }}<br>@endif
                <strong>Data:</strong> {{ now()->format('d/m/Y') }}
            </td></tr>
        </table>
        <p style="margin:0 0 28px;"><a href="{{ url('/dashboard') }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:700;padding:13px 26px;border-radius:10px;">Acessar o painel</a></p>
        <p style="margin:0 0 30px;font-size:0.85rem;color:#64748b;line-height:1.6;">
            O comprovante e as próximas faturas ficam em <a href="{{ route('client.invoices.index') }}" style="color:#2563eb;">Minha conta → Faturas</a>.
        </p>
    </td>
</tr>
@endsection
