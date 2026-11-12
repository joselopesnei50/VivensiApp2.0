@extends('layouts.app')

@section('content')
@php
    use App\Support\TeamAccess;
    $st = $stats ?? [];
    $roleStyle = [
        'ngo'         => 'background:#dcfce7;color:#15803d;',
        'manager'     => 'background:#e0e7ff;color:#4338ca;',
        'credenciado' => 'background:#fef3c7;color:#92400e;',
        'employee'    => 'background:#f1f5f9;color:#475569;',
    ];
    $openAdd = $errors->any() && old('_form') === 'add';
@endphp

<div class="header-page" style="margin-bottom: 26px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
    <div>
        <h2 style="margin: 0; color: var(--text-primary, #2c3e50);">Equipe da organização</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Quem acessa a Vivensi e o que cada pessoa pode ver.</p>
    </div>
    <button onclick="toggleModal()" class="btn-premium">
        <i class="fas fa-user-plus"></i> Adicionar membro
    </button>
</div>

@if($errors->any())
    <div class="alert alert-danger" style="border-radius:10px;">
        <ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

{{-- O que cada função pode fazer --}}
<div class="grid-3" style="margin-bottom: 18px;">
    @foreach(['ngo', 'manager', 'employee'] as $r)
        <div class="vivensi-card" style="padding:18px 20px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <span style="display:inline-block;padding:4px 12px;border-radius:999px;font-size:.78rem;font-weight:800;{{ $roleStyle[$r] }}">{{ TeamAccess::label($r) }}</span>
                <strong style="font-size:1.3rem;">{{ (int) ($st[$r] ?? 0) }}</strong>
            </div>
            <p style="margin:0;font-size:.85rem;color:#64748b;line-height:1.5;">{{ TeamAccess::ROLE_DESCRIPTIONS[$r] }}</p>
        </div>
    @endforeach
</div>

<div class="vivensi-card" style="margin-bottom: 14px;">
    <div style="display:flex; gap: 10px; flex-wrap: wrap; align-items:center; justify-content: space-between;">
        <div style="color:#64748b; font-weight:700;">
            {{ (int) ($st['total'] ?? count($users)) }} {{ ($st['total'] ?? 0) == 1 ? 'pessoa' : 'pessoas' }} · {{ (int) ($st['active'] ?? 0) }} {{ ($st['active'] ?? 0) == 1 ? 'ativa' : 'ativas' }}
            @if(($st['credenciado'] ?? 0) > 0) · {{ (int) $st['credenciado'] }} credenciado(s) por projeto @endif
        </div>
        <input id="teamSearch" type="text" placeholder="Buscar por nome, e-mail ou função..." class="form-control-vivensi" style="max-width: 320px;">
    </div>
</div>

