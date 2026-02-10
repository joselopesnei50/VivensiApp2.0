@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h6 style="color: #6366f1; font-weight: 700; text-transform: uppercase; margin: 0 0 5px 0;">SaaS Management</h6>
        <h2 style="margin: 0; color: #111827;">Detalhes da Organização</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Informações completas sobre o tenant #{{ $tenant->id }}</p>
    </div>
    <a href="{{ url('/admin/tenants') }}" class="btn-outline">
        <i class="fas fa-arrow-left me-2"></i> Voltar
    </a>
</div>

<div class="row" style="display: flex; gap: 30px; flex-wrap: wrap;">
    <!-- Tenant Info -->
    <div class="col-8" style="flex: 2; min-width: 300px;">
        <div class="vivensi-card">
            <h3 style="margin-top: 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 20px;">
                <i class="fas fa-building me-2" style="color: #6366f1;"></i> Dados da Organização
            </h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label style="font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Nome</label>
                    <div style="font-size: 1.1rem; color: #1e293b; font-weight: 600;">{{ $tenant->name }}</div>
                </div>
                <div>
                    <label style="font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Documento</label>
                    <div style="font-size: 1.1rem; color: #1e293b;">{{ $tenant->document ?? 'Não informado' }}</div>
                </div>
                <div>
                    <label style="font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Criado em</label>
                    <div style="font-size: 1rem; color: #1e293b;">{{ $tenant->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <div>
                    <label style="font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Status</label>
                    <div>
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
                        <span style="color: {{ $st['color'] }}; background: {{ $st['bg'] }}; padding: 4px 10px; border-radius: 8px; font-size: 0.85rem; font-weight: 700;">
                            {{ strtoupper($st['label']) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="vivensi-card" style="margin-top: 30px;">
            <h3 style="margin-top: 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 20px;">
                <i class="fas fa-user-shield me-2" style="color: #6366f1;"></i> Administrador Principal
            </h3>
            
            @if($user)
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div style="width: 60px; height: 60px; background: #e0e7ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #4338ca; font-size: 1.5rem; font-weight: 700;">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                    <div>
                        <div style="font-size: 1.1rem; font-weight: 700; color: #1e293b;">{{ $user->name }}</div>
                        <div style="color: #64748b;">{{ $user->email }}</div>
                        <div style="font-size: 0.85rem; color: #94a3b8; margin-top: 5px;">
                            Último login: {{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->diffForHumans() : 'Nunca logou' }}
                        </div>
                    </div>
                </div>
            @else
                <p class="text-muted">Nenhum usuário vinculado a este tenant.</p>
            @endif
        </div>
    </div>

    <!-- Sidebar Plan -->
    <div class="col-4" style="flex: 1; min-width: 250px;">
        <div class="vivensi-card" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white;">
            <h3 style="margin-top: 0; color: white; opacity: 0.9; font-size: 1rem; text-transform: uppercase;">Plano Atual</h3>
            
            @if($plan)
                <div style="font-size: 2rem; font-weight: 800; margin: 15px 0;">{{ $plan->name }}</div>
                <div style="font-size: 1.5rem; opacity: 0.8;">R$ {{ number_format($plan->price, 2, ',', '.') }}<span style="font-size: 0.9rem;">/{{ $plan->interval }}</span></div>
                
                <hr style="border-color: rgba(255,255,255,0.1); margin: 20px 0;">
                
                <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.9rem; opacity: 0.8;">
                    @if($tenant->trial_ends_at && \Carbon\Carbon::parse($tenant->trial_ends_at)->isFuture())
                        <li style="margin-bottom: 10px;"><i class="fas fa-clock me-2"></i> Trial até {{ \Carbon\Carbon::parse($tenant->trial_ends_at)->format('d/m/Y') }}</li>
                    @endif
                    <li><i class="fas fa-check-circle me-2"></i> Assinatura {{ $tenant->subscription_status }}</li>
                </ul>
            @else
                <p>Sem plano ativo</p>
            @endif
        </div>

        <div class="vivensi-card" style="margin-top: 30px; border: 1px solid #e2e8f0;">
            <h4 style="color: #1e293b; margin-top: 0;">Ações Administrativas</h4>
            <p style="font-size: 0.85rem; color: #64748b;">Gerencie o acesso desta organização.</p>
            
            @if($tenant->subscription_status === 'suspended')
                <form action="{{ route('admin.tenants.activate', $tenant->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja reativar o acesso desta organização?');">
                    @csrf
                    <button type="submit" class="btn btn-success" style="width: 100%; background: #22c55e; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: 700; cursor: pointer;">
                        <i class="fas fa-unlock me-2"></i> Reativar Acesso
                    </button>
                </form>
            @else
                <form action="{{ route('admin.tenants.suspend', $tenant->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja SUSPENDER o acesso desta organização? Os usuários não conseguirão mais logar.');">
                    @csrf
                    <button type="submit" class="btn btn-warning" style="width: 100%; background: #f59e0b; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: 700; cursor: pointer;">
                        <i class="fas fa-ban me-2"></i> Suspender Acesso
                    </button>
                </form>
            @endif

            <div style="margin-top: 15px; border-top: 1px solid #f1f5f9; padding-top: 15px;">
                <button class="btn btn-danger" style="width: 100%; background: #fee2e2; color: #dc2626; border: none; padding: 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem;" disabled title="Funcionalidade desabilitada por segurança">
                    <i class="fas fa-trash me-2"></i> Excluir Organização (Desabilitado)
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
