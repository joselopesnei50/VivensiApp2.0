@extends('layouts.app')

@section('content')
<div class="header-page mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h6 class="text-primary fw-700 text-uppercase mb-1 ls-1" style="font-size: 0.75rem;">Arrecadação Digital</h6>
            <h2 class="fw-800 mb-0">Rifas Online</h2>
            <p class="text-muted small mb-0">Crie e gerencie suas campanhas de sorteio para captação de recursos.</p>
        </div>
        <a href="{{ route('raffles.create') }}" class="btn btn-primary d-flex align-items-center gap-2 px-4 shadow-sm py-2 rounded-pill">
            <i class="bi bi-plus-lg"></i>
            <span class="fw-bold">Nova Rifa</span>
        </a>
    </div>
</div>

@if(!auth()->user()->tenant->pix_key)
<div class="card border-0 shadow-sm mb-4 overflow-hidden" style="border-radius: 20px; background: linear-gradient(135deg, #fef3c7 0%, #fffbeb 100%); border: 1px solid #fde68a !important;">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 45px; height: 45px; flex-shrink: 0;">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                </div>
                <div>
                    <h5 class="fw-800 mb-1 text-warning-emphasis">Atenção: Seu PIX ainda não foi configurado!</h5>
                    <p class="mb-0 text-muted small">Para receber os pagamentos das suas rifas e doações, você precisa cadastrar sua chave PIX oficial nas configurações da ONG.</p>
                </div>
            </div>
            <a href="{{ route('settings.branding') }}" class="btn btn-warning fw-800 px-4 rounded-pill shadow-sm py-2">
                CONFIGURAR AGORA
            </a>
        </div>
    </div>
</div>
@endif

<!-- Stats Summary -->
<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm transition-hover" style="border-radius: 18px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-shape bg-primary-soft text-primary rounded-circle p-3">
                        <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted small text-uppercase fw-bold ls-1 mb-0">Arrecadação Total</p>
                        <h3 class="mb-0 fw-800">R$ {{ number_format($raffles->sum(function($r){ return $r->tickets->where('status', 'paid')->count() * $r->ticket_price; }), 2, ',', '.') }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm transition-hover" style="border-radius: 18px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-shape bg-info-soft text-info rounded-circle p-3">
                        <i class="bi bi-people-fill fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted small text-uppercase fw-bold ls-1 mb-0">Partícipes Totais</p>
                        <h3 class="mb-0 fw-800">{{ $raffles->sum(function($r){ return $r->tickets->where('status', 'paid')->unique('buyer_email')->count(); }) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm transition-hover" style="border-radius: 18px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-shape bg-warning-soft text-warning rounded-circle p-3">
                        <i class="bi bi-trophy-fill fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted small text-uppercase fw-bold ls-1 mb-0">Rifas em Andamento</p>
                        <h3 class="mb-0 fw-800">{{ $raffles->where('status', 'active')->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Raffles List -->
<div class="row g-4 pb-5">
    @forelse($raffles as $raffle)
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm h-100 overflow-hidden" style="border-radius: 20px;">
            @if($raffle->image_path)
            <div class="position-relative" style="height: 160px;">
                <img src="{{ Storage::url($raffle->image_path) }}" class="w-100 h-100 object-fit-cover">
                <div class="position-absolute top-0 end-0 m-3">
                    <span class="badge {{ $raffle->status == 'active' ? 'bg-success' : 'bg-secondary' }} rounded-pill px-3 py-2 shadow-sm fw-bold">
                        {{ $raffle->status == 'active' ? 'ATIVA' : 'INATIVA' }}
                    </span>
                </div>
            </div>
            @else
            <div class="bg-light d-flex align-items-center justify-content-center position-relative" style="height: 160px; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%) !important;">
                <i class="bi bi-ticket-perforated fs-1 opacity-10"></i>
                <div class="position-absolute top-0 end-0 m-3">
                    <span class="badge {{ $raffle->status == 'active' ? 'bg-success' : 'bg-secondary' }} rounded-pill px-3 py-2 shadow-sm fw-bold">
                        {{ $raffle->status == 'active' ? 'ATIVA' : 'INATIVA' }}
                    </span>
                </div>
            </div>
            @endif
            
            <div class="card-body p-4 d-flex flex-column">
                <h5 class="fw-bold mb-2">{{ $raffle->title }}</h5>
                <p class="text-muted small mb-4 flex-grow-1 text-truncate-2">{{ $raffle->description }}</p>
                
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted fw-bold">Progresso de Vendas</span>
                        <span class="fw-800">{{ $raffle->tickets->where('status', 'paid')->count() }} / {{ $raffle->total_tickets }}</span>
                    </div>
                    <div class="progress rounded-pill" style="height: 6px; background: #f1f5f9;">
                        @php 
                            $percent = ($raffle->tickets->where('status', 'paid')->count() / $raffle->total_tickets) * 100;
                        @endphp
                        <div class="progress-bar rounded-pill bg-primary" role="progressbar" style="width: {{ $percent }}%"></div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-auto pt-3 border-top border-light">
                    <div>
                        <p class="mb-0 small text-muted text-uppercase fw-700 ls-1" style="font-size: 0.65rem;">Bilhete</p>
                        <h5 class="mb-0 fw-800 text-primary">R$ {{ number_format($raffle->ticket_price, 2, ',', '.') }}</h5>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('raffles.show', $raffle) }}" class="btn btn-outline-primary btn-sm rounded-pill p-2 shadow-none" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('public.raffle.show', $raffle->slug) }}" target="_blank" class="btn btn-primary btn-sm rounded-pill p-2" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-link-45deg fs-5"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 py-5">
        <div class="card border-0 shadow-sm text-center p-5" style="border-radius: 24px;">
            <div class="mb-4">
                <i class="bi bi-ticket-perforated text-muted opacity-25" style="font-size: 5rem;"></i>
            </div>
            <h4 class="fw-bold">Nenhuma rifa criada ainda</h4>
            <p class="text-muted mb-4 mx-auto" style="max-width: 400px;">Crie sua primeira campanha agora e comece a arrecadar fundos de forma profissional.</p>
            <a href="{{ route('raffles.create') }}" class="btn btn-primary px-5 rounded-pill fw-bold">CRIAR MINHA PRIMEIRA RIFA</a>
        </div>
    </div>
    @endforelse
</div>

<style>
    .icon-shape {
        width: 54px;
        height: 54px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .bg-primary-soft { background: rgba(67, 97, 238, 0.1); }
    .bg-info-soft { background: rgba(76, 201, 240, 0.1); }
    .bg-warning-soft { background: rgba(247, 37, 133, 0.1); }
    
    .ls-1 { letter-spacing: 1px; }
    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .transition-hover {
        transition: all 0.3s ease;
    }
    .transition-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px -10px rgba(0,0,0,0.1) !important;
    }
    .fw-800 { font-weight: 800; }
    .fw-700 { font-weight: 700; }
</style>
@endsection
