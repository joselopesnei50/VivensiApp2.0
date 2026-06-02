@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
        <a href="{{ route('sic.index') }}" style="color:#64748b; text-decoration:none; font-size:.9rem;">← Voltar à lista</a>
        <h2 style="margin: 6px 0 0; color: #2c3e50;">Solicitação SIC</h2>
        <p style="color: #64748b; margin: 4px 0 0; font-family:monospace;">{{ $sic->protocol }}</p>
    </div>
    <div>
        <span style="display:inline-block; padding:6px 16px; border-radius:20px; font-size:.82rem; font-weight:800; text-transform:uppercase; background:{{ $sic->status_color }}22; color:{{ $sic->status_color }};">
            {{ $sic->status_label }}
        </span>
        @if($sic->isOverdue())
            <span style="display:inline-block; margin-left:8px; padding:6px 16px; border-radius:20px; font-size:.82rem; font-weight:800; background:#fee2e2; color:#dc2626;">PRAZO VENCIDO</span>
        @endif
    </div>
</div>

<div class="grid-2" style="gap: 20px; align-items: start;">

    <!-- Request Details -->
    <div>
        <div class="vivensi-card" style="margin-bottom: 16px;">
            <div style="font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom:16px;">Dados da Solicitação</div>
            <table style="width:100%; font-size:.92rem; border-collapse:collapse;">
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:10px 0; color:#64748b; font-weight:700; width:40%;">Solicitante</td>
                    <td style="padding:10px 0; color:#1e293b; font-weight:600;">{{ $sic->requester_name }}</td>
                </tr>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:10px 0; color:#64748b; font-weight:700;">E-mail</td>
                    <td style="padding:10px 0; color:#1e293b;">{{ $sic->requester_email }}</td>
                </tr>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:10px 0; color:#64748b; font-weight:700;">Recebida em</td>
                    <td style="padding:10px 0; color:#1e293b;">{{ $sic->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:10px 0; color:#64748b; font-weight:700;">Prazo</td>
                    <td style="padding:10px 0; color:{{ $sic->isOverdue() ? '#dc2626' : '#1e293b' }}; font-weight:{{ $sic->isOverdue() ? '800' : '600' }};">
                        {{ $sic->deadline_at->format('d/m/Y') }}
                    </td>
                </tr>
                @if($sic->responded_at)
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:10px 0; color:#64748b; font-weight:700;">Respondida em</td>
                    <td style="padding:10px 0; color:#16a34a; font-weight:600;">{{ $sic->responded_at->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0; color:#64748b; font-weight:700;">Respondida por</td>
                    <td style="padding:10px 0; color:#1e293b;">{{ optional($sic->responder)->name ?? '—' }}</td>
                </tr>
                @endif
            </table>
        </div>

        <div class="vivensi-card" style="margin-bottom: 16px;">
            <div style="font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom:12px;">Assunto</div>
            <div style="font-size:1rem; font-weight:700; color:#1e293b;">{{ $sic->subject }}</div>
        </div>

        <div class="vivensi-card">
            <div style="font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom:12px;">Mensagem do Solicitante</div>
            <div style="background:#f8fafc; border-radius:8px; padding:16px; border:1px solid #e2e8f0; font-size:.93rem; line-height:1.7; white-space:pre-wrap; word-break:break-word; color:#334155;">{{ $sic->message }}</div>
        </div>
    </div>

    <!-- Response Form -->
    <div>
        @if($sic->response)
        <div class="vivensi-card" style="margin-bottom:16px; border-left: 4px solid #16a34a;">
            <div style="font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#16a34a; margin-bottom:12px;">✅ Resposta Enviada</div>
            <div style="background:#f0fdf4; border-radius:8px; padding:16px; border:1px solid #bbf7d0; font-size:.93rem; line-height:1.7; white-space:pre-wrap; word-break:break-word; color:#166534;">{{ $sic->response }}</div>
        </div>
        @endif

        <div class="vivensi-card">
            <div style="font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom:16px;">
                {{ $sic->response ? 'Atualizar Resposta' : 'Registrar Resposta' }}
            </div>

            @if(session('success'))
                <div style="background:#dcfce7; color:#16a34a; border:1px solid #bbf7d0; border-radius:8px; padding:12px 16px; font-weight:700; margin-bottom:16px;">
                    ✓ {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div style="background:#fef2f2; color:#dc2626; border:1px solid #fecaca; border-radius:8px; padding:12px 16px; margin-bottom:16px;">
                    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                </div>
            @endif

            <form action="{{ route('sic.respond', $sic->id) }}" method="POST">
                @csrf
                <div class="form-group" style="margin-bottom:16px;">
                    <label style="display:block; font-size:.8rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:#475569; margin-bottom:8px;">Resposta *</label>
                    <textarea name="response" class="form-control-vivensi" rows="8" required maxlength="10000" placeholder="Redija a resposta à solicitação...">{{ old('response', $sic->response) }}</textarea>
                </div>
                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block; font-size:.8rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:#475569; margin-bottom:8px;">Encerrar com *</label>
                    <select name="status" class="form-control-vivensi" required>
                        <option value="answered" {{ old('status', $sic->status)==='answered'?'selected':'' }}>✅ Respondida (informação fornecida)</option>
                        <option value="denied"   {{ old('status', $sic->status)==='denied'?'selected':''   }}>❌ Negada (com justificativa)</option>
                        <option value="closed"   {{ old('status', $sic->status)==='closed'?'selected':''   }}>🔒 Encerrada</option>
                    </select>
                </div>
                <button type="submit" class="btn-premium" style="width:100%; justify-content:center;">
                    <i class="fas fa-paper-plane"></i> {{ $sic->response ? 'Atualizar Resposta' : 'Enviar Resposta' }}
                </button>
            </form>
        </div>

        <div class="vivensi-card" style="margin-top:16px; background:#fffbeb; border:1px solid #fde68a;">
            <div style="font-size:.8rem; color:#92400e; font-weight:700; margin-bottom:6px;">Lei de Acesso à Informação (LAI)</div>
            <div style="font-size:.82rem; color:#92400e; line-height:1.5;">
                O prazo legal de resposta é de <strong>20 dias úteis</strong>, prorrogável por mais 10 dias mediante justificativa. A negativa de acesso deve conter fundamentação legal.
            </div>
        </div>
    </div>
</div>
@endsection
