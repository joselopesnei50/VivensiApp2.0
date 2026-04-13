@extends('emails.layout')

@section('title', 'Reunião Confirmada – Vivensi')

@section('content')
<table width="100%" cellpadding="0" cellspacing="0">
    {{-- Header accent --}}
    <tr>
        <td style="background:#4F6EF7;padding:28px 40px;">
            <p style="margin:0;font-size:1.1rem;font-weight:800;color:#fff;letter-spacing:-.3px;">Vivensi</p>
        </td>
    </tr>

    {{-- Body --}}
    <tr>
        <td style="padding:40px 40px 32px;">
            <p style="margin:0 0 6px;font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#4F6EF7;">Agendamento Confirmado</p>
            <h1 style="margin:0 0 24px;font-size:1.5rem;font-weight:900;color:#0f172a;line-height:1.2;">
                Sua reunião está marcada,<br>{{ $booking->name }}!
            </h1>

            {{-- Card de detalhes --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;border-radius:12px;overflow:hidden;margin-bottom:28px;">
                <tr>
                    <td style="padding:20px 24px;border-bottom:1px solid #e2e8f0;">
                        <p style="margin:0 0 3px;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#94a3b8;">Data</p>
                        <p style="margin:0;font-size:.95rem;font-weight:800;color:#0f172a;text-transform:capitalize;">{{ $formattedDate }}</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 24px;border-bottom:1px solid #e2e8f0;">
                        <p style="margin:0 0 3px;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#94a3b8;">Horário</p>
                        <p style="margin:0;font-size:.95rem;font-weight:800;color:#0f172a;">{{ $booking->meeting_time }} (Brasília)</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 24px;border-bottom:1px solid #e2e8f0;">
                        <p style="margin:0 0 3px;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#94a3b8;">Duração</p>
                        <p style="margin:0;font-size:.95rem;font-weight:800;color:#0f172a;">30 minutos</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 24px;">
                        <p style="margin:0 0 3px;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#94a3b8;">Formato</p>
                        <p style="margin:0;font-size:.95rem;font-weight:800;color:#0f172a;">Videoconferência — link enviado em breve</p>
                    </td>
                </tr>
            </table>

            @if($booking->notes)
            <p style="margin:0 0 6px;font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Suas observações</p>
            <p style="margin:0 0 28px;font-size:.9rem;color:#475569;background:#f8fafc;border-left:3px solid #4F6EF7;padding:12px 16px;border-radius:0 8px 8px 0;">{{ $booking->notes }}</p>
            @endif

            <p style="margin:0 0 28px;font-size:.88rem;color:#64748b;line-height:1.6;">
                Nossa equipe entrará em contato com o link de videoconferência até 24 horas antes da reunião. Se precisar cancelar, clique no botão abaixo.
            </p>

            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding-right:10px;">
                        <a href="{{ $cancelUrl }}" style="display:inline-block;padding:11px 24px;background:#f1f5f9;color:#475569;font-size:.8rem;font-weight:700;border-radius:8px;text-decoration:none;border:1px solid #e2e8f0;">
                            Cancelar reunião
                        </a>
                    </td>
                    <td>
                        <a href="{{ url('/') }}" style="display:inline-block;padding:11px 24px;background:#4F6EF7;color:#fff;font-size:.8rem;font-weight:700;border-radius:8px;text-decoration:none;">
                            Visitar Vivensi
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Footer --}}
    <tr>
        <td style="padding:20px 40px;border-top:1px solid #f1f5f9;">
            <p style="margin:0;font-size:.72rem;color:#94a3b8;line-height:1.6;">
                Este e-mail foi enviado porque você agendou uma reunião em vivensi.com.br.<br>
                &copy; {{ date('Y') }} Vivensi — Todos os direitos reservados.
            </p>
        </td>
    </tr>
</table>
@endsection
