@extends('layouts.app')

@section('content')
<div style="max-width:1100px;margin:0 auto;padding:24px 16px;">

    <div style="margin-bottom:24px;">
        <a href="{{ route('admin.dashboard') }}" style="color:#6366f1;font-size:0.8rem;font-weight:700;text-decoration:none;">
            <i class="fas fa-arrow-left me-1"></i> Voltar ao Painel
        </a>
        <h2 style="margin:10px 0 4px;font-weight:950;font-size:1.6rem;letter-spacing:-1px;">
            <i class="fas fa-shield-check me-2" style="color:#10b981;"></i>Conformidade — Thresholds Globais
        </h2>
        <p style="color:#64748b;font-size:0.85rem;">Edite os limites mínimos dos requisitos marcados como editáveis. A alteração invalida o cache de todos os tenants ativos.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
    $eixoLabels = [
        'cebas_geral'     => ['label' => 'CEBAS Geral',           'color' => '#6366f1'],
        'cebas_as'        => ['label' => 'CEBAS Assist. Social',  'color' => '#8b5cf6'],
        'cebas_saude'     => ['label' => 'CEBAS Saúde',           'color' => '#ec4899'],
        'cebas_educacao'  => ['label' => 'CEBAS Educação',        'color' => '#f59e0b'],
        'mrosc'           => ['label' => 'MROSC',                 'color' => '#3b82f6'],
        'suas'            => ['label' => 'SUAS',                  'color' => '#10b981'],
        'sem_eixo'        => ['label' => 'Sem Eixo',              'color' => '#94a3b8'],
    ];
    @endphp

    @foreach($regras as $eixo => $grupo)
    @php $meta = $eixoLabels[$eixo] ?? ['label' => $eixo, 'color' => '#64748b']; @endphp
    <div style="margin-bottom:32px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
            <div style="width:4px;height:24px;background:{{ $meta['color'] }};border-radius:2px;"></div>
            <h5 style="margin:0;font-weight:800;color:#1e293b;">{{ $meta['label'] }}</h5>
            <span style="font-size:0.75rem;color:#94a3b8;">({{ $grupo->count() }} requisito{{ $grupo->count() !== 1 ? 's' : '' }})</span>
        </div>

        <div style="background:white;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;">
            <table style="width:100%;border-collapse:collapse;font-size:0.82rem;">
                <thead>
                    <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                        <th style="padding:10px 14px;text-align:left;font-weight:800;color:#475569;width:110px;">Código</th>
                        <th style="padding:10px 14px;text-align:left;font-weight:800;color:#475569;">Requisito</th>
                        <th style="padding:10px 14px;text-align:center;font-weight:800;color:#475569;width:80px;">Tipo</th>
                        <th style="padding:10px 14px;text-align:center;font-weight:800;color:#475569;width:180px;">Threshold</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grupo as $r)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:10px 14px;font-weight:700;color:#475569;font-family:monospace;">
                            {{ $r->requisito?->codigo ?? '—' }}
                        </td>
                        <td style="padding:10px 14px;color:#1e293b;">
                            {{ $r->requisito?->titulo ?? '—' }}
                            @if($r->unidade)
                                <span style="color:#94a3b8;font-size:0.75rem;"> · {{ $r->unidade }}</span>
                            @endif
                        </td>
                        <td style="padding:10px 14px;text-align:center;">
                            @php $tipo = $r->requisito?->tipo ?? '?'; @endphp
                            <span style="font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:6px;
                                background:{{ $tipo === 'A' ? '#e0e7ff' : ($tipo === 'B' ? '#fef3c7' : '#dcfce7') }};
                                color:{{ $tipo === 'A' ? '#3730a3' : ($tipo === 'B' ? '#92400e' : '#166534') }};">
                                Tipo {{ $tipo }}
                            </span>
                        </td>
                        <td style="padding:8px 14px;text-align:center;">
                            @if($r->threshold_editavel_admin && $r->threshold !== null)
                                <form action="{{ route('admin.conformidade.regra.update', $r->id) }}" method="POST"
                                      style="display:flex;align-items:center;gap:6px;justify-content:center;">
                                    @csrf @method('PUT')
                                    <input type="number" name="threshold" value="{{ (float) $r->threshold }}"
                                           min="0" max="100" step="0.1" required
                                           style="width:80px;padding:4px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:0.82rem;text-align:right;">
                                    <button type="submit" style="padding:4px 10px;background:#10b981;color:white;border:none;border-radius:6px;font-size:0.78rem;cursor:pointer;font-weight:700;">
                                        Salvar
                                    </button>
                                </form>
                            @elseif($r->threshold !== null)
                                <span style="color:#94a3b8;font-size:0.82rem;">
                                    {{ (float) $r->threshold }}
                                    <span style="font-size:0.7rem;">(fixo)</span>
                                </span>
                            @else
                                <span style="color:#cbd5e1;font-size:0.78rem;">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach

</div>
@endsection
