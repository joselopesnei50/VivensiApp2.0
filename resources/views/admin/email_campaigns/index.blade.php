@extends('layouts.app')
@section('title', 'Campanhas de E-mail')

@section('content')
<div class="header-page" style="margin-bottom: 32px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:16px;">
        <div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                <span style="background:#6366f1; width:12px; height:3px; border-radius:2px;"></span>
                <h6 style="color:#6366f1; font-weight:800; text-transform:uppercase; margin:0; letter-spacing:2px; font-size:0.7rem;">E-mail Marketing</h6>
            </div>
            <h2 style="margin:0; color:#1e293b; font-weight:900; font-size:2.2rem; letter-spacing:-1px;">Campanhas</h2>
            <p style="color:#64748b; margin:6px 0 0; font-size:1rem;">Disparos em massa com métricas de entregabilidade via Brevo.</p>
        </div>
        <a href="{{ route('admin.email_campaigns.create') }}"
           style="display:inline-flex; align-items:center; gap:8px; background:#6366f1; color:white; padding:14px 24px; border-radius:14px; font-weight:800; font-size:0.9rem; text-decoration:none;">
            <i class="fas fa-plus"></i> Nova Campanha
        </a>
    </div>
</div>

@if(session('success'))
    <div style="background:#ecfdf5; color:#065f46; padding:16px 20px; border-radius:12px; margin-bottom:24px; border:1px solid #a7f3d0; font-weight:700; display:flex; align-items:center; gap:10px;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div style="background:#fef2f2; color:#991b1b; padding:16px 20px; border-radius:12px; margin-bottom:24px; border:1px solid #fca5a5; font-weight:700; display:flex; align-items:center; gap:10px;">
        <i class="fas fa-circle-exclamation"></i> {{ session('error') }}
    </div>
@endif

