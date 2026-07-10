@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Recursos Humanos & Voluntariado</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Gestão completa da equipe, pagamentos e colaboradores.</p>
    </div>
    {{-- Toolbar de ações: 6 botões compactos, flex-wrap, hierarquia visual (4 export + 2 ação) --}}
    <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
        <a href="{{ url('/ngo/hr/employees/export') }}" class="btn-premium" style="padding: 8px 14px !important; font-size: 0.85rem !important; background:#4f46e5 !important; color:#ffffff !important;">
            <i class="fas fa-file-csv"></i> CSV Funcionários
        </a>
        <a href="{{ url('/ngo/hr/volunteers/export') }}" class="btn-premium" style="padding: 8px 14px !important; font-size: 0.85rem !important; background:#0ea5e9 !important; color:#ffffff !important;">
            <i class="fas fa-file-csv"></i> CSV Voluntários
        </a>
        <a href="{{ url('/ngo/hr/payroll/pdf') }}?month={{ date('n') }}&year={{ date('Y') }}" class="btn-premium" style="padding: 8px 14px !important; font-size: 0.85rem !important; background:#16a34a !important; color:#ffffff !important;">
            <i class="fas fa-file-pdf"></i> PDF Folha
        </a>
        <a href="{{ url('/ngo/hr/certificates') }}" class="btn-premium" style="padding: 8px 14px !important; font-size: 0.85rem !important; background:#111827 !important; color:#ffffff !important;">
            <i class="fas fa-certificate"></i> Certificados
        </a>
        <span style="width:1px; height:24px; background:#e2e8f0; margin: 0 4px;" aria-hidden="true"></span>
        <button onclick="openModal('employeeModal')" class="btn-premium" style="padding: 8px 14px !important; font-size: 0.85rem !important; background:#10b981 !important; color:#ffffff !important;">
            <i class="fas fa-briefcase"></i> Novo Funcionário
        </button>
        <button onclick="openModal('volunteerModal')" class="btn-premium" style="padding: 8px 14px !important; font-size: 0.85rem !important; background:#4f46e5 !important; color:#ffffff !important;">
            <i class="fas fa-hands-helping"></i> Novo Voluntário
        </button>
    </div>
</div>

@php
    $employeesCount = (int) ($stats['employees_count'] ?? count($employees));
    $volunteersCount = (int) ($stats['volunteers_count'] ?? count($volunteers));
    $monthlyPayroll = (float) ($stats['monthly_payroll'] ?? 0);
    $avgSalary = (float) ($stats['avg_salary'] ?? 0);
@endphp

<div class="grid-2" style="margin-bottom: 20px;">
    <div class="vivensi-card" style="border-left: 5px solid #4f46e5;">
        <p style="text-transform: uppercase; font-size: 0.75rem; color: #64748b; font-weight: 800;">Equipe Ativa</p>
        <h3 style="margin: 10px 0; font-size: 1.9rem;">{{ number_format($employeesCount + $volunteersCount) }}</h3>
        <p style="font-size: 0.9rem; color: #475569; margin:0;">
            Funcionários: <strong>{{ number_format($employeesCount) }}</strong> · Voluntários: <strong>{{ number_format($volunteersCount) }}</strong>
        </p>
    </div>
    <div class="vivensi-card" style="border-left: 5px solid #16a34a;">
        <p style="text-transform: uppercase; font-size: 0.75rem; color: #64748b; font-weight: 800;">Folha Mensal (estimativa)</p>
        <h3 style="margin: 10px 0; font-size: 1.9rem;">R$ {{ number_format($monthlyPayroll, 2, ',', '.') }}</h3>
        <p style="font-size: 0.9rem; color: #16a34a; margin:0;">
            Salário médio: <strong>R$ {{ number_format($avgSalary, 2, ',', '.') }}</strong>
        </p>
    </div>
</div>

<!-- Tabs -->
<div style="margin-bottom: 20px; border-bottom: 1px solid #e2e8f0;">
    <button class="tab-btn active" onclick="showTab('employees')" id="btn-employees" style="padding: 10px 20px; border: none; background: none; font-weight: 600; color: #4f46e5; border-bottom: 2px solid #4f46e5; cursor: pointer;">Funcionários (CLT/PJ)</button>
    <button class="tab-btn" onclick="showTab('volunteers')" id="btn-volunteers" style="padding: 10px 20px; border: none; background: none; font-weight: 600; color: #64748b; cursor: pointer;">Voluntários</button>
</div>

