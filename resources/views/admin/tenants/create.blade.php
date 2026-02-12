@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h6 style="color: #6366f1; font-weight: 700; text-transform: uppercase; margin: 0 0 5px 0;">SaaS Management</h6>
        <h2 style="margin: 0; color: #111827;">Novo Cliente</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Criação manual de organização e usuário mestre.</p>
    </div>
    <a href="{{ route('admin.tenants.index') }}" class="btn-outline" style="text-decoration: none;">Voltar para Lista</a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="vivensi-card">
            @if(session('error'))
                <div class="alert alert-danger mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('admin.tenants.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-12 mb-4">
                        <h5 style="color: #1e293b; font-weight: 700; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                            <i class="fas fa-building me-2"></i> Dados da Organização
                        </h5>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Nome da Organização</label>
                        <input type="text" name="tenant_name" class="form-control border-0 bg-light py-3 rounded-4 @error('tenant_name') is-invalid @enderror" value="{{ old('tenant_name') }}" placeholder="Ex: Instituto Vivensi" required>
                        @error('tenant_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6 mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Plano de Assinatura</label>
                        <select name="plan_id" class="form-select border-0 bg-light py-3 rounded-4 @error('plan_id') is-invalid @enderror" required>
                            <option value="">Selecione um plano...</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }} - R$ {{ number_format($plan->price, 2, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                        @error('plan_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12 mb-4">
                        <h5 style="color: #1e293b; font-weight: 700; margin-top: 20px; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                            <i class="fas fa-user-tie me-2"></i> Usuário Administrador (Mestre)
                        </h5>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Nome Completo</label>
                        <input type="text" name="name" class="form-control border-0 bg-light py-3 rounded-4 @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Ex: João Silva" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6 mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">E-mail de Acesso</label>
                        <input type="email" name="email" class="form-control border-0 bg-light py-3 rounded-4 @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="joao@exemplo.com" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6 mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Senha Inicial</label>
                        <input type="password" name="password" class="form-control border-0 bg-light py-3 rounded-4 @error('password') is-invalid @enderror" placeholder="Mínimo 8 caracteres" required>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6 mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Painel de Destino (Tipo de Conta)</label>
                        <select name="account_type" class="form-select border-0 bg-light py-3 rounded-4 @error('account_type') is-invalid @enderror" required>
                            <option value="ngo_admin" {{ old('account_type') == 'ngo_admin' ? 'selected' : '' }}>Terceiro Setor / ONG</option>
                            <option value="project_manager" {{ old('account_type') == 'project_manager' ? 'selected' : '' }}>Gestor de Projetos</option>
                            <option value="client" {{ old('account_type') == 'client' ? 'selected' : '' }}>Cliente Pessoal</option>
                        </select>
                        @error('account_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12 mb-4">
                        <h5 style="color: #1e293b; font-weight: 700; margin-top: 20px; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                            <i class="fas fa-credit-card me-2"></i> Configuração de Cobrança
                        </h5>
                    </div>

                    <div class="col-md-12 mb-4">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="billing-option-card">
                                    <input type="radio" name="billing_mode" value="trial" id="mode_trial" {{ old('billing_mode', 'trial') == 'trial' ? 'checked' : '' }}>
                                    <label for="mode_trial">
                                        <i class="fas fa-clock"></i>
                                        <strong>Trial (7 Dias)</strong>
                                        <span>Fluxo padrão gratuito</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="billing-option-card">
                                    <input type="radio" name="billing_mode" value="courtesy" id="mode_courtesy" {{ old('billing_mode') == 'courtesy' ? 'checked' : '' }}>
                                    <label for="mode_courtesy">
                                        <i class="fas fa-gift"></i>
                                        <strong>Cortesia</strong>
                                        <span>Acesso vitalício/grátis</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="billing-option-card">
                                    <input type="radio" name="billing_mode" value="manual_pay" id="mode_manual" {{ old('billing_mode') == 'manual_pay' ? 'checked' : '' }}>
                                    <label for="mode_manual">
                                        <i class="fas fa-link"></i>
                                        <strong>Gerar Pagamento</strong>
                                        <span>Bloqueado até pagar</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-top">
                    <button type="submit" class="btn btn-dark py-3 px-5 rounded-pill fw-bold shadow-lg">
                        Criar Conta e Usuário
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="vivensi-card bg-light border-0">
            <h5 style="color: #1e293b; font-weight: 700;">Dicas Importantes</h5>
            <ul style="color: #64748b; font-size: 0.9rem; padding-left: 20px; margin-top: 15px;">
                <li class="mb-3"><strong>Cortesia:</strong> Use para parceiros estratégicos ou testes internos. O status será 'Ativo' imediatamente.</li>
                <li class="mb-3"><strong>Gerar Pagamento:</strong> O cliente receberá acesso 'Pendente'. Você deve enviar o link de checkout para ele.</li>
                <li class="mb-3"><strong>Tipo de Conta:</strong> Garante que o usuário caia no painel correto após o primeiro login.</li>
            </ul>
        </div>
    </div>
</div>

<style>
    .billing-option-card {
        height: 100%;
    }
    .billing-option-card input {
        display: none;
    }
    .billing-option-card label {
        display: block;
        padding: 20px;
        background: #fff;
        border: 2px solid #f1f5f9;
        border-radius: 16px;
        cursor: pointer;
        text-align: center;
        transition: all 0.2s ease;
        height: 100%;
    }
    .billing-option-card i {
        font-size: 1.5rem;
        color: #94a3b8;
        display: block;
        margin-bottom: 10px;
    }
    .billing-option-card strong {
        display: block;
        color: #1e293b;
        margin-bottom: 5px;
    }
    .billing-option-card span {
        font-size: 0.75rem;
        color: #64748b;
    }
    .billing-option-card input:checked + label {
        border-color: #6366f1;
        background: #f5f3ff;
    }
    .billing-option-card input:checked + label i {
        color: #6366f1;
    }
</style>
@endsection
