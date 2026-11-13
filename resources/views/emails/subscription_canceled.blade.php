@extends('emails.layout')

@section('title', 'Cancelamento confirmado')

@section('content')
@php $org = $tenant->brand_name ?: ($tenant->razao_social ?: $tenant->name); $ret = \App\Services\Billing\SubscriptionCancellation::RETENTION_DAYS; @endphp
<tr>
    <td style="padding: 36px 40px 0;">
        <div style="display:inline-block;background:#f1f5f9;color:#334155;font-size:0.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:5px 14px;border-radius:20px;margin-bottom:16px;">
            Cancelamento confirmado
        </div>
        <h1 style="margin:0 0 10px;font-size:1.3rem;font-weight:800;color:#0f172a;line-height:1.3;">
            A assinatura de {{ $org }} foi cancelada
        </h1>
        @if($refund)
            <p style="margin:0 0 18px;font-size:0.95rem;color:#475569;line-height:1.65;">
                Como o cancelamento foi feito em até 7 dias da contratação, você recebe a <strong>devolução integral</strong> do valor pago.
                Nossa equipe faz o estorno pela AbacatePay e avisa por e-mail quando estiver concluído. O acesso ao painel foi encerrado hoje.
            </p>
        @else
            <p style="margin:0 0 18px;font-size:0.95rem;color:#475569;line-height:1.65;">
                Não haverá novas cobranças. O painel continua liberado até <strong>{{ $accessUntil->format('d/m/Y') }}</strong>, o fim do período já pago.
                Até lá, você pode desistir do cancelamento em Minha conta → Faturas.
            </p>
        @endif
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin:0 0 22px;">
            <tr><td style="padding:16px 18px;font-size:0.9rem;color:#334155;line-height:1.6;">
                <strong>Seus dados:</strong> ficam guardados por {{ $ret }} dias depois do fim do acesso. Nesse prazo você pode reativar a conta ou exportar os dados.
                Depois disso, são apagados de forma definitiva.
            </td></tr>
        </table>
        <p style="margin:0 0 30px;font-size:0.85rem;color:#64748b;line-height:1.6;">
            Ficou alguma dúvida ou foi um engano? Responda este e-mail ou escreva para <a href="mailto:{{ config('legal.email_support') }}" style="color:#2563eb;">{{ config('legal.email_support') }}</a>.
        </p>
    </td>
</tr>
@endsection