<!-- Employees Section -->
<div id="tab-employees" class="tab-content">
    <div class="vivensi-card" style="margin-bottom: 14px;">
        <div style="display:flex; gap: 10px; flex-wrap: wrap; align-items:center; justify-content: space-between;">
            <div style="color:#64748b; font-weight:800;">
                Dica: use a busca para encontrar rápido por nome/cargo.
            </div>
            <input id="hrEmployeeSearch" type="text" placeholder="Buscar funcionário..." class="form-control-vivensi" style="max-width: 320px;">
        </div>
    </div>
    <div class="vivensi-card" style="padding: 0; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                <tr>
                    <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Nome / Cargo</th>
                    <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Contrato</th>
                    <th style="padding: 15px; text-align: right; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Salário</th>
                    <th style="padding: 15px; text-align: center; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Carga Horária</th>
                    <th style="padding: 15px; text-align: center; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $employee)
                <tr class="hr-employee-row" data-q="{{ strtolower(($employee->name ?? '').' '.($employee->position ?? '').' '.($employee->contract_type ?? '')) }}" style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 15px;">
                        <strong style="display: block; color: #1e293b;">{{ $employee->name }}</strong>
                        <span style="font-size: 0.85rem; color: #64748b;">{{ $employee->position }}</span>
                        @if(!empty($employee->project_id))
                            <div style="margin-top: 6px; color:#94a3b8; font-size:.8rem;">
                                <i class="fas fa-diagram-project"></i>
                                Projeto: {{ optional($projects->firstWhere('id', $employee->project_id))->name ?? '—' }}
                            </div>
                        @endif
                    </td>
                    <td style="padding: 15px;">
                        <span style="background: #f1f5f9; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; text-transform: uppercase;">{{ $employee->contract_type }}</span>
                    </td>
                    <td style="padding: 15px; text-align: right; font-weight: 600; color: #334155;">
                        R$ {{ number_format($employee->salary, 2, ',', '.') }}
                    </td>
                    <td style="padding: 15px; text-align: center; color: #64748b;">
                        {{ $employee->work_hours_weekly }}
                    </td>
                    <td style="padding: 15px; text-align: center; white-space: nowrap;">
                        <button type="button" title="Editar" style="border: none; background: none; color: #4f46e5; cursor: pointer; font-size: 1rem; padding: 4px 8px;"
                            onclick='openEditEmployee(
                                {{ $employee->id }},
                                @json($employee->name),
                                @json($employee->position),
                                @json($employee->contract_type),
                                "{{ number_format($employee->salary, 2, ',', '.') }}",
                                @json($employee->work_hours_weekly),
                                "{{ optional($employee->hired_at)->format('Y-m-d') }}",
                                @json($employee->status ?? "active"),
                                {{ $employee->project_id ?? "null" }}
                            )'>
                            <i class="fas fa-pen"></i>
                        </button>
                        <form method="POST" action="{{ url('/ngo/hr/employees/'.$employee->id) }}" style="display:inline;" onsubmit="return confirm('Remover funcionário {{ addslashes($employee->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" title="Excluir" style="border: none; background: none; color: #ef4444; cursor: pointer; font-size: 1rem; padding: 4px 8px;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
                @if(count($employees) == 0)
                <tr><td colspan="5" style="padding: 30px; text-align: center; color: #94a3b8;">Nenhum funcionário cadastrado.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- Volunteers Section -->
