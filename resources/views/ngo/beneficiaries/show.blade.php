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
        <button type="button" class="btn-premium" style="background:#111827;" onclick="toggleEditBeneficiary()">
            <i class="fas fa-pen"></i> Editar
        </button>
        @php
            $bPhone = preg_replace('/\D+/', '', (string) ($beneficiary->phone ?? ''));
            $bMsg = "Olá! Entrando em contato referente ao seu acompanhamento. Beneficiário: " . ($beneficiary->name ?? '');
        @endphp
        @if(!empty($bPhone))
            <a target="_blank" rel="noopener" href="https://wa.me/{{ $bPhone }}?text={{ urlencode($bMsg) }}" class="btn-premium" style="background:#dcfce7; color:#166534;">
                <i class="fab fa-whatsapp"></i> WhatsApp
            </a>
        @endif
        <a href="{{ url('/ngo/beneficiaries/' . $beneficiary->id . '/pdf') . '?' . http_build_query(request()->query()) }}" class="btn-premium" style="background:#16a34a;">
            <i class="fas fa-file-pdf"></i> PDF Ficha
        </a>
        <a href="{{ url('/ngo/beneficiaries/' . $beneficiary->id . '/attendance/export') . '?' . http_build_query(request()->query()) }}" class="btn-premium" style="background:#4f46e5;">
            <i class="fas fa-file-csv"></i> CSV Atendimentos
        </a>
        <a href="{{ url('/ngo/beneficiaries/' . $beneficiary->id . '/attendance/print') . '?' . http_build_query(request()->query()) }}" class="btn-premium" style="background:#f1f5f9; color:#0f172a;">
            <i class="fas fa-print"></i> Imprimir
        </a>
        <a href="{{ url('/ngo/beneficiaries') }}" class="btn-premium" style="background: #f1f5f9; color: #475569;">Voltar</a>
        <form method="POST" action="{{ url('/ngo/beneficiaries/' . $beneficiary->id) }}" onsubmit="return confirm('Remover este beneficiário e todo o histórico?');" style="display:inline;">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-premium" style="background:#fee2e2; color:#991b1b;">
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
                <ul style="list-style: none; padding: 0; font-size: 0.9rem; color: #475569;">
                    <li style="margin-bottom: 8px;"><strong>CPF:</strong> {{ $beneficiary->cpf ?? '-' }}</li>
                    <li style="margin-bottom: 8px;"><strong>Nascimento:</strong> {{ $beneficiary->birth_date ? \Carbon\Carbon::parse($beneficiary->birth_date)->format('d/m/Y') : '-' }}</li>
                    <li style="margin-bottom: 8px;"><strong>Telefone:</strong> {{ $beneficiary->phone ?? '-' }}</li>
                    <li style="margin-bottom: 8px;"><strong>Status:</strong> {{ strtoupper($beneficiary->status ?? '—') }}</li>
                    <li><strong>Endereço:</strong> {{ $beneficiary->address ?? '-' }}</li>
                </ul>
            </div>

            <div id="beneficiaryEdit" style="display:none;">
                <form method="POST" action="{{ url('/ngo/beneficiaries/' . $beneficiary->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label class="form-label">Nome</label>
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
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control-vivensi" required>
                            <option value="active" @if(($beneficiary->status ?? '')==='active') selected @endif>Ativo</option>
                            <option value="inactive" @if(($beneficiary->status ?? '')==='inactive') selected @endif>Inativo</option>
                            <option value="graduated" @if(($beneficiary->status ?? '')==='graduated') selected @endif>Graduado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Endereço</label>
                        <input type="text" name="address" class="form-control-vivensi" value="{{ $beneficiary->address }}">
                    </div>
                    <div style="text-align:right; margin-top: 10px;">
                        <button type="button" class="btn-premium" style="background:#f1f5f9; color:#0f172a;" onclick="toggleEditBeneficiary(false)">Cancelar</button>
                        <button type="submit" class="btn-premium" style="background:#111827;"><i class="fas fa-save"></i> Salvar</button>
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
            <button onclick="document.getElementById('familyForm').style.display = 'block'" class="btn-premium" style="width: 100%; margin-top: 10px; font-size: 0.85rem; justify-content: center; background: #111827; color: #fff;">
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
                    <a class="btn-premium" style="background:#f1f5f9; color:#0f172a;" href="{{ url('/ngo/beneficiaries/' . $beneficiary->id) }}">Limpar</a>
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
                            <button type="button" class="btn-premium" style="font-size:.78rem; padding: 4px 10px; background:#f1f5f9; color:#0f172a;" onclick="toggleAttendanceEdit({{ (int) $attendance->id }})">
                                <i class="fas fa-pen"></i> Editar
                            </button>
                            <form method="POST" action="{{ url('/ngo/beneficiaries/' . $beneficiary->id . '/attendance/' . $attendance->id) }}" onsubmit="return confirm('Remover este atendimento?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-premium" style="font-size:.78rem; padding: 4px 10px; background:#fee2e2; color:#991b1b;">
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
@endsection
