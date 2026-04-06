@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: end;">
        <div>
            <h6 style="color: #6366f1; font-weight: 700; text-transform: uppercase; margin: 0 0 5px 0;">SaaS HQ</h6>
            <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">Time Vivensi</h2>
            <p style="color: #6b7280; margin: 5px 0 0 0;">Gestão da equipe interna, hierarquia e setores da plataforma.</p>
        </div>
        <div>
            <button class="btn-premium" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                <i class="fas fa-user-plus me-2"></i> Adicionar Membro
            </button>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm" style="border-radius: 12px;">{{ session('success') }}</div>
@endif

<div class="row">
    <!-- Lista por Setores -->
    <div class="col-md-12">
        <div class="vivensi-card" style="padding: 0; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                    <tr>
                        <th style="padding: 15px 25px; text-align: left; font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Membro</th>
                        <th style="padding: 15px 25px; text-align: left; font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Setor / Função</th>
                        <th style="padding: 15px 25px; text-align: left; font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Líder / Superior</th>
                        <th style="padding: 15px 25px; text-align: center; font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Status</th>
                        <th style="padding: 15px 25px; text-align: right; font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $sectorLabels = [
                            'tecnico' => ['label' => 'Tecnologia', 'color' => '#6366f1'],
                            'suporte' => ['label' => 'Suporte ao Cliente', 'color' => '#10b981'],
                            'vendas' => ['label' => 'Vendas / Growth', 'color' => '#f59e0b'],
                            'marketing' => ['label' => 'Marketing', 'color' => '#ec4899'],
                            'gestao' => ['label' => 'Gestão Geral', 'color' => '#111827'],
                        ];
                    @endphp
                    @foreach($team as $member)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 15px 25px;">
                            <div style="display: flex; align-items: center;">
                                <div style="width: 40px; height: 40px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-weight: 700; color: #64748b;">
                                    {{ substr($member->name, 0, 1) }}
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: #1e293b;">{{ $member->name }}</div>
                                    <div style="font-size: 0.75rem; color: #64748b;">{{ $member->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 15px 25px;">
                            @php $s = $sectorLabels[$member->department] ?? ['label' => $member->department, 'color' => '#64748b']; @endphp
                            <span style="background: {{ $s['color'] }}15; color: {{ $s['color'] }}; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                                {{ $s['label'] }}
                            </span>
                            <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 5px;">{{ $member->role }}</div>
                        </td>
                        <td style="padding: 15px 25px; color: #64748b; font-size: 0.85rem;">
                            @if($member->supervisor)
                                <i class="fas fa-level-up-alt" style="margin-right: 5px; color: #cbd5e1;"></i> {{ $member->supervisor->name }}
                            @else
                                <span style="color: #cbd5e1;">—</span>
                            @endif
                        </td>
                        <td style="padding: 15px 25px; text-align: center;">
                             <span style="color: #16a34a; background: #f0fdf4; padding: 4px 10px; border-radius: 8px; font-size: 0.7rem; font-weight: 700;">
                                ATIVO
                            </span>
                        </td>
                        <td style="padding: 15px 25px; text-align: right;">
                            <a href="{{ route('admin.team.profile', $member->id) }}" class="btn-outline me-2" style="padding: 6px 12px; border-radius: 6px; font-size: 0.75rem; text-decoration: none;">
                                <i class="fas fa-id-card me-1"></i> Perfil
                            </a>
                            <form action="{{ route('admin.team.destroy', $member->id) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-outline text-danger" style="border-color: #fecaca; padding: 6px 12px;" onclick="return confirm('Remover membro do time?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Adicionar Membro -->
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 50px rgba(0,0,0,0.1);">
            <div class="modal-header border-0 p-4">
                <h5 class="modal-title font-weight-bold">Novo Membro Vivensi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.team.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4 pt-0">
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Nome Completo</label>
                        <input type="text" name="name" class="form-control rounded-3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">E-mail Corporativo</label>
                        <input type="email" name="email" class="form-control rounded-3" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small font-weight-bold">Setor</label>
                            <select name="department" class="form-select rounded-3" required>
                                <option value="tecnico">Técnico / Dev</option>
                                <option value="suporte">Suporte</option>
                                <option value="vendas">Vendas / Growth</option>
                                <option value="marketing">Marketing</option>
                                <option value="gestao">Gestão Geral</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small font-weight-bold">Hierarquia (Líder)</label>
                            <select name="supervisor_id" class="form-select rounded-3">
                                <option value="">Nenhum (Diretoria)</option>
                                @foreach($supervisors as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Cargo/Role</label>
                        <input type="text" name="role" class="form-control rounded-3" placeholder="Ex: Senior Dev, Head of Sales" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Senha de Acesso</label>
                        <input type="password" name="password" class="form-control rounded-3" required>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn-premium w-100">Confirmar Cadastro</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