<div id="tab-volunteers" class="tab-content" style="display: none;">
    <div class="vivensi-card" style="margin-bottom: 14px;">
        <div style="display:flex; gap: 10px; flex-wrap: wrap; align-items:center; justify-content: space-between;">
            <div style="display:flex; gap: 8px; flex-wrap: wrap; align-items:center;">
                <button class="vol-status-pill active" data-filter="all" style="padding:5px 14px; border-radius:99px; font-size:.78rem; font-weight:700; border:1.5px solid #e2e8f0; background:#0f172a; color:#fff; cursor:pointer;">Todos</button>
                <button class="vol-status-pill" data-filter="active" style="padding:5px 14px; border-radius:99px; font-size:.78rem; font-weight:700; border:1.5px solid #e2e8f0; background:#fff; color:#64748b; cursor:pointer;">Ativos</button>
                <button class="vol-status-pill" data-filter="inactive" style="padding:5px 14px; border-radius:99px; font-size:.78rem; font-weight:700; border:1.5px solid #e2e8f0; background:#fff; color:#64748b; cursor:pointer;">Inativos</button>
            </div>
            <input id="hrVolunteerSearch" type="text" placeholder="Buscar voluntário..." class="form-control-vivensi" style="max-width: 280px;">
        </div>
    </div>
    <div class="grid-3">
        @foreach($volunteers as $volunteer)
        @php
            $vCerts = $certByVolunteer->get($volunteer->id) ?? collect();
            $vCertCount = (int) $vCerts->count();
            $vCertRecent = $vCerts->take(3);
        @endphp
        <div class="vivensi-card hr-volunteer-card"
             data-q="{{ strtolower(($volunteer->name ?? '').' '.($volunteer->email ?? '').' '.($volunteer->skills ?? '')) }}"
             data-status="{{ $volunteer->status ?? 'active' }}"
             style="position: relative; {{ ($volunteer->status ?? 'active') === 'inactive' ? 'opacity:.6;' : '' }}">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <div style="width: 50px; height: 50px; background: #e0e7ff; color: #4338ca; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem; position: relative;">
                    {{ $volunteer->name[0] }}
                    @if($volunteer->level_badge === 'bronze')
                        <div style="position: absolute; bottom: -5px; right: -5px; width: 20px; height: 20px; background: #cd7f32; border-radius: 50%; border: 2px solid white;" title="Nível Bronze"></div>
                    @elseif($volunteer->level_badge === 'prata')
                        <div style="position: absolute; bottom: -5px; right: -5px; width: 20px; height: 20px; background: #c0c0c0; border-radius: 50%; border: 2px solid white;" title="Nível Prata"></div>
                    @elseif($volunteer->level_badge === 'ouro')
                        <div style="position: absolute; bottom: -5px; right: -5px; width: 20px; height: 20px; background: #ffd700; border-radius: 50%; border: 2px solid white;" title="Nível Ouro"></div>
                    @elseif($volunteer->level_badge === 'diamante')
                        <div style="position: absolute; bottom: -5px; right: -5px; width: 20px; height: 20px; background: #b9f2ff; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 5px #0ea5e9;" title="Nível Diamante"></div>
                    @endif
                </div>
                <div style="flex:1; min-width:0;">
                    <h4 style="margin: 0; font-size: 1rem; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                        {{ $volunteer->name }}
                        @if(($volunteer->status ?? 'active') === 'inactive')
                            <span style="font-size:.65rem; background:#f1f5f9; color:#64748b; padding:2px 7px; border-radius:4px; text-transform:uppercase; font-weight:700;">Inativo</span>
                        @endif
                        @if($volunteer->level_badge && $volunteer->level_badge !== 'bronze')
                            <span style="font-size: 0.65rem; background: #fef08a; color: #854d0e; padding: 2px 6px; border-radius: 4px; text-transform: uppercase;">{{ $volunteer->level_badge }}</span>
                        @endif
                    </h4>
                    <span style="font-size: 0.8rem; color: #64748b;">{{ $volunteer->email }}</span>
                </div>
            </div>
            <div style="font-size: 0.85rem; color: #475569; margin-bottom: 15px;">
                <strong>Habilidades:</strong> {{ $volunteer->skills ?? 'Não informado' }}<br>
                <strong>Disponibilidade:</strong> {{ ucfirst($volunteer->availability) ?? 'Variável' }}<br>
                <div style="margin-top: 8px; display: flex; gap: 15px; background: #f8fafc; padding: 10px; border-radius: 8px;">
                    <div><i class="fas fa-clock" style="color: #64748b;"></i> <strong>{{ number_format($volunteer->hours_logged ?? 0, 0, '', '.') }}h</strong> doadas</div>
                    <div><i class="fas fa-star" style="color: #f59e0b;"></i> <strong>{{ number_format($volunteer->points ?? 0, 0, '', '.') }}</strong> pontos</div>
                </div>
            </div>

            <div style="font-size: 0.85rem; color: #334155; margin-bottom: 12px;">
                <strong>Certificados:</strong>
                <span style="background:#eef2ff; color:#3730a3; padding:2px 8px; border-radius:999px; font-weight:800; font-size:.75rem;">
                    {{ number_format($vCertCount) }}
                </span>
                @if($vCertCount > 0)
                    <div style="margin-top:8px; color:#64748b; font-size:.82rem;">
                        @foreach($vCertRecent as $c)
                            <div style="display:flex; align-items:center; justify-content: space-between; gap:10px; padding: 4px 0;">
                                <div style="overflow:hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <i class="fas fa-file-pdf"></i>
                                    {{ optional($c->issued_at)->format('d/m/Y') ?? '—' }}
                                    · {{ (int) ($c->hours ?? 0) }}h
                                    <span style="color:#94a3b8;">· #{{ (int) $c->id }}</span>
                                </div>
                                <div style="display:flex; gap: 8px; align-items:center;">
                                    <a class="btn-premium" style="font-size: .78rem; padding: 4px 10px; background:#111827;" href="{{ url('/ngo/hr/certificates/'.$c->id.'/download') }}">
                                        <i class="fas fa-download"></i> Baixar
                                    </a>
                                    @if(!empty($volunteer->phone) && !empty(($certCodes[(int) $c->id] ?? null)))
                                        @php
                                            $code = $certCodes[(int) $c->id] ?? '';
                                            $validateUrl = url('/validar-certificado/' . $c->uuid) . '?code=' . $code;
                                            $msg = "Olá ".$volunteer->name."! Segue o link para validar seu Certificado de Voluntariado: ".$validateUrl;
                                            $phone = preg_replace('/\\D+/', '', (string) $volunteer->phone);
                                        @endphp
                                        <a class="btn-premium" target="_blank" rel="noopener" style="font-size: .78rem; padding: 4px 10px; background:#dcfce7; color:#166534;" href="https://wa.me/{{ $phone }}?text={{ urlencode($msg) }}">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        @if($vCertCount > 3)
                            <div style="margin-top:6px; color:#94a3b8; display:flex; justify-content: space-between; align-items:center; gap: 10px;">
                                <span>Mostrando os 3 mais recentes.</span>
                                <a href="{{ url('/ngo/hr/certificates') }}?volunteer_id={{ (int) $volunteer->id }}" style="color:#4f46e5; font-weight:800; text-decoration:none;">
                                    Ver todos
                                </a>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top:4px;">
                @if(!empty($volunteer->phone))
                @php $vphone = preg_replace('/\D+/', '', (string) $volunteer->phone); @endphp
                <a class="btn-premium" href="https://wa.me/{{ $vphone }}" target="_blank" rel="noopener" style="font-size: 0.8rem; background: #dcfce7; color: #166534; padding: 5px 10px;">
                    <i class="fab fa-whatsapp"></i> Contatar
                </a>
                @else
                <button type="button" class="btn-ds btn-ds-outline" style="font-size: 0.8rem; padding: 5px 10px;" disabled>
                    <i class="fab fa-whatsapp"></i> Contatar
                </button>
                @endif
                <button type="button" class="btn-ds btn-ds-outline" style="font-size: 0.8rem; padding: 5px 10px;" onclick='openCertificateModal({{ (int) $volunteer->id }}, @json($volunteer->name))'>
                    <i class="fas fa-certificate"></i> Certificado
                </button>
                <button type="button" class="btn-premium" style="font-size: 0.8rem; background: #fffbeb !important; color: #b45309 !important; padding: 5px 10px; border: 1px solid #fde68a;" onclick='openLogHoursModal({{ (int) $volunteer->id }}, @json($volunteer->name))'>
                    <i class="fas fa-plus"></i> Horas
                </button>
                <button type="button" class="btn-premium" style="font-size: 0.8rem; background: #f0fdf4 !important; color: #166534 !important; padding: 5px 10px; border: 1px solid #bbf7d0;" onclick='openHourLogs({{ (int) $volunteer->id }}, @json($volunteer->name))'>
                    <i class="fas fa-history"></i> Histórico
                </button>
                <button type="button" class="btn-premium" style="font-size: 0.8rem; background: #eef2ff !important; color: #4338ca !important; padding: 5px 10px;"
                    onclick='openEditVolunteer({{ (int) $volunteer->id }}, @json($volunteer->name), @json($volunteer->email ?? ""), @json($volunteer->phone ?? ""), @json($volunteer->skills ?? ""), @json($volunteer->availability ?? ""))'>
                    <i class="fas fa-pen"></i> Editar
                </button>
                {{-- Toggle status --}}
                <form method="POST" action="{{ url('/ngo/hr/volunteers/'.$volunteer->id.'/toggle-status') }}" style="display:inline;">
                    @csrf @method('PATCH')
                    @if(($volunteer->status ?? 'active') === 'active')
                    <button type="submit" class="btn-premium" style="font-size: 0.8rem; background: #f1f5f9 !important; color: #475569 !important; padding: 5px 10px;" title="Desativar voluntário">
                        <i class="fas fa-user-slash"></i>
                    </button>
                    @else
                    <button type="submit" class="btn-premium" style="font-size: 0.8rem; background: #dcfce7 !important; color: #16a34a !important; padding: 5px 10px;" title="Reativar voluntário">
                        <i class="fas fa-user-check"></i>
                    </button>
                    @endif
                </form>
                {{-- Delete with cert-count warning --}}
                <button type="button" class="btn-premium" style="font-size: 0.8rem; background: #fee2e2 !important; color: #dc2626 !important; padding: 5px 10px;"
                    onclick='confirmDeleteVolunteer({{ (int) $volunteer->id }}, @json($volunteer->name), {{ $vCertCount }})'>
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @if(count($volunteers) == 0)
        <div style="text-align: center; padding: 40px; color: #94a3b8;">Nenhum voluntário cadastrado.</div>
    @endif
