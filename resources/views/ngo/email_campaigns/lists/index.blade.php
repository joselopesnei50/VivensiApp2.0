@extends('layouts.app')
@section('title', 'Listas de contatos')

@section('content')
<div style="margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
    <div>
        <a href="{{ route('ngo.email_campaigns.index') }}"
           style="display:inline-flex; align-items:center; gap:6px; color:var(--ds-brand); font-weight:700; font-size:0.85rem; text-decoration:none; margin-bottom:8px;">
            <i class="fas fa-arrow-left"></i> Voltar às campanhas
        </a>
        <h2 style="margin:0; color:#1e293b; font-weight:900; font-size:1.7rem; letter-spacing:-0.5px;">Listas de contatos</h2>
        <p style="color:#64748b; margin:6px 0 0; font-size:0.9rem;">Cadastre uma vez, use em quantas campanhas quiser.</p>
    </div>
    <a href="{{ route('ngo.email_campaigns.lists.create') }}"
       style="background:var(--ds-brand); color:white; padding:13px 24px; border-radius:12px; text-decoration:none; font-weight:800; font-size:0.9rem; display:inline-flex; align-items:center; gap:8px; box-shadow:0 6px 18px rgba(5,150,105,.25);">
        <i class="fas fa-plus"></i> Nova lista
    </a>
</div>

@if(session('success'))
    <div style="background:#ecfdf5; border:1px solid #a7f3d0; border-radius:12px; padding:14px 18px; margin-bottom:20px; color:#065f46; font-weight:600;">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    </div>
@endif

@if($lists->isEmpty())
    <div class="vivensi-card" style="padding:56px 20px; text-align:center; border-radius:20px; border:2px dashed #e2e8f0;">
        <i class="fas fa-address-book" style="font-size:2.5rem; color:#94a3b8; margin-bottom:14px; display:block;"></i>
        <div style="font-weight:800; color:#1e293b; font-size:1.05rem; margin-bottom:6px;">Nenhuma lista cadastrada ainda</div>
        <p style="color:#64748b; margin:0 0 20px; font-size:0.9rem;">Crie a primeira e importe seu CSV.</p>
        <a href="{{ route('ngo.email_campaigns.lists.create') }}"
           style="background:var(--ds-brand); color:white; padding:12px 22px; border-radius:12px; text-decoration:none; font-weight:800; font-size:0.9rem; display:inline-flex; align-items:center; gap:8px;">
            <i class="fas fa-plus"></i> Criar primeira lista
        </a>
    </div>
@else
    <div style="display:grid; gap:14px;">
        @foreach($lists as $l)
            <a href="{{ route('ngo.email_campaigns.lists.show', $l) }}"
               class="vivensi-card"
               style="padding:22px 26px; border-radius:16px; text-decoration:none; color:inherit; display:flex; align-items:center; gap:18px; border:2px solid transparent; transition:border-color .2s, transform .2s;"
               onmouseover="this.style.borderColor='var(--ds-brand)'; this.style.transform='translateY(-2px)';"
               onmouseout="this.style.borderColor='transparent'; this.style.transform='translateY(0)';">
                <div style="width:52px; height:52px; border-radius:14px; background:#ecfdf5; color:var(--ds-brand); display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0;">
                    <i class="fas fa-address-book"></i>
                </div>
                <div style="flex:1; min-width:0;">
                    <div style="font-weight:800; color:#1e293b; font-size:1.05rem;">{{ $l->name }}</div>
                    @if($l->description)
                        <div style="color:#64748b; font-size:0.85rem; margin-top:2px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $l->description }}</div>
                    @endif
                    @if(!empty($l->tags))
                        <div style="display:flex; gap:6px; margin-top:8px; flex-wrap:wrap;">
                            @foreach((array) $l->tags as $t)
                                <span style="background:#ecfdf5; color:#047857; padding:3px 10px; border-radius:12px; font-size:.68rem; font-weight:700;">{{ $t }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div style="text-align:right; flex-shrink:0;">
                    <div style="font-size:1.6rem; font-weight:900; color:#1e293b; line-height:1;">{{ number_format($l->active_contacts_count) }}</div>
                    <div style="font-size:.72rem; color:#94a3b8; margin-top:4px; text-transform:uppercase; letter-spacing:.5px;">
                        ativos · {{ number_format($l->contacts_count) }} total
                    </div>
                </div>
            </a>
        @endforeach
    </div>
    <div style="margin-top:20px;">{{ $lists->links() }}</div>
@endif
@endsection
