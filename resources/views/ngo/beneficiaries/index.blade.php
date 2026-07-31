@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Beneficiários</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Gestão de famílias atendidas e histórico de evolução.</p>
    </div>
    <div style="display:flex; gap: 10px; flex-wrap: wrap;">
        <a href="{{ url('/ngo/beneficiaries/insights') }}" class="btn-premium" style="background:#111827;">
            <i class="fas fa-chart-line"></i> Indicadores
        </a>
        <a href="{{ url('/ngo/beneficiaries/reports/annual') }}" class="btn-premium" style="background:#16a34a;">
            <i class="fas fa-file-alt"></i> Relatório anual
        </a>
        <a href="{{ url('/ngo/beneficiaries/attendances/export') . '?' . http_build_query(request()->query()) }}" class="btn-premium" style="background:#0ea5e9;">
            <i class="fas fa-file-csv"></i> CSV Atendimentos (lote)
        </a>
        <a href="{{ url('/ngo/beneficiaries/export') . '?' . http_build_query(request()->query()) }}" class="btn-premium" style="background:#4f46e5;">
            <i class="fas fa-file-csv"></i> Exportar CSV
        </a>
        <a href="{{ url('/ngo/beneficiaries/print') . '?' . http_build_query(request()->query()) }}" class="btn-premium" style="background:#f1f5f9 !important; color:#0f172a !important;">
            <i class="fas fa-print"></i> Imprimir
        </a>
        <a href="{{ url('/ngo/beneficiaries/create') }}#importar" class="btn-premium" style="background:#0f766e;">
            <i class="fas fa-file-import"></i> Importar Planilha
        </a>
        <a href="{{ url('/ngo/beneficiaries/create') }}" class="btn-premium">
            <i class="fas fa-plus"></i> Novo Beneficiário
        </a>
    </div>
</div>

@php
    $total = (int) ($stats['total'] ?? 0);
    $active = (int) ($stats['active'] ?? 0);
    $inactive = (int) ($stats['inactive'] ?? 0);
    $graduated = (int) ($stats['graduated'] ?? 0);
    $monthAttendances = (int) ($stats['monthAttendances'] ?? 0);
@endphp

<div class="grid-2" style="margin-bottom: 18px; grid-template-columns: 1fr 1fr; gap: 16px;">
    <div class="vivensi-card" style="border-left: 5px solid #4f46e5;">
        <p style="text-transform: uppercase; font-size: 0.75rem; color: #64748b; font-weight: 800;">Famílias / Beneficiários</p>
        <h3 style="margin: 10px 0; font-size: 1.9rem;">{{ number_format($total) }}</h3>
        <p style="font-size: 0.9rem; color: #475569; margin:0;">
            Ativos: <strong>{{ number_format($active) }}</strong> · Inativos: <strong>{{ number_format($inactive) }}</strong> · Graduados: <strong>{{ number_format($graduated) }}</strong>
        </p>
    </div>
    <div class="vivensi-card" style="border-left: 5px solid #16a34a;">
        <p style="text-transform: uppercase; font-size: 0.75rem; color: #64748b; font-weight: 800;">Atendimentos (mês atual)</p>
        <h3 style="margin: 10px 0; font-size: 1.9rem;">{{ number_format($monthAttendances) }}</h3>
        <p style="font-size: 0.9rem; color: #64748b; margin:0;">Indicador operacional para acompanhamento.</p>
    </div>
</div>

@php
    $f = $filters ?? ['q'=>$q ?? '', 'status'=>$status ?? '', 'gender'=>'', 'education'=>'', 'age_bracket'=>'', 'project_id'=>'', 'novo_dias'=>''];
    $fo = $filterOptions ?? ['genders'=>[], 'educations'=>[], 'ageBrackets'=>[], 'novoDias'=>[], 'projects'=>collect()];
@endphp

