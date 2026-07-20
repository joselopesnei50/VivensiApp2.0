@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 20px; display: flex; justify-content: space-between;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">{{ $beneficiary->name }}</h2>
        <span style="color: #64748b; font-size: 0.9rem;">
            NIS: {{ $beneficiary->nis ?? 'N/A' }} | Status: {{ ucfirst($beneficiary->status) }}
        </span>
    </div>
    <div style="display:flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end;">
        <button type="button" class="btn-premium" onclick="toggleEditBeneficiary()">
            <i class="fas fa-pen"></i> Editar
        </button>
        @php
            $bPhone = preg_replace('/\D+/', '', (string) ($beneficiary->phone ?? ''));
            $bMsg = "Olá! Entrando em contato referente ao seu acompanhamento. Beneficiário: " . ($beneficiary->name ?? '');
        @endphp
        @if(!empty($bPhone))
            <a target="_blank" rel="noopener" href="https://wa.me/{{ $bPhone }}?text={{ urlencode($bMsg) }}"
               class="btn-ds btn-ds-outline" style="background:#dcfce7; color:#166534; border-color:#bbf7d0;">
                <i class="fab fa-whatsapp"></i> WhatsApp
            </a>
        @endif
        <button id="btn-term-pdf" onclick="generateTermPdf(this)"
           class="btn-ds btn-ds-outline" style="background:#f0fdf4; color:#16a34a; border-color:#bbf7d0;">
            <i class="fas fa-file-pdf"></i> PDF
        </button>
        <a href="{{ url('/ngo/beneficiaries/' . $beneficiary->id . '/attendance/export') . '?' . http_build_query(request()->query()) }}"
           class="btn-ds btn-ds-outline" style="background:var(--ds-brand-bg); color:var(--ds-brand); border-color:var(--ds-brand);">
            <i class="fas fa-file-csv"></i> CSV
        </a>
        <a href="{{ url('/ngo/beneficiaries/' . $beneficiary->id . '/attendance/print') . '?' . http_build_query(request()->query()) }}"
           class="btn-ds btn-ds-outline">
            <i class="fas fa-print"></i> Imprimir
        </a>
        <a href="{{ url('/ngo/beneficiaries') }}" class="btn-ds btn-ds-ghost">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <form method="POST" action="{{ url('/ngo/beneficiaries/' . $beneficiary->id) }}" onsubmit="return confirm('Remover este beneficiário e todo o histórico?');" style="display:inline;">
            @csrf @method('DELETE')
            <button type="submit" class="btn-ds btn-ds-danger">
                <i class="fas fa-trash"></i> Remover
            </button>
        </form>
    </div>
</div>

