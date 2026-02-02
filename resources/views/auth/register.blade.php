<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vivensi - Criar Conta</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #2563EB; --primary-hover: #1D4ED8; }
        body { margin: 0; font-family: 'Outfit', sans-serif; background: #FAFAFA; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .register-card { width: 100%; max-width: 500px; background: white; padding: 40px; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.06); border: 1px solid rgba(0,0,0,0.03); }
        .brand-logo { display: flex; justify-content: center; margin-bottom: 20px; }
        .brand-logo img { max-height: 50px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #64748B; font-size: 0.9rem; font-weight: 500; }
        .form-control { width: 100%; padding: 12px 15px; border: 1px solid #E2E8F0; border-radius: 12px; font-size: 1rem; transition: all 0.3s; background: #F8FAFC; box-sizing: border-box; }
        .form-control:focus { background: white; border-color: var(--primary-color); box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); outline: none; }
        .btn-primary { width: 100%; padding: 14px; background: var(--primary-color); color: white; border: none; border-radius: 12px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s; margin-top: 10px; }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25); }
        .plan-summary { background: #F0F9FF; border: 1px solid #BAE6FD; padding: 15px; border-radius: 12px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body>

<div class="register-card">
    <div class="brand-logo">
        <img src="{{ asset('img/logovivensi.png') }}" alt="VIVENSI">
    </div>
    <h2 style="text-align: center; color: #1e293b; margin-bottom: 10px;">Comece sua Jornada</h2>
    <p style="text-align: center; color: #64748B; margin-bottom: 30px;">Crie sua organização e comece a gerir com inteligência.</p>

    @if($plan)
    <div class="plan-summary">
        <div>
            <span style="font-size: 0.8rem; color: #0369A1; font-weight: 700; text-transform: uppercase;">Plano Selecionado</span>
            <div style="font-weight: 700; color: #0C4A6E;">{{ $plan->name }}</div>
        </div>
        <div style="font-weight: 800; color: #2563EB;">R$ {{ number_format($plan->price, 2, ',', '.') }}</div>
    </div>
    @endif

    @if(session('error'))
        <div style="background: #FEF2F2; color: #DC2626; padding: 12px; border-radius: 8px; border: 1px solid #FECACA; margin-bottom: 20px; font-size: 0.9rem;">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background: #FEF2F2; color: #DC2626; padding: 12px; border-radius: 8px; border: 1px solid #FECACA; margin-bottom: 20px; font-size: 0.9rem;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ url('/register') }}" method="POST">
        @csrf
        <input type="hidden" name="plan_id" value="{{ $plan ? $plan->id : '' }}">

        <div class="form-group">
            <label>Nome da Organização / Empresa</label>
            <input type="text" name="organization_name" class="form-control" placeholder="Ex: Minha ONG ou Minha Empresa" required value="{{ old('organization_name') }}">
        </div>

        <div class="form-group">
            <label>Seu Nome Completo (Gestor)</label>
            <input type="text" name="name" class="form-control" placeholder="Seu nome" required value="{{ old('name') }}">
        </div>

        <div class="form-group">
            <label>E-mail de Acesso</label>
            <input type="email" name="email" class="form-control" placeholder="voce@email.com" required value="{{ old('email') }}">
        </div>

        <div class="row" style="display: flex; gap: 15px;">
            <div class="form-group" style="flex: 1;">
                <label>Senha</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="form-group" style="flex: 1;">
                <label>Confirmar Senha</label>
                <input type="password" name="password_confirmation" class="form-control" placeholder="••••••••" required>
            </div>
        </div>

        <button type="submit" class="btn-primary">
            Criar Minha Conta <i class="fas fa-rocket" style="margin-left: 8px;"></i>
        </button>
    </form>

    <div style="text-align: center; margin-top: 30px; font-size: 0.9rem; color: #64748B;">
        Já tem uma conta? <a href="{{ route('login') }}" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Acessar Login</a>
    </div>
</div>

</body>
</html>