<div class="vivensi-card" style="margin-bottom: 14px;">
    <form method="GET" action="{{ url('/ngo/beneficiaries') }}" style="display:flex; gap: 10px; flex-wrap: wrap; align-items: end;">
        <div class="form-group" style="min-width: 260px; margin:0; flex:1;">
            <label>Busca</label>
            <input class="form-control-vivensi" type="text" name="q" value="{{ $f['q'] }}" placeholder="Nome, CPF, NIS, telefone...">
        </div>
        <div class="form-group" style="min-width: 160px; margin:0;">
            <label>Status</label>
            <select class="form-control-vivensi" name="status">
                <option value="">Todos</option>
                <option value="active" @if($f['status']==='active') selected @endif>Ativo</option>
                <option value="inactive" @if($f['status']==='inactive') selected @endif>Inativo</option>
                <option value="graduated" @if($f['status']==='graduated') selected @endif>Graduado</option>
            </select>
        </div>
        <div class="form-group" style="min-width: 200px; margin:0;">
            <label>Faixa etária</label>
            <select class="form-control-vivensi" name="age_bracket">
                <option value="">Todas as idades</option>
                @foreach($fo['ageBrackets'] as $key => $label)
                    <option value="{{ $key }}" @if($f['age_bracket']===$key) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="min-width: 180px; margin:0;">
            <label>Gênero</label>
            <select class="form-control-vivensi" name="gender">
                <option value="">Todos</option>
                @foreach($fo['genders'] as $key => $label)
                    <option value="{{ $key }}" @if($f['gender']===$key) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="min-width: 220px; margin:0;">
            <label>Escolaridade</label>
            <select class="form-control-vivensi" name="education">
                <option value="">Todas</option>
                @foreach($fo['educations'] as $key => $label)
                    <option value="{{ $key }}" @if($f['education']===$key) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="min-width: 220px; margin:0;">
            <label>Projeto vinculado</label>
            <select class="form-control-vivensi" name="project_id">
                <option value="">Todos os projetos</option>
                @foreach($fo['projects'] as $p)
                    <option value="{{ $p->id }}" @if((string) $f['project_id'] === (string) $p->id) selected @endif>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="min-width: 210px; margin:0;">
            <label>Cadastro</label>
            <select class="form-control-vivensi" name="novo_dias">
                <option value="">Qualquer época</option>
                @foreach($fo['novoDias'] as $key => $label)
                    <option value="{{ $key }}" @if((string) $f['novo_dias'] === (string) $key) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex; gap: 10px;">
            <button class="btn-premium" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
            <a class="btn-premium" style="background:#f1f5f9 !important; color:#0f172a !important;" href="{{ url('/ngo/beneficiaries') }}">Limpar</a>
        </div>
    </form>
</div>

