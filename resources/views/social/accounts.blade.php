@extends('layouts.app')
@section('title', 'Redes Sociais – Contas Conectadas')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">Redes Sociais</h1>
            <p class="text-muted small mb-0">Conecte suas contas do Facebook e Instagram para agendar e publicar posts.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('social.posts.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-calendar-alt me-1"></i> Calendário de Posts
            </a>
            @if($configured)
                <a href="{{ route('social.facebook.connect') }}" class="btn btn-primary btn-sm">
                    <i class="fab fa-facebook me-1"></i> Conectar Facebook
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 rounded-3"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 rounded-3"><i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}</div>
    @endif


    @if(!$configured)
    <div class="alert alert-warning border-0 rounded-3 d-flex align-items-center gap-3">
        <i class="fas fa-exclamation-triangle fs-4 text-warning"></i>
        <div>
            <strong>Módulo não configurado.</strong>
            O administrador ainda não inseriu as credenciais do App Meta.
            @if(auth()->user()->role === 'super_admin')
                <a href="{{ url('/admin/settings') }}" class="alert-link">Configurar agora →</a>
            @endif
        </div>
    </div>
    @endif

    @if($accounts->isEmpty())
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <i class="fab fa-facebook text-muted" style="font-size:3rem;opacity:.3;"></i>
                <h5 class="mt-3 fw-bold">Nenhuma conta conectada</h5>
                <p class="text-muted small">Clique em "Conectar Facebook" para vincular suas páginas.</p>
            </div>
        </div>
    @else
    <div class="row g-3">
        @foreach($accounts as $account)
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-start gap-3 p-4">
                    @if($account->page_picture)
                        <img src="{{ $account->page_picture }}" alt="" class="rounded-circle" style="width:48px;height:48px;object-fit:cover;flex-shrink:0;">
                    @else
                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;flex-shrink:0;">
                            <i class="fab fa-facebook text-primary"></i>
                        </div>
                    @endif
                    <div class="flex-grow-1 min-width-0">
                        <h6 class="fw-bold mb-0 text-truncate">{{ $account->page_name }}</h6>
                        <small class="text-muted">ID: {{ $account->page_id }}</small>

                        @if($account->instagram_username)
                        <div class="mt-1">
                            <span class="badge bg-light text-dark border">
                                <i class="fab fa-instagram me-1" style="color:#e1306c;"></i>@{{ $account->instagram_username }}
                            </span>
                        </div>
                        @endif

                        <div class="mt-2 d-flex align-items-center gap-2 flex-wrap">
                            @if($account->is_active)
                                <span class="badge bg-success bg-opacity-15 text-success">Ativa</span>
                            @else
                                <span class="badge bg-danger bg-opacity-15 text-danger">Desconectada</span>
                            @endif

                            @if($account->token_expires_at)
                                @if($account->isTokenExpired())
                                    <span class="badge bg-danger bg-opacity-15 text-danger">Token expirado</span>
                                @elseif($account->isTokenExpiringSoon())
                                    <span class="badge bg-warning bg-opacity-15 text-warning">Expira em breve</span>
                                @else
                                    <span class="badge bg-light text-muted border" style="font-size:.65rem;">
                                        Expira {{ $account->token_expires_at->diffForHumans() }}
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white border-top d-flex gap-2 px-4 py-2">
                    <a href="{{ route('social.posts.create') }}?account={{ $account->id }}" class="btn btn-sm btn-outline-primary flex-fill">
                        <i class="fas fa-plus me-1"></i> Novo Post
                    </a>
                    <form action="{{ route('social.accounts.disconnect', $account) }}" method="POST" class="flex-fill">
                        @csrf @method('PATCH')
                        <button class="btn btn-sm btn-outline-secondary w-100">Desconectar</button>
                    </form>
                    <form action="{{ route('social.accounts.destroy', $account) }}" method="POST"
                          onsubmit="return confirm('Remover esta conta e todos os posts agendados?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

@endsection
