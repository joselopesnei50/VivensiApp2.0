@extends('emails.layout')

@php
    $org = $tenant->brand_name ?: ($tenant->razao_social ?: $tenant->name);
    $badge = [
        'nova'     => ['#dbeafe', '#1d4ed8', 'Nova fatura'],
        'lembrete' => ['#fef3c7', '#b45309', 'Vence amanhã'],
        'vencida'  => ['#fee2e2', '#b91c1c', 'Fatura vencida'],
        'bloqueio' => ['#fee2e2', '#b91c1c', 'Acesso restrito'],
        'paga'     => ['#dcfce7', '#15803d', 'Pagamento recebido'],
    ][$kind] ?? ['#e2e8f0', '#334155', 'Fatura'];
    $blockDays = \App\Services\Billing\InvoiceService::BLOCK_DAYS_AFTER_DUE;
@endphp

@section('title', \App\Mail\InvoiceNoticeMail::SUBJECTS[$kind] ?? 'Fatura Vivensi')

@section('content')
<tr>
    <td style="padding: 36px 40px 0;">
        <div style="display:inline-block;background:{{ $badge[0] }};color:{{ $badge[1] }};font-size:0.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:5px 14px;border-radius:20px;margin-bottom:16px;">
            {{ $badge[2] }}
        </div>
        <h1 style="margin:0 0 10px;font-size:1.3rem;font-weight:800;color:#0f172a;line-height:1.3;">
            @switch($kind)
                @case('nova') A fatura da sua assinatura já está disponível @break
                @case('lembrete') Sua fatura vence amanhã @break
                @case('vencida') Sua fatura venceu @break
                @case('bloqueio') O acesso ao painel está restrito @break
                @case('paga') Recebemos o seu pagamento @break
            @endswitch
        </h1>
        <p style="margin:0 0 18px;font-size:0.95rem;color:#475569;line-height:1.65;">
            Olá, <strong>{{ $recipientName }}</strong>!
            @switch($kind)
                @case('nova') A mensalidade da Vivensi de <strong>{{ $org }}</strong> foi gerada. Você pode pagar por PIX em poucos segundos. @break
                @case('lembrete') Passando para lembrar que a mensalidade de <strong>{{ $org }}</strong> vence amanhã. @break
                @case('vencida') Ainda não identificamos o pagamento da mensalidade de <strong>{{ $org }}</strong>. Se o pagamento não for feito em até {{ $blockDays }} dias após o vencimento, o painel fica restrito à tela de faturas. @break
                @case('bloqueio') A mensalidade de <strong>{{ $org }}</strong> está em atraso há {{ $blockDays }} dias ou mais, e o painel ficou restrito à tela de faturas. Assim que o PIX for confirmado, o acesso volta na hora. @break
                @case('paga') Confirmamos o pagamento da mensalidade de <strong>{{ $org }}</strong>. Obrigado por seguir com a Vivensi! @break
            @endswitch
        </p>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin:0 0 22px;">
            <tr><td style="padding:16px 18px;font-size:0.9rem;color:#334155;line-height:1.6;">
                <strong>Fatura:</strong> {{ $invoice->description }}<br>
                <strong>Valor:</strong> {{ $invoice->formatted_amount }}<br>
                @if($kind === 'paga')
                    <strong>Pago em:</strong> {{ optional($invoice->paid_at)->format('d/m/Y') }}
                @else
                    <strong>Vencimento:</strong> {{ $invoice->due_date->format('d/m/Y') }}
                @endif
            </td></tr>
        </table>
        @if($kind !== 'paga')
            <p style="margin:0 0 28px;"><a href="{{ route('client.invoices.index') }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:700;padding:13px 26px;border-radius:10px;">Pagar com PIX</a></p>
            <p style="margin:0 0 30px;font-size:0.85rem;color:#64748b;line-height:1.6;">Já pagou? Desconsidere este aviso — a confirmação pode levar alguns minutos.</p>
        @else
            <p style="margin:0 0 30px;"><a href="{{ route('client.invoices.index') }}" style="color:#2563eb;font-size:0.9rem;">Ver minhas faturas</a></p>
        @endif
    </td>
</tr>
@endsection
