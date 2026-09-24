@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
        <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px;"></span>
        <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Catálogo</h6>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 12px;">
        <div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.4rem; letter-spacing: -1px;">Produtos &amp; Serviços</h2>
            <p style="color: #64748b; margin: 6px 0 0 0; font-size: 1rem; font-weight: 500;">Base do que sua empresa vende — alimenta orçamentos, contratos e recibos.</p>
        </div>
        <a href="{{ route('catalog.create') }}" class="btn-premium" style="background: #1e293b; text-decoration: none; border: none; font-weight: 700;">
            <i class="fas fa-plus me-2" style="color: #10b981;"></i> Novo Item
        </a>
    </div>
</div>

@if(session('success'))
    <div class="ds-alert ds-alert-success" style="margin-bottom: 20px;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

<div class="row g-2" style="margin-bottom: 20px;">
    @php
        $currentType   = request('type');
        $showInactive  = request()->boolean('inactive');
        $cards = [
            ['key' => null,       'label' => 'Todos ativos', 'icon' => 'fa-layer-group', 'bg' => '#eef2ff', 'fg' => '#4338ca', 'count' => $stats['products'] + $stats['services']],
            ['key' => 'product',  'label' => 'Produtos',     'icon' => 'fa-box',         'bg' => '#fef3c7', 'fg' => '#92400e', 'count' => $stats['products']],
            ['key' => 'service',  'label' => 'Serviços',     'icon' => 'fa-briefcase',   'bg' => '#dcfce7', 'fg' => '#166534', 'count' => $stats['services']],
            ['key' => 'inactive', 'label' => 'Arquivados',   'icon' => 'fa-archive',     'bg' => '#f1f5f9', 'fg' => '#475569', 'count' => $stats['inactive']],
        ];
    @endphp
    @foreach($cards as $card)
        @php
            $isActive = ($card['key'] === 'inactive' && $showInactive)
                || (!$showInactive && $card['key'] === $currentType && $card['key'] !== 'inactive')
                || (!$showInactive && $card['key'] === null && !$currentType);
            $url = $card['key'] === 'inactive'
                ? route('catalog.index', ['inactive' => 1])
                : ($card['key'] ? route('catalog.index', ['type' => $card['key']]) : route('catalog.index'));
        @endphp
        <div class="col">
            <a href="{{ $url }}" style="text-decoration:none;">
                <div class="vivensi-card" style="padding:16px; text-align:center; border:2px solid {{ $isActive ? $card['fg'] : '#f1f5f9' }}; background: {{ $isActive ? $card['bg'] : 'white' }};">
                    <div style="font-size:.7rem; color:{{ $card['fg'] }}; font-weight:900; text-transform:uppercase;">
                        <i class="fas {{ $card['icon'] }}"></i> {{ $card['label'] }}
                    </div>
                    <div style="font-size:1.5rem; font-weight:900; color:#1e293b; margin-top:4px;">{{ $card['count'] }}</div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="vivensi-card" style="padding: 24px; margin-bottom: 20px;">
    <form method="GET" action="{{ route('catalog.index') }}" style="display:flex; gap:10px; flex-wrap:wrap; align-items:end;">
        @if($currentType)<input type="hidden" name="type" value="{{ $currentType }}">@endif
        @if($showInactive)<input type="hidden" name="inactive" value="1">@endif
        <div style="flex:1; min-width:220px;">
            <label style="font-size:.72rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.5px;">Buscar por nome / SKU / descrição</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Ex: Consultoria, SKU-100..." class="form-control" style="border-radius:10px; padding:10px 12px; border-color:#cbd5e1; margin-top:4px;">
        </div>
        <div style="min-width:180px;">
            <label style="font-size:.72rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.5px;">Categoria</label>
            <select name="category" class="form-select" style="border-radius:10px; padding:10px 12px; border-color:#cbd5e1; margin-top:4px;">
                <option value="">Todas</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-premium" style="background:#4f46e5; color:white; border:none; font-weight:800; padding:10px 20px; border-radius:10px;">
            <i class="fas fa-search"></i> Filtrar
        </button>
    </form>
</div>

<div class="vivensi-card p-4" style="background: white; border-radius: 24px; border: 1px solid #f1f5f9;">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="border-collapse: separate; border-spacing: 0 10px;">
            <thead>
                <tr style="color: #94a3b8; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">
                    <th style="border: none;">Item</th>
                    <th style="border: none;">Tipo</th>
                    <th style="border: none;">Categoria</th>
                    <th style="border: none; text-align: right;">Preço</th>
                    <th style="border: none; text-align: center;">Estoque</th>
                    <th style="border: none; text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr style="background: #f8fafc; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                    <td style="border: none; border-radius: 12px 0 0 12px; padding: 15px 20px;">
                        <div style="font-weight: 800; color: #1e293b;">{{ $item->name }}</div>
                        @if($item->sku)
                            <div style="font-size: 0.72rem; color: #64748b; font-weight:600;"><i class="fas fa-barcode" style="font-size:.65rem;"></i> SKU {{ $item->sku }}</div>
                        @endif
                    </td>
                    <td style="border: none;">
                        @if($item->type === 'product')
                            <span class="badge" style="background:#fef3c7; color:#92400e; padding: 5px 10px; border-radius: 8px; font-weight:800;"><i class="fas fa-box"></i> Produto</span>
                        @else
                            <span class="badge" style="background:#dcfce7; color:#166534; padding: 5px 10px; border-radius: 8px; font-weight:800;"><i class="fas fa-briefcase"></i> Serviço</span>
                        @endif
                    </td>
                    <td style="border: none; font-size: 0.85rem; color: #475569;">
                        {{ $item->category ?: '—' }}
                    </td>
                    <td style="border: none; text-align: right; font-weight: 800; color: #059669; font-size: 0.95rem;">
                        {{ $item->formatted_price }}
                        <div style="font-size:.68rem; color:#94a3b8; font-weight:600;">por {{ $item->unit_label }}</div>
                    </td>
                    <td style="border: none; text-align: center; font-size: 0.85rem;">
                        @if($item->track_stock)
                            <span style="font-weight:800; color: {{ $item->isLowStock() ? '#dc2626' : '#1e293b' }};">
                                {{ $item->stock_quantity ?? 0 }}
                            </span>
                            @if($item->isLowStock())
                                <div style="font-size:.65rem; color:#dc2626; font-weight:800;"><i class="fas fa-triangle-exclamation"></i> BAIXO</div>
                            @endif
                        @else
                            <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td style="border: none; border-radius: 0 12px 12px 0; text-align: right; padding: 15px 20px;">
                        <a href="{{ route('catalog.show', $item) }}" class="btn btn-sm btn-light" style="border-radius: 8px; color: #475569; font-weight: 700;" title="Detalhes"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('catalog.edit', $item) }}" class="btn btn-sm btn-light" style="border-radius: 8px; color: #4f46e5; font-weight: 700;" title="Editar"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('catalog.destroy', $item) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Remover este item do catálogo?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light" style="border-radius: 8px; color: #ef4444; font-weight: 700;" title="Remover"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding: 0; border: none;">
                        <x-empty-state
                            icon="fa-box-open"
                            title="Catálogo vazio"
                            description="Cadastre seus produtos e serviços para reutilizar em orçamentos, contratos e recibos sem digitar tudo de novo."
                            action_label="Cadastrar Primeiro Item"
                            action_url="{{ route('catalog.create') }}"
                        />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($items->hasPages())
    <div class="mt-4">{{ $items->links() }}</div>
    @endif
</div>
@endsection