<div class="grid-2" style="grid-template-columns: 1fr 2fr; gap: 20px;">
    <!-- Coluna Esquerda: Dados e Família -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- Dados Básicos -->
        <div class="vivensi-card">
            <h4 style="margin-top: 0; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">Dados Pessoais</h4>
            <div id="beneficiaryView">
                @php
                    $genderLabels = ['masculino'=>'Masculino','feminino'=>'Feminino','nao_binario'=>'Não-binário','outro'=>'Outro','prefiro_nao_informar'=>'Não informado'];
                    $raceLabels   = ['branca'=>'Branca','preta'=>'Preta','parda'=>'Parda','amarela'=>'Amarela','indigena'=>'Indígena','prefiro_nao_informar'=>'Não informado'];
                    $eduLabels    = ['sem_escolaridade'=>'Sem escolaridade','fundamental_incompleto'=>'Fund. incompleto','fundamental_completo'=>'Fund. completo','medio_incompleto'=>'Médio incompleto','medio_completo'=>'Médio completo','superior_incompleto'=>'Superior incompleto','superior_completo'=>'Superior completo','pos_graduacao'=>'Pós-graduação'];
                    $idade = $beneficiary->birth_date ? \Carbon\Carbon::parse($beneficiary->birth_date)->age . ' anos' : '-';
                @endphp
                <ul style="list-style: none; padding: 0; font-size: 0.9rem; color: #475569;">
                    <li style="margin-bottom: 8px;"><strong>CPF:</strong> {{ $beneficiary->cpf ?? '-' }}</li>
                    <li style="margin-bottom: 8px;"><strong>Nascimento:</strong> {{ $beneficiary->birth_date ? \Carbon\Carbon::parse($beneficiary->birth_date)->format('d/m/Y') : '-' }} @if($beneficiary->birth_date)<span style="color:#94a3b8; font-size:0.8rem;">({{ $idade }})</span>@endif</li>
                    <li style="margin-bottom: 8px;"><strong>Sexo:</strong> {{ $genderLabels[$beneficiary->gender ?? ''] ?? '-' }}</li>
                    <li style="margin-bottom: 8px;"><strong>Cor/Raça:</strong> {{ $raceLabels[$beneficiary->race_color ?? ''] ?? '-' }}</li>
                    <li style="margin-bottom: 8px;"><strong>Escolaridade:</strong> {{ $eduLabels[$beneficiary->education ?? ''] ?? '-' }}</li>
                    <li style="margin-bottom: 8px;"><strong>Telefone:</strong> {{ $beneficiary->phone ?? '-' }}</li>
                    <li style="margin-bottom: 8px;"><strong>Status:</strong> {{ strtoupper($beneficiary->status ?? '—') }}</li>
                    <li><strong>Endereço:</strong> {{ $beneficiary->address ?? '-' }}</li>
                </ul>
            </div>

            <div id="beneficiaryEdit" style="display:none;">
                <form method="POST" action="{{ url('/ngo/beneficiaries/' . $beneficiary->id) }}">
                    @csrf @method('PUT')

                    <div class="form-group">
                        <label class="form-label">Nome <span style="color:var(--ds-danger)">*</span></label>
                        <input type="text" name="name" class="form-control-vivensi" value="{{ $beneficiary->name }}" required>
                    </div>

                    <div class="grid-2" style="gap: 12px;">
                        <div class="form-group">
                            <label class="form-label">NIS</label>
                            <input type="text" name="nis" class="form-control-vivensi" value="{{ $beneficiary->nis }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">CPF</label>
                            <input type="text" name="cpf" class="form-control-vivensi" value="{{ $beneficiary->cpf }}">
                        </div>
                    </div>

                    <div class="grid-2" style="gap: 12px;">
                        <div class="form-group">
                            <label class="form-label">Nascimento</label>
                            <input type="date" name="birth_date" class="form-control-vivensi" value="{{ optional($beneficiary->birth_date)->format('Y-m-d') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Telefone</label>
                            <input type="text" name="phone" class="form-control-vivensi" value="{{ $beneficiary->phone }}">
                        </div>
                    </div>

                    <div class="grid-2" style="gap: 12px;">
                        <div class="form-group">
                            <label class="form-label">Sexo / Gênero</label>
                            <select name="gender" class="form-control-vivensi">
                                <option value="">Selecione...</option>
                                @foreach(['masculino'=>'Masculino','feminino'=>'Feminino','nao_binario'=>'Não-binário','outro'=>'Outro','prefiro_nao_informar'=>'Prefiro não informar'] as $val => $lbl)
                                    <option value="{{ $val }}" {{ $beneficiary->gender === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cor / Raça</label>
                            <select name="race_color" class="form-control-vivensi">
                                <option value="">Selecione...</option>
                                @foreach(['branca'=>'Branca','preta'=>'Preta','parda'=>'Parda','amarela'=>'Amarela','indigena'=>'Indígena','prefiro_nao_informar'=>'Prefiro não informar'] as $val => $lbl)
                                    <option value="{{ $val }}" {{ $beneficiary->race_color === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid-2" style="gap: 12px;">
                        <div class="form-group">
                            <label class="form-label">Escolaridade</label>
                            <select name="education" class="form-control-vivensi">
                                <option value="">Selecione...</option>
                                @foreach(['sem_escolaridade'=>'Sem escolaridade','fundamental_incompleto'=>'Fund. incompleto','fundamental_completo'=>'Fund. completo','medio_incompleto'=>'Médio incompleto','medio_completo'=>'Médio completo','superior_incompleto'=>'Superior incompleto','superior_completo'=>'Superior completo','pos_graduacao'=>'Pós-graduação'] as $val => $lbl)
                                    <option value="{{ $val }}" {{ $beneficiary->education === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status <span style="color:var(--ds-danger)">*</span></label>
                            <select name="status" class="form-control-vivensi" required>
                                <option value="active"    {{ $beneficiary->status === 'active'    ? 'selected' : '' }}>Ativo</option>
                                <option value="inactive"  {{ $beneficiary->status === 'inactive'  ? 'selected' : '' }}>Inativo</option>
                                <option value="graduated" {{ $beneficiary->status === 'graduated' ? 'selected' : '' }}>Graduado</option>
                            </select>
                        </div>
                    </div>

                    <x-address-fields :model="$beneficiary" />

                    <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:12px;">
                        <button type="button" class="btn-ds btn-ds-ghost" onclick="toggleEditBeneficiary(false)">Cancelar</button>
                        <button type="submit" class="btn-premium"><i class="fas fa-save"></i> Salvar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Composição Familiar -->
        <div class="vivensi-card">
            <h4 style="margin-top: 0; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">Composição Familiar</h4>
            @if($beneficiary->familyMembers->count() > 0)
                <ul style="list-style: none; padding: 0; font-size: 0.9rem;">
                    @foreach($beneficiary->familyMembers as $member)
                        <li style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; gap: 10px; align-items: center;">
                            <div>
                                <div style="font-weight:800; color:#0f172a;">{{ $member->name }}</div>
                                <div style="color: #64748b; font-size: 0.82rem;">
                                    {{ $member->kinship }}
                                    @if(!empty($member->birth_date))
                                        · Nasc.: {{ optional($member->birth_date)->format('d/m/Y') }}
                                    @endif
                                </div>
                            </div>
                            <form method="POST" action="{{ url('/ngo/beneficiaries/' . $beneficiary->id . '/family-members/' . $member->id) }}" onsubmit="return confirm('Remover este familiar?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Remover" style="border:none; background:#fee2e2; color:#991b1b; padding:6px 10px; border-radius:10px; cursor:pointer; font-weight:800;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @else
                <p style="color: #94a3b8; font-size: 0.9rem;">Nenhum familiar cadastrado.</p>
            @endif
            <button onclick="document.getElementById('familyForm').style.display = 'block'" class="btn-premium" style="width: 100%; margin-top: 10px; font-size: 0.85rem; justify-content: center;">
                <i class="fas fa-user-plus"></i> Adicionar Familiar
            </button>

            <div id="familyForm" style="display:none; margin-top: 12px; background:#f8fafc; padding: 14px; border-radius: 10px; border: 1px solid #e2e8f0;">
                <form method="POST" action="{{ url('/ngo/beneficiaries/' . $beneficiary->id . '/family-members') }}">
                    @csrf
                    <div class="form-group">
                        <label style="display:block; font-size:.8rem; margin-bottom:5px;">Nome</label>
                        <input type="text" name="name" class="form-control-vivensi" required>
                    </div>
                    <div class="grid-2" style="gap: 12px;">
                        <div>
                            <label style="display:block; font-size:.8rem; margin-bottom:5px;">Parentesco</label>
                            <input type="text" name="kinship" class="form-control-vivensi" placeholder="Ex: Filho(a), Cônjuge, Avó" required>
                        </div>
                        <div>
                            <label style="display:block; font-size:.8rem; margin-bottom:5px;">Nascimento (opcional)</label>
                            <input type="date" name="birth_date" class="form-control-vivensi">
                        </div>
                    </div>
                    <div style="text-align:right; margin-top: 10px;">
                        <button type="button" onclick="document.getElementById('familyForm').style.display='none'" style="border: none; background: none; color: #64748b; margin-right: 12px; cursor: pointer;">Cancelar</button>
                        <button type="submit" class="btn-premium" style="display:inline-flex; width:auto; font-size:.9rem;">
                            <i class="fas fa-save"></i> Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Projetos vinculados -->
        <div class="vivensi-card">
            <h4 style="margin-top: 0; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                <i class="fas fa-project-diagram" style="color:#4f46e5; margin-right:6px;"></i> Projetos vinculados
                <span style="color:#94a3b8; font-weight:700; font-size:.85rem;">({{ $beneficiary->projectMemberships->count() }})</span>
            </h4>
            @forelse($beneficiary->projectMemberships as $link)
                @php
                    $enrolStatus = $link->enrollment_status ?? 'ativo';
                    $enrolColor  = match(strtolower($enrolStatus)) {
                        'ativo'         => ['#059669', '#d1fae5'],
                        'desligado'     => ['#dc2626', '#fee2e2'],
                        'concluido', 'concluído' => ['#2563eb', '#dbeafe'],
                        default         => ['#64748b', '#f1f5f9'],
                    };
                @endphp
                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid #f1f5f9;">
                    <div style="flex:1;">
                        @if($link->project)
                            <a href="{{ url('/projects/details/' . $link->project->id) }}"
                               style="font-weight:800; color:#0f172a; text-decoration:none;">
                                {{ $link->project->name }}
                                <i class="fas fa-external-link-alt" style="font-size:.7rem; color:#94a3b8; margin-left:4px;"></i>
                            </a>
                        @else
                            <span style="font-weight:800; color:#94a3b8;">— projeto excluído —</span>
                        @endif
                        <div style="font-size:.8rem; color:#64748b; margin-top:2px;">
                            Nome no projeto: {{ $link->name }}
                        </div>
                        @php
                            $activeTurmas = $link->enrollments
                                ->map(fn ($e) => $e->projectClass)
                                ->filter()
                                ->unique('id');
                        @endphp
                        @if($activeTurmas->isNotEmpty())
                            <div style="font-size:.75rem; color:#4f46e5; margin-top:4px; font-weight:700;">
                                <i class="fas fa-chalkboard-teacher" style="margin-right:4px;"></i>
                                Turmas ativas:
                                @foreach($activeTurmas as $turma)
                                    <span style="display:inline-block; margin-right:6px; padding:2px 8px; border-radius:99px; background:#eef2ff; color:#4338ca; font-weight:800;">
                                        {{ $turma->name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <span style="padding:4px 10px; border-radius:99px; font-size:.72rem; font-weight:800; letter-spacing:.3px; color:{{ $enrolColor[0] }}; background:{{ $enrolColor[1] }};">
                        {{ ucfirst($enrolStatus) }}
                    </span>
                </div>
            @empty
                <p style="color:#94a3b8; font-size:.9rem; margin:0;">Este beneficiário ainda não foi vinculado a nenhum projeto.</p>
                <div style="font-size:.8rem; color:#64748b; margin-top:6px;">Vá em <strong>Projetos</strong> → escolha um projeto → <em>"Adicionar pessoa"</em> → botão "Escolher beneficiário".</div>
            @endforelse
        </div>
    </div>

    <!-- Coluna Direita: Evolução e Atendimentos -->
    <div class="vivensi-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
            <h3 style="margin: 0; font-size: 1.1rem; color: #1e293b;">Histórico de Atendimentos</h3>
            <button type="button" onclick="openAttendanceForm()" class="btn-premium" style="font-size: 0.9rem;">
                <i class="fas fa-notes-medical"></i> Registrar Evolução
            </button>
        </div>

        @php
            $totalAttendances = (int) ($stats['attendances_total'] ?? 0);
            $lastAttendanceAt = $stats['last_attendance_at'] ?? null;
        @endphp
        <div class="vivensi-card" style="margin-bottom: 14px; background:#f8fafc; border:1px solid #e2e8f0;">
            <div style="display:flex; gap: 14px; flex-wrap: wrap; justify-content: space-between; align-items:center;">
                <div style="color:#334155; font-weight:800;">
                    Total de registros: <span style="color:#4f46e5;">{{ number_format($totalAttendances) }}</span>
                </div>
                <div style="color:#64748b;">
                    Último atendimento: <strong>{{ $lastAttendanceAt ? \Carbon\Carbon::parse($lastAttendanceAt)->format('d/m/Y') : '—' }}</strong>
                </div>
            </div>
        </div>

        <div class="vivensi-card" style="margin-bottom: 14px; border:1px solid #e2e8f0;">
            <form method="GET" action="" style="display:flex; gap: 10px; flex-wrap: wrap; align-items: end;">
                <div class="form-group" style="margin:0;">
                    <label style="display:block; font-size:.8rem; margin-bottom:5px;">De</label>
                    <input type="date" name="from" class="form-control-vivensi" value="{{ $from ?? '' }}">
                </div>
                <div class="form-group" style="margin:0;">
                    <label style="display:block; font-size:.8rem; margin-bottom:5px;">Até</label>
                    <input type="date" name="to" class="form-control-vivensi" value="{{ $to ?? '' }}">
                </div>
                <div class="form-group" style="min-width: 220px; margin:0;">
                    <label style="display:block; font-size:.8rem; margin-bottom:5px;">Tipo</label>
                    <select name="type" class="form-control-vivensi">
                        <option value="">Todos</option>
                        @foreach(($types ?? []) as $t)
                            <option value="{{ $t }}" @if(($type ?? '')===$t) selected @endif>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="min-width: 240px; margin:0;">
                    <label style="display:block; font-size:.8rem; margin-bottom:5px;">Busca</label>
                    <input type="text" name="q" class="form-control-vivensi" value="{{ $q ?? '' }}" placeholder="Texto na descrição...">
                </div>
                <div style="display:flex; gap: 10px;">
                    <button class="btn-premium" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
                    <a class="btn-premium" style="background:#f1f5f9 !important; color:#0f172a !important;" href="{{ url('/ngo/beneficiaries/' . $beneficiary->id) }}">Limpar</a>
                </div>
            </form>
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
                    <textarea id="attendanceDescription" name="description" rows="3" class="form-control-vivensi" placeholder="Descreva o atendimento..." required></textarea>
                </div>
                <div style="text-align: right;">
                    <button type="button" onclick="closeAttendanceForm()" style="border: none; background: none; color: #64748b; margin-right: 15px; cursor: pointer;">Cancelar</button>
                    <button type="submit" class="btn-premium" style="display: inline-flex; width: auto; font-size: 0.9rem;">Salvar Registro</button>
                </div>
            </form>
        </div>

        <!-- Timeline -->
        <div class="timeline">
            @foreach($attendances as $attendance)
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
                    <div style="display:flex; justify-content: space-between; gap: 10px; align-items:center; margin-top: 6px; flex-wrap: wrap;">
                        <span style="font-size: 0.75rem; color: #94a3b8;">Registrado por: {{ $attendance->user->name ?? 'Sistema' }}</span>
                        <div style="display:flex; gap: 8px; align-items:center;">
                            <button type="button" class="btn-premium" style="font-size:.78rem; padding: 4px 10px; background:#f1f5f9 !important; color:#0f172a !important;" onclick="toggleAttendanceEdit({{ (int) $attendance->id }})">
                                <i class="fas fa-pen"></i> Editar
                            </button>
                            <form method="POST" action="{{ url('/ngo/beneficiaries/' . $beneficiary->id . '/attendance/' . $attendance->id) }}" onsubmit="return confirm('Remover este atendimento?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-premium" style="font-size:.78rem; padding: 4px 10px; background:#fee2e2 !important; color:#991b1b !important;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div id="attEdit{{ (int) $attendance->id }}" style="display:none; margin-top: 10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius: 10px; padding: 12px;">
                        <form method="POST" action="{{ url('/ngo/beneficiaries/' . $beneficiary->id . '/attendance/' . $attendance->id) }}">
                            @csrf
                            @method('PUT')
                            <div class="grid-2" style="gap: 12px; margin-bottom: 10px;">
                                <div>
                                    <label style="display:block; font-size:.8rem; margin-bottom:5px;">Data</label>
                                    <input type="date" name="date" class="form-control-vivensi" value="{{ optional($attendance->date)->format('Y-m-d') }}" required>
                                </div>
                                <div>
                                    <label style="display:block; font-size:.8rem; margin-bottom:5px;">Tipo</label>
                                    <input type="text" name="type" class="form-control-vivensi" value="{{ $attendance->type }}" required>
                                </div>
                            </div>
                            <div>
                                <label style="display:block; font-size:.8rem; margin-bottom:5px;">Descrição</label>
                                <textarea name="description" rows="3" class="form-control-vivensi" required>{{ $attendance->description }}</textarea>
                            </div>
                            <div style="text-align:right; margin-top: 10px;">
                                <button type="button" onclick="toggleAttendanceEdit({{ (int) $attendance->id }}, false)" style="border:none; background:none; color:#64748b; margin-right: 12px; cursor:pointer;">Cancelar</button>
                                <button type="submit" class="btn-premium" style="display:inline-flex; width:auto; font-size:.85rem; background:#111827;"><i class="fas fa-save"></i> Salvar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
            
            @if($attendances->count() == 0)
                <p style="text-align: center; color: #94a3b8; padding: 20px;">Nenhum atendimento registrado.</p>
            @endif
        </div>

        <div style="margin-top: 10px;">
            {{ $attendances->links() }}
        </div>
    </div>
</div>

<script>
    function toggleEditBeneficiary(force) {
        const view = document.getElementById('beneficiaryView');
        const edit = document.getElementById('beneficiaryEdit');
        if (!view || !edit) return;
        const showEdit = (typeof force === 'boolean') ? force : (edit.style.display === 'none' || edit.style.display === '');
        edit.style.display = showEdit ? 'block' : 'none';
        view.style.display = showEdit ? 'none' : 'block';
    }

    function toggleAttendanceEdit(id, force) {
        const el = document.getElementById('attEdit' + id);
        if (!el) return;
        const show = (typeof force === 'boolean') ? force : (el.style.display === 'none' || el.style.display === '');
        el.style.display = show ? 'block' : 'none';
    }

    function openAttendanceForm() {
        const box = document.getElementById('attendanceForm');
        if (!box) return;
        box.style.display = 'block';
        // Ensure user sees it (sometimes it's below the fold)
        try { box.scrollIntoView({ behavior: 'smooth', block: 'start' }); } catch (e) {}
        // Focus textarea for quick typing
        const t = document.getElementById('attendanceDescription');
        if (t) {
            try { t.focus(); } catch (e) {}
        }
    }

    function closeAttendanceForm() {
        const box = document.getElementById('attendanceForm');
        if (!box) return;
        box.style.display = 'none';
    }
</script>

@push('scripts')
<script>
const _termPdfUrl = '{{ url("/ngo/beneficiaries/".$beneficiary->id."/pdf") }}';
const _termPdfQuery = '{{ http_build_query(request()->query()) }}';
let _termPdfPollStatusUrl = null;
let _termPdfPollBtn = null;
let _termPdfPollOrig = null;

function pollTermPdf(attempts) {
    attempts = attempts || 0;
    if (attempts > 36) {
        alert('A geração do PDF demorou demais. Tente novamente.');
        if (_termPdfPollBtn) { _termPdfPollBtn.innerHTML = _termPdfPollOrig; _termPdfPollBtn.disabled = false; }
        return;
    }
    fetch(_termPdfPollStatusUrl).then(r => r.json()).then(function(data) {
        if (data.status === 'done' && data.download_url) {
            window.location.href = data.download_url;
            if (_termPdfPollBtn) { _termPdfPollBtn.innerHTML = _termPdfPollOrig; _termPdfPollBtn.disabled = false; }
        } else if (data.status === 'failed') {
            alert('Falha ao gerar o PDF. Tente novamente.');
            if (_termPdfPollBtn) { _termPdfPollBtn.innerHTML = _termPdfPollOrig; _termPdfPollBtn.disabled = false; }
        } else {
            setTimeout(function() { pollTermPdf(attempts + 1); }, 5000);
        }
    }).catch(function() {
        setTimeout(function() { pollTermPdf(attempts + 1); }, 8000);
    });
}

async function generateTermPdf(btn) {
    _termPdfPollOrig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando...';
    btn.disabled = true;
    _termPdfPollBtn = btn;
    const url = _termPdfUrl + (_termPdfQuery ? '?' + _termPdfQuery : '');
    try {
        const resp = await fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}});
        const data = await resp.json();
        if (data.status_url) {
            _termPdfPollStatusUrl = data.status_url;
            pollTermPdf(0);
        } else {
            alert('Erro ao iniciar geração do PDF.');
            btn.innerHTML = _termPdfPollOrig; btn.disabled = false;
        }
    } catch(e) {
        alert('Falha na comunicação com o servidor.');
        btn.innerHTML = _termPdfPollOrig; btn.disabled = false;
    }
}
</script>
@endpush
@endsection
