@extends('layouts.app')
@section('title', $campaign->name)

@section('content')
<div class="header-page" style="margin-bottom:32px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:16px;">
        <div>
            <a href="{{ route('admin.email_campaigns.index') }}"
               style="display:inline-flex; align-items:center; gap:6px; color:#6366f1; font-weight:700; font-size:0.85rem; text-decoration:none; margin-bottom:14px;">
                <i class="fas fa-arrow-left"></i> Campanhas
            </a>
            <h2 style="margin:0; color:#1e293b; font-weight:900; font-size:2rem; letter-spacing:-1px;">{{ $campaign->name }}</h2>
            <p style="color:#64748b; margin:6px 0 0; font-size:0.9rem;">{{ $campaign->subject }}</p>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            @if($campaign->status === 'draft')
                <form action="{{ route('admin.email_campaigns.send', $campaign) }}" method="POST"
                      onsubmit="return confirm('Disparar campanha agora?')">
                    @csrf
                    <button type="submit"
                            style="padding:14px 24px; border:none; border-radius:14px; background:#6366f1; color:white; font-weight:800; font-size:0.9rem; cursor:pointer;">
                        <i class="fas fa-paper-plane me-2"></i>Disparar Agora
                    </button>
                </form>
            @endif
            @if($campaign->status === 'sent' && $campaign->brevo_campaign_id)
                <form action="{{ route('admin.email_campaigns.stats', $campaign) }}" method="POST">
                    @csrf
                    <button type="submit"
                            style="padding:14px 24px; border:none; border-radius:14px; background:#eff6ff; color:#3b82f6; font-weight:800; font-size:0.9rem; cursor:pointer;">
                        <i class="fas fa-arrow-rotate-right me-2"></i>Atualizar Métricas
                    </button>
                </form>
            @endif
        </div>
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

{{-- Métricas --}}
@if($campaign->status === 'sent')
<div class="row g-4 mb-4">
    @php
        $metrics = [
            ['label'=>'Destinatários','value'=> number_format($campaign->recipient_count),'icon'=>'fa-users','color'=>'#6366f1','bg'=>'#eff6ff'],
            ['label'=>'Entregues','value'=> $campaign->stat_delivered ? number_format($campaign->stat_delivered) : '—','icon'=>'fa-inbox','color'=>'#059669','bg'=>'#ecfdf5'],
            ['label'=>'Aberturas','value'=> $campaign->openRate() !== null ? $campaign->openRate().'%' : '—','sub'=> $campaign->stat_opens ? number_format($campaign->stat_opens).' únicos' : null,'icon'=>'fa-envelope-open','color'=>'#d97706','bg'=>'#fffbeb'],
            ['label'=>'Cliques','value'=> $campaign->clickRate() !== null ? $campaign->clickRate().'%' : '—','sub'=> $campaign->stat_clicks ? number_format($campaign->stat_clicks).' únicos' : null,'icon'=>'fa-arrow-pointer','color'=>'#3b82f6','bg'=>'#eff6ff'],
            ['label'=>'Bounces','value'=> $campaign->bounceRate() !== null ? $campaign->bounceRate().'%' : ($campaign->stat_bounces !== null ? number_format($campaign->stat_bounces) : '—'),'icon'=>'fa-circle-xmark','color'=>'#ef4444','bg'=>'#fef2f2'],
            ['label'=>'Descadastros','value'=> $campaign->stat_unsubscribes !== null ? number_format($campaign->stat_unsubscribes) : '—','icon'=>'fa-user-minus','color'=>'#64748b','bg'=>'#f1f5f9'],
        ];
    @endphp
    @foreach($metrics as $m)
    <div class="col-6 col-md-4 col-xl-2">
        <div class="vivensi-card" style="padding:22px; border-radius:16px; text-align:center;">
            <div style="width:42px; height:42px; background:{{ $m['bg'] }}; border-radius:12px; display:flex; align-items:center; justify-content:center; margin:0 auto 12px; color:{{ $m['color'] }}; font-size:1rem;">
                <i class="fas {{ $m['icon'] }}"></i>
            </div>
            <div style="font-size:1.5rem; font-weight:900; color:#1e293b; letter-spacing:-0.5px;">{{ $m['value'] }}</div>
            @if(!empty($m['sub']))<div style="font-size:0.7rem; color:#94a3b8; margin-top:2px;">{{ $m['sub'] }}</div>@endif
            <div style="font-size:0.72rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-top:6px;">{{ $m['label'] }}</div>
        </div>
    </div>
    @endforeach
</div>
@if($campaign->stats_fetched_at)
    <p style="color:#94a3b8; font-size:0.75rem; text-align:right; margin:-8px 0 24px;">
        Métricas atualizadas em {{ $campaign->stats_fetched_at->format('d/m/Y H:i') }}
    </p>
@endif
@endif

<div class="row g-4">
    <div class="col-lg-8">
        {{-- Preview --}}
        <div class="vivensi-card" style="padding:0; border-radius:20px; overflow:hidden;">
            <div style="padding:18px 24px; border-bottom:1px solid #f1f5f9; background:#f8fafc; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-eye" style="color:#6366f1;"></i>
                <strong style="font-size:0.9rem; color:#1e293b;">Preview do E-mail</strong>
            </div>
            <iframe srcdoc="{{ $campaign->html_content }}"
                    style="width:100%; height:600px; border:none;"></iframe>
        </div>
    </div>
    <div class="col-lg-4">
        {{-- Detalhes --}}
        <div class="vivensi-card" style="padding:28px; border-radius:20px;">
            <h4 style="margin:0 0 20px; font-weight:900; color:#1e293b; font-size:1rem;">Detalhes</h4>
            @php
                $details = [
                    ['label'=>'Status', 'value'=> ucfirst($campaign->status)],
                    ['label'=>'Remetente', 'value'=> $campaign->sender_email ?: 'Padrão do sistema'],
                    ['label'=>'Reply-To', 'value'=> $campaign->reply_to_email ?: '—'],
                    ['label'=>'Público', 'value'=> $campaign->audienceLabel()],
                    ['label'=>'Criada por', 'value'=> $campaign->creator->name ?? '—'],
                    ['label'=>'Criada em', 'value'=> $campaign->created_at->format('d/m/Y H:i')],
                    ['label'=>'Enviada em', 'value'=> $campaign->sent_at ? $campaign->sent_at->format('d/m/Y H:i') : '—'],
                    ['label'=>'ID Brevo', 'value'=> $campaign->brevo_campaign_id ?? '—'],
                ];
            @endphp
            @foreach($details as $d)
            <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f1f5f9; font-size:0.85rem;">
                <span style="color:#64748b; font-weight:600;">{{ $d['label'] }}</span>
                <span style="color:#1e293b; font-weight:700; text-align:right; max-width:60%;">{{ $d['value'] }}</span>
            </div>
            @endforeach
            @if($campaign->error_message)
            <div style="background:#fef2f2; border:1px solid #fca5a5; border-radius:10px; padding:14px; margin-top:16px; font-size:0.8rem; color:#991b1b; line-height:1.6;">
                <strong><i class="fas fa-triangle-exclamation me-1"></i>Erro:</strong> {{ $campaign->error_message }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
