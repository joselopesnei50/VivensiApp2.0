@extends('emails.layout')

@section('title', 'Despesa aguardando aprovação')

@section('content')
<tr>
    <td style="padding: 36px 40px 0;">
        <div style="width:56px;height:56px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:24px;">
            <span style="font-size:1.6rem;">⏳</span>
        </div>
        <h1 style="margin:0 0 10px;font-size:1.35rem;font-weight:800;color:#0f172a;line-height:1.3;">
            Despesa aguardando aprovação
        </h1>
        <p style="margin:0 0 24px;font-size:0.95rem;color:#475569;line-height:1.65;">
            <strong>{{ $submittedBy->name }}</strong> registrou uma despesa que requer sua aprovação antes de ser processada.
        </p>
    </td>
</tr>

{{-- Expense details box --}}
<tr>
    <td style="padding: 0 40px;">
        <table style="width:100%;border-collapse:collapse;background:#f8fafc;border-radius:14px;overflow:hidden;border:1px solid #e2e8f0;">
            <tr>
                <td style="padding:14px 18px;border-bottom:1px solid #e2e8f0;">
                    <div style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px;">Descrição</div>
                    <div style="font-size:0.95rem;font-weight:600;color:#1e293b;">{{ $transaction->description }}</div>
                </td>
            </tr>
            <tr>
                <td style="padding:14px 18px;border-bottom:1px solid #e2e8f0;">
                    <div style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px;">Valor</div>
                    <div style="font-size:1.2rem;font-weight:900;color:#dc2626;">
                        R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                    </div>
                </td>
            </tr>
            <tr>
                <td style="padding:14px 18px;border-bottom:1px solid #e2e8f0;">
                    <div style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px;">Data</div>
                    <div style="font-size:0.95rem;font-weight:600;color:#1e293b;">
                        {{ $transaction->date?->format('d/m/Y') ?? '—' }}
                    </div>
                </td>
            </tr>
            <tr>
                <td style="padding:14px 18px;">
                    <div style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px;">Lançado por</div>
                    <div style="font-size:0.95rem;font-weight:600;color:#1e293b;">{{ $submittedBy->name }}</div>
                </td>
            </tr>
        </table>
    </td>
</tr>

{{-- CTA --}}
<tr>
    <td style="padding: 28px 40px 36px;">
        <a href="{{ url('/transactions') }}"
           style="display:inline-block;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;text-decoration:none;font-weight:700;font-size:0.95rem;padding:14px 28px;border-radius:12px;box-shadow:0 4px 16px rgba(79,70,229,0.35);">
            ✅ Revisar e aprovar agora
        </a>
        <p style="margin:18px 0 0;font-size:0.82rem;color:#94a3b8;line-height:1.55;">
            Acesse <a href="{{ url('/transactions') }}" style="color:#4f46e5;text-decoration:none;">Financeiro → Transações</a>
            e filtre por "Pendente de aprovação" para revisar todos os lançamentos em aberto.
        </p>
    </td>
</tr>
@endsection