</div>

<!-- Modal Employee -->
<div id="employeeModal" class="custom-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000; overflow-y: auto; pointer-events: auto !important; -webkit-overflow-scrolling: touch;">
    <div class="vivensi-card" style="width: 95%; max-width: 600px; margin: 40px auto; pointer-events: auto !important; position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>Novo Funcionário</h3>
            <button onclick="closeModal('employeeModal')" style="border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form action="{{ url('/ngo/hr/employees') }}" method="POST">
            @csrf
            <div class="form-group"><label for="name">Nome Completo</label><input type="text" name="name" class="form-control-vivensi" required id="name"></div>
            <div class="grid-2" style="gap: 15px;">
                <div class="form-group"><label for="position">Cargo/Função</label><input type="text" name="position" class="form-control-vivensi" required id="position"></div>
                <div class="form-group"><label for="contract_type">Tipo Contrato</label>
                    <select name="contract_type" class="form-control-vivensi" id="contract_type">
                        <option value="clt">CLT (Efetivo)</option>
                        <option value="pj">PJ (Prestador)</option>
                        <option value="trainee">Estagiário</option>
                        <option value="temporary">Temporário</option>
                    </select>
                </div>
            </div>
            <div class="grid-2" style="gap: 15px;">
                <div class="form-group"><label for="salary">Salário (R$)</label><input type="text" name="salary" class="form-control-vivensi" placeholder="0,00" required id="salary"></div>
                <div class="form-group"><label for="work_hours_weekly">Carga Horária (Ex: 40h)</label><input type="text" name="work_hours_weekly" class="form-control-vivensi" value="40h Semanais" required id="work_hours_weekly"></div>
            </div>
            <div class="form-group"><label for="hired_at">Data de Admissão</label><input type="date" name="hired_at" class="form-control-vivensi" required id="hired_at"></div>

            <div class="form-group">
                <label for="project_id">Vincular a um Projeto (opcional)</label>
                <select name="project_id" class="form-control-vivensi" id="project_id">
                    <option value="">— Nenhum —</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}">{{ $p->name ?? ('Projeto #'.$p->id) }}</option>
                    @endforeach
                </select>
            </div>
            
            <button type="submit" class="btn-premium" style="width: 100%; justify-content: center;">Cadastrar Funcionário</button>
        </form>
    </div>
