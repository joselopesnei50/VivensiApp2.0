@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Recursos Humanos & Voluntariado</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Gestão completa da equipe, pagamentos e colaboradores.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <div style="display:flex; gap: 10px; flex-wrap: wrap;">
            <a href="{{ url('/ngo/hr/employees/export') }}" class="btn-premium" style="background:#4f46e5;">
                <i class="fas fa-file-csv"></i> CSV Funcionários
            </a>
            <a href="{{ url('/ngo/hr/volunteers/export') }}" class="btn-premium" style="background:#0ea5e9;">
                <i class="fas fa-file-csv"></i> CSV Voluntários
            </a>
            <a href="{{ url('/ngo/hr/payroll/pdf') }}?month={{ date('n') }}&year={{ date('Y') }}" class="btn-premium" style="background:#16a34a;">
                <i class="fas fa-file-pdf"></i> PDF Folha
            </a>
            <a href="{{ url('/ngo/hr/certificates') }}" class="btn-premium" style="background:#111827;">
                <i class="fas fa-certificate"></i> Certificados
            </a>
        </div>
        <button onclick="openModal('employeeModal')" class="btn-premium">
            <i class="fas fa-briefcase"></i> Novo Funcionário
        </button>
        <button onclick="openModal('volunteerModal')" class="btn-premium" style="background: #e0e7ff; color: #4338ca;">
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
                    <td style="padding: 15px; text-align: center;">
                        <button type="button" title="Em breve" style="border: none; background: none; color: #94a3b8; cursor: not-allowed;" disabled><i class="fas fa-pen"></i></button>
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
            <div style="color:#64748b; font-weight:800;">
                Dica: busque por nome, e-mail ou habilidades.
            </div>
            <input id="hrVolunteerSearch" type="text" placeholder="Buscar voluntário..." class="form-control-vivensi" style="max-width: 320px;">
        </div>
    </div>
    <div class="grid-3">
        @foreach($volunteers as $volunteer)
        @php
            $vCerts = $certByVolunteer->get($volunteer->id) ?? collect();
            $vCertCount = (int) $vCerts->count();
            $vCertRecent = $vCerts->take(3);
        @endphp
        <div class="vivensi-card hr-volunteer-card" data-q="{{ strtolower(($volunteer->name ?? '').' '.($volunteer->email ?? '').' '.($volunteer->skills ?? '')) }}" style="position: relative;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <div style="width: 50px; height: 50px; background: #e0e7ff; color: #4338ca; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem;">
                    {{ $volunteer->name[0] }}
                </div>
                <div>
                    <h4 style="margin: 0; font-size: 1rem;">{{ $volunteer->name }}</h4>
                    <span style="font-size: 0.8rem; color: #64748b;">{{ $volunteer->email }}</span>
                </div>
            </div>
            <div style="font-size: 0.85rem; color: #475569; margin-bottom: 15px;">
                <strong>Habilidades:</strong> {{ $volunteer->skills ?? 'Não informado' }}<br>
                <strong>Disponibilidade:</strong> {{ ucfirst($volunteer->availability) ?? 'Variável' }}
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
                                            $validateUrl = url('/validar-certificado/' . (int) $c->id) . '?code=' . $code;
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

            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn-premium" style="font-size: 0.8rem; background: #dcfce7; color: #166534; padding: 5px 10px;" onclick="alert('Em breve: abrir WhatsApp com mensagem padrão.');">
                    <i class="fab fa-whatsapp"></i> Contatar
                </button>
                <button type="button" class="btn-premium" style="font-size: 0.8rem; background: #f1f5f9; color: #64748b; padding: 5px 10px;" onclick='openCertificateModal({{ (int) $volunteer->id }}, @json($volunteer->name))'>
                    <i class="fas fa-certificate"></i> Certificado
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
<div id="employeeModal" class="custom-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div class="vivensi-card" style="width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>Novo Funcionário</h3>
            <button onclick="closeModal('employeeModal')" style="border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form action="{{ url('/ngo/hr/employees') }}" method="POST">
            @csrf
            <div class="form-group"><label>Nome Completo</label><input type="text" name="name" class="form-control-vivensi" required></div>
            <div class="grid-2" style="gap: 15px;">
                <div class="form-group"><label>Cargo/Função</label><input type="text" name="position" class="form-control-vivensi" required></div>
                <div class="form-group"><label>Tipo Contrato</label>
                    <select name="contract_type" class="form-control-vivensi">
                        <option value="clt">CLT (Efetivo)</option>
                        <option value="pj">PJ (Prestador)</option>
                        <option value="trainee">Estagiário</option>
                        <option value="temporary">Temporário</option>
                    </select>
                </div>
            </div>
            <div class="grid-2" style="gap: 15px;">
                <div class="form-group"><label>Salário (R$)</label><input type="text" name="salary" class="form-control-vivensi" placeholder="0,00" required></div>
                <div class="form-group"><label>Carga Horária (Ex: 40h)</label><input type="text" name="work_hours_weekly" class="form-control-vivensi" value="40h Semanais" required></div>
            </div>
            <div class="form-group"><label>Data de Admissão</label><input type="date" name="hired_at" class="form-control-vivensi" required></div>

            <div class="form-group">
                <label>Vincular a um Projeto (opcional)</label>
                <select name="project_id" class="form-control-vivensi">
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
<div id="volunteerModal" class="custom-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div class="vivensi-card" style="width: 90%; max-width: 500px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>Novo Voluntário</h3>
            <button onclick="closeModal('volunteerModal')" style="border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form action="{{ url('/ngo/hr/volunteers') }}" method="POST">
            @csrf
            <div class="form-group"><label>Nome</label><input type="text" name="name" class="form-control-vivensi" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control-vivensi"></div>
            <div class="form-group"><label>Telefone / WhatsApp</label><input type="text" name="phone" class="form-control-vivensi"></div>
            <div class="form-group"><label>Habilidades (Tags)</label><input type="text" name="skills" class="form-control-vivensi" placeholder="Ex: Fotografia, Cozinha, Eventos"></div>
            <div class="form-group"><label>Disponibilidade</label>
                <select name="availability" class="form-control-vivensi">
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
<div id="certificateModal" class="custom-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div class="vivensi-card" style="width: 90%; max-width: 520px;">
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
                <label>Atividade / Descrição</label>
                <input type="text" name="activity_description" class="form-control-vivensi" required placeholder="Ex: Apoio em eventos, distribuição de alimentos...">
            </div>
            <div class="grid-2" style="gap: 15px;">
                <div class="form-group">
                    <label>Horas</label>
                    <input type="number" name="hours" class="form-control-vivensi" required min="1" max="1000" value="4">
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

<script>
    function openModal(id) { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    function openCertificateModal(volunteerId, volunteerName) {
        const form = document.getElementById('certificateForm');
        const label = document.getElementById('certVolunteerName');
        if (label) label.textContent = volunteerName || '—';
        if (form) form.action = "{{ url('/ngo/hr/volunteers') }}/" + volunteerId + "/certificate";
        openModal('certificateModal');
    }
    
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

        const vol = document.getElementById('hrVolunteerSearch');
        if (vol) {
            vol.addEventListener('input', function() {
                const q = (vol.value || '').toLowerCase().trim();
                document.querySelectorAll('.hr-volunteer-card').forEach(function(card) {
                    const hay = (card.getAttribute('data-q') || '');
                    card.style.display = (!q || hay.includes(q)) ? '' : 'none';
                });
            });
        }
    })();
</script>
@endsection
