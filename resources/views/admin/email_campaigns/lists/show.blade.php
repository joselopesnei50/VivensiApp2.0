@extends('layouts.app')
@section('title', $list->name)

@section('content')
<div class="container" style="max-width:1100px; margin:24px auto;">
    <div style="margin-bottom:20px;">
        <a href="{{ route('admin.email_campaigns.lists.index') }}" style="color:#6366f1; text-decoration:none; font-size:.85rem;">← Voltar às listas</a>
        <h1 style="margin:8px 0 4px; color:#0f172a;">📇 {{ $list->name }}</h1>
        @if($list->description)
            <p style="color:#64748b; margin:0;">{{ $list->description }}</p>
        @endif
    </div>

    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; padding:12px 16px; border-radius:8px; margin-bottom:16px;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div style="background:#fee2e2; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:16px;">{{ session('error') }}</div>
    @endif

    {{-- Stats --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:12px; margin-bottom:20px;">
        <div style="background:#fff; padding:18px 22px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.05);">
            <div style="font-size:.72rem; font-weight:700; color:#64748b; text-transform:uppercase;">Total</div>
            <div style="font-size:1.8rem; font-weight:900; color:#0f172a;">{{ number_format($stats['total']) }}</div>
        </div>
        <div style="background:#fff; padding:18px 22px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.05);">
            <div style="font-size:.72rem; font-weight:700; color:#059669; text-transform:uppercase;">Ativos</div>
            <div style="font-size:1.8rem; font-weight:900; color:#059669;">{{ number_format($stats['active']) }}</div>
        </div>
        <div style="background:#fff; padding:18px 22px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.05);">
            <div style="font-size:.72rem; font-weight:700; color:#dc2626; text-transform:uppercase;">Bounces</div>
            <div style="font-size:1.5rem; font-weight:900; color:#dc2626;">{{ number_format($stats['bounced']) }}</div>
        </div>
        <div style="background:#fff; padding:18px 22px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.05);">
            <div style="font-size:.72rem; font-weight:700; color:#64748b; text-transform:uppercase;">Descadastros</div>
            <div style="font-size:1.5rem; font-weight:900; color:#0f172a;">{{ number_format($stats['unsubscribed']) }}</div>
        </div>
    </div>

    {{-- Barra de ações --}}
    <div style="background:#fff; padding:18px 22px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.05); margin-bottom:20px;">
        <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            {{-- Upload CSV --}}
            <form method="POST" action="{{ route('admin.email_campaigns.lists.import_csv', $list) }}"
                  enctype="multipart/form-data" style="display:flex; gap:8px; align-items:center;">
                @csrf
                <label style="background:#eff6ff; color:#1d4ed8; padding:9px 14px; border-radius:8px; font-weight:700; cursor:pointer; font-size:.85rem;">
                    <input type="file" name="csv" accept=".csv,text/csv" required onchange="this.form.submit()" style="display:none;">
                    📎 Importar CSV
                </label>
            </form>

            {{-- Adicionar manual --}}
            <form method="POST" action="{{ route('admin.email_campaigns.lists.contacts.add', $list) }}"
                  style="display:flex; gap:6px; align-items:center; flex:1; min-width:280px;">
                @csrf
                <input type="email" name="email" placeholder="email@dominio.com" required
                       style="flex:1; padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:8px; min-width:150px;">
                <input type="text" name="name" placeholder="Nome"
                       style="width:160px; padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:8px;">
                <button type="submit" style="padding:9px 14px; background:#10b981; color:#fff; border:0; border-radius:8px; font-weight:700; font-size:.85rem;">+ Adicionar</button>
            </form>

            {{-- Export --}}
            <a href="{{ route('admin.email_campaigns.lists.export', $list) }}"
               style="padding:9px 14px; background:#f1f5f9; color:#334155; border-radius:8px; text-decoration:none; font-weight:700; font-size:.85rem;">
                ⬇️ Exportar CSV
            </a>

            {{-- Delete lista --}}
            <form method="POST" action="{{ route('admin.email_campaigns.lists.destroy', $list) }}" onsubmit="return confirm('Remover esta lista e TODOS os contatos?')">
                @csrf @method('DELETE')
                <button type="submit" style="padding:9px 14px; background:#fef2f2; color:#dc2626; border:0; border-radius:8px; font-weight:700; font-size:.85rem; cursor:pointer;">
                    🗑 Excluir lista
                </button>
            </form>
        </div>
    </div>

    {{-- Busca + lista de contatos --}}
    <div style="background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.05); overflow:hidden;">
        <div style="padding:14px 22px; border-bottom:1px solid #f1f5f9;">
            <form method="GET" style="display:flex; gap:10px;">
                <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por email ou nome…"
                       style="flex:1; padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:8px;">
                <button type="submit" style="padding:9px 16px; background:#0f172a; color:#fff; border:0; border-radius:8px; font-weight:700; font-size:.85rem;">Buscar</button>
                @if($q)<a href="{{ route('admin.email_campaigns.lists.show', $list) }}" style="align-self:center; color:#64748b; font-size:.85rem;">Limpar</a>@endif
            </form>
        </div>

        @if($contacts->isEmpty())
            <div style="padding:60px 20px; text-align:center; color:#94a3b8;">
                {{ $q ? 'Nenhum contato encontrado.' : 'Nenhum contato ainda. Importe um CSV ou adicione manualmente.' }}
            </div>
        @else
            <table style="width:100%; border-collapse:collapse;">
                <thead style="background:#f8fafc;">
                    <tr style="font-size:.75rem; text-transform:uppercase; color:#64748b; text-align:left;">
                        <th style="padding:12px 22px;">Email</th>
                        <th style="padding:12px 22px;">Nome</th>
                        <th style="padding:12px 22px;">Status</th>
                        <th style="padding:12px 22px;">Adicionado</th>
                        <th style="padding:12px 22px; width:60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contacts as $c)
                        <tr style="border-top:1px solid #f1f5f9;">
                            <td style="padding:11px 22px; color:#0f172a; font-weight:600; font-size:.9rem;">{{ $c->email }}</td>
                            <td style="padding:11px 22px; color:#64748b; font-size:.88rem;">{{ $c->name ?? '—' }}</td>
                            <td style="padding:11px 22px;">
                                @php
                                    $statusColors = ['active' => '#059669', 'bounced' => '#dc2626', 'unsubscribed' => '#64748b', 'invalid' => '#f59e0b'];
                                    $sc = $statusColors[$c->status] ?? '#64748b';
                                @endphp
                                <span style="background:{{ $sc }}22; color:{{ $sc }}; padding:3px 10px; border-radius:10px; font-size:.72rem; font-weight:700;">{{ $c->status }}</span>
                            </td>
                            <td style="padding:11px 22px; color:#94a3b8; font-size:.82rem;">{{ $c->added_at?->format('d/m/Y') }}</td>
                            <td style="padding:11px 22px;">
                                <form method="POST" action="{{ route('admin.email_campaigns.lists.contacts.remove', [$list, $c]) }}" onsubmit="return confirm('Remover este contato?')" style="margin:0;">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="color:#dc2626; background:none; border:0; cursor:pointer; font-size:.85rem;">Remover</button>
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
