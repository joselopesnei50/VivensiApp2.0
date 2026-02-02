@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Gestão de Equipe</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Gerencie usuários e permissões de acesso.</p>
    </div>
    <button onclick="toggleModal()" class="btn-premium">
        <i class="fas fa-user-plus"></i> Adicionar Membro
    </button>
</div>

<!-- Team List -->
<div class="grid-3">
    @foreach($users as $user)
    <div class="vivensi-card" style="text-align: center; position: relative;">
        @if($user->id != auth()->id())
        <form action="{{ url('/ngo/team/'.$user->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja remover este usuário?');" style="position: absolute; top: 15px; right: 15px;">
            @csrf
            @method('DELETE')
            <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 1rem;"><i class="fas fa-trash-alt"></i></button>
        </form>
        @endif

        <div style="width: 80px; height: 80px; background: #e0e7ff; color: #4f46e5; border-radius: 50%; font-size: 2rem; font-weight: 700; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <h3 style="font-size: 1.2rem; margin-bottom: 5px;">{{ $user->name }}</h3>
        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 15px;">{{ $user->email }}</p>
        
        <div style="display: inline-block; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; 
            @if($user->role == 'ngo') background: #dcfce7; color: #16a34a; 
            @elseif($user->role == 'manager') background: #e0e7ff; color: #4f46e5;
            @else background: #f1f5f9; color: #64748b; @endif">
            @if($user->role == 'ngo') Administrador (ONG)
            @elseif($user->role == 'manager') Gestor de Projetos
            @else Colaborador
            @endif
        </div>
    </div>
    @endforeach
</div>

<!-- Modal Add User -->
<div id="teamModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div class="vivensi-card" style="width: 90%; max-width: 500px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;">Novo Usuário</h3>
            <button onclick="toggleModal()" style="border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        
        <form action="{{ url('/ngo/team') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Nome Completo</label>
                <input type="text" name="name" class="form-control-vivensi" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email de Acesso</label>
                <input type="email" name="email" class="form-control-vivensi" required>
            </div>
            <div class="form-group">
                <label class="form-label">Função / Permissão</label>
                <select name="role" class="form-control-vivensi">
                    <option value="ngo">Administrador (Acesso Total)</option>
                    <option value="manager">Gestor de Projetos</option>
                    <option value="employee">Colaborador (Restrito)</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Senha Inicial</label>
                <input type="password" name="password" class="form-control-vivensi" required minlength="6">
            </div>

            <button type="submit" class="btn-premium" style="width: 100%; justify-content: center;">Criar Usuário</button>
        </form>
    </div>
</div>

<script>
    function toggleModal() {
        const modal = document.getElementById('teamModal');
        modal.style.display = modal.style.display === 'none' ? 'flex' : 'none';
    }
</script>
@endsection
