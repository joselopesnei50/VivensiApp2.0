@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
        <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px;"></span>
        <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Agenda</h6>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 12px;">
        <div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.4rem; letter-spacing: -1px;">Meus Compromissos</h2>
            <p style="color: #64748b; margin: 6px 0 0 0; font-size: 1rem; font-weight: 500;">Reuniões, visitas, prazos e renovações — tudo o que não pode passar do dia.</p>
        </div>
        <a href="{{ route('agenda.create') }}" class="btn-premium" style="background: #1e293b; text-decoration: none; border: none; font-weight: 700;">
            <i class="fas fa-plus me-2" style="color: #10b981;"></i> Novo Compromisso
        </a>
    </div>
</div>

@if(session('success'))
    <div class="ds-alert ds-alert-success" style="margin-bottom: 20px;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

@php
    $currentStatus = request('status');
    $cards = [
        ['key' => 'overdue',  'label' => 'Atrasados', 'icon' => 'fa-triangle-exclamation', 'bg' => '#fee2e2', 'fg' => '#991b1b', 'count' => $stats['overdue']],
        ['key' => 'today',    'label' => 'Hoje',      'icon' => 'fa-bolt',                 'bg' => '#fef3c7', 'fg' => '#92400e', 'count' => $stats['today']],
        ['key' => 'upcoming', 'label' => 'Próximos',  'icon' => 'fa-calendar-day',         'bg' => '#eef2ff', 'fg' => '#4338ca', 'count' => $stats['upcoming']],
        ['key' => 'done',     'label' => 'Concluídos','icon' => 'fa-check-double',         'bg' => '#dcfce7', 'fg' => '#166534', 'count' => $stats['done']],
    ];
@endphp