</div>

<!-- Modal Volunteer -->
<div id="volunteerModal" class="custom-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000; overflow-y: auto; pointer-events: auto !important; -webkit-overflow-scrolling: touch;">
    <div class="vivensi-card" style="width: 95%; max-width: 500px; margin: 40px auto; pointer-events: auto !important; position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>Novo Voluntário</h3>
            <button onclick="closeModal('volunteerModal')" style="border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form action="{{ url('/ngo/hr/volunteers') }}" method="POST">
            @csrf
            <div class="form-group"><label for="name">Nome</label><input type="text" name="name" class="form-control-vivensi" required id="name"></div>
            <div class="form-group"><label for="email">Email</label><input type="email" name="email" class="form-control-vivensi" id="email"></div>
            <div class="form-group"><label for="phone">Telefone / WhatsApp</label><input type="text" name="phone" class="form-control-vivensi" id="phone"></div>
            <div class="form-group"><label for="skills">Habilidades (Tags)</label><input type="text" name="skills" class="form-control-vivensi" placeholder="Ex: Fotografia, Cozinha, Eventos" id="skills"></div>
            <div class="form-group"><label for="availability">Disponibilidade</label>
                <select name="availability" class="form-control-vivensi" id="availability">
                    <option value="">Selecione...</option>
                    <option value="morning">Manhã</option>
                    <option value="afternoon">Tarde</option>
                    <option value="night">Noite</option>
                    <option value="weekends">Finais de Semana</option>
                </select>
            </div>
            <button type="submit" class="btn-premium" style="width: 100%; justify-content: center;">Cadastrar Voluntário</button>
        </form>
    </div>
</div>

<!-- Modal Certificate -->
<div id="certificateModal" class="custom-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000; overflow-y: auto; pointer-events: auto !important; -webkit-overflow-scrolling: touch;">
    <div class="vivensi-card" style="width: 95%; max-width: 520px; margin: 40px auto; pointer-events: auto !important; position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <h3 style="margin:0;">Emitir Certificado</h3>
                <div style="color:#64748b; font-size:.9rem; margin-top:4px;">
                    Voluntário: <strong id="certVolunteerName">—</strong>
                </div>
            </div>
            <button onclick="closeModal('certificateModal')" style="border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>

        <form id="certificateForm" method="POST" action="#">
            @csrf
            <div class="form-group">
                <label for="activity_description">Atividade / Descrição</label>
                <input type="text" name="activity_description" class="form-control-vivensi" required placeholder="Ex: Apoio em eventos, distribuição de alimentos..." id="activity_description">
            </div>
            <div class="grid-2" style="gap: 15px;">
                <div class="form-group">
                    <label for="hours">Horas</label>
                    <input type="number" name="hours" class="form-control-vivensi" required min="1" max="1000" value="4" id="hours">
                </div>
                <div class="form-group">
                    <label>Data de Emissão</label>
                    <input type="date" name="issued_at" class="form-control-vivensi" value="{{ date('Y-m-d') }}">
                </div>
            </div>

            <button type="submit" class="btn-premium" style="width: 100%; justify-content: center; background:#111827;">
                <i class="fas fa-file-pdf"></i> Gerar PDF do Certificado
            </button>
        </form>
    </div>
</div>

<!-- Modal Edit Employee -->
<div id="editEmployeeModal" class="custom-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000; overflow-y: auto; pointer-events: auto !important; -webkit-overflow-scrolling: touch;">
    <div class="vivensi-card" style="width: 95%; max-width: 600px; margin: 40px auto; pointer-events: auto !important; position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>Editar Funcionário</h3>
            <button onclick="closeModal('editEmployeeModal')" style="border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form id="editEmployeeForm" method="POST" action="#">
            @csrf @method('PUT')
            <div class="form-group"><label for="editEmpName">Nome Completo</label><input type="text" id="editEmpName" name="name" class="form-control-vivensi" required></div>
            <div class="grid-2" style="gap: 15px;">
                <div class="form-group"><label for="editEmpPosition">Cargo/Função</label><input type="text" id="editEmpPosition" name="position" class="form-control-vivensi" required></div>
                <div class="form-group"><label for="editEmpContractType">Tipo Contrato</label>
                    <select id="editEmpContractType" name="contract_type" class="form-control-vivensi">
                        <option value="clt">CLT (Efetivo)</option>
                        <option value="pj">PJ (Prestador)</option>
                        <option value="trainee">Estagiário</option>
                        <option value="temporary">Temporário</option>
                    </select>
                </div>
            </div>
            <div class="grid-2" style="gap: 15px;">
                <div class="form-group"><label for="editEmpSalary">Salário (R$)</label><input type="text" id="editEmpSalary" name="salary" class="form-control-vivensi" required></div>
                <div class="form-group"><label for="editEmpHours">Carga Horária</label><input type="text" id="editEmpHours" name="work_hours_weekly" class="form-control-vivensi" required></div>
            </div>
            <div class="grid-2" style="gap: 15px;">
                <div class="form-group"><label for="editEmpHiredAt">Data de Admissão</label><input type="date" id="editEmpHiredAt" name="hired_at" class="form-control-vivensi" required></div>
                <div class="form-group"><label for="editEmpStatus">Status</label>
                    <select id="editEmpStatus" name="status" class="form-control-vivensi">
                        <option value="active">Ativo</option>
                        <option value="vacation">Férias</option>
                        <option value="terminated">Desligado</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="editEmpProject">Projeto (opcional)</label>
                <select id="editEmpProject" name="project_id" class="form-control-vivensi">
                    <option value="">— Nenhum —</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}">{{ $p->name ?? ('Projeto #'.$p->id) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-premium" style="width: 100%; justify-content: center;">Salvar Alterações</button>
        </form>
    </div>
