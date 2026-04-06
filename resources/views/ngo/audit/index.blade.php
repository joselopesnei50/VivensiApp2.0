@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 18px; display:flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Central de Auditoria</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Rastreabilidade completa de ações sensíveis do sistema.</p>
    </div>
    <div style="display:flex; gap: 10px; flex-wrap: wrap;">
        <a class="btn-premium" href="{{ url('/ngo/audit/export?'.http_build_query(request()->query())) }}" style="background:#4f46e5;">
            <i class="fas fa-file-csv"></i> Exportar CSV
        </a>
        <button type="button" onclick="window.print()" class="btn-premium" style="background:#475569;">
            <i class="fas fa-print"></i> Imprimir
        </button>
    </div>
</div>

<div class="vivensi-card" style="margin-bottom: 14px;">
    <form method="GET" action="{{ url('/ngo/audit') }}" style="display:flex; gap: 10px; flex-wrap: wrap; align-items:end;">
        <div style="flex: 1; min-width: 220px;">
            <label style="display:block; font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom: 6px;">Busca</label>
            <input type="text" name="q" value="{{ request('q') }}" class="form-control-vivensi" placeholder="Tipo, evento, IP, URL...">
        </div>
        <div style="min-width: 160px;">
            <label style="display:block; font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom: 6px;">Evento</label>
            <select name="event" class="form-control-vivensi">
                <option value="">Todos</option>
                @foreach(($events ?? []) as $ev)
                    <option value="{{ $ev }}" {{ request('event') === $ev ? 'selected' : '' }}>{{ strtoupper($ev) }}</option>
                @endforeach
            </select>
        </div>
        <div style="min-width: 200px;">
            <label style="display:block; font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom: 6px;">Usuário</label>
            <select name="user_id" class="form-control-vivensi">
                <option value="">Todos</option>
                @foreach(($users ?? []) as $u)
                    <option value="{{ $u->id }}" {{ (string)request('user_id') === (string)$u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="min-width: 150px;">
            <label style="display:block; font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom: 6px;">De</label>
            <input type="date" name="from" value="{{ request('from') }}" class="form-control-vivensi">
        </div>
        <div style="min-width: 150px;">
            <label style="display:block; font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom: 6px;">Até</label>
            <input type="date" name="to" value="{{ request('to') }}" class="form-control-vivensi">
        </div>
        <div style="display:flex; gap: 10px;">
            <button type="submit" class="btn-premium" style="justify-content:center;">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            <a href="{{ url('/ngo/audit') }}" class="btn-premium" style="background:#f1f5f9; color:#0f172a; border:1px solid #e2e8f0;">
                Limpar
            </a>
        </div>
    </form>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <tr>
                <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Data / Hora</th>
                <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Usuário</th>
                <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Evento</th>
                <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Módulo / Item</th>
                <th style="padding: 15px; text-align: center; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">IP</th>
                <th style="padding: 15px; text-align: center; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Ação</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 15px; color: #475569; font-size: 0.9rem;">
                    {{ $log->created_at->format('d/m/Y H:i:s') }}
                </td>
                <td style="padding: 15px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 30px; height: 30px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700;">
                            {{ strtoupper(substr($log->user->name ?? '?', 0, 1)) }}
                        </div>
                        <span style="color: #1e293b; font-weight: 500;">{{ $log->user->name ?? 'Sistema' }}</span>
                    </div>
                </td>
                <td style="padding: 15px;">
                    @php
                        $badgeColor = match($log->event) {
                            'created' => '#dcfce7; color: #16a34a',
                            'updated' => '#fef9c3; color: #ca8a04',
                            'deleted' => '#fecaca; color: #dc2626',
                            default => '#f1f5f9; color: #64748b'
                        };
                    @endphp
                    <span style="background: {{ explode(';', $badgeColor)[0] }}; {{ explode(';', $badgeColor)[1] }}; padding: 3px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                        {{ $log->event }}
                    </span>
                </td>
                <td style="padding: 15px; color: #475569; font-size: 0.9rem;">
                    <strong>{{ last(explode('\\', $log->auditable_type)) }}</strong>
                    <span style="color: #94a3b8;">(ID: {{ $log->auditable_id }})</span>
                </td>
                <td style="padding: 15px; text-align: center; color: #94a3b8; font-size: 0.8rem;">
                    {{ $log->ip_address }}
                </td>
                <td style="padding: 15px; text-align: center;">
                    <a href="{{ url('/ngo/audit/'.$log->id) }}" title="Ver detalhes" style="background: none; border: none; color: #4f46e5; cursor: pointer; text-decoration:none;">
                        <i class="fas fa-search-plus"></i>
                    </a>
                </td>
            </tr>
            @endforeach
            @if($logs->isEmpty())
                <tr>
                    <td colspan="6" style="padding: 40px; text-align:center; color:#94a3b8;">
                        Nenhum registro encontrado com os filtros atuais.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
    <div style="padding: 20px;">
        {{ $logs->links() }}
    </div>
</div>

<style>
@media print {
    .btn-premium, form, #sidebar, .header-main { display: none !important; }
    .main-content { margin: 0 !important; width: 100% !important; border: none; }
    .vivensi-card { box-shadow: none; border: none; }
}
</style>
@endsection
