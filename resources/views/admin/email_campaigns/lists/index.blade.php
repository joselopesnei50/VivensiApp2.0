@extends('layouts.app')
@section('title', 'Listas de contatos')

@section('content')
<div class="container" style="max-width:1100px; margin:24px auto;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <div>
            <h1 style="margin:0; color:#0f172a;">📇 Listas de contatos</h1>
            <p style="color:#64748b; margin:4px 0 0;">Listas reutilizáveis para campanhas de e-mail.</p>
        </div>
        <a href="{{ route('admin.email_campaigns.lists.create') }}"
           style="background:#10b981; color:#fff; padding:10px 18px; border-radius:8px; text-decoration:none; font-weight:700;">
            + Nova lista
        </a>
    </div>

    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; padding:12px 16px; border-radius:8px; margin-bottom:16px;">{{ session('success') }}</div>
    @endif

    @if($lists->isEmpty())
        <div style="background:#fff; padding:60px 20px; border-radius:12px; text-align:center; color:#94a3b8;">
            Nenhuma lista cadastrada. Crie uma nova e faça upload do CSV.
        </div>
    @else
        <div style="display:grid; gap:14px;">
            @foreach($lists as $l)
                <a href="{{ route('admin.email_campaigns.lists.show', $l) }}"
                   style="background:#fff; padding:18px 22px; border-radius:12px; text-decoration:none; color:inherit; box-shadow:0 2px 8px rgba(0,0,0,.05); display:flex; justify-content:space-between; align-items:center;">
                    <div style="flex:1;">
                        <div style="font-weight:700; color:#0f172a; font-size:1.05rem;">{{ $l->name }}</div>
                        @if($l->description)
                            <div style="color:#64748b; font-size:.85rem; margin-top:2px;">{{ \Illuminate\Support\Str::limit($l->description, 100) }}</div>
                        @endif
                        <div style="display:flex; gap:8px; margin-top:6px; flex-wrap:wrap;">
                            @foreach((array) ($l->tags ?? []) as $t)
                                <span style="background:#eff6ff; color:#1d4ed8; padding:2px 8px; border-radius:10px; font-size:.7rem; font-weight:700;">{{ $t }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:1.6rem; font-weight:900; color:#0f172a;">{{ number_format($l->active_contacts_count) }}</div>
                        <div style="font-size:.72rem; color:#64748b;">ativos / {{ number_format($l->contacts_count) }} total</div>
                    </div>
                </a>
            @endforeach
        </div>
        <div style="margin-top:20px;">{{ $lists->links() }}</div>
    @endif
</div>
@endsection
