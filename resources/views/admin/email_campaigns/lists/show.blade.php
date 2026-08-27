@extends('layouts.app')
@section('title', $list->name)

@push('styles')
<style>
    .brc-back {
        display: inline-flex; align-items: center; gap: 6px;
        color: #6b6b6f; text-decoration: none; font-size: .85rem; font-weight: 600; margin-bottom: 10px;
    }
    .brc-back:hover { color: #FF7A1A; }

    .brc-hero {
        background: linear-gradient(135deg, #0A0A0B 0%, #1a1a1c 100%);
        border-radius: 20px; padding: 28px 32px; margin-bottom: 22px;
        display: flex; align-items: center; gap: 18px;
        box-shadow: 0 10px 30px rgba(10,10,11,.12);
    }
    .brc-hero__title { color: #F4F4F5; font-weight: 900; font-size: 1.6rem; margin: 0; letter-spacing: -.5px; }
    .brc-hero__sub   { color: #9a9a9e; font-size: .9rem; margin: 4px 0 0; }
    .brc-hero__tags { display: flex; gap: 6px; margin-top: 10px; flex-wrap: wrap; }
    .brc-hero__tag {
        background: rgba(255,122,26,.15); color: #FF7A1A;
        padding: 3px 10px; border-radius: 12px;
        font-size: .7rem; font-weight: 700; letter-spacing: .3px;
    }

    .brc-flash-success {
        background: rgba(255,122,26,.08); border-left: 4px solid #FF7A1A;
        color: #0A0A0B; padding: 14px 18px; border-radius: 10px; margin-bottom: 18px; font-weight: 600;
    }
    .brc-flash-error {
        background: rgba(220,38,38,.08); border-left: 4px solid #dc2626;
        color: #7f1d1d; padding: 14px 18px; border-radius: 10px; margin-bottom: 18px; font-weight: 600;
    }

    .brc-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 22px; }
    .brc-stat {
        background: #fff; padding: 18px 22px; border-radius: 14px;
        box-shadow: 0 2px 10px rgba(10,10,11,.05); position: relative;
    }
    .brc-stat::before {
        content: ''; position: absolute; left: 0; top: 12px; bottom: 12px; width: 3px; border-radius: 0 3px 3px 0;
        background: var(--brc-stat-color, #6b6b6f);
    }
    .brc-stat__label { font-size: .7rem; font-weight: 800; color: #6b6b6f; text-transform: uppercase; letter-spacing: 1px; }
    .brc-stat__value { font-size: 1.75rem; font-weight: 900; color: var(--brc-stat-color, #0A0A0B); margin-top: 4px; letter-spacing: -.5px; }

    .brc-actions {
        background: #fff; padding: 18px 22px; border-radius: 14px;
        box-shadow: 0 2px 10px rgba(10,10,11,.05); margin-bottom: 20px;
        display: flex; gap: 12px; align-items: center; flex-wrap: wrap;
    }
    .brc-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 10px 16px; border-radius: 10px; font-weight: 700; font-size: .85rem;
        text-decoration: none; border: 0; cursor: pointer; transition: transform .15s;
    }
    .brc-btn:hover { transform: translateY(-1px); }
    .brc-btn-primary { background: #FF7A1A; color: #0A0A0B; box-shadow: 0 4px 12px rgba(255,122,26,.3); }
    .brc-btn-primary:hover { color: #0A0A0B; }
    .brc-btn-neutral { background: #F4F4F5; color: #3A3A3C; }
    .brc-btn-neutral:hover { color: #0A0A0B; }
    .brc-btn-danger { background: rgba(220,38,38,.1); color: #dc2626; }

    .brc-add-inline {
        display: flex; gap: 8px; align-items: center; flex: 1; min-width: 300px;
    }
    .brc-inline-input {
        padding: 10px 12px; border: 2px solid #d9d9dc; border-radius: 10px;
        font-size: .9rem; transition: border-color .15s;
    }
    .brc-inline-input:focus { outline: 0; border-color: #FF7A1A; }

    .brc-panel {
        background: #fff; border-radius: 14px; box-shadow: 0 2px 10px rgba(10,10,11,.05); overflow: hidden;
    }
    .brc-search-bar { padding: 14px 22px; border-bottom: 1px solid #F4F4F5; display: flex; gap: 10px; align-items: center; }
    .brc-search-input {
        flex: 1; padding: 10px 12px; border: 2px solid #d9d9dc; border-radius: 10px; font-size: .9rem;
    }
    .brc-search-input:focus { outline: 0; border-color: #FF7A1A; }
    .brc-search-btn {
        padding: 10px 20px; background: #0A0A0B; color: #F4F4F5;
        border: 0; border-radius: 10px; font-weight: 700; font-size: .85rem; cursor: pointer;
    }

    .brc-table { width: 100%; border-collapse: collapse; }
    .brc-table thead { background: #F4F4F5; }
    .brc-table th {
        font-size: .72rem; text-transform: uppercase; color: #6b6b6f; text-align: left;
        padding: 12px 22px; font-weight: 800; letter-spacing: .5px;
    }
    .brc-table td { padding: 13px 22px; border-top: 1px solid #F4F4F5; font-size: .88rem; }
    .brc-table tr:hover td { background: rgba(255,122,26,.03); }

    .brc-status {
        padding: 3px 10px; border-radius: 12px; font-size: .68rem; font-weight: 700;
        letter-spacing: .3px; text-transform: uppercase;
    }
    .brc-status--active       { background: rgba(5,150,105,.12); color: #059669; }
    .brc-status--bounced      { background: rgba(220,38,38,.1);  color: #dc2626; }
    .brc-status--unsubscribed { background: #F4F4F5;             color: #6b6b6f; }
    .brc-status--invalid      { background: rgba(245,158,11,.12);color: #b45309; }

    .brc-empty {
        padding: 60px 20px; text-align: center; color: #6b6b6f;
    }
</style>
@endpush

@section('content')
<div class="container" style="max-width:1100px; margin:24px auto;">
    <a href="{{ route('admin.email_campaigns.lists.index') }}" class="brc-back">
        <i class="fas fa-arrow-left"></i> Voltar às listas
    </a>

    <div class="brc-hero">
        <img src="{{ asset('img/bruce/bruceia-icone-fundo-escuro.svg') }}" alt="Bruce IA" width="48" height="48" style="flex-shrink:0;">
        <div>
            <h1 class="brc-hero__title">{{ $list->name }}</h1>
            @if($list->description)
                <p class="brc-hero__sub">{{ $list->description }}</p>
            @endif
            @if(!empty($list->tags))
                <div class="brc-hero__tags">
                    @foreach((array) $list->tags as $t)
                        <span class="brc-hero__tag">{{ $t }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="brc-flash-success">✓ {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="brc-flash-error">✗ {{ session('error') }}</div>
    @endif

    {{-- Stats --}}
    <div class="brc-stats">
        <div class="brc-stat" style="--brc-stat-color:#0A0A0B;">
            <div class="brc-stat__label">Total</div>
            <div class="brc-stat__value">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="brc-stat" style="--brc-stat-color:#059669;">
            <div class="brc-stat__label">Ativos</div>
            <div class="brc-stat__value">{{ number_format($stats['active']) }}</div>
        </div>
        <div class="brc-stat" style="--brc-stat-color:#dc2626;">
            <div class="brc-stat__label">Bounces</div>
            <div class="brc-stat__value">{{ number_format($stats['bounced']) }}</div>
        </div>
        <div class="brc-stat" style="--brc-stat-color:#6b6b6f;">
            <div class="brc-stat__label">Descadastros</div>
            <div class="brc-stat__value">{{ number_format($stats['unsubscribed']) }}</div>
        </div>
    </div>

    {{-- Barra de ações --}}
    <div class="brc-actions">
        <form method="POST" action="{{ route('admin.email_campaigns.lists.import_csv', $list) }}"
              enctype="multipart/form-data" style="margin:0;">
            @csrf
            <label class="brc-btn brc-btn-neutral" style="margin:0;">
                <input type="file" name="csv" accept=".csv,text/csv" required onchange="this.form.submit()" style="display:none;">
                <i class="fas fa-file-import"></i> Importar CSV
            </label>
        </form>

        <form method="POST" action="{{ route('admin.email_campaigns.lists.contacts.add', $list) }}" class="brc-add-inline">
            @csrf
            <input type="email" name="email" placeholder="email@dominio.com" required class="brc-inline-input" style="flex:1;">
            <input type="text" name="name" placeholder="Nome" class="brc-inline-input" style="width:160px;">
            <button type="submit" class="brc-btn brc-btn-primary">
                <i class="fas fa-plus"></i> Adicionar
            </button>
        </form>

        <a href="{{ route('admin.email_campaigns.lists.export', $list) }}" class="brc-btn brc-btn-neutral">
            <i class="fas fa-file-export"></i> Exportar
        </a>

        <form method="POST" action="{{ route('admin.email_campaigns.lists.destroy', $list) }}"
              onsubmit="return confirm('Remover esta lista e TODOS os contatos?')" style="margin:0;">
            @csrf @method('DELETE')
            <button type="submit" class="brc-btn brc-btn-danger">
                <i class="fas fa-trash"></i> Excluir lista
            </button>
        </form>
    </div>

    {{-- Busca + lista --}}
    <div class="brc-panel">
        <div class="brc-search-bar">
            <form method="GET" style="display:flex; gap:10px; flex:1; margin:0;">
                <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por email ou nome…" class="brc-search-input">
                <button type="submit" class="brc-search-btn">Buscar</button>
                @if($q)<a href="{{ route('admin.email_campaigns.lists.show', $list) }}" style="align-self:center; color:#6b6b6f; font-size:.85rem;">Limpar</a>@endif
            </form>
        </div>

        @if($contacts->isEmpty())
            <div class="brc-empty">
                <i class="fas fa-inbox" style="font-size:2rem; color:#9a9a9e; display:block; margin-bottom:10px;"></i>
                {{ $q ? 'Nenhum contato encontrado.' : 'Nenhum contato ainda. Importe um CSV ou adicione manualmente acima.' }}
            </div>
        @else
            <table class="brc-table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Nome</th>
                        <th>Status</th>
                        <th>Adicionado</th>
                        <th style="width:60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contacts as $c)
                        <tr>
                            <td style="color:#0A0A0B; font-weight:600;">{{ $c->email }}</td>
                            <td style="color:#6b6b6f;">{{ $c->name ?? '—' }}</td>
                            <td>
                                <span class="brc-status brc-status--{{ $c->status }}">{{ $c->status }}</span>
                            </td>
                            <td style="color:#9a9a9e;">{{ $c->added_at?->format('d/m/Y') }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.email_campaigns.lists.contacts.remove', [$list, $c]) }}"
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
