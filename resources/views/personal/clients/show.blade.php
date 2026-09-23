@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 40px;">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
        <a href="{{ route('clients.index') }}" style="color: #64748b; text-decoration: none;"><i class="fas fa-arrow-left"></i> Voltar</a>
        <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px; margin-left: 10px;"></span>
        <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Mini CRM</h6>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 56px; height: 56px; background: #e0e7ff; color: #4f46e5; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.5rem;">
                {{ strtoupper(substr($client->name, 0, 1)) }}
            </div>
            <div>
                <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2rem; letter-spacing: -0.5px;">{{ $client->name }}</h2>
                <span style="font-size: 0.85rem; color: #64748b;">
                    {{ $client->type === 'company' ? 'Pessoa Jurídica' : 'Pessoa Física' }}
                    &nbsp;·&nbsp; Cadastrado em {{ $client->created_at->format('d/m/Y') }}
                    &nbsp;·&nbsp;
                    @php
                        $stageColors = [
                            'lead'     => ['bg' => '#e0e7ff', 'fg' => '#4338ca'],
                            'prospect' => ['bg' => '#fef3c7', 'fg' => '#92400e'],
                            'active'   => ['bg' => '#dcfce7', 'fg' => '#166534'],
                            'churned'  => ['bg' => '#fee2e2', 'fg' => '#991b1b'],
                        ];
                        $sc = $stageColors[$client->stage] ?? $stageColors['active'];
                    @endphp
                    <span style="background:{{ $sc['bg'] }}; color:{{ $sc['fg'] }}; padding:2px 10px; border-radius:99px; font-weight:800; font-size:.72rem; text-transform:uppercase;">
                        {{ $client->stage_label }}
                    </span>
                </span>
            </div>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            @php $phone = preg_replace('/\D+/', '', (string)($client->phone ?? '')); @endphp
            @if($phone)
            <a href="https://wa.me/55{{ $phone }}" target="_blank" rel="noopener" class="btn-premium" style="background: #dcfce7; color: #166534; border: none;">
                <i class="fab fa-whatsapp"></i> WhatsApp
            </a>
            @endif
            <a href="{{ route('clients.edit', $client) }}" class="btn-premium" style="background: #fef3c7 !important; color: #92400e !important; border: none;">
                <i class="fas fa-pen"></i> Editar
            </a>
            <form action="{{ route('clients.destroy', $client) }}" method="POST" onsubmit="return confirm('Remover este cliente?');" style="display:inline;">
                @csrf @method('DELETE')
                <button type="submit" class="btn-premium" style="background: #fee2e2 !important; color: #991b1b !important; border: none;">
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
    <div class="col-md-3 col-6">
        <div class="vivensi-card" style="padding:20px; background:linear-gradient(135deg,#ecfdf5,#ffffff); border:1px solid #d1fae5;">
            <div style="font-size:.7rem; color:#166534; font-weight:900; text-transform:uppercase; letter-spacing:1px;">LTV Total</div>
            <div style="font-size:1.6rem; color:#065f46; font-weight:900; letter-spacing:-.5px; margin-top:6px;">
                R$ {{ number_format($stats['ltv'], 2, ',', '.') }}
            </div>
            <div style="font-size:.72rem; color:#64748b; font-weight:600; margin-top:4px;">Receitas pagas acumuladas</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="vivensi-card" style="padding:20px; background:linear-gradient(135deg,#fef3c7,#ffffff); border:1px solid #fde68a;">
            <div style="font-size:.7rem; color:#92400e; font-weight:900; text-transform:uppercase; letter-spacing:1px;">A Receber</div>
            <div style="font-size:1.6rem; color:#78350f; font-weight:900; letter-spacing:-.5px; margin-top:6px;">
                R$ {{ number_format($stats['pending'], 2, ',', '.') }}
            </div>
            <div style="font-size:.72rem; color:#64748b; font-weight:600; margin-top:4px;">Faturas em aberto</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="vivensi-card" style="padding:20px; background:linear-gradient(135deg,#e0e7ff,#ffffff); border:1px solid #c7d2fe;">
            <div style="font-size:.7rem; color:#4338ca; font-weight:900; text-transform:uppercase; letter-spacing:1px;">Transações</div>
            <div style="font-size:1.6rem; color:#3730a3; font-weight:900; letter-spacing:-.5px; margin-top:6px;">
                {{ $stats['count'] }}
            </div>
            <div style="font-size:.72rem; color:#64748b; font-weight:600; margin-top:4px;">Registros financeiros</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="vivensi-card" style="padding:20px; background:linear-gradient(135deg,#f1f5f9,#ffffff); border:1px solid #e2e8f0;">
            <div style="font-size:.7rem; color:#475569; font-weight:900; text-transform:uppercase; letter-spacing:1px;">Últ. Transação</div>
            <div style="font-size:1.3rem; color:#1e293b; font-weight:900; letter-spacing:-.5px; margin-top:6px;">
                {{ $stats['last_transaction'] ? \Carbon\Carbon::parse($stats['last_transaction'])->format('d/m/Y') : '—' }}
            </div>
            <div style="font-size:.72rem; color:#64748b; font-weight:600; margin-top:4px;">
                {{ $stats['last_transaction'] ? \Carbon\Carbon::parse($stats['last_transaction'])->diffForHumans() : 'Nenhuma ainda' }}
            </div>
        </div>
    </div>