<div class="row g-2" style="margin-bottom: 20px;">
    @foreach($cards as $card)
        @php
            $isActive = $currentStatus === $card['key'];
            $url = $isActive ? route('agenda.index') : route('agenda.index', ['status' => $card['key']]);
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
    <form method="GET" action="{{ route('agenda.index') }}" style="display:flex; gap:10px; flex-wrap:wrap; align-items:end;">
        @if($currentStatus)<input type="hidden" name="status" value="{{ $currentStatus }}">@endif
        <div style="flex:1; min-width:220px;">
            <label style="font-size:.72rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.5px;">Buscar por título / descrição / local</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Ex: Reunião ACME, entregar proposta..." class="form-control" style="border-radius:10px; padding:10px 12px; border-color:#cbd5e1; margin-top:4px;">
        </div>
        <div style="min-width:170px;">
            <label style="font-size:.72rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.5px;">Tipo</label>
            <select name="kind" class="form-select" style="border-radius:10px; padding:10px 12px; border-color:#cbd5e1; margin-top:4px;">
                <option value="">Todos</option>
                @foreach(\App\Models\AgendaEvent::KINDS as $key => $label)
                    <option value="{{ $key }}" @selected(request('kind') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div style="min-width:200px;">
            <label style="font-size:.72rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.5px;">Cliente</label>
            <select name="client_id" class="form-select" style="border-radius:10px; padding:10px 12px; border-color:#cbd5e1; margin-top:4px;">
                <option value="">Todos</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected((string) request('client_id') === (string) $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="min-width:150px;">
            <label style="font-size:.72rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.5px;">De</label>
            <input type="date" name="from" value="{{ request('from') }}" class="form-control" style="border-radius:10px; padding:10px 12px; border-color:#cbd5e1; margin-top:4px;">
        </div>
        <div style="min-width:150px;">
            <label style="font-size:.72rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.5px;">Até</label>
            <input type="date" name="to" value="{{ request('to') }}" class="form-control" style="border-radius:10px; padding:10px 12px; border-color:#cbd5e1; margin-top:4px;">
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
                    <th style="border: none;">Quando</th>
                    <th style="border: none;">Compromisso</th>
                    <th style="border: none;">Tipo</th>
                    <th style="border: none;">Cliente</th>
                    <th style="border: none; text-align: center;">Status</th>
                    <th style="border: none; text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr style="background: #f8fafc; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                    <td style="border: none; border-radius: 12px 0 0 12px; padding: 15px 20px; border-left: 4px solid {{ $item->display_color }};">
                        <div style="font-weight: 800; color: {{ $item->isOverdue() ? '#991b1b' : '#1e293b' }};">
                            {{ $item->starts_on ? $item->starts_on->format('d/m/Y') : '—' }}
                        </div>
                        @if(!$item->all_day && $item->starts_at)
                            <div style="font-size: 0.75rem; color: #64748b; font-weight:600;">
                                <i class="fas fa-clock" style="font-size:.65rem;"></i>
                                {{ $item->starts_at }}@if($item->ends_at) — {{ $item->ends_at }}@endif
                            </div>
                        @elseif($item->all_day)
                            <div style="font-size: 0.72rem; color: #6366f1; font-weight:700;">DIA INTEIRO</div>
                        @endif
                        @if($item->isOverdue())
                            <div style="font-size:.65rem; color:#dc2626; font-weight:800; margin-top:3px;"><i class="fas fa-triangle-exclamation"></i> ATRASADO</div>
                        @endif
                    </td>
                    <td style="border: none;">
                        <div style="font-weight: 800; color: #1e293b;">{{ $item->title }}</div>
                        @if($item->location)
                            <div style="font-size: 0.72rem; color: #64748b; font-weight:600;"><i class="fas fa-location-dot"></i> {{ $item->location }}</div>
                        @endif
                    </td>
                    <td style="border: none;">
                        <span class="badge" style="background: {{ $item->display_color }}22; color: {{ $item->display_color }}; padding: 5px 10px; border-radius: 8px; font-weight:800;">
                            {{ $item->kind_label }}
                        </span>
                    </td>
                    <td style="border: none; font-size: 0.85rem; color: #475569;">
                        {{ $item->client?->name ?? '—' }}
                    </td>
                    <td style="border: none; text-align: center;">
                        @if($item->status === 'done')
                            <span class="badge" style="background:#dcfce7; color:#166534; padding: 5px 10px; border-radius: 8px; font-weight:800;"><i class="fas fa-check"></i> Concluído</span>
                        @elseif($item->status === 'cancelled')
                            <span class="badge" style="background:#f1f5f9; color:#475569; padding: 5px 10px; border-radius: 8px; font-weight:800;">Cancelado</span>
                        @else
                            <span class="badge" style="background:#fef3c7; color:#92400e; padding: 5px 10px; border-radius: 8px; font-weight:800;">Pendente</span>
                        @endif
                    </td>
                    <td style="border: none; border-radius: 0 12px 12px 0; text-align: right; padding: 15px 20px;">
                        @if($item->status === 'pending')
                            <form action="{{ route('agenda.done', $item) }}" method="POST" style="display:inline-block;">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-light" style="border-radius: 8px; color: #059669; font-weight: 700;" title="Marcar como concluído"><i class="fas fa-check"></i></button>
                            </form>
                        @endif
                        <a href="{{ route('agenda.show', $item) }}" class="btn btn-sm btn-light" style="border-radius: 8px; color: #475569; font-weight: 700;" title="Detalhes"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('agenda.edit', $item) }}" class="btn btn-sm btn-light" style="border-radius: 8px; color: #4f46e5; font-weight: 700;" title="Editar"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('agenda.destroy', $item) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Remover este compromisso?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light" style="border-radius: 8px; color: #ef4444; font-weight: 700;" title="Remover"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding: 0; border: none;">
                        <x-empty-state
                            icon="fa-calendar-check"
                            title="Agenda vazia"
                            description="Cadastre reuniões, visitas, prazos e renovações pra não deixar passar nenhum compromisso importante do seu negócio."
                            action_label="Novo Compromisso"
                            action_url="{{ route('agenda.create') }}"
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
