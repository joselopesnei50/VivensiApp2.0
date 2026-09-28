@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
        <a href="{{ route('agenda.index') }}" style="color: #64748b; text-decoration: none;"><i class="fas fa-arrow-left"></i> Voltar à agenda</a>
        <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px; margin-left: 10px;"></span>
        <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Agenda</h6>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 12px;">
        <div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2rem; letter-spacing: -0.5px;">{{ $item->title }}</h2>
            <div style="margin-top: 6px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <span style="background: {{ $item->display_color }}22; color: {{ $item->display_color }}; padding:3px 10px; border-radius:99px; font-weight:800; font-size:.72rem; text-transform:uppercase;">
                    {{ $item->kind_label }}
                </span>
                @if($item->status === 'done')
                    <span style="background:#dcfce7; color:#166534; padding:3px 10px; border-radius:99px; font-weight:800; font-size:.72rem; text-transform:uppercase;"><i class="fas fa-check"></i> Concluído</span>
                @elseif($item->status === 'cancelled')
                    <span style="background:#f1f5f9; color:#475569; padding:3px 10px; border-radius:99px; font-weight:800; font-size:.72rem; text-transform:uppercase;">Cancelado</span>
                @else
                    <span style="background:#fef3c7; color:#92400e; padding:3px 10px; border-radius:99px; font-weight:800; font-size:.72rem; text-transform:uppercase;">Pendente</span>
                @endif
                @if($item->isOverdue())
                    <span style="background:#fee2e2; color:#991b1b; padding:3px 10px; border-radius:99px; font-weight:800; font-size:.72rem; text-transform:uppercase;"><i class="fas fa-triangle-exclamation"></i> Atrasado</span>
                @endif
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            @if($item->status === 'pending')
                <form action="{{ route('agenda.done', $item) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn-premium" style="background: #dcfce7; color: #166534; border: none;">
                        <i class="fas fa-check"></i> Marcar concluído
                    </button>
                </form>
                <form action="{{ route('agenda.cancel', $item) }}" method="POST" style="display:inline;" onsubmit="return confirm('Cancelar este compromisso?');">
                    @csrf
                    <button type="submit" class="btn-premium" style="background: #f1f5f9; color: #475569; border: none;">
                        <i class="fas fa-ban"></i> Cancelar
                    </button>
                </form>
            @else
                <form action="{{ route('agenda.reopen', $item) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn-premium" style="background: #fef3c7; color: #92400e; border: none;">
                        <i class="fas fa-rotate-left"></i> Reabrir
                    </button>
                </form>
            @endif
            <a href="{{ route('agenda.edit', $item) }}" class="btn-premium" style="background: #eef2ff; color: #4338ca; border: none;">
                <i class="fas fa-pen"></i> Editar
            </a>
            <form action="{{ route('agenda.destroy', $item) }}" method="POST" onsubmit="return confirm('Remover este compromisso?');" style="display:inline;">
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
        <div class="vivensi-card" style="padding:20px; background:linear-gradient(135deg,#eef2ff,#ffffff); border:1px solid #c7d2fe;">
            <div style="font-size:.7rem; color:#4338ca; font-weight:900; text-transform:uppercase; letter-spacing:1px;">Quando</div>
            <div style="font-size:1.3rem; color:#3730a3; font-weight:900; letter-spacing:-.5px; margin-top:6px;">
                {{ $item->when_label }}
            </div>
            <div style="font-size:.72rem; color:#64748b; font-weight:600; margin-top:4px;">
                {{ $item->starts_on?->diffForHumans() }}
            </div>
        </div>
    </div>
    @if($item->client)
    <div class="col-md-4 col-6">
        <div class="vivensi-card" style="padding:20px; background:linear-gradient(135deg,#ecfdf5,#ffffff); border:1px solid #d1fae5;">
            <div style="font-size:.7rem; color:#166534; font-weight:900; text-transform:uppercase; letter-spacing:1px;">Cliente</div>
            <div style="font-size:1.3rem; color:#065f46; font-weight:900; letter-spacing:-.5px; margin-top:6px;">
                {{ $item->client->name }}
            </div>
            @if($item->client->phone)
                <div style="font-size:.72rem; color:#64748b; font-weight:600; margin-top:4px;"><i class="fas fa-phone"></i> {{ $item->client->phone }}</div>
            @endif
        </div>
    </div>
    @endif
    @if($item->location)
    <div class="col-md-4 col-6">
        <div class="vivensi-card" style="padding:20px; background:linear-gradient(135deg,#fef3c7,#ffffff); border:1px solid #fde68a;">
            <div style="font-size:.7rem; color:#92400e; font-weight:900; text-transform:uppercase; letter-spacing:1px;">Local</div>
            <div style="font-size:1.1rem; color:#78350f; font-weight:900; letter-spacing:-.5px; margin-top:6px;">
                {{ $item->location }}
            </div>
        </div>
    </div>
    @endif
</div>

<div class="vivensi-card p-4" style="background: white; border-radius: 20px; border: 1px solid #f1f5f9;">
    <h5 style="margin: 0 0 14px 0; font-weight: 800; color: #1e293b; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 1px;">
        <i class="fas fa-align-left me-2" style="color: #4f46e5;"></i> Notas
    </h5>
    @if($item->description)
        <p style="margin: 0; color: #475569; font-size: 0.95rem; line-height: 1.7; white-space: pre-wrap;">{{ $item->description }}</p>
    @else
        <p style="margin: 0; color: #cbd5e1; font-style: italic; font-size: 0.9rem;">Nenhuma nota registrada.</p>
    @endif
    @if($item->creator)
        <div style="margin-top: 16px; padding-top:14px; border-top:1px solid #f1f5f9; font-size:.75rem; color:#94a3b8; font-weight:600;">
            <i class="fas fa-user"></i> Cadastrado por {{ $item->creator->name }} em {{ $item->created_at->format('d/m/Y H:i') }}
        </div>
    @endif
</div>
@endsection
