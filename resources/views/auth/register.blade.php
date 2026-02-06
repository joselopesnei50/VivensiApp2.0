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
        
        /* Role Selector Styles */
        .role-selector { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 10px; }
        .role-option { flex: 1; min-width: 140px; padding: 18px 12px; border: 2px solid #E2E8F0; border-radius: 12px; text-align: center; cursor: pointer; transition: all 0.3s; background: #FAFAFA; }
        .role-option:hover { border-color: #2563EB; background: #F0F9FF; transform: translateY(-2px); }
        .role-option.selected { border-color: #2563EB; background: #EFF6FF; box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); }
        .role-option i { font-size: 1.8rem; color: #64748B; margin-bottom: 8px; display: block; }
        .role-option.selected i { color: #2563EB; }
        .role-option .role-title { font-weight: 600; color: #1e293b; margin-bottom: 4px; font-size: 0.9rem; }
        .role-option .role-desc { font-size: 0.75rem; color: #64748B; line-height: 1.3; }
        .error-message { background: #FEF2F2; color: #DC2626; padding: 10px 12px; border-radius: 8px; border: 1px solid #FECACA; margin-top: 8px; font-size: 0.85rem; display: none; }
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
            <label>Tipo de Conta <span style="color: #DC2626;">*</span></label>
            <div class="role-selector">
                <div class="role-option" data-role="project_manager">
                    <i class="fas fa-briefcase"></i>
                    <div class="role-title">Gestor de Projetos</div>
                    <div class="role-desc">Gerenciar projetos e equipes</div>
                </div>
                <div class="role-option" data-role="ngo_admin">
                    <i class="fas fa-hands-helping"></i>
                    <div class="role-title">Terceiro Setor / ONG</div>
                    <div class="role-desc">Organização sem fins lucrativos</div>
                </div>
                <div class="role-option" data-role="client">
                    <i class="fas fa-user"></i>
                    <div class="role-title">Cliente Pessoal</div>
                    <div class="role-desc">Uso individual</div>
                </div>
            </div>
            <input type="hidden" name="account_type" id="account_type" value="" required>
            <div class="error-message" id="role-error">Por favor, selecione um tipo de conta.</div>
        </div>

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

<script>
// Role Selection Handler
document.querySelectorAll('.role-option').forEach(option => {
    option.addEventListener('click', function() {
        // Remove selected from all
        document.querySelectorAll('.role-option').forEach(o => o.classList.remove('selected'));
        
        // Add selected to clicked
        this.classList.add('selected');
        
        // Set hidden input value
        document.getElementById('account_type').value = this.dataset.role;
        
        // Hide error message
        document.getElementById('role-error').style.display = 'none';
    });
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const accountType = document.getElementById('account_type').value;
    
    if (!accountType) {
        e.preventDefault();
        document.getElementById('role-error').style.display = 'block';
        document.querySelector('.role-selector').scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
    }
});
</script>

</body>
</html>
