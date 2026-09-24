@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
        <a href="{{ route('catalog.index') }}" style="color: #64748b; text-decoration: none;"><i class="fas fa-arrow-left"></i> Voltar ao catálogo</a>
        <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px; margin-left: 10px;"></span>
        <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Catálogo</h6>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 12px;">
        <div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2rem; letter-spacing: -0.5px;">{{ $item->name }}</h2>
            <div style="margin-top: 6px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                @if($item->type === 'product')
                    <span style="background:#fef3c7; color:#92400e; padding:3px 10px; border-radius:99px; font-weight:800; font-size:.72rem; text-transform:uppercase;"><i class="fas fa-box"></i> Produto</span>
                @else
                    <span style="background:#dcfce7; color:#166534; padding:3px 10px; border-radius:99px; font-weight:800; font-size:.72rem; text-transform:uppercase;"><i class="fas fa-briefcase"></i> Serviço</span>
                @endif
                @if(!$item->active)
                    <span style="background:#fee2e2; color:#991b1b; padding:3px 10px; border-radius:99px; font-weight:800; font-size:.72rem; text-transform:uppercase;">Arquivado</span>
                @endif
                @if($item->sku)
                    <span style="font-size:.8rem; color:#64748b; font-weight:600;"><i class="fas fa-barcode"></i> SKU {{ $item->sku }}</span>
                @endif
                @if($item->category)
                    <span style="font-size:.8rem; color:#64748b; font-weight:600;"><i class="fas fa-tag"></i> {{ $item->category }}</span>
                @endif
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('catalog.edit', $item) }}" class="btn-premium" style="background: #fef3c7; color: #92400e; border: none;">
                <i class="fas fa-pen"></i> Editar
            </a>
            <form action="{{ route('catalog.destroy', $item) }}" method="POST" onsubmit="return confirm('Remover este item?');" style="display:inline;">
                @csrf @method('DELETE')
                <button type="submit" class="btn-premium" style="background: #fee2e2; color: #991b1b; border: none;">
                    <i class="fas fa-trash"></i> Remover
                </button>
            </form>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="ds-alert ds-alert-success" style="margin-bottom: 20px;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

<div class="row g-3" style="margin-bottom: 20px;">
    <div class="col-md-4 col-6">
        <div class="vivensi-card" style="padding:20px; background:linear-gradient(135deg,#ecfdf5,#ffffff); border:1px solid #d1fae5;">
            <div style="font-size:.7rem; color:#166534; font-weight:900; text-transform:uppercase; letter-spacing:1px;">Preço unitário</div>
            <div style="font-size:1.6rem; color:#065f46; font-weight:900; letter-spacing:-.5px; margin-top:6px;">
                {{ $item->formatted_price }}
            </div>
            <div style="font-size:.72rem; color:#64748b; font-weight:600; margin-top:4px;">por {{ $item->unit_label }}</div>
        </div>
    </div>
    @if($item->type === 'product' && $item->track_stock)
    <div class="col-md-4 col-6">
        <div class="vivensi-card" style="padding:20px; background:linear-gradient(135deg, {{ $item->isLowStock() ? '#fee2e2' : '#eef2ff' }}, #ffffff); border:1px solid {{ $item->isLowStock() ? '#fecaca' : '#c7d2fe' }};">
            <div style="font-size:.7rem; color:{{ $item->isLowStock() ? '#991b1b' : '#4338ca' }}; font-weight:900; text-transform:uppercase; letter-spacing:1px;">Em estoque</div>
            <div style="font-size:1.6rem; color:{{ $item->isLowStock() ? '#7f1d1d' : '#3730a3' }}; font-weight:900; letter-spacing:-.5px; margin-top:6px;">
                {{ $item->stock_quantity ?? 0 }} {{ $item->unit_label }}
            </div>
            @if($item->isLowStock())
                <div style="font-size:.72rem; color:#dc2626; font-weight:800; margin-top:4px;"><i class="fas fa-triangle-exclamation"></i> Estoque baixo</div>
            @else
                <div style="font-size:.72rem; color:#64748b; font-weight:600; margin-top:4px;">Controle ativo</div>
            @endif
        </div>
    </div>
    @endif
    <div class="col-md-4 col-6">
        <div class="vivensi-card" style="padding:20px; background:linear-gradient(135deg,#f1f5f9,#ffffff); border:1px solid #e2e8f0;">
            <div style="font-size:.7rem; color:#475569; font-weight:900; text-transform:uppercase; letter-spacing:1px;">Cadastrado</div>
            <div style="font-size:1.3rem; color:#1e293b; font-weight:900; letter-spacing:-.5px; margin-top:6px;">
                {{ $item->created_at->format('d/m/Y') }}
            </div>
            <div style="font-size:.72rem; color:#64748b; font-weight:600; margin-top:4px;">
                {{ $item->created_at->diffForHumans() }}
            </div>
        </div>
    </div>
</div>

<div class="vivensi-card p-4" style="background: white; border-radius: 20px; border: 1px solid #f1f5f9;">
    <h5 style="margin: 0 0 14px 0; font-weight: 800; color: #1e293b; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 1px;">
        <i class="fas fa-align-left me-2" style="color: #4f46e5;"></i> Descrição
    </h5>
    @if($item->description)
        <p style="margin: 0; color: #475569; font-size: 0.95rem; line-height: 1.7; white-space: pre-wrap;">{{ $item->description }}</p>
    @else
        <p style="margin: 0; color: #cbd5e1; font-style: italic; font-size: 0.9rem;">Nenhuma descrição registrada.</p>
    @endif
</div>
@endsection
