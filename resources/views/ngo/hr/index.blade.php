@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Recursos Humanos & Voluntariado</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Gestão completa da equipe, pagamentos e colaboradores.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <button onclick="openModal('employeeModal')" class="btn-premium">
            <i class="fas fa-briefcase"></i> Novo Funcionário
        </button>
        <button onclick="openModal('volunteerModal')" class="btn-premium" style="background: #e0e7ff; color: #4338ca;">
            <i class="fas fa-hands-helping"></i> Novo Voluntário
        </button>
    </div>
</div>

<!-- Tabs -->
<div style="margin-bottom: 20px; border-bottom: 1px solid #e2e8f0;">
    <button class="tab-btn active" onclick="showTab('employees')" id="btn-employees" style="padding: 10px 20px; border: none; background: none; font-weight: 600; color: #4f46e5; border-bottom: 2px solid #4f46e5; cursor: pointer;">Funcionários (CLT/PJ)</button>
    <button class="tab-btn" onclick="showTab('volunteers')" id="btn-volunteers" style="padding: 10px 20px; border: none; background: none; font-weight: 600; color: #64748b; cursor: pointer;">Voluntários</button>
</div>

<!-- Employees Section -->
<div id="tab-employees" class="tab-content">
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
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 15px;">
                        <strong style="display: block; color: #1e293b;">{{ $employee->name }}</strong>
                        <span style="font-size: 0.85rem; color: #64748b;">{{ $employee->position }}</span>
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
                        <button style="border: none; background: none; color: #3b82f6; cursor: pointer;"><i class="fas fa-edit"></i></button>
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
    <div class="grid-3">
        @foreach($volunteers as $volunteer)
        <div class="vivensi-card" style="position: relative;">
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
            <div style="display: flex; gap: 10px;">
                <button class="btn-premium" style="font-size: 0.8rem; background: #dcfce7; color: #166534; padding: 5px 10px;">
                    <i class="fab fa-whatsapp"></i> Contatar
                </button>
                <button class="btn-premium" style="font-size: 0.8rem; background: #f1f5f9; color: #64748b; padding: 5px 10px;">
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

<script>
    function openModal(id) { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    
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
</script>
@endsection
