@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 20px; display: flex; justify-content: space-between;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">{{ $beneficiary->name }}</h2>
        <span style="color: #64748b; font-size: 0.9rem;">
            NIS: {{ $beneficiary->nis ?? 'N/A' }} | Status: {{ ucfirst($beneficiary->status) }}
        </span>
    </div>
    <a href="{{ url('/ngo/beneficiaries') }}" class="btn-premium" style="background: #f1f5f9; color: #475569;">Voltar</a>
</div>

<div class="grid-2" style="grid-template-columns: 1fr 2fr; gap: 20px;">
    <!-- Coluna Esquerda: Dados e Família -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- Dados Básicos -->
        <div class="vivensi-card">
            <h4 style="margin-top: 0; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">Dados Pessoais</h4>
            <ul style="list-style: none; padding: 0; font-size: 0.9rem; color: #475569;">
                <li style="margin-bottom: 8px;"><strong>CPF:</strong> {{ $beneficiary->cpf ?? '-' }}</li>
                <li style="margin-bottom: 8px;"><strong>Nascimento:</strong> {{ $beneficiary->birth_date ? \Carbon\Carbon::parse($beneficiary->birth_date)->format('d/m/Y') : '-' }}</li>
                <li style="margin-bottom: 8px;"><strong>Telefone:</strong> {{ $beneficiary->phone ?? '-' }}</li>
                <li><strong>Endereço:</strong> {{ $beneficiary->address ?? '-' }}</li>
            </ul>
        </div>

        <!-- Composição Familiar -->
        <div class="vivensi-card">
            <h4 style="margin-top: 0; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">Composição Familiar</h4>
            @if($beneficiary->familyMembers->count() > 0)
                <ul style="list-style: none; padding: 0; font-size: 0.9rem;">
                    @foreach($beneficiary->familyMembers as $member)
                        <li style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between;">
                            <span>{{ $member->name }}</span>
                            <span style="color: #64748b; font-size: 0.8rem;">{{ $member->kinship }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p style="color: #94a3b8; font-size: 0.9rem;">Nenhum familiar cadastrado.</p>
            @endif
            <button class="btn-premium" style="width: 100%; margin-top: 10px; font-size: 0.8rem; justify-content: center; background: #e2e8f0; color: #475569;">+ Adicionar Familiar</button>
        </div>
    </div>

    <!-- Coluna Direita: Evolução e Atendimentos -->
    <div class="vivensi-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
            <h3 style="margin: 0; font-size: 1.1rem; color: #1e293b;">Histórico de Atendimentos</h3>
            <button onclick="document.getElementById('attendanceForm').style.display = 'block'" class="btn-premium" style="font-size: 0.9rem;">
                <i class="fas fa-notes-medical"></i> Registrar Evolução
            </button>
        </div>

        <!-- Form de Novo Atendimento (Hidden by default) -->
        <div id="attendanceForm" style="display: none; background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
            <form action="{{ url('/ngo/beneficiaries/' . $beneficiary->id . '/attendance') }}" method="POST">
                @csrf
                <div class="grid-2" style="gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; margin-bottom: 5px;">Data</label>
                        <input type="date" name="date" class="form-control-vivensi" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; margin-bottom: 5px;">Tipo</label>
                        <select name="type" class="form-control-vivensi">
                            <option value="Atendimento Social">Atendimento Social</option>
                            <option value="Visita Domiciliar">Visita Domiciliar</option>
                            <option value="Entr. Benefícios">Entrega de Benefícios</option>
                            <option value="Psicológico">Apoio Psicológico</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 0.8rem; margin-bottom: 5px;">Descrição da Evolução</label>
                    <textarea name="description" rows="3" class="form-control-vivensi" placeholder="Descreva o atendimento..." required></textarea>
                </div>
                <div style="text-align: right;">
                    <button type="button" onclick="document.getElementById('attendanceForm').style.display='none'" style="border: none; background: none; color: #64748b; margin-right: 15px; cursor: pointer;">Cancelar</button>
                    <button type="submit" class="btn-premium" style="display: inline-flex; width: auto; font-size: 0.9rem;">Salvar Registro</button>
                </div>
            </form>
        </div>

        <!-- Timeline -->
        <div class="timeline">
            @foreach($beneficiary->attendances()->orderBy('date', 'desc')->get() as $attendance)
            <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                <div style="display: flex; flex-direction: column; align-items: center;">
                    <div style="width: 12px; height: 12px; background: #4f46e5; border-radius: 50%; margin-top: 5px;"></div>
                    <div style="width: 2px; height: 100%; background: #e2e8f0; flex: 1;"></div>
                </div>
                <div style="flex: 1; padding-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <strong style="color: #1e293b; font-size: 0.95rem;">{{ $attendance->type }}</strong>
                        <span style="color: #64748b; font-size: 0.8rem;">{{ \Carbon\Carbon::parse($attendance->date)->format('d/m/Y') }}</span>
                    </div>
                    <p style="margin: 0; font-size: 0.9rem; color: #475569; line-height: 1.5;">
                        {{ $attendance->description }}
                    </p>
                    <span style="font-size: 0.75rem; color: #94a3b8; display: block; margin-top: 5px;">Registrado por: {{ $attendance->user->name ?? 'Sistema' }}</span>
                </div>
            </div>
            @endforeach
            
            @if($beneficiary->attendances->count() == 0)
                <p style="text-align: center; color: #94a3b8; padding: 20px;">Nenhum atendimento registrado.</p>
            @endif
        </div>
    </div>
</div>
@endsection