</div>

<div class="row" style="gap: 0;">
    <div class="col-md-5" style="padding-right: 12px; margin-bottom: 20px;">
        <div class="vivensi-card p-4" style="background: white; border-radius: 20px; border: 1px solid #f1f5f9; height: 100%;">
            <h5 style="margin: 0 0 20px 0; font-weight: 800; color: #1e293b; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 1px;">
                <i class="fas fa-id-card me-2" style="color: #4f46e5;"></i> Dados do Cliente
            </h5>

            <div style="display: flex; flex-direction: column; gap: 14px;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    <span style="font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Tipo</span>
                    <span style="font-weight: 700; color: #1e293b;">
                        @if($client->type === 'company')
                            <span style="background: #fef3c7; color: #d97706; padding: 3px 10px; border-radius: 8px; font-size: 0.8rem;">Pessoa Jurídica</span>
                        @else
                            <span style="background: #e0f2fe; color: #0284c7; padding: 3px 10px; border-radius: 8px; font-size: 0.8rem;">Pessoa Física</span>
                        @endif
                    </span>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    <span style="font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">E-mail</span>
                    <span style="font-weight: 600; color: #475569; font-size: 0.9rem;">
                        @if($client->email)
                            <a href="mailto:{{ $client->email }}" style="color: #4f46e5; text-decoration: none;">{{ $client->email }}</a>
                        @else
                            <span style="color: #cbd5e1;">—</span>
                        @endif
                    </span>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    <span style="font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Telefone / WhatsApp</span>
                    <span style="font-weight: 600; color: #475569; font-size: 0.9rem;">{{ $client->phone ?: '—' }}</span>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    <span style="font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Última atualização</span>
                    <span style="font-weight: 600; color: #475569; font-size: 0.9rem;">{{ $client->updated_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-7" style="padding-left: 12px; display: flex; flex-direction: column; gap: 16px; margin-bottom: 20px;">
        <div class="vivensi-card p-4" style="background: white; border-radius: 20px; border: 1px solid #f1f5f9;">
            <h5 style="margin: 0 0 14px 0; font-weight: 800; color: #1e293b; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 1px;">
                <i class="fas fa-shopping-bag me-2" style="color: #10b981;"></i> Histórico de Compras
            </h5>
            @if($client->purchase_history)
                <p style="margin: 0; color: #475569; font-size: 0.9rem; line-height: 1.7; white-space: pre-wrap;">{{ $client->purchase_history }}</p>
            @else
                <p style="margin: 0; color: #cbd5e1; font-style: italic; font-size: 0.9rem;">Nenhum histórico registrado ainda.</p>
            @endif
        </div>

        <div class="vivensi-card p-4" style="background: white; border-radius: 20px; border: 1px solid #f1f5f9;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h5 style="margin: 0; font-weight: 800; color: #1e293b; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 1px;">
                    <i class="fas fa-sticky-note me-2" style="color: #f59e0b;"></i> Anotações CRM
                </h5>
                <a href="{{ route('clients.edit', $client) }}" style="font-size: 0.78rem; color: #4f46e5; font-weight: 700; text-decoration: none;">
                    <i class="fas fa-pen"></i> Editar
                </a>
            </div>
            @if($client->relationship_notes)
                <p style="margin: 0; color: #475569; font-size: 0.9rem; line-height: 1.7; white-space: pre-wrap;">{{ $client->relationship_notes }}</p>
            @else
                <p style="margin: 0; color: #cbd5e1; font-style: italic; font-size: 0.9rem;">Nenhuma anotação de relacionamento registrada.</p>
            @endif
        </div>
    </div>
</div>

<div class="vivensi-card p-4" style="background: white; border-radius: 20px; border: 1px solid #f1f5f9; margin-bottom: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
        <h5 style="margin: 0; font-weight: 800; color: #1e293b; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 1px;">
            <i class="fas fa-clock-rotate-left me-2" style="color: #4f46e5;"></i> Histórico Financeiro
        </h5>
        <a href="{{ url('/transactions/create?client_id=' . $client->id) }}" class="btn-ds btn-ds-outline" style="font-size:.72rem; padding:6px 14px;">
            <i class="fas fa-plus"></i> NOVA TRANSAÇÃO
        </a>
    </div>
    @forelse($transactions as $tx)
    <div style="display:flex; align-items:center; padding:14px 0; border-bottom:1px solid #f1f5f9;">
        <div style="width:44px; height:44px; border-radius:12px; background: {{ $tx->type === 'income' ? '#ecfdf5' : '#fef2f2' }}; color: {{ $tx->type === 'income' ? '#10b981' : '#ef4444' }}; display:flex; align-items:center; justify-content:center; margin-right:16px; font-size:.95rem;">
            <i class="fas {{ $tx->type === 'income' ? 'fa-arrow-down' : 'fa-arrow-up' }}"></i>
        </div>
        <div style="flex:1;">
            <div style="font-weight:700; color:#1e293b; font-size:.92rem;">{{ $tx->description }}</div>
            <div style="font-size:.72rem; color:#64748b; font-weight:600;">
                {{ \Carbon\Carbon::parse($tx->date)->translatedFormat('d \d\e F \d\e Y') }}
                &nbsp;·&nbsp;
                @if($tx->status === 'paid')
                    <span style="color:#10b981; font-weight:800;">PAGO</span>
                @elseif($tx->status === 'pending')
                    <span style="color:#f59e0b; font-weight:800;">PENDENTE</span>
                @else
                    <span style="color:#94a3b8; font-weight:800;">{{ strtoupper($tx->status) }}</span>
                @endif
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-weight:900; color: {{ $tx->type === 'income' ? '#10b981' : '#ef4444' }}; font-size:1rem;">
                {{ $tx->type === 'income' ? '+' : '-' }} R$ {{ number_format($tx->amount, 2, ',', '.') }}
            </div>
        </div>
    </div>
    @empty
    <div style="text-align:center; padding:40px; color:#94a3b8; font-weight:600;">
        <i class="fas fa-inbox" style="font-size:2rem; color:#cbd5e1; display:block; margin-bottom:10px;"></i>
        Nenhuma transação vinculada a este cliente ainda.
    </div>
    @endforelse
</div>
@endsection
