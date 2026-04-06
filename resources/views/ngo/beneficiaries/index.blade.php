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
        <a href="{{ url('/ngo/beneficiaries/print') . '?' . http_build_query(request()->query()) }}" class="btn-premium" style="background:#f1f5f9; color:#0f172a;">
            <i class="fas fa-print"></i> Imprimir
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

<div class="vivensi-card" style="margin-bottom: 14px;">
    <form method="GET" action="{{ url('/ngo/beneficiaries') }}" style="display:flex; gap: 10px; flex-wrap: wrap; align-items: end;">
        <div class="form-group" style="min-width: 260px; margin:0;">
            <label>Busca</label>
            <input class="form-control-vivensi" type="text" name="q" value="{{ $q ?? '' }}" placeholder="Nome, CPF, NIS, telefone...">
        </div>
        <div class="form-group" style="min-width: 220px; margin:0;">
            <label>Status</label>
            <select class="form-control-vivensi" name="status">
                <option value="">Todos</option>
                <option value="active" @if(($status ?? '')==='active') selected @endif>Ativo</option>
                <option value="inactive" @if(($status ?? '')==='inactive') selected @endif>Inativo</option>
                <option value="graduated" @if(($status ?? '')==='graduated') selected @endif>Graduado</option>
            </select>
        </div>
        <div style="display:flex; gap: 10px;">
            <button class="btn-premium" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
            <a class="btn-premium" style="background:#f1f5f9; color:#0f172a;" href="{{ url('/ngo/beneficiaries') }}">Limpar</a>
        </div>
    </form>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <tr>
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
                        <a href="{{ url('/ngo/beneficiaries/' . $beneficiary->id) }}" class="btn-premium" style="padding: 5px 10px; font-size: 0.8rem; background: #f1f5f9; color: #475569;">
                            <i class="fas fa-eye"></i>
                        </a>
                        <form method="POST" action="{{ url('/ngo/beneficiaries/' . $beneficiary->id) }}" onsubmit="return confirm('Remover este beneficiário e todo o histórico?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-premium" style="padding: 5px 10px; font-size: 0.8rem; background: #fee2e2; color: #991b1b;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
            @if($beneficiaries->count() === 0)
                <tr>
                    <td colspan="6" style="padding: 30px; text-align: center; color: #94a3b8;">
                        Nenhum beneficiário encontrado para os filtros selecionados.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
    <div style="padding: 20px;">
        {{ $beneficiaries->links() }}
    </div>
</div>
@endsection
