@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">SIC — Serviço de Informação ao Cidadão</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Gerencie e responda as solicitações de informação recebidas.</p>
    </div>
</div>

@php
    $pending   = (int) ($counts['pending']   ?? 0);
    $inReview  = (int) ($counts['in_review'] ?? 0);
    $answered  = (int) ($counts['answered']  ?? 0);
    $denied    = (int) ($counts['denied']    ?? 0);
    $closed    = (int) ($counts['closed']    ?? 0);
    $total     = $pending + $inReview + $answered + $denied + $closed;
@endphp

<div class="grid-2" style="margin-bottom: 18px; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));">
    <div class="vivensi-card" style="border-left: 5px solid #ca8a04;">
        <p style="text-transform: uppercase; font-size: .75rem; color: #64748b; font-weight: 800;">Aguardando</p>
        <h3 style="margin: 8px 0; font-size: 1.8rem; color: #ca8a04;">{{ $pending }}</h3>
    </div>
    <div class="vivensi-card" style="border-left: 5px solid #2563eb;">
        <p style="text-transform: uppercase; font-size: .75rem; color: #64748b; font-weight: 800;">Em análise</p>
        <h3 style="margin: 8px 0; font-size: 1.8rem; color: #2563eb;">{{ $inReview }}</h3>
    </div>
    <div class="vivensi-card" style="border-left: 5px solid #16a34a;">
        <p style="text-transform: uppercase; font-size: .75rem; color: #64748b; font-weight: 800;">Respondidas</p>
        <h3 style="margin: 8px 0; font-size: 1.8rem; color: #16a34a;">{{ $answered }}</h3>
    </div>
    <div class="vivensi-card" style="border-left: 5px solid #64748b;">
        <p style="text-transform: uppercase; font-size: .75rem; color: #64748b; font-weight: 800;">Total</p>
        <h3 style="margin: 8px 0; font-size: 1.8rem;">{{ $total }}</h3>
    </div>
</div>

<div class="vivensi-card" style="margin-bottom: 14px;">
    <form method="GET" action="{{ route('sic.index') }}" style="display:flex; gap: 10px; flex-wrap: wrap; align-items:end;">
        <div style="flex: 1; min-width: 200px;">
            <label style="display:block; font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom: 6px;">Busca</label>
            <input type="text" name="q" value="{{ $q }}" class="form-control-vivensi" placeholder="Protocolo, assunto, nome...">
        </div>
        <div style="min-width: 160px;">
            <label style="display:block; font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom: 6px;">Status</label>
            <select name="status" class="form-control-vivensi">
                <option value="">Todos</option>
                <option value="pending"   {{ $status==='pending'?'selected':''   }}>Aguardando</option>
                <option value="in_review" {{ $status==='in_review'?'selected':'' }}>Em análise</option>
                <option value="answered"  {{ $status==='answered'?'selected':''  }}>Respondida</option>
                <option value="denied"    {{ $status==='denied'?'selected':''    }}>Negada</option>
                <option value="closed"    {{ $status==='closed'?'selected':''    }}>Encerrada</option>
            </select>
        </div>
        <div style="display:flex; gap: 10px;">
            <button type="submit" class="btn-premium" style="justify-content:center;"><i class="fas fa-filter"></i> Filtrar</button>
            <a href="{{ route('sic.index') }}" class="btn-premium" style="background:#f1f5f9 !important; color:#0f172a !important; border:1px solid #e2e8f0;">Limpar</a>
        </div>
    </form>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <tr>
                <th style="padding: 14px 16px; text-align: left; font-size: .8rem; color: #64748b; text-transform: uppercase;">Protocolo</th>
                <th style="padding: 14px 16px; text-align: left; font-size: .8rem; color: #64748b; text-transform: uppercase;">Assunto</th>
                <th style="padding: 14px 16px; text-align: left; font-size: .8rem; color: #64748b; text-transform: uppercase;">Solicitante</th>
                <th style="padding: 14px 16px; text-align: center; font-size: .8rem; color: #64748b; text-transform: uppercase;">Status</th>
                <th style="padding: 14px 16px; text-align: center; font-size: .8rem; color: #64748b; text-transform: uppercase;">Prazo</th>
                <th style="padding: 14px 16px; text-align: center; font-size: .8rem; color: #64748b; text-transform: uppercase;">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $sic)
            @php
                $overdue = $sic->isOverdue();
            @endphp
            <tr style="border-bottom: 1px solid #f1f5f9; {{ $overdue ? 'background:#fff5f5;' : '' }}">
                <td style="padding: 14px 16px; font-family: monospace; font-weight: 700; font-size: .9rem; color: #475569;">
                    {{ $sic->protocol }}
                    @if($overdue)<span style="color:#dc2626; font-size:.7rem; font-weight:900; margin-left:6px;">ATRASADO</span>@endif
                </td>
                <td style="padding: 14px 16px; max-width: 260px;">
                    <div style="font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $sic->subject }}</div>
                    <div style="font-size: .8rem; color: #94a3b8;">{{ $sic->created_at->format('d/m/Y H:i') }}</div>
                </td>
                <td style="padding: 14px 16px; color: #475569; font-size: .9rem;">{{ $sic->requester_name }}</td>
                <td style="padding: 14px 16px; text-align: center;">
                    <form action="{{ route('sic.status', $sic->id) }}" method="POST" style="display:inline;">
                        @csrf @method('PATCH')
                        <select name="status" onchange="this.form.submit()" style="font-size:.78rem; padding:3px 6px; border:1px solid #e2e8f0; border-radius:6px; font-weight:700; color:{{ $sic->status_color }}; background:{{ $sic->status_color }}18; cursor:pointer;">
                            <option value="pending"   {{ $sic->status==='pending'?'selected':''   }} style="color:#ca8a04;">Aguardando</option>
                            <option value="in_review" {{ $sic->status==='in_review'?'selected':'' }} style="color:#2563eb;">Em análise</option>
                            <option value="answered"  {{ $sic->status==='answered'?'selected':''  }} style="color:#16a34a;">Respondida</option>
                            <option value="denied"    {{ $sic->status==='denied'?'selected':''    }} style="color:#dc2626;">Negada</option>
                            <option value="closed"    {{ $sic->status==='closed'?'selected':''    }} style="color:#64748b;">Encerrada</option>
                        </select>
                    </form>
                </td>
                <td style="padding: 14px 16px; text-align: center; font-size: .88rem; {{ $overdue ? 'color:#dc2626; font-weight:700;' : 'color:#475569;' }}">
                    {{ $sic->deadline_at->format('d/m/Y') }}
                </td>
                <td style="padding: 14px 16px; text-align: center;">
                    <a href="{{ route('sic.show', $sic->id) }}" class="btn-ds btn-ds-outline" style="padding: 5px 14px; font-size: .82rem;">
                        <i class="fas fa-eye"></i> Ver / Responder
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="padding: 0; border: none;">
                    <x-empty-state
                        icon="fa-inbox"
                        title="Nenhuma solicitação encontrada"
                        description="Ainda não há solicitações SIC ou nenhuma corresponde ao filtro aplicado."
                    />
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div style="padding: 20px;">{{ $requests->links() }}</div>
</div>

@if(session('success'))
    <div style="position:fixed; bottom:24px; right:24px; background:#dcfce7; color:#16a34a; border:1px solid #bbf7d0; border-radius:10px; padding:14px 20px; font-weight:700; z-index:9999; box-shadow:0 4px 20px rgba(0,0,0,.1);">
        ✓ {{ session('success') }}
    </div>
@endif
@endsection
