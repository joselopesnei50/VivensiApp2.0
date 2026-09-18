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

@php
    $st = $stats ?? [];
@endphp

<div class="grid-2" style="margin-bottom: 18px;">
    <div class="vivensi-card" style="border-left: 5px solid #4f46e5;">
        <p style="text-transform: uppercase; font-size: 0.75rem; color: #64748b; font-weight: 800;">Usuários (total)</p>
        <h3 style="margin: 10px 0; font-size: 1.9rem;">{{ number_format((int)($st['total'] ?? count($users))) }}</h3>
        <p style="font-size: 0.9rem; color:#475569; margin:0;">
            Ativos: <strong>{{ number_format((int)($st['active'] ?? 0)) }}</strong>
        </p>
    </div>
    <div class="vivensi-card" style="border-left: 5px solid #16a34a;">
        <p style="text-transform: uppercase; font-size: 0.75rem; color: #64748b; font-weight: 800;">Perfis</p>
        <h3 style="margin: 10px 0; font-size: 1.2rem;">
            <span style="color:#16a34a; font-weight: 900;">{{ number_format((int)($st['ngo'] ?? 0)) }}</span> Admin
            <span style="color:#94a3b8;"> · </span>
            <span style="color:#4f46e5; font-weight: 900;">{{ number_format((int)($st['manager'] ?? 0)) }}</span> Gestor
            <span style="color:#94a3b8;"> · </span>
            <span style="color:#64748b; font-weight: 900;">{{ number_format((int)($st['employee'] ?? 0)) }}</span> Colab
            <span style="color:#94a3b8;"> · </span>
            <span style="color:#92400e; font-weight: 900;">{{ number_format((int)($st['credenciado'] ?? 0)) }}</span> Credenc.
        </h3>
        <p style="font-size: 0.9rem; color:#64748b; margin:0;">Busca rápida disponível abaixo.</p>
    </div>
</div>

<div class="vivensi-card" style="margin-bottom: 14px;">
    <div style="display:flex; gap: 10px; flex-wrap: wrap; align-items:center; justify-content: space-between;">
        <div style="color:#64748b; font-weight:800;">
            Dica: busque por nome, e-mail ou perfil.
        </div>
        <input id="teamSearch" type="text" placeholder="Buscar membro..." class="form-control-vivensi" style="max-width: 320px;">
    </div>
</div>

<!-- Team List -->
<div class="grid-3">
    @foreach($users as $user)
    <div class="vivensi-card team-card" data-q="{{ strtolower(($user->name ?? '').' '.($user->email ?? '').' '.($user->role ?? '').' '.($user->status ?? '')) }}" style="text-align: center; position: relative;">
        <div style="position: absolute; top: 15px; right: 15px;">
            <button class="btn-edit-user" data-user="{{ json_encode($user) }}" title="Editar" style="background: none; border: none; color: #4f46e5; cursor: pointer; font-size: 1rem;"><i class="fas fa-edit"></i></button>
        </div>

        <div style="width: 80px; height: 80px; background: #e0e7ff; color: #4f46e5; border-radius: 50%; font-size: 2rem; font-weight: 700; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <h3 style="font-size: 1.2rem; margin-bottom: 5px;">{{ $user->name }}</h3>
        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 15px;">{{ $user->email }}</p>
        
        <div style="display: inline-block; padding: 5px 15px; border-radius: 999px; font-size: 0.8rem; font-weight: 800;
            @if($user->role == 'ngo') background: #dcfce7; color: #16a34a;
            @elseif($user->role == 'manager') background: #e0e7ff; color: #4f46e5;
            @elseif($user->role == 'credenciado') background: #fef3c7; color: #92400e;
            @else background: #f1f5f9; color: #64748b; @endif">
            @if($user->role == 'ngo') Administrador (ONG)
            @elseif($user->role == 'manager') Gestor de Projetos
            @elseif($user->role == 'credenciado') Credenciado
            @else Colaborador
            @endif
        </div>

        <div style="margin-top: 10px; font-size: .8rem; color:#94a3b8; font-weight:800;">
            Status:
            <span style="color: {{ ($user->status ?? 'active') === 'active' ? '#16a34a' : '#ef4444' }};">
                {{ strtoupper($user->status ?? 'active') }}
            </span>
        </div>

        @if($user->id != auth()->id() && $user->role !== 'super_admin')
        <form action="{{ url('/ngo/team/'.$user->id) }}" method="POST"
              onsubmit="return confirm('Remover {{ addslashes($user->name) }} ({{ $user->email }})?\n\nEsta ação será registrada na auditoria.');"
              style="margin-top:16px;">
            @csrf
            @method('DELETE')
            <button type="submit"
                    style="display:inline-flex; align-items:center; gap:8px; background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; font-weight:800; font-size:.82rem; padding:8px 18px; border-radius:10px; cursor:pointer; transition:all .15s;"
                    onmouseover="this.style.background='#fecaca';"
                    onmouseout="this.style.background='#fee2e2';">
                <i class="fas fa-trash-alt"></i> Remover membro
            </button>
        </form>
        @endif
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
                <label for="name" class="form-label">Nome Completo</label>
                <input type="text" name="name" class="form-control-vivensi" required id="name">
            </div>
            <div class="form-group">
                <label for="email" class="form-label">Email de Acesso</label>
                <input type="email" name="email" class="form-control-vivensi" required id="email">
            </div>
            <div class="form-group">
                <label for="role" class="form-label">Função / Permissão</label>
                <select name="role" class="form-control-vivensi" id="role">
                    <option value="ngo">Administrador (Acesso Total)</option>
                    <option value="manager">Gestor de Projetos</option>
                    <option value="employee">Colaborador (Restrito)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="password" class="form-label">Senha Inicial</label>
                <input type="password" name="password" class="form-control-vivensi" required minlength="6" id="password">
            </div>

            <button type="submit" class="btn-premium" style="width: 100%; justify-content: center;">Criar Usuário</button>
        </form>
    </div>