</div>

<!-- Modal Edit Volunteer -->
<div id="editVolunteerModal" class="custom-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000; overflow-y: auto; pointer-events: auto !important; -webkit-overflow-scrolling: touch;">
    <div class="vivensi-card" style="width: 95%; max-width: 500px; margin: 40px auto; pointer-events: auto !important; position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>Editar Voluntário</h3>
            <button onclick="closeModal('editVolunteerModal')" style="border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form id="editVolunteerForm" method="POST" action="#">
            @csrf @method('PUT')
            <div class="form-group"><label for="editVolName">Nome</label><input type="text" id="editVolName" name="name" class="form-control-vivensi" required></div>
            <div class="form-group"><label for="editVolEmail">Email</label><input type="email" id="editVolEmail" name="email" class="form-control-vivensi"></div>
            <div class="form-group"><label for="editVolPhone">Telefone / WhatsApp</label><input type="text" id="editVolPhone" name="phone" class="form-control-vivensi"></div>
            <div class="form-group"><label for="editVolSkills">Habilidades</label><input type="text" id="editVolSkills" name="skills" class="form-control-vivensi"></div>
            <div class="form-group"><label for="editVolAvailability">Disponibilidade</label>
                <select id="editVolAvailability" name="availability" class="form-control-vivensi">
                    <option value="">Selecione...</option>
                    <option value="morning">Manhã</option>
                    <option value="afternoon">Tarde</option>
                    <option value="night">Noite</option>
                    <option value="weekends">Finais de Semana</option>
                </select>
            </div>
            <button type="submit" class="btn-premium" style="width: 100%; justify-content: center;">Salvar Alterações</button>
        </form>
    </div>
</div>

<!-- Modal Hour Logs History -->
<div id="hourLogsModal" class="custom-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000; overflow-y: auto; pointer-events: auto !important; -webkit-overflow-scrolling: touch;">
    <div class="vivensi-card" style="width: 95%; max-width: 560px; margin: 40px auto; pointer-events: auto !important; position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <h3 style="margin:0;">Histórico de Horas</h3>
                <div style="color:#64748b; font-size:.9rem; margin-top:4px;">Voluntário: <strong id="hourLogsVolName">—</strong></div>
            </div>
            <button onclick="closeModal('hourLogsModal')" style="border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <div id="hourLogsLoading" style="text-align:center; padding:30px; color:#94a3b8;">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
        </div>
        <div id="hourLogsEmpty" style="display:none; text-align:center; padding:30px; color:#94a3b8;">
            <i class="fas fa-clock fa-2x" style="margin-bottom:8px; display:block; opacity:.3;"></i>
            Nenhuma hora registrada ainda.
        </div>
        <div id="hourLogsTable" style="display:none;">
            <table style="width:100%; border-collapse:collapse; font-size:.88rem;">
                <thead>
                    <tr style="background:#f8fafc; text-transform:uppercase; font-size:.72rem; color:#64748b; letter-spacing:.05em;">
                        <th style="padding:10px 12px; text-align:left;">Data</th>
                        <th style="padding:10px 12px; text-align:center;">Horas</th>
                        <th style="padding:10px 12px; text-align:left;">Descrição</th>
                        <th style="padding:10px 12px; text-align:left;">Registrado por</th>
                    </tr>
                </thead>
                <tbody id="hourLogsTbody"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Delete Volunteer Confirm -->
