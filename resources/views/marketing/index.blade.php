@extends('layouts.app')
@section('title', 'Hub de Marketing Estratégico')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1" style="color:#1e293b;">
                <i class="fas fa-brain me-2" style="color:#4f46e5;"></i> Hub de Planejamento Estratégico
            </h4>
            <p class="text-muted small mb-0">Gere planos de marketing completos com IA em segundos.</p>
        </div>
        <a href="{{ route('marketing.create') }}" class="btn btn-primary rounded-pill px-4 fw-bold">
            <i class="fas fa-plus me-2"></i> Novo Plano
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success rounded-3 border-0 mb-4">{{ session('success') }}</div>
    @endif

    @if($plans->isEmpty())
        <div class="card border-0 shadow-sm rounded-4 text-center py-5">
            <i class="fas fa-map fa-3x mb-3" style="color:#c7d2fe;"></i>
            <h5 class="text-muted">Nenhum plano criado ainda.</h5>
            <p class="text-muted small">Clique em "Novo Plano" para gerar seu primeiro mapa estratégico com IA.</p>
            <a href="{{ route('marketing.create') }}" class="btn btn-primary rounded-pill px-4 fw-bold mx-auto mt-2" style="width:fit-content;">
                Criar agora
            </a>
        </div>
    @else
    <div class="row g-3">
        @foreach($plans as $plan)
        @php
            $statusColor = match($plan->status) {
                'done'       => ['bg' => '#f0fdf4', 'color' => '#16a34a', 'label' => 'Concluído'],
                'processing' => ['bg' => '#eff6ff', 'color' => '#2563eb', 'label' => 'Processando'],
                'pending'    => ['bg' => '#fefce8', 'color' => '#ca8a04', 'label' => 'Aguardando'],
                default      => ['bg' => '#fef2f2', 'color' => '#dc2626', 'label' => 'Falhou'],
            };
        @endphp
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="transition:transform .2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform=''">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:44px;height:44px;background:linear-gradient(135deg,#4f46e5,#7c3aed);">
                            <i class="fas fa-sitemap text-white"></i>
                        </div>
                        <span class="badge rounded-pill fw-700" style="background:{{ $statusColor['bg'] }};color:{{ $statusColor['color'] }};font-size:.72rem;">
                            {{ $statusColor['label'] }}
                        </span>
                    </div>
                    <h6 class="fw-bold mb-1" style="color:#1e293b;">{{ mb_substr($plan->title ?? $plan->objective, 0, 60) }}</h6>
                    <p class="text-muted small mb-3">{{ mb_substr($plan->objective, 0, 80) }}...</p>
                    <div class="d-flex gap-2 flex-wrap mb-3">
                        <span class="badge bg-light text-dark border">{{ $plan->scope === 'online_offline' ? 'Online + Presencial' : 'Online' }}</span>
                        @if($plan->ai_provider)
                            <span class="badge bg-light text-dark border"><i class="fas fa-robot me-1"></i> Bruce AI</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('marketing.show', $plan->id) }}" class="btn btn-sm btn-primary rounded-pill flex-fill fw-bold">
                            <i class="fas fa-eye me-1"></i> Ver Mapa
                        </a>
                        <form action="{{ route('marketing.destroy', $plan->id) }}" method="POST" onsubmit="return confirm('Remover este plano?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger rounded-pill px-3"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                    <div class="mt-3 text-muted" style="font-size:.72rem;">
                        <i class="fas fa-clock me-1"></i> {{ $plan->created_at->format('d/m/Y H:i') }}
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <div class="mt-4">{{ $plans->links() }}</div>
    @endif
</div>
@endsection
