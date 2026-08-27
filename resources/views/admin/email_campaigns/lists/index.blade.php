@extends('layouts.app')
@section('title', 'Listas de contatos')

@push('styles')
<style>
    .brc-hero {
        background: linear-gradient(135deg, #0A0A0B 0%, #1a1a1c 100%);
        border-radius: 20px; padding: 32px 34px; margin-bottom: 24px;
        box-shadow: 0 10px 30px rgba(10,10,11,.12);
        display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;
    }
    .brc-hero__body { display: flex; align-items: center; gap: 18px; flex: 1; min-width: 260px; }
    .brc-hero__title { color: #F4F4F5; font-weight: 900; font-size: 1.7rem; letter-spacing: -.5px; margin: 0; }
    .brc-hero__sub   { color: #9a9a9e; font-size: .95rem; margin: 4px 0 0; }
    .brc-cta {
        background: #FF7A1A; color: #0A0A0B; padding: 13px 26px; border-radius: 12px;
        text-decoration: none; font-weight: 800; font-size: .95rem; letter-spacing: .2px;
        box-shadow: 0 6px 18px rgba(255,122,26,.35); transition: transform .15s;
        display: inline-flex; align-items: center; gap: 8px; border: 0; cursor: pointer;
    }
    .brc-cta:hover { transform: translateY(-1px); color: #0A0A0B; }

    .brc-list-card {
        background: #fff; border-radius: 16px; padding: 20px 24px; text-decoration: none;
        color: inherit; box-shadow: 0 2px 10px rgba(10,10,11,.06);
        border: 2px solid transparent; transition: all .18s;
        display: flex; align-items: center; gap: 18px;
    }
    .brc-list-card:hover {
        border-color: #FF7A1A; transform: translateY(-2px);
        box-shadow: 0 8px 26px rgba(255,122,26,.15); color: inherit;
    }
    .brc-list-card__icon {
        width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0;
        background: #F4F4F5; color: #FF7A1A;
        display: flex; align-items: center; justify-content: center; font-size: 1.35rem;
    }
    .brc-list-card__main { flex: 1; min-width: 0; }
    .brc-list-card__title { font-weight: 800; color: #0A0A0B; font-size: 1.05rem; margin: 0; }
    .brc-list-card__desc  { color: #6b6b6f; font-size: .85rem; margin: 3px 0 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .brc-tags { display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap; }
    .brc-tag {
        background: rgba(255,122,26,.1); color: #B54A00;
        padding: 3px 10px; border-radius: 12px;
        font-size: .68rem; font-weight: 700; letter-spacing: .3px; text-transform: lowercase;
    }
    .brc-list-card__stats { text-align: right; flex-shrink: 0; }
    .brc-list-card__count { font-size: 1.8rem; font-weight: 900; color: #0A0A0B; letter-spacing: -1px; line-height: 1; }
    .brc-list-card__label { font-size: .7rem; color: #9a9a9e; margin-top: 4px; text-transform: uppercase; letter-spacing: .8px; }

    .brc-empty {
        background: #fff; border-radius: 16px; padding: 60px 20px;
        text-align: center; color: #6b6b6f; border: 2px dashed #d9d9dc;
    }
    .brc-empty__icon { color: #9a9a9e; font-size: 2.5rem; margin-bottom: 14px; }

    .brc-flash-success {
        background: rgba(255,122,26,.08); border-left: 4px solid #FF7A1A;
        color: #0A0A0B; padding: 14px 18px; border-radius: 10px; margin-bottom: 18px;
        font-size: .9rem; font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="container" style="max-width:1100px; margin:24px auto;">
    <div class="brc-hero">
        <div class="brc-hero__body">
            <img src="{{ asset('img/bruce/bruceia-icone-fundo-escuro.svg') }}" alt="Bruce IA" width="56" height="56" style="flex-shrink:0;">
            <div>
                <h1 class="brc-hero__title">Listas de contatos</h1>
                <p class="brc-hero__sub">Cadastre listas reutilizáveis. Uma vez subida, use em quantas campanhas quiser.</p>
            </div>
        </div>
        <a href="{{ route('admin.email_campaigns.lists.create') }}" class="brc-cta">
            <i class="fas fa-plus"></i> Nova lista
        </a>
    </div>

    @if(session('success'))
        <div class="brc-flash-success">✓ {{ session('success') }}</div>
    @endif

    @if($lists->isEmpty())
        <div class="brc-empty">
            <div class="brc-empty__icon"><i class="fas fa-address-book"></i></div>
            <div style="font-weight:700; color:#0A0A0B; font-size:1.05rem; margin-bottom:6px;">Nenhuma lista cadastrada ainda</div>
            <p style="margin:0 0 20px; font-size:.9rem;">Crie a primeira e faça upload do seu CSV.</p>
            <a href="{{ route('admin.email_campaigns.lists.create') }}" class="brc-cta">
                <i class="fas fa-plus"></i> Criar primeira lista
            </a>
        </div>
    @else
        <div style="display:grid; gap:14px;">
            @foreach($lists as $l)
                <a href="{{ route('admin.email_campaigns.lists.show', $l) }}" class="brc-list-card">
                    <div class="brc-list-card__icon"><i class="fas fa-address-book"></i></div>
                    <div class="brc-list-card__main">
                        <div class="brc-list-card__title">{{ $l->name }}</div>
                        @if($l->description)
                            <div class="brc-list-card__desc">{{ $l->description }}</div>
                        @endif
                        @if(!empty($l->tags))
                            <div class="brc-tags">
                                @foreach((array) $l->tags as $t)
                                    <span class="brc-tag">{{ $t }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="brc-list-card__stats">
                        <div class="brc-list-card__count">{{ number_format($l->active_contacts_count) }}</div>
                        <div class="brc-list-card__label">
                            ativos · {{ number_format($l->contacts_count) }} total
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
        <div style="margin-top:20px;">{{ $lists->links() }}</div>
    @endif
</div>
@endsection
