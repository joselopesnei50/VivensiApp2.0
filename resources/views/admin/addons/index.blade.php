@extends('layouts.app')

@section('content')
@php $brl = fn ($cents) => 'R$ ' . number_format($cents / 100, 2, ',', '.'); @endphp
<div class="header-page" style="margin-bottom: 24px;">
    <h2 style="margin: 0; color: #2c3e50;">Adicionais</h2>
    <p style="color: #64748b; margin: 5px 0 0 0;">
        Cobrados na mesma fatura do plano. O preço daqui precisa ser <strong>igual</strong> ao do produto avulso na AbacatePay.
    </p>
</div>

<div class="vivensi-card" style="margin-bottom: 22px; display:flex; gap:28px; flex-wrap:wrap;">
    <div><div style="font-size:.8rem;color:#64748b;font-weight:700;">Organizações com adicional</div><div style="font-size:1.5rem;font-weight:800;">{{ $tenants->count() }}</div></div>
    <div><div style="font-size:.8rem;color:#64748b;font-weight:700;">Receita mensal dos adicionais</div><div style="font-size:1.5rem;font-weight:800;color:#4338ca;">{{ $brl($monthlyCents) }}</div></div>
</div>

<h4 style="font-weight:800;margin-bottom:12px;">Catálogo</h4>
<div class="row g-4" style="margin-bottom: 28px;">
    @foreach($catalog as $addon)
    <div class="col-md-6">
        <form class="vivensi-card" method="POST" action="{{ route('admin.addons.catalog', $addon->id) }}">
            @csrf
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <code>{{ $addon->code }}</code>
                <label style="font-size:.85rem;font-weight:700;"><input type="checkbox" name="is_active" value="1" @checked($addon->is_active)> Ativo</label>
            </div>
            <label class="form-label">Nome</label>
            <input class="form-control-vivensi" name="name" value="{{ $addon->name }}" required>
            <label class="form-label" style="margin-top:10px;">Descrição</label>
            <input class="form-control-vivensi" name="description" value="{{ $addon->description }}">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-top:10px;">
                <div><label class="form-label">Preço (R$)</label><input class="form-control-vivensi" type="number" step="0.01" name="unit_price" value="{{ $addon->unit_price }}" required></div>
                <div><label class="form-label" title="1 número no bot, ou e-mails/dia por pacote">Libera por unidade</label><input class="form-control-vivensi" type="number" name="unit_amount" value="{{ $addon->unit_amount }}" required></div>
                <div><label class="form-label">Máx. por conta</label><input class="form-control-vivensi" type="number" name="max_quantity" value="{{ $addon->max_quantity }}" required></div>
            </div>
            <label class="form-label" style="margin-top:10px;">ID do produto na AbacatePay (avulso)</label>
            <input class="form-control-vivensi" name="abacatepay_product_id" value="{{ $addon->abacatepay_product_id }}" placeholder="prod_...">
            <button type="submit" class="btn-premium" style="margin-top:14px;">Salvar</button>
        </form>
    </div>
    @endforeach
</div>

<h4 style="font-weight:800;margin-bottom:12px;">Quem contratou</h4>
<div class="vivensi-card" style="padding:0;overflow:auto;">
    <table class="table" style="margin:0;">
        <thead><tr><th>Organização</th><th>Adicional</th><th>Qtd.</th><th>Por mês</th><th style="text-align:right;">Ajustar</th></tr></thead>
        <tbody>
        @forelse($contracts as $c)
            <tr>
                <td><a href="{{ url('/admin/tenants/' . $c->tenant_id) }}">{{ $tenants[$c->tenant_id]->name ?? ('#' . $c->tenant_id) }}</a></td>
                <td>{{ $c->addon?->name }}</td>
                <td>{{ $c->quantity }}</td>
                <td>{{ $brl($c->quantity * ($c->addon?->unitCents() ?? 0)) }}</td>
                <td style="text-align:right;">
                    @if($c->addon)
                    <form method="POST" action="{{ route('admin.addons.tenant', [$c->tenant_id, $c->addon->id]) }}" style="display:inline-flex;gap:6px;">
                        @csrf
                        <input type="number" name="quantity" min="0" max="{{ $c->addon->max_quantity }}" value="{{ $c->quantity }}" style="width:70px;padding:5px 8px;border:1px solid #cbd5e1;border-radius:6px;">
                        <button type="submit" style="background:#6366f1;color:#fff;border:none;padding:5px 12px;border-radius:6px;font-weight:700;">OK</button>
                    </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" style="color:#64748b;">Nenhuma organização contratou adicionais ainda.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