<div id="deleteVolunteerModal" class="custom-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000; overflow-y: auto; pointer-events: auto !important; -webkit-overflow-scrolling: touch;">
    <div class="vivensi-card" style="width: 95%; max-width: 460px; margin: 120px auto; pointer-events: auto !important; position: relative;">
        <div style="text-align:center; margin-bottom:20px;">
            <div style="width:56px; height:56px; background:#fee2e2; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 14px;">
                <i class="fas fa-triangle-exclamation" style="color:#dc2626; font-size:1.4rem;"></i>
            </div>
            <h3 style="margin:0; color:#0f172a;">Excluir Voluntário</h3>
            <p id="deleteVolMsg" style="color:#64748b; margin:10px 0 0; font-size:.92rem; line-height:1.5;"></p>
        </div>
        <div id="deleteVolCertWarning" style="display:none; background:#fef2f2; border:1px solid #fecaca; border-radius:10px; padding:12px 16px; margin-bottom:18px; font-size:.85rem; color:#b91c1c;">
            <i class="fas fa-triangle-exclamation me-1"></i>
            <strong id="deleteVolCertCount"></strong> certificado(s) emitido(s) para este voluntário também serão <strong>excluídos permanentemente</strong>.
        </div>
        <form id="deleteVolForm" method="POST" action="#">
            @csrf @method('DELETE')
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" onclick="closeModal('deleteVolunteerModal')" class="btn-ds btn-ds-outline" style="padding:9px 20px;">Cancelar</button>
                <button type="submit" style="background:#dc2626; color:#fff; border:none; border-radius:8px; padding:9px 20px; font-weight:700; cursor:pointer;">
                    <i class="fas fa-trash me-1"></i> Excluir mesmo assim
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Log Hours -->
<div id="logHoursModal" class="custom-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000; overflow-y: auto; pointer-events: auto !important; -webkit-overflow-scrolling: touch;">
    <div class="vivensi-card" style="width: 95%; max-width: 450px; margin: 40px auto; pointer-events: auto !important; position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h3 style="margin:0;">Lançar Horas</h3>
                <div style="color:#64748b; font-size:.9rem; margin-top:4px;">
                    Voluntário: <strong id="logHoursVolunteerName">—</strong>
                </div>
            </div>
            <button onclick="closeModal('logHoursModal')" style="border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form id="logHoursForm" method="POST" action="#">
            @csrf
            
            <div style="background: #fffbeb; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem; color: #92400e;">
                <strong>Regra Financeira:</strong> A cada 1 hora trabalhada o voluntário recebe automaticamente 10 Pontos, que o fará subir de nível.
            </div>

            <div class="grid-2" style="gap: 15px;">
                <div class="form-group">
                    <label for="hours">Horas doadas</label>
                    <input type="number" name="hours" class="form-control-vivensi" min="1" required placeholder="Ex: 5" id="hours">
                </div>
                <div class="form-group">
                    <label for="description">Descrição Opcional</label>
                    <input type="text" name="description" class="form-control-vivensi" placeholder="Ex: Sopa solidária..." id="description">
                </div>
            </div>
            <button type="submit" class="btn-premium" style="width: 100%; justify-content: center; background: #f59e0b; color: white;">Registrar Horas e Pontos</button>
        </form>
    </div>
</div>