<div class="grid-3">
    @foreach($users as $user)
    @php $isSelf = $user->id === auth()->id(); $suspended = ($user->status ?? 'active') === 'suspended'; @endphp
    <div class="vivensi-card team-card" data-q="{{ strtolower($user->name . ' ' . $user->email . ' ' . TeamAccess::label($user->role) . ' ' . ($suspended ? 'suspenso' : 'ativo')) }}" style="text-align: center; position: relative; {{ $suspended ? 'opacity:.7;' : '' }}">
        @if($user->role !== 'credenciado' && $user->role !== 'super_admin')
        <div style="position: absolute; top: 15px; right: 15px;">
            <button class="btn-edit-user" title="Editar"
                    data-user="{{ json_encode(['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'status' => $user->status ?? 'active', 'self' => $isSelf]) }}"
                    style="background: none; border: none; color: #4f46e5; cursor: pointer; font-size: 1rem;"><i class="fas fa-edit"></i></button>
        </div>
        @endif

        <div style="width: 72px; height: 72px; background: #e0e7ff; color: #4f46e5; border-radius: 50%; font-size: 1.8rem; font-weight: 700; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
            {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
        </div>
        <h3 style="font-size: 1.1rem; margin-bottom: 4px;">{{ $user->name }} @if($isSelf)<small style="color:#94a3b8;font-weight:600;">(você)</small>@endif</h3>
        <p style="color: #64748b; font-size: 0.88rem; margin-bottom: 12px; word-break: break-all;">{{ $user->email }}</p>

        <span style="display:inline-block;padding:5px 14px;border-radius:999px;font-size:.8rem;font-weight:800;{{ $roleStyle[$user->role] ?? $roleStyle['employee'] }}">{{ TeamAccess::label($user->role) }}</span>

        <div style="margin-top: 10px; font-size: .8rem; font-weight:700; color: {{ $suspended ? '#dc2626' : '#16a34a' }};">
            <i class="fas fa-circle" style="font-size:.5rem;vertical-align:2px;"></i> {{ $suspended ? 'Acesso suspenso' : 'Ativo' }}
            @if($user->must_change_password) <span style="color:#b45309;"> · ainda não trocou a senha</span> @endif
        </div>

        @if(!$isSelf && !in_array($user->role, ['super_admin', 'credenciado'], true))
        <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-top:16px;">
            <form action="{{ route('ngo.team.invite', $user->id) }}" method="POST">
                @csrf
                <button type="submit" title="Envia um link para a pessoa criar uma nova senha (vale 72 horas)"
                        style="display:inline-flex;align-items:center;gap:6px;background:#eef2ff;color:#4338ca;border:1px solid #c7d2fe;font-weight:700;font-size:.8rem;padding:7px 14px;border-radius:10px;cursor:pointer;">
                    <i class="fas fa-paper-plane"></i> Reenviar convite
                </button>
            </form>
            <form action="{{ url('/ngo/team/'.$user->id) }}" method="POST"
                  onsubmit="return confirm('Remover {{ addslashes($user->name) }} ({{ $user->email }})?\n\nEsta ação será registrada na auditoria.');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        style="display:inline-flex;align-items:center;gap:6px;background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;font-weight:700;font-size:.8rem;padding:7px 14px;border-radius:10px;cursor:pointer;">
                    <i class="fas fa-trash-alt"></i> Remover
                </button>
            </form>
        </div>
        @endif
    </div>
    @endforeach
</div>

<!-- Modal: novo membro -->
<div id="teamModal" style="display:{{ $openAdd ? 'flex' : 'none' }}; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; padding: 16px;">
    <div class="vivensi-card" style="width: 100%; max-width: 520px; max-height: 92vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <h3 style="margin: 0;">Novo membro</h3>
            <button onclick="toggleModal()" aria-label="Fechar" style="border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>

        <form action="{{ url('/ngo/team') }}" method="POST" id="addMemberForm">
            @csrf
            <input type="hidden" name="_form" value="add">
            <div class="form-group">
                <label for="name" class="form-label">Nome completo</label>
                <input type="text" name="name" class="form-control-vivensi" required id="name" value="{{ old('name') }}">
            </div>
            <div class="form-group">
                <label for="email" class="form-label">E-mail de acesso</label>
                <input type="email" name="email" class="form-control-vivensi" required id="email" value="{{ old('email') }}">
            </div>
            <div class="form-group">
                <label for="role" class="form-label">Função</label>
                <select name="role" class="form-control-vivensi" id="role">
                    @foreach(['employee', 'manager', 'ngo'] as $r)
                        <option value="{{ $r }}" @selected(old('role', 'employee') === $r)>{{ TeamAccess::label($r) }}</option>
                    @endforeach
                </select>
                <small id="roleHelp" style="display:block;color:#64748b;margin-top:6px;line-height:1.45;"></small>
            </div>

            <div class="form-group">
                <span class="form-label">Como a pessoa vai entrar</span>
                <label style="display:flex;gap:10px;align-items:flex-start;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:10px;cursor:pointer;margin-bottom:8px;">
                    <input type="radio" name="access_mode" value="invite" @checked(old('access_mode', 'invite') === 'invite') style="margin-top:4px;">
                    <span><strong>Enviar convite por e-mail</strong> <small style="color:#16a34a;font-weight:700;">(recomendado)</small><br>
                        <small style="color:#64748b;">A pessoa recebe um link e cria a própria senha. Ninguém mais fica sabendo.</small></span>
                </label>
                <label style="display:flex;gap:10px;align-items:flex-start;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:10px;cursor:pointer;">
                    <input type="radio" name="access_mode" value="password" @checked(old('access_mode') === 'password') style="margin-top:4px;">
                    <span><strong>Definir uma senha inicial</strong><br>
                        <small style="color:#64748b;">Você repassa a senha; no primeiro acesso a pessoa é obrigada a trocar.</small></span>
                </label>
            </div>

            <div class="form-group" id="passwordGroup" style="display:none;">
                <label for="password" class="form-label">Senha inicial</label>
                <input type="password" name="password" class="form-control-vivensi" minlength="12" id="password" autocomplete="new-password">
                <small style="display:block;color:#64748b;margin-top:6px;">Mínimo de 12 caracteres, com letra maiúscula, letra minúscula e número.</small>
            </div>

            <button type="submit" class="btn-premium" style="width: 100%; justify-content: center;" id="addSubmit">Enviar convite</button>
        </form>
    </div>
</div>

<!-- Modal: editar membro -->
<div id="editTeamModal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; padding: 16px;">
    <div class="vivensi-card" style="width: 100%; max-width: 500px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <h3 style="margin: 0;">Editar membro</h3>
            <button onclick="toggleEditModal()" aria-label="Fechar" style="border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>

        <form id="editUserForm" action="" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="edit_name" class="form-label">Nome completo</label>
                <input type="text" name="name" id="edit_name" class="form-control-vivensi" required>
            </div>
            <div class="form-group">
                <label for="edit_email" class="form-label">E-mail (não editável)</label>
                <input type="email" id="edit_email" class="form-control-vivensi" readonly style="background: #f1f5f9;">
            </div>
            <div class="form-group">
                <label for="edit_role" class="form-label">Função</label>
                <select name="role" id="edit_role" class="form-control-vivensi">
                    @foreach(['employee', 'manager', 'ngo'] as $r)
                        <option value="{{ $r }}">{{ TeamAccess::label($r) }}</option>
                    @endforeach
                </select>
                <small id="editRoleHelp" style="display:block;color:#64748b;margin-top:6px;line-height:1.45;"></small>
            </div>
            <div class="form-group">
                <label for="edit_status" class="form-label">Acesso</label>
                <select name="status" id="edit_status" class="form-control-vivensi">
                    <option value="active">Ativo</option>
                    <option value="suspended">Suspenso (não consegue entrar)</option>
                </select>
            </div>

            <button type="submit" class="btn-premium" style="width: 100%; justify-content: center;">Salvar alterações</button>
        </form>
    </div>
</div>

<script>
    const ROLE_HELP = @json(TeamAccess::ROLE_DESCRIPTIONS);

    function toggleModal() {
        const modal = document.getElementById('teamModal');
        modal.style.display = modal.style.display === 'none' ? 'flex' : 'none';
    }
    function toggleEditModal() {
        const modal = document.getElementById('editTeamModal');
        modal.style.display = modal.style.display === 'none' ? 'flex' : 'none';
    }

    function bindRoleHelp(selectId, helpId) {
        const sel = document.getElementById(selectId), help = document.getElementById(helpId);
        const upd = () => { help.textContent = ROLE_HELP[sel.value] || ''; };
        sel.addEventListener('change', upd); upd();
        return upd;
    }
    bindRoleHelp('role', 'roleHelp');
    const updEditHelp = bindRoleHelp('edit_role', 'editRoleHelp');

    // Convite x senha inicial
    (function () {
        const group = document.getElementById('passwordGroup'), pw = document.getElementById('password'), btn = document.getElementById('addSubmit');
        function upd() {
            const manual = document.querySelector('input[name="access_mode"]:checked').value === 'password';
            group.style.display = manual ? '' : 'none';
            pw.required = manual;
            btn.textContent = manual ? 'Cadastrar membro' : 'Enviar convite';
        }
        document.querySelectorAll('input[name="access_mode"]').forEach(r => r.addEventListener('change', upd));
        upd();
    })();

    document.querySelectorAll('.btn-edit-user').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const u = JSON.parse(this.getAttribute('data-user'));
            document.getElementById('editUserForm').action = "{{ url('/ngo/team') }}/" + u.id;
            document.getElementById('edit_name').value = u.name;
            document.getElementById('edit_email').value = u.email;
            document.getElementById('edit_role').value = u.role;
            document.getElementById('edit_status').value = u.status || 'active';
            // Você não suspende o próprio acesso.
            document.querySelector('#edit_status option[value="suspended"]').disabled = !!u.self;
            updEditHelp();
            toggleEditModal();
        });
    });

    (function () {
        const s = document.getElementById('teamSearch');
        if (!s) return;
        s.addEventListener('input', function () {
            const q = (s.value || '').toLowerCase().trim();
            document.querySelectorAll('.team-card').forEach(function (card) {
                card.style.display = (!q || (card.getAttribute('data-q') || '').includes(q)) ? '' : 'none';
            });
        });
    })();
</script>
@endsection