<div class="vivensi-card" style="border-radius:20px; overflow:hidden; border:1px solid #f1f5f9;">
    @if($campaigns->isEmpty())
        <div style="text-align:center; padding:80px 20px; color:#94a3b8;">
            <i class="fas fa-envelope-open" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.3;"></i>
            <p style="font-weight:700; font-size:1rem; margin:0 0 16px;">Nenhuma campanha criada ainda.</p>
            <a href="{{ route('admin.email_campaigns.create') }}"
               style="background:#6366f1; color:white; padding:12px 24px; border-radius:12px; font-weight:700; text-decoration:none; font-size:0.9rem;">
                Criar primeira campanha
            </a>
        </div>
    @else
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0;">
                    <th style="padding:14px 20px; text-align:left; font-size:0.75rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Campanha</th>
                    <th style="padding:14px 20px; text-align:left; font-size:0.75rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Público</th>
                    <th style="padding:14px 20px; text-align:center; font-size:0.75rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Destinatários</th>
                    <th style="padding:14px 20px; text-align:center; font-size:0.75rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Abertura</th>
                    <th style="padding:14px 20px; text-align:center; font-size:0.75rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Cliques</th>
                    <th style="padding:14px 20px; text-align:center; font-size:0.75rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Status</th>
                    <th style="padding:14px 20px; text-align:right; font-size:0.75rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($campaigns as $c)
                @php
                    $statusConfig = [
                        'draft'    => ['bg'=>'#f1f5f9','color'=>'#64748b','label'=>'Rascunho','icon'=>'fa-pen'],
                        'sending'  => ['bg'=>'#fffbeb','color'=>'#d97706','label'=>'Enviando','icon'=>'fa-spinner fa-spin'],
                        'sent'     => ['bg'=>'#ecfdf5','color'=>'#059669','label'=>'Enviada','icon'=>'fa-check'],
                        'error'    => ['bg'=>'#fef2f2','color'=>'#dc2626','label'=>'Erro','icon'=>'fa-circle-exclamation'],
                        'scheduled'=> ['bg'=>'#eff6ff','color'=>'#3b82f6','label'=>'Agendada','icon'=>'fa-clock'],
                    ];
                    $sc = $statusConfig[$c->status] ?? $statusConfig['draft'];
                @endphp
                <tr style="border-bottom:1px solid #f1f5f9;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                    <td style="padding:16px 20px;">
                        <div style="font-weight:800; color:#1e293b; font-size:0.9rem; margin-bottom:3px;">{{ $c->name }}</div>
                        <div style="color:#64748b; font-size:0.78rem;">{{ Str::limit($c->subject, 50) }}</div>
                        <div style="color:#94a3b8; font-size:0.72rem; margin-top:2px;">
                            {{ $c->created_at->format('d/m/Y') }}
                            @if($c->sent_at) · Enviada {{ $c->sent_at->format('d/m H:i') }} @endif
                        </div>
                    </td>
                    <td style="padding:16px 20px;">
                        <span style="font-size:0.78rem; color:#475569; font-weight:600;">{{ $c->audienceLabel() }}</span>
                    </td>
                    <td style="padding:16px 20px; text-align:center;">
                        <span style="font-weight:800; color:#1e293b; font-size:0.95rem;">{{ $c->recipient_count ?: '—' }}</span>
                    </td>
                    <td style="padding:16px 20px; text-align:center;">
                        @if($c->openRate() !== null)
                            <span style="font-weight:800; color:#059669; font-size:0.95rem;">{{ $c->openRate() }}%</span>
                            <div style="color:#94a3b8; font-size:0.7rem;">{{ number_format($c->stat_opens) }} aberturas</div>
                        @else
                            <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td style="padding:16px 20px; text-align:center;">
                        @if($c->clickRate() !== null)
                            <span style="font-weight:800; color:#3b82f6; font-size:0.95rem;">{{ $c->clickRate() }}%</span>
                            <div style="color:#94a3b8; font-size:0.7rem;">{{ number_format($c->stat_clicks) }} cliques</div>
                        @else
                            <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td style="padding:16px 20px; text-align:center;">
                        <span style="background:{{ $sc['bg'] }}; color:{{ $sc['color'] }}; font-weight:800; font-size:0.72rem; padding:5px 12px; border-radius:20px; white-space:nowrap;">
                            <i class="fas {{ $sc['icon'] }} me-1"></i>{{ $sc['label'] }}
                        </span>
                    </td>
                    <td style="padding:16px 20px; text-align:right;">
                        <div style="display:flex; gap:6px; justify-content:flex-end; align-items:center;">
                            <a href="{{ route('admin.email_campaigns.show', $c) }}"
                               style="padding:7px 12px; border-radius:8px; background:#f1f5f9; color:#475569; font-size:0.78rem; font-weight:700; text-decoration:none;">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($c->status === 'draft')
                                <form action="{{ route('admin.email_campaigns.send', $c) }}" method="POST"
                                      onsubmit="return confirm('Disparar campanha para {{ $c->audienceLabel() }}?')">
                                    @csrf
                                    <button type="submit"
                                            style="padding:7px 14px; border-radius:8px; background:#6366f1; color:white; font-size:0.78rem; font-weight:800; border:none; cursor:pointer;">
                                        <i class="fas fa-paper-plane me-1"></i>Disparar
                                    </button>
                                </form>
                                <form action="{{ route('admin.email_campaigns.destroy', $c) }}" method="POST"
                                      onsubmit="return confirm('Excluir campanha?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            style="padding:7px 10px; border-radius:8px; background:#fef2f2; color:#dc2626; font-size:0.78rem; border:none; cursor:pointer;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                            @if($c->status === 'sent')
                                <form action="{{ route('admin.email_campaigns.stats', $c) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                            style="padding:7px 12px; border-radius:8px; background:#eff6ff; color:#3b82f6; font-size:0.78rem; font-weight:700; border:none; cursor:pointer;">
                                        <i class="fas fa-arrow-rotate-right me-1"></i>Métricas
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($campaigns->hasPages())
            <div style="padding:20px 24px; border-top:1px solid #f1f5f9;">
                {{ $campaigns->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
