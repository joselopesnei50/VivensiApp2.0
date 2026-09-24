@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
        <a href="{{ route('quotes.show', $quote) }}" style="color: #64748b; text-decoration: none;"><i class="fas fa-arrow-left"></i> Voltar</a>
        <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px; margin-left: 10px;"></span>
        <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Vendas · Orçamentos</h6>
    </div>
    <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.2rem; letter-spacing: -1px;">Editar {{ $quote->quote_number }}</h2>
</div>

<div class="vivensi-card p-5" style="background: white; border-radius: 24px; border: 1px solid #f1f5f9;">
    @include('personal.quotes._form')
</div>
@endsection
