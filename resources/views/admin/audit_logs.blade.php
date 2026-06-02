@extends('layouts.app')

@section('content')
<div style="max-width:1100px;margin:0 auto;padding:24px 16px;">

    <div style="margin-bottom:24px;">
        <a href="{{ route('admin.dashboard') }}" style="color:#6366f1;font-size:0.8rem;font-weight:700;text-decoration:none;">
            <i class="fas fa-arrow-left me-1"></i> Voltar ao Painel
        </a>
        <h2 style="margin:10px 0 4px;font-weight:950;font-size:1.6rem;letter-spacing:-1px;">
            <i class="fas fa-shield-halved me-2" style="color:#6366f1;"></i>Log de Auditoria Admin
        </h2>
        <p style="color:#64748b;font-size:0.85rem;">Registro de todas as ações críticas realizadas pelo super admin.</p>
    </div>

    <div style="background:white;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                    <th style="padding:12px 16px;text-align:left;font-weight:800;color:#475569;">Data/Hora</th>
                    <th style="padding:12px 16px;text-align:left;font-weight:800;color:#475569;">Admin</th>
                    <th style="padding:12px 16px;text-align:left;font-weight:800;color:#475569;">Ação</th>
                    <th style="padding:12px 16px;text-align:left;font-weight:800;color:#475569;">Organização</th>
                    <th style="padding:12px 16px;text-align:left;font-weight:800;color:#475569;">Detalhes</th>
                    <th style="padding:12px 16px;text-align:left;font-weight:800;color:#475569;">IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                @php
                    $actionConfig = match($log->action) {
                        'tenant.suspend' => ['label' => 'Suspensão',   'color' => '#f59e0b', 'bg' => '#fef3c7', 'icon' => 'fa-ban'],
                        'tenant.activate'=> ['label' => 'Reativação',  'color' => '#10b981', 'bg' => '#d1fae5', 'icon' => 'fa-circle-check'],
                        'tenant.delete'  => ['label' => 'Deleção',     'color' => '#ef4444', 'bg' => '#fee2e2', 'icon' => 'fa-trash'],
                        'tenant.create'  => ['label' => 'Criação',     'color' => '#6366f1', 'bg' => '#ede9fe', 'icon' => 'fa-plus'],
                        default          => ['label' => $log->action,  'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'fa-circle-info'],
                    };
                    $ctx = $log->context ?? [];
                @endphp
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:12px 16px;color:#475569;white-space:nowrap;">
                        {{ $log->created_at->format('d/m/Y') }}<br>
                        <span style="font-size:0.75rem;color:#94a3b8;">{{ $log->created_at->format('H:i:s') }}</span>
                    </td>
                    <td style="padding:12px 16px;">
                        <span style="font-weight:700;color:#1e293b;">{{ $log->admin->name ?? 'Sistema' }}</span>
                    </td>
                    <td style="padding:12px 16px;">
                        <span style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:20px;font-weight:700;font-size:0.78rem;background:{{ $actionConfig['bg'] }};color:{{ $actionConfig['color'] }};">
                            <i class="fas {{ $actionConfig['icon'] }}"></i>
                            {{ $actionConfig['label'] }}
                        </span>
                    </td>
                    <td style="padding:12px 16px;">
                        <span style="font-weight:700;color:#1e293b;">{{ $log->target_name ?? '—' }}</span>
                        @if($log->target_id)
                            <br><span style="font-size:0.72rem;color:#94a3b8;">ID #{{ $log->target_id }}</span>
                        @endif
                    </td>
                    <td style="padding:12px 16px;color:#475569;font-size:0.8rem;">
                        @if($log->action === 'tenant.suspend' || $log->action === 'tenant.activate')
                            {{ $ctx['previous_status'] ?? '?' }} → {{ $ctx['new_status'] ?? '?' }}
                        @elseif($log->action === 'tenant.delete')
                            {{ $ctx['user_count'] ?? 0 }} usuário(s) removido(s)<br>
                            <span style="color:#94a3b8;">{{ $ctx['tenant_email'] ?? '' }}</span>
                        @elseif($log->action === 'tenant.create')
                            {{ $ctx['account_type'] ?? '' }} · {{ $ctx['billing_mode'] ?? '' }}<br>
                            <span style="color:#94a3b8;">{{ $ctx['plan_name'] ?? '' }}</span>
                        @else
                            <pre style="margin:0;font-size:0.72rem;">{{ json_encode($ctx, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                        @endif
                    </td>
                    <td style="padding:12px 16px;color:#94a3b8;font-size:0.78rem;">
                        {{ $log->ip_address ?? '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding:40px;text-align:center;color:#94a3b8;">
                        <i class="fas fa-shield-halved" style="font-size:2rem;margin-bottom:10px;display:block;"></i>
                        Nenhuma ação registrada ainda.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div style="margin-top:20px;">
        {{ $logs->links() }}
    </div>
    @endif

</div>
@endsection
