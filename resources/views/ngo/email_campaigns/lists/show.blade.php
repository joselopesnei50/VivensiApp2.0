@extends('layouts.app')
@section('title', $list->name)

@section('content')
<div style="max-width:1100px; margin:0 auto;">
    <a href="{{ route('ngo.email_campaigns.lists.index') }}"
       style="display:inline-flex; align-items:center; gap:6px; color:var(--ds-brand); font-weight:700; font-size:0.85rem; text-decoration:none; margin-bottom:14px;">
        <i class="fas fa-arrow-left"></i> Voltar às listas
    </a>

    <div style="display:flex; align-items:center; gap:18px; margin-bottom:22px; flex-wrap:wrap;">
        <div style="width:52px; height:52px; border-radius:14px; background:#ecfdf5; color:var(--ds-brand); display:flex; align-items:center; justify-content:center; font-size:1.4rem;">
            <i class="fas fa-address-book"></i>
        </div>
        <div style="flex:1; min-width:200px;">
            <h2 style="margin:0; color:#1e293b; font-weight:900; font-size:1.6rem; letter-spacing:-0.4px;">{{ $list->name }}</h2>
            @if($list->description)<p style="color:#64748b; margin:4px 0 0; font-size:.9rem;">{{ $list->description }}</p>@endif
            @if(!empty($list->tags))
                <div style="display:flex; gap:6px; margin-top:8px; flex-wrap:wrap;">
                    @foreach((array) $list->tags as $t)
                        <span style="background:#ecfdf5; color:#047857; padding:3px 10px; border-radius:12px; font-size:.7rem; font-weight:700;">{{ $t }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if(session('success'))<div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:14px 18px; border-radius:12px; margin-bottom:18px; font-weight:600;"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</div>@endif
    @if(session('error'))<div style="background:#fef2f2; border:1px solid #fca5a5; color:#991b1b; padding:14px 18px; border-radius:12px; margin-bottom:18px; font-weight:600;">✗ {{ session('error') }}</div>@endif

    {{-- Stats --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:12px; margin-bottom:22px;">
        @foreach([
            ['label'=>'Total',       'value'=>$stats['total'],       'color'=>'#1e293b', 'bg'=>'#f8fafc'],
            ['label'=>'Ativos',      'value'=>$stats['active'],      'color'=>'#059669', 'bg'=>'#ecfdf5'],
            ['label'=>'Bounces',     'value'=>$stats['bounced'],     'color'=>'#dc2626', 'bg'=>'#fef2f2'],
            ['label'=>'Descadastros','value'=>$stats['unsubscribed'],'color'=>'#64748b', 'bg'=>'#f1f5f9'],
        ] as $s)
            <div class="vivensi-card" style="padding:18px 22px; border-radius:14px; border-left:4px solid {{ $s['color'] }};">
                <div style="font-size:.72rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">{{ $s['label'] }}</div>
                <div style="font-size:1.7rem; font-weight:900; color:{{ $s['color'] }}; margin-top:4px;">{{ number_format($s['value']) }}</div>
            </div>
        @endforeach
    </div>

    {{-- Ações --}}
    <div class="vivensi-card" style="padding:18px 22px; border-radius:14px; margin-bottom:20px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <form method="POST" action="{{ route('ngo.email_campaigns.lists.import_csv', $list) }}" enctype="multipart/form-data" style="margin:0;">
            @csrf
            <label style="display:inline-flex; align-items:center; gap:6px; padding:10px 16px; background:#f1f5f9; color:#475569; border-radius:10px; font-weight:700; font-size:.85rem; cursor:pointer;">
                <input type="file" name="csv" accept=".csv,text/csv" required onchange="this.form.submit()" style="display:none;">
                <i class="fas fa-file-import"></i> Importar CSV
            </label>
        </form>

        <form method="POST" action="{{ route('ngo.email_campaigns.lists.contacts.add', $list) }}" style="margin:0; display:flex; gap:8px; flex:1; min-width:280px;">
            @csrf
            <input type="email" name="email" placeholder="email@dominio.com" required
                   style="flex:1; padding:10px 12px; border:2px solid #f1f5f9; border-radius:10px; font-size:.9rem;">
            <input type="text" name="name" placeholder="Nome"
                   style="width:150px; padding:10px 12px; border:2px solid #f1f5f9; border-radius:10px; font-size:.9rem;">
            <button type="submit"
                    style="padding:10px 16px; background:var(--ds-brand); color:white; border:0; border-radius:10px; font-weight:700; font-size:.85rem; cursor:pointer;">
                <i class="fas fa-plus"></i> Adicionar
            </button>
        </form>

        <a href="{{ route('ngo.email_campaigns.lists.export', $list) }}"
           style="padding:10px 16px; background:#f1f5f9; color:#475569; border-radius:10px; text-decoration:none; font-weight:700; font-size:.85rem;">
            <i class="fas fa-file-export"></i> Exportar
        </a>

        <form method="POST" action="{{ route('ngo.email_campaigns.lists.destroy', $list) }}"
              onsubmit="return confirm('Remover esta lista e TODOS os contatos?')" style="margin:0;">
            @csrf @method('DELETE')
            <button type="submit"
                    style="padding:10px 16px; background:#fef2f2; color:#dc2626; border:0; border-radius:10px; font-weight:700; font-size:.85rem; cursor:pointer;">
                <i class="fas fa-trash"></i> Excluir lista
            </button>
        </form>
    </div>

    {{-- Busca + lista --}}
    <div class="vivensi-card" style="border-radius:14px; overflow:hidden;">
        <div style="padding:14px 22px; border-bottom:1px solid #f1f5f9;">
            <form method="GET" style="display:flex; gap:10px;">
                <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por email ou nome…"
                       style="flex:1; padding:10px 12px; border:2px solid #f1f5f9; border-radius:10px; font-size:.9rem;">
                <button type="submit" style="padding:10px 20px; background:#1e293b; color:white; border:0; border-radius:10px; font-weight:700; font-size:.85rem; cursor:pointer;">Buscar</button>
                @if($q)<a href="{{ route('ngo.email_campaigns.lists.show', $list) }}" style="align-self:center; color:#64748b; font-size:.85rem;">Limpar</a>@endif
            </form>
        </div>

        @if($contacts->isEmpty())
            <div style="padding:56px 20px; text-align:center; color:#64748b;">
                <i class="fas fa-inbox" style="font-size:2rem; color:#94a3b8; display:block; margin-bottom:10px;"></i>
                {{ $q ? 'Nenhum contato encontrado.' : 'Nenhum contato ainda. Importe um CSV ou adicione manualmente acima.' }}
            </div>
        @else
            <table style="width:100%; border-collapse:collapse;">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th style="font-size:.72rem; text-transform:uppercase; color:#64748b; text-align:left; padding:12px 22px; font-weight:800; letter-spacing:.5px;">Email</th>
                        <th style="font-size:.72rem; text-transform:uppercase; color:#64748b; text-align:left; padding:12px 22px; font-weight:800; letter-spacing:.5px;">Nome</th>
                        <th style="font-size:.72rem; text-transform:uppercase; color:#64748b; text-align:left; padding:12px 22px; font-weight:800; letter-spacing:.5px;">Status</th>
                        <th style="font-size:.72rem; text-transform:uppercase; color:#64748b; text-align:left; padding:12px 22px; font-weight:800; letter-spacing:.5px;">Adicionado</th>
                        <th style="width:60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contacts as $c)
                        @php
                            $statusColors = [
                                'active'       => ['bg' => '#ecfdf5', 'color' => '#059669'],
                                'bounced'      => ['bg' => '#fef2f2', 'color' => '#dc2626'],
                                'unsubscribed' => ['bg' => '#f1f5f9', 'color' => '#64748b'],
                                'invalid'      => ['bg' => '#fffbeb', 'color' => '#b45309'],
                            ];
                            $sc = $statusColors[$c->status] ?? $statusColors['unsubscribed'];
                        @endphp
                        <tr style="border-top:1px solid #f1f5f9;">
                            <td style="padding:13px 22px; font-size:.88rem; color:#1e293b; font-weight:600;">{{ $c->email }}</td>
                            <td style="padding:13px 22px; font-size:.88rem; color:#64748b;">{{ $c->name ?? '—' }}</td>
                            <td style="padding:13px 22px;">
                                <span style="background:{{ $sc['bg'] }}; color:{{ $sc['color'] }}; padding:3px 10px; border-radius:12px; font-size:.68rem; font-weight:700; letter-spacing:.3px; text-transform:uppercase;">{{ $c->status }}</span>
                            </td>
                            <td style="padding:13px 22px; font-size:.85rem; color:#94a3b8;">{{ $c->added_at?->format('d/m/Y') }}</td>
                            <td style="padding:13px 22px;">
                                <form method="POST" action="{{ route('ngo.email_campaigns.lists.contacts.remove', [$list, $c]) }}"
                                      onsubmit="return confirm('Remover este contato?')" style="margin:0;">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="color:#dc2626; background:none; border:0; cursor:pointer; font-size:.85rem; font-weight:700;">Remover</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="padding:14px 22px;">{{ $contacts->links() }}</div>
        @endif
    </div>
</div>
@endsection
