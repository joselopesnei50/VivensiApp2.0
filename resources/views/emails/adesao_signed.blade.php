@extends('emails.layout')

@section('title', 'Contrato de adesão assinado')

@section('content')
<tr>
    <td style="padding: 36px 40px 0;">
        <div style="display:inline-block;background:#dcfce7;color:#15803d;font-size:0.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:5px 14px;border-radius:20px;margin-bottom:16px;">
            Contrato assinado
        </div>
        <h1 style="margin:0 0 10px;font-size:1.3rem;font-weight:800;color:#0f172a;line-height:1.3;">
            Recebemos a assinatura do seu contrato de adesão
        </h1>
        <p style="margin:0 0 18px;font-size:0.95rem;color:#475569;line-height:1.65;">
            Olá, <strong>{{ $recipientName }}</strong>! O contrato de adesão de <strong>{{ $tenant->brand_name ?: ($tenant->razao_social ?: $tenant->name) }}</strong>
            à Vivensi foi assinado em {{ optional($contract->signed_at)->format('d/m/Y \à\s H:i') }} por {{ $contract->signer_name }}.
        </p>
        @if($plan)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin:0 0 22px;">
            <tr><td style="padding:16px 18px;font-size:0.9rem;color:#334155;line-height:1.6;">
                <strong>Plano:</strong> {{ $plan->name }}<br>
                @unless($plan->is_courtesy)<strong>Valor:</strong> R$ {{ number_format((float) $plan->price, 2, ',', '.') }}@endunless
            </td></tr>
        </table>
        @endif
        @if($plan && !$plan->is_courtesy)
            <p style="margin:0 0 22px;font-size:0.95rem;color:#475569;line-height:1.65;">
                <strong>Próximo passo:</strong> concluir o pagamento para liberar o acesso. Assim que o PIX for confirmado, o painel é liberado automaticamente e você recebe outro e-mail.
            </p>
            <p style="margin:0 0 28px;"><a href="{{ route('checkout.index', ['plan_id' => $plan->id]) }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:700;padding:13px 26px;border-radius:10px;">Ir para o pagamento</a></p>
        @else
            <p style="margin:0 0 28px;"><a href="{{ url('/dashboard') }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:700;padding:13px 26px;border-radius:10px;">Acessar o painel</a></p>
        @endif
        <p style="margin:0 0 30px;font-size:0.85rem;color:#64748b;line-height:1.6;">
            A cópia do contrato fica disponível no painel: <a href="{{ route('adesao.download', $contract->id) }}" style="color:#2563eb;">baixar contrato (PDF)</a>.
        </p>
    </td>
</tr>
@endsection