<div id="bulk-bar" style="display:none; margin-bottom: 12px; padding: 14px 18px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px;">
    <div style="display:flex; justify-content: space-between; align-items: center; gap: 15px; flex-wrap: wrap;">
        <div style="color:#78350f; font-weight:700;">
            <i class="fas fa-check-square"></i>
            <strong id="bulk-count">0</strong> beneficiário(s) selecionado(s)
            <span style="color:#a16207; font-weight:500; font-size:.85rem;">— seleção reinicia ao mudar de página</span>
        </div>
        <form method="POST" action="{{ url('/ngo/beneficiaries/bulk') }}" id="bulk-form" style="display:flex; gap:8px; flex-wrap:wrap; margin:0;">
            @csrf
            <input type="hidden" name="action" id="bulk-action">
            <input type="hidden" name="value"  id="bulk-value">
            <div id="bulk-ids-container"></div>
            <button type="button" onclick="bulkStatus('active','Ativo')"       class="btn-premium" style="background:#16a34a;">Marcar Ativo</button>
            <button type="button" onclick="bulkStatus('inactive','Inativo')"   class="btn-premium" style="background:#94a3b8;">Marcar Inativo</button>
            <button type="button" onclick="bulkStatus('graduated','Graduado')" class="btn-premium" style="background:#2563eb;">Marcar Graduado</button>
            @can('delete-beneficiaries')
            <button type="button" onclick="bulkDelete()"                       class="btn-premium" style="background:#dc2626;">Remover</button>
            @endcan
        </form>
    </div>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <tr>
                <th style="padding: 15px; text-align: center; width: 40px;">
                    <input type="checkbox" id="bulk-check-all" title="Selecionar todos desta página">
                </th>
                <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Nome</th>
                <th style="padding: 15px; text-align: center; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">NIS / CPF</th>
                <th style="padding: 15px; text-align: center; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Idade</th>
                <th style="padding: 15px; text-align: center; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Atendimentos</th>
                <th style="padding: 15px; text-align: center; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Status</th>
                <th style="padding: 15px; text-align: center; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach($beneficiaries as $beneficiary)
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 15px; text-align: center;">
                    <input type="checkbox" class="bulk-check" value="{{ $beneficiary->id }}" aria-label="Selecionar {{ $beneficiary->name }}">
                </td>
                <td style="padding: 15px;">
                    <a href="{{ url('/ngo/beneficiaries/' . $beneficiary->id) }}" style="color: #4f46e5; font-weight: 600; text-decoration: none;">
                        {{ $beneficiary->name }}
                    </a>
                </td>
                <td style="padding: 15px; text-align: center; color: #64748b; font-size: 0.9rem;">
                    {{ $beneficiary->nis ?? $beneficiary->cpf ?? '-' }}
                </td>
                <td style="padding: 15px; text-align: center; color: #64748b;">
                    {{ $beneficiary->birth_date ? \Carbon\Carbon::parse($beneficiary->birth_date)->age . ' anos' : '-' }}
                </td>
                <td style="padding: 15px; text-align: center;">
                    <span style="background: #e0e7ff; color: #4338ca; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: 700;">
                        {{ $beneficiary->attendances_count }}
                    </span>
                </td>
                <td style="padding: 15px; text-align: center;">
                    @if($beneficiary->status == 'active')
                        <span style="color: #16a34a; background: #dcfce7; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem;">ATIVO</span>
                    @elseif($beneficiary->status == 'graduated')
                        <span style="color: #2563eb; background: #dbeafe; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem;">GRADUADO</span>
                    @else
                        <span style="color: #94a3b8; background: #f1f5f9; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem;">INATIVO</span>
                    @endif
                </td>
                <td style="padding: 15px; text-align: center;">
                    <div style="display:flex; gap: 8px; justify-content:center; align-items:center;">
                        <a href="{{ url('/ngo/beneficiaries/' . $beneficiary->id) }}" class="btn-premium" title="Ver detalhes" aria-label="Ver detalhes do beneficiário" style="padding: 6px 10px !important; font-size: 0.85rem !important; background: #475569 !important; color: #ffffff !important; box-shadow: none !important;">
                            <i class="fas fa-eye"></i>
                        </a>
                        @can('delete-beneficiaries')
                        <form method="POST" action="{{ url('/ngo/beneficiaries/' . $beneficiary->id) }}" onsubmit="return confirm('Remover este beneficiário e todo o histórico?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-premium" title="Excluir" aria-label="Excluir beneficiário" style="padding: 6px 10px !important; font-size: 0.85rem !important; background: #dc2626 !important; color: #ffffff !important; box-shadow: none !important;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @endcan
                    </div>
                </td>
            </tr>
            @endforeach
            @if($beneficiaries->count() === 0)
                <tr>
                    <td colspan="6" style="padding: 0; border: none;">
                        <x-empty-state
                            icon="fa-people-roof"
                            title="Nenhum beneficiário encontrado"
                            description="Ajuste os filtros ou cadastre novos beneficiários para começar a registrar atendimentos."
                            action_label="Cadastrar Beneficiário"
                            action_url="{{ url('/ngo/beneficiaries/create') }}"
                        />
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
    <div style="padding: 20px;">
        {{ $beneficiaries->links() }}
    </div>
</div>

<script>
(function () {
    function selectedIds() {
        return Array.from(document.querySelectorAll('.bulk-check:checked')).map(function (c) { return c.value; });
    }
    function fillIds() {
        var box = document.getElementById('bulk-ids-container');
        box.innerHTML = '';
        selectedIds().forEach(function (id) {
            var el = document.createElement('input');
            el.type = 'hidden'; el.name = 'ids[]'; el.value = id;
            box.appendChild(el);
        });
    }
    function refreshBar() {
        var n = selectedIds().length;
        document.getElementById('bulk-count').textContent = n;
        document.getElementById('bulk-bar').style.display = n > 0 ? 'block' : 'none';
    }
    window.bulkStatus = function (value, label) {
        var n = selectedIds().length;
        if (!n) return;
        if (!confirm('Marcar ' + n + ' beneficiário(s) como "' + label + '"?')) return;
        document.getElementById('bulk-action').value = 'status';
        document.getElementById('bulk-value').value = value;
        fillIds();
        document.getElementById('bulk-form').submit();
    };
    window.bulkDelete = function () {
        var n = selectedIds().length;
        if (!n) return;
        if (!confirm('Remover ' + n + ' beneficiário(s) e todo o histórico?')) return;
        if (!confirm('Esta ação NÃO pode ser desfeita. Confirma?')) return;
        document.getElementById('bulk-action').value = 'delete';
        document.getElementById('bulk-value').value = '';
        fillIds();
        document.getElementById('bulk-form').submit();
    };
    document.addEventListener('change', function (e) {
        if (e.target.id === 'bulk-check-all') {
            document.querySelectorAll('.bulk-check').forEach(function (c) { c.checked = e.target.checked; });
            refreshBar();
        } else if (e.target.classList.contains('bulk-check')) {
            refreshBar();
        }
    });
})();
</script>
@endsection