</div>

<!-- Modal Edit User -->
<div id="editTeamModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div class="vivensi-card" style="width: 90%; max-width: 500px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;">Editar Usuário</h3>
            <button onclick="toggleEditModal()" style="border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        
        <form id="editUserForm" action="" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="edit_name" class="form-label">Nome Completo</label>
                <input type="text" name="name" id="edit_name" class="form-control-vivensi" required>
            </div>
            <div class="form-group">
                <label for="edit_email" class="form-label">Email (Não editável)</label>
                <input type="email" id="edit_email" class="form-control-vivensi" readonly style="background: #f1f5f9;">
            </div>
            <div class="form-group">
                <label for="edit_role" class="form-label">Função / Permissão</label>
                <select name="role" id="edit_role" class="form-control-vivensi">
                    <option value="ngo">Administrador (Acesso Total)</option>
                    <option value="manager">Gestor de Projetos</option>
                    <option value="employee">Colaborador (Restrito)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_status" class="form-label">Status</label>
                <select name="status" id="edit_status" class="form-control-vivensi">
                    <option value="active">Ativo</option>
                    <option value="suspended">Suspenso</option>
                </select>
            </div>

            <button type="submit" class="btn-premium" style="width: 100%; justify-content: center;">Salvar Alterações</button>
        </form>
    </div>
</div>

<script>
    function toggleModal() {
        const modal = document.getElementById('teamModal');
        modal.style.display = modal.style.display === 'none' ? 'flex' : 'none';
    }

    function toggleEditModal() {
        const modal = document.getElementById('editTeamModal');
        modal.style.display = modal.style.display === 'none' ? 'flex' : 'none';
    }

    document.querySelectorAll('.btn-edit-user').forEach(function(btn) {
        btn.addEventListener('click', function() {
            editUser(JSON.parse(this.getAttribute('data-user')));
        });
    });

    function editUser(user) {
        document.getElementById('editUserForm').action = "{{ url('/ngo/team') }}/" + user.id;
        document.getElementById('edit_name').value = user.name;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_status').value = user.status || 'active';
        toggleEditModal();
    }

    (function() {
        const s = document.getElementById('teamSearch');
        if (!s) return;
        s.addEventListener('input', function() {
            const q = (s.value || '').toLowerCase().trim();
            document.querySelectorAll('.team-card').forEach(function(card) {
                const hay = (card.getAttribute('data-q') || '');
                card.style.display = (!q || hay.includes(q)) ? '' : 'none';
            });
        });
    })();
</script>
@endsection
