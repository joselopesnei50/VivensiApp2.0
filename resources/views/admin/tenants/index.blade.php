@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h6 style="color: #6366f1; font-weight: 700; text-transform: uppercase; margin: 0 0 5px 0;">SaaS Management</h6>
        <h2 style="margin: 0; color: #111827;">Organizações (Tenants)</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Gestão completa da base de clientes.</p>
    </div>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <tr>
                <th style="padding: 15px 25px; text-align: left; font-size: 0.8rem; color: #64748b; font-weight: 600; text-transform: uppercase;">ID</th>
                <th style="padding: 15px 25px; text-align: left; font-size: 0.8rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Organização</th>
                <th style="padding: 15px 25px; text-align: left; font-size: 0.8rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Plano</th>
                <th style="padding: 15px 25px; text-align: center; font-size: 0.8rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Status</th>
                <th style="padding: 15px 25px; text-align: right; font-size: 0.8rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tenants as $tenant)
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 15px 25px; color: #64748b;">#{{ $tenant->id }}</td>
                <td style="padding: 15px 25px; color: #334155;">
                    <strong style="display: block;">{{ $tenant->name }}</strong>
                    <span style="font-size: 0.8rem; color: #94a3b8;">Doc: {{ $tenant->document ?? 'N/A' }}</span>
                </td>
                <td style="padding: 15px 25px;">
                    <span style="background: #e0f2fe; color: #0284c7; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                        {{ $tenant->plan_type }}
                    </span>
                </td>
                <td style="padding: 15px 25px; text-align: center;">
                    @php
                        $statusColors = [
                            'active' => ['color' => '#16a34a', 'bg' => '#f0fdf4', 'label' => 'Ativo'],
                            'pending' => ['color' => '#ca8a04', 'bg' => '#fefce8', 'label' => 'Pendente'],
                            'trialing' => ['color' => '#2563eb', 'bg' => '#eff6ff', 'label' => 'Trial'],
                            'past_due' => ['color' => '#dc2626', 'bg' => '#fef2f2', 'label' => 'Atrasado'],
                            'canceled' => ['color' => '#64748b', 'bg' => '#f8fafc', 'label' => 'Cancelado']
                        ];
                        $st = $statusColors[$tenant->subscription_status] ?? ['color' => '#64748b', 'bg' => '#f1f5f9', 'label' => $tenant->subscription_status];
                    @endphp
                    <span style="color: {{ $st['color'] }}; background: {{ $st['bg'] }}; padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 700;">
                        <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 5px; vertical-align: middle;"></i>
                        {{ strtoupper($st['label']) }}
                    </span>
                </td>
                <td style="padding: 15px 25px; text-align: right;">
                    <a href="#" class="btn-outline" style="padding: 6px 12px; border-radius: 6px; font-size: 0.8rem;">Detalhes</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div style="padding: 20px;">
        {{ $tenants->links() }}
    </div>
</div>
@endsection
