@extends('layouts.app')
@section('title', 'Destine seu IR — Inscrições')

@push('styles')
<style>
    .s-hero { background: linear-gradient(135deg, #0b4f6c 0%, #145676 100%); color: #fff; border-radius: 18px; padding: 24px 30px; margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; }
    .s-hero h1 { margin: 0; font-size: 1.4rem; font-weight: 800; }
    .s-hero p { margin: 6px 0 0; opacity: .9; font-size: .95rem; }
    .s-csv-btn { background:#f5b027; color:#0b4f6c; padding:10px 18px; border-radius:10px; font-weight:800; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
    .s-csv-btn:hover { background:#f0a018; color:#0b4f6c; }

    .s-filters { display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap; }
    .s-filter { padding:10px 18px; background:#fff; color:#64748b; border-radius:10px; text-decoration:none; font-weight:700; font-size:.88rem; border:2px solid transparent; }
    .s-filter--on { background:#0b4f6c; color:#fff; border-color:#f5b027; }
    .s-filter__badge { background:#f1f5f9; color:#334155; padding:2px 8px; border-radius:999px; font-size:.72rem; font-weight:800; margin-left:6px; }
    .s-filter--on .s-filter__badge { background:#f5b027; color:#0b4f6c; }

    .s-table-wrap { background:#fff; border-radius:14px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.06); }
    .s-table { width:100%; border-collapse:collapse; }
    .s-table th { background:#f1f5f9; text-align:left; padding:12px 16px; font-size:.78rem; text-transform:uppercase; color:#475569; font-weight:700; letter-spacing:.5px; }
    .s-table td { padding:14px 16px; border-top:1px solid #f1f5f9; font-size:.9rem; vertical-align:top; }
    .s-table tr:hover td { background:#f8fafc; }
    .s-mono { font-family: ui-monospace, 'SF Mono', monospace; font-size:.86rem; }
    .s-chip { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:.72rem; font-weight:700; }
    .s-chip--partner { background:#dbeafe; color:#1e40af; }
    .s-chip--pending { background:#fef3c7; color:#92400e; }
    .s-chip--notified { background:#d1fae5; color:#065f46; }
    .s-btn-notified { background:#0b4f6c; color:#fff; border:none; border-radius:6px; padding:5px 12px; font-weight:700; font-size:.76rem; cursor:pointer; }
    .s-btn-notified--undo { background:#fff; color:#0b4f6c; border:1px solid #0b4f6c; }

    .s-empty { padding:60px 20px; text-align:center; color:#9ca3af; }
    .s-flash { background:rgba(16,185,129,.08); border-left:4px solid #10b981; color:#065f46; padding:12px 16px; border-radius:8px; margin-bottom:18px; font-weight:600; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="s-hero">
        <div>
            <h1><i class="fas fa-user-plus"></i> Inscrições na reunião online</h1>
            <p>Interessados em participar da reunião coletiva sobre destinação de IR.</p>
        </div>
        <a href="{{ route('admin.destine-ir.signups.csv') }}" class="s-csv-btn">
            <i class="fas fa-file-csv"></i> Baixar CSV
        </a>
    </div>

    @if(session('status'))
        <div class="s-flash"><i class="fas fa-check-circle"></i> {{ session('status') }}</div>
    @endif

    <div class="s-filters">
        <a href="{{ route('admin.destine-ir.signups', ['status' => 'all']) }}" class="s-filter {{ $filter === 'all' ? 's-filter--on' : '' }}">
            Todas <span class="s-filter__badge">{{ $totals['all'] }}</span>
        </a>
        <a href="{{ route('admin.destine-ir.signups', ['status' => 'pending']) }}" class="s-filter {{ $filter === 'pending' ? 's-filter--on' : '' }}">
            Pendentes <span class="s-filter__badge">{{ $totals['pending'] }}</span>
        </a>
        <a href="{{ route('admin.destine-ir.signups', ['status' => 'notified']) }}" class="s-filter {{ $filter === 'notified' ? 's-filter--on' : '' }}">
            Notificadas <span class="s-filter__badge">{{ $totals['notified'] }}</span>
        </a>
    </div>

    <div class="s-table-wrap">
        <table class="s-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Nome</th>
                    <th>Contato</th>
                    <th>Veio por</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($signups as $s)
                    <tr>
                        <td class="s-mono">{{ optional($s->created_at)->format('d/m/Y H:i') }}</td>
                        <td style="font-weight:600;">{{ $s->name }}</td>
                        <td>
                            <div class="s-mono">{{ $s->email }}</div>
                            @if($s->phone)
                                <div class="s-mono" style="color:#6b7280; font-size:.8rem; margin-top:2px;">
                                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $s->phone) }}" target="_blank" style="color:#25d366; text-decoration:none;">
                                        <i class="fab fa-whatsapp"></i> {{ $s->phone }}
                                    </a>
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($s->partner)
                                <span class="s-chip s-chip--partner">{{ $s->partner->name }}</span>
                            @else
                                <span style="color:#9ca3af; font-size:.82rem;">link direto</span>
                            @endif
                        </td>
                        <td>
                            @if($s->notified_at)
                                <span class="s-chip s-chip--notified"><i class="fas fa-check"></i> Notificado</span>
                                <div style="font-size:.72rem; color:#6b7280; margin-top:4px;">em {{ $s->notified_at->format('d/m H:i') }}</div>
                            @else
                                <span class="s-chip s-chip--pending">Pendente</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <form method="POST" action="{{ route('admin.destine-ir.signups.notified', $s) }}">
                                @csrf
                                @if($s->notified_at)
                                    <button type="submit" class="s-btn-notified s-btn-notified--undo">Desmarcar</button>
                                @else
                                    <button type="submit" class="s-btn-notified">Marcar notificado</button>
                                @endif
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="s-empty"><i class="fas fa-inbox" style="font-size:2rem; display:block; margin-bottom:10px;"></i>Ninguém se inscreveu ainda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($signups->hasPages())
        <div style="margin-top:20px;">
            {{ $signups->links() }}
        </div>
    @endif
</div>
@endsection
