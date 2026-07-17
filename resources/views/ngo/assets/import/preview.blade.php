@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Preview do Import</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">
            {{ $summary['valid'] }} linhas válidas · {{ $summary['errors'] }} com erro · {{ $summary['total'] }} no total
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="{{ route('assets.import.form') }}" class="btn-premium" style="background:#64748b;">
            <i class="fas fa-times"></i> Cancelar
        </a>
        @if($summary['valid'] > 0)
        <form action="{{ route('assets.import.confirm') }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="btn-premium" style="background:#10b981;">
                <i class="fas fa-check"></i> Confirmar {{ $summary['valid'] }} linhas
            </button>
        </form>
        @endif
    </div>
</div>

@if(!empty($parseErrors))
    <div class="alert alert-warning" style="margin-bottom: 20px;">
        <strong>Erros no CSV:</strong>
        <ul style="margin: 5px 0 0 0;">
            @foreach($parseErrors as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="vivensi-card" style="padding: 0; overflow: auto;">
    <table style="width: 100%; border-collapse: collapse; min-width: 900px;">
        <thead style="background: #f8fafc;">
            <tr>
                <th style="padding: 12px; text-align: center; font-size: 0.75rem; color: #64748b; text-transform: uppercase;">#</th>
                <th style="padding: 12px; text-align: left; font-size: 0.75rem; color: #64748b; text-transform: uppercase;">Nome</th>
                <th style="padding: 12px; text-align: left; font-size: 0.75rem; color: #64748b; text-transform: uppercase;">Código</th>
                <th style="padding: 12px; text-align: left; font-size: 0.75rem; color: #64748b; text-transform: uppercase;">Data</th>
                <th style="padding: 12px; text-align: right; font-size: 0.75rem; color: #64748b; text-transform: uppercase;">Valor</th>
                <th style="padding: 12px; text-align: left; font-size: 0.75rem; color: #64748b; text-transform: uppercase;">Status</th>
                <th style="padding: 12px; text-align: left; font-size: 0.75rem; color: #64748b; text-transform: uppercase;">Erro?</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr style="border-bottom: 1px solid #f1f5f9; {{ $row['_error'] ? 'background: #fef2f2;' : '' }}">
                <td style="padding: 10px; text-align: center; color: #94a3b8; font-size: 0.85rem;">{{ $row['_line'] }}</td>
                <td style="padding: 10px; color: #334155;">{{ $row['nome'] ?? '—' }}</td>
                <td style="padding: 10px; color: #64748b;">{{ $row['codigo'] ?: '—' }}</td>
                <td style="padding: 10px; color: #64748b;">{{ $row['data_aquisicao'] ?? '—' }}</td>
                <td style="padding: 10px; text-align: right; color: #334155;">
                    @if(!empty($row['valor']) && is_numeric($row['valor']))
                        R$ {{ number_format($row['valor'], 2, ',', '.') }}
                    @else
                        —
                    @endif
                </td>
                <td style="padding: 10px; color: #64748b;">{{ $row['status'] ?? '—' }}</td>
                <td style="padding: 10px; color: #dc2626; font-size: 0.85rem;">{{ $row['_error'] ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