<script>
    function openModal(id) { 
        const m = document.getElementById(id);
        if (m) {
            // Unfreeze UI and prevent background scroll
            document.body.style.overflow = 'hidden';
            document.body.style.pointerEvents = 'auto'; 
            
            const main = document.querySelector('.main-content');
            if(main) main.style.pointerEvents = 'auto';

            // Show with block/initial to allow margin centering within overflow-y: auto container
            m.style.setProperty('display', 'block', 'important');
            m.style.setProperty('pointer-events', 'auto', 'important');
            
            // Focus first input
            setTimeout(() => {
                const firstInput = m.querySelector('input, select, textarea');
                if(firstInput) firstInput.focus();
            }, 150);
        }
    }
    function closeModal(id) { 
        const m = document.getElementById(id);
        if (m) {
            m.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }

    // Modal behavior: close on click outside (the scrollable container)
    document.addEventListener('mousedown', function(e) {
        if (e.target.classList.contains('custom-modal')) {
            closeModal(e.target.id);
        }
    });

    // ESC key safety
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.custom-modal').forEach(m => {
                if(m.style.display !== 'none') closeModal(m.id);
            });
        }
    });

    function openCertificateModal(volunteerId, volunteerName) {
        const form = document.getElementById('certificateForm');
        const label = document.getElementById('certVolunteerName');
        if (label) label.textContent = volunteerName || '—';
        if (form) form.action = "{{ url('/ngo/hr/volunteers') }}/" + volunteerId + "/certificate";
        openModal('certificateModal');
    }

    function openEditEmployee(id, name, position, contractType, salary, workHours, hiredAt, status, projectId) {
        const form = document.getElementById('editEmployeeForm');
        if (form) form.action = "{{ url('/ngo/hr/employees') }}/" + id;
        document.getElementById('editEmpName').value = name || '';
        document.getElementById('editEmpPosition').value = position || '';
        document.getElementById('editEmpContractType').value = contractType || 'clt';
        document.getElementById('editEmpSalary').value = salary || '';
        document.getElementById('editEmpHours').value = workHours || '';
        document.getElementById('editEmpHiredAt').value = hiredAt || '';
        document.getElementById('editEmpStatus').value = status || 'active';
        const projSel = document.getElementById('editEmpProject');
        if (projSel) projSel.value = projectId || '';
        openModal('editEmployeeModal');
    }

    function openEditVolunteer(id, name, email, phone, skills, availability) {
        const form = document.getElementById('editVolunteerForm');
        if (form) form.action = "{{ url('/ngo/hr/volunteers') }}/" + id;
        document.getElementById('editVolName').value = name || '';
        document.getElementById('editVolEmail').value = email || '';
        document.getElementById('editVolPhone').value = phone || '';
        document.getElementById('editVolSkills').value = skills || '';
        document.getElementById('editVolAvailability').value = availability || '';
        openModal('editVolunteerModal');
    }

    function openLogHoursModal(volunteerId, volunteerName) {
        const form = document.getElementById('logHoursForm');
        const label = document.getElementById('logHoursVolunteerName');
        if (label) label.textContent = volunteerName || '—';
        if (form) form.action = "{{ url('/ngo/hr/volunteers') }}/" + volunteerId + "/log-hours";
        openModal('logHoursModal');
    }

    function openHourLogs(volunteerId, volunteerName) {
        document.getElementById('hourLogsVolName').textContent = volunteerName || '—';
        document.getElementById('hourLogsLoading').style.display = 'block';
        document.getElementById('hourLogsEmpty').style.display = 'none';
        document.getElementById('hourLogsTable').style.display = 'none';
        openModal('hourLogsModal');

        fetch("{{ url('/ngo/hr/volunteers') }}/" + volunteerId + "/hour-logs", {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(function(logs) {
            document.getElementById('hourLogsLoading').style.display = 'none';
            if (!logs.length) {
                document.getElementById('hourLogsEmpty').style.display = 'block';
                return;
            }
            const tbody = document.getElementById('hourLogsTbody');
            tbody.innerHTML = '';
            logs.forEach(function(l) {
                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid #f1f5f9';
                tr.innerHTML = `
                    <td style="padding:9px 12px; color:#64748b; white-space:nowrap;">${l.created_at}</td>
                    <td style="padding:9px 12px; text-align:center; font-weight:800; color:#b45309;">${l.hours}h</td>
                    <td style="padding:9px 12px; color:#334155;">${l.description}</td>
                    <td style="padding:9px 12px; color:#64748b; font-size:.82rem;">${l.logged_by}</td>
                `;
                tbody.appendChild(tr);
            });
            document.getElementById('hourLogsTable').style.display = 'block';
        })
        .catch(function() {
            document.getElementById('hourLogsLoading').style.display = 'none';
            document.getElementById('hourLogsEmpty').style.display = 'block';
        });
    }

    function confirmDeleteVolunteer(volunteerId, volunteerName, certCount) {
        document.getElementById('deleteVolMsg').textContent =
            'Tem certeza que deseja excluir o voluntário "' + volunteerName + '"? Esta ação não pode ser desfeita.';
        const warn = document.getElementById('deleteVolCertWarning');
        if (certCount > 0) {
            document.getElementById('deleteVolCertCount').textContent = certCount;
            warn.style.display = 'block';
        } else {
            warn.style.display = 'none';
        }
        const form = document.getElementById('deleteVolForm');
        if (form) form.action = "{{ url('/ngo/hr/volunteers') }}/" + volunteerId;
        openModal('deleteVolunteerModal');
    }

    // Status filter pills
    (function() {
        const pills = document.querySelectorAll('.vol-status-pill');
        pills.forEach(function(pill) {
            pill.addEventListener('click', function() {
                pills.forEach(p => {
                    p.style.background = '#fff';
                    p.style.color = '#64748b';
                    p.classList.remove('active');
                });
                pill.style.background = '#0f172a';
                pill.style.color = '#fff';
                pill.classList.add('active');

                const filter = pill.dataset.filter;
                const search = (document.getElementById('hrVolunteerSearch')?.value || '').toLowerCase().trim();
                applyVolFilter(filter, search);
            });
        });

        function applyVolFilter(statusFilter, searchQuery) {
            document.querySelectorAll('.hr-volunteer-card').forEach(function(card) {
                const statusMatch = statusFilter === 'all' || card.dataset.status === statusFilter;
                const searchMatch = !searchQuery || (card.getAttribute('data-q') || '').includes(searchQuery);
                card.style.display = (statusMatch && searchMatch) ? '' : 'none';
            });
        }

        const vol = document.getElementById('hrVolunteerSearch');
        if (vol) {
            vol.addEventListener('input', function() {
                const activeFilter = document.querySelector('.vol-status-pill.active')?.dataset.filter || 'all';
                applyVolFilter(activeFilter, vol.value.toLowerCase().trim());
            });
        }
    })();

    function showTab(tabName) {
        document.getElementById('tab-employees').style.display = 'none';
        document.getElementById('tab-volunteers').style.display = 'none';
        document.getElementById('btn-employees').style.borderBottom = 'none';
        document.getElementById('btn-employees').style.color = '#64748b';
        document.getElementById('btn-volunteers').style.borderBottom = 'none';
        document.getElementById('btn-volunteers').style.color = '#64748b';

        document.getElementById('tab-' + tabName).style.display = 'block';
        document.getElementById('btn-' + tabName).style.borderBottom = '2px solid #4f46e5';
        document.getElementById('btn-' + tabName).style.color = '#4f46e5';
    }

    // Client-side search (MVP)
    (function() {
        const emp = document.getElementById('hrEmployeeSearch');
        if (emp) {
            emp.addEventListener('input', function() {
                const q = (emp.value || '').toLowerCase().trim();
                document.querySelectorAll('.hr-employee-row').forEach(function(row) {
                    const hay = (row.getAttribute('data-q') || '');
                    row.style.display = (!q || hay.includes(q)) ? '' : 'none';
                });
            });
        }

    })();
</script>
@endsection
