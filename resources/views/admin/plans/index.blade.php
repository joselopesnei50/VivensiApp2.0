@extends('layouts.app')

@section('content')
<div class="header-page d-flex justify-content-between align-items-center" style="margin-bottom: 30px;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Planos de Assinatura</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Gerencie os planos para ONGs, Gestores e Pessoa Comum.</p>
    </div>
    <a href="{{ route('admin.plans.create') }}" class="btn-premium text-decoration-none">
        <i class="fas fa-plus me-2"></i> Criar Novo Plano
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
        {{ session('success') }}
    </div>
@endif

<div class="row g-4">
    @php
        $audiences = [
            'ngo' => ['label' => 'Terceiro Setor (ONG)', 'icon' => 'fa-landmark', 'color' => '#6366f1'],
            'manager' => ['label' => 'Gestor de Empresas', 'icon' => 'fa-user-shield', 'color' => '#10b981'],
            'common' => ['label' => 'Pessoa Comum', 'icon' => 'fa-user', 'color' => '#f59e0b']
        ];
    @endphp

    @forelse($plans as $plan)
    <div class="col-md-4">
        <div class="vivensi-card h-100 position-relative">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="badge rounded-pill px-3 py-2" style="background: {{ $audiences[$plan->target_audience]['color'] }}20; color: {{ $audiences[$plan->target_audience]['color'] }}; font-weight: 700;">
                    <i class="fas {{ $audiences[$plan->target_audience]['icon'] }} me-1"></i>
                    {{ $audiences[$plan->target_audience]['label'] }}
                </span>
                <div class="dropdown">
                    <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-3">
                        <li><a class="dropdown-item" href="{{ route('admin.plans.edit', $plan->id) }}"><i class="fas fa-edit me-2"></i> Editar</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('admin.plans.destroy', $plan->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este plano?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash me-2"></i> Excluir</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>

            <h3 class="fw-bold mb-1">{{ $plan->name }}</h3>
            <div class="d-flex align-items-baseline mb-4">
                <span class="fs-2 fw-800">R$ {{ number_format($plan->price, 2, ',', '.') }}</span>
                <span class="text-muted ms-2">/ {{ $plan->interval == 'monthly' ? 'mês' : 'ano' }}</span>
            </div>

            <div class="features-list mb-4">
                <p class="small fw-bold text-muted text-uppercase mb-2">Recursos Inclusos:</p>
                <ul class="list-unstyled">
                    @if($plan->features)
                        @foreach($plan->features as $feature)
                            <li class="mb-2 d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <span class="small">{{ $feature }}</span>
                            </li>
                        @endforeach
                    @else
                        <li class="text-muted small italic">Nenhum recurso listado.</li>
                    @endif
                </ul>
            </div>

            <div class="mt-auto pt-3 border-top">
                <div class="form-check form-switch m-0">
                    <input class="form-check-switch" type="checkbox" role="switch" {{ $plan->is_active ? 'checked' : '' }} disabled>
                    <label class="form-check-label small fw-bold {{ $plan->is_active ? 'text-success' : 'text-danger' }}">
                        {{ $plan->is_active ? 'Plano Ativo' : 'Plano Inativo' }}
                    </label>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 text-center py-5">
        <div class="vivensi-card">
            <i class="fas fa-box-open fa-3x text-muted opacity-20 mb-3"></i>
            <p class="text-muted">Nenhum plano cadastrado ainda.</p>
            <a href="{{ route('admin.plans.create') }}" class="btn btn-primary rounded-pill px-4 fw-bold">Criar Primeiro Plano</a>
        </div>
    </div>
    @endforelse
</div>

<style>
    .fw-800 { font-weight: 800; }
    .features-list { min-height: 120px; }
</style>
@endsection
