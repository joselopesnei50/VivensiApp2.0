@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 18px; display:flex; justify-content: space-between; align-items:center; gap: 14px; flex-wrap: wrap;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Termo de Inventário Patrimonial</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Relatório para controle interno, auditoria e assinatura.</p>
    </div>
    <div style="display:flex; gap: 10px; flex-wrap: wrap;">
        <a href="{{ url('/ngo/assets') }}" class="btn-premium" style="background:#64748b;">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <button type="button" onclick="window.print()" class="btn-premium" style="background:#475569;">
            <i class="fas fa-print"></i> Imprimir
        </button>
    </div>
</div>

@php
    $generatedAt = now()->format('d/m/Y H:i');
@endphp

<div class="grid-2" style="margin-bottom: 14px;">
    <div class="vivensi-card">
        <div style="font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b;">Resumo</div>
        <div style="margin-top: 8px; display:flex; gap: 16px; flex-wrap: wrap; color:#0f172a; font-weight:900;">
            <div>Total itens: {{ number_format((int)($totals['count'] ?? 0)) }}</div>
            <div>Ativos: {{ number_format((int)($totals['active'] ?? 0)) }}</div>
            <div>Manutenção: {{ number_format((int)($totals['maintenance'] ?? 0)) }}</div>
            <div>Baixados: {{ number_format((int)($totals['disposed'] ?? 0)) }}</div>
        </div>
        <div style="margin-top: 10px; color:#475569;">
            <strong>Valor total:</strong> R$ {{ number_format((float)($totals['value'] ?? 0), 2, ',', '.') }}
        </div>
    </div>
    <div class="vivensi-card">
        <div style="font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b;">Identificação</div>
        <div style="margin-top: 8px; color:#0f172a; font-weight:900;">
            Organização: {{ auth()->user()->tenant_id == 1 ? 'INSTITUTO VIVENSI' : 'ORGANIZAÇÃO SOCIAL' }}
        </div>
        <div style="margin-top: 10px; color:#64748b;">
            Gerado em: <strong>{{ $generatedAt }}</strong>
        </div>
        <div style="margin-top: 8px; color:#64748b;">
            Responsável (emitente): <strong>{{ auth()->user()->name }}</strong>
        </div>
    </div>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <tr>
                <th style="padding: 12px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Código</th>
                <th style="padding: 12px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Bem</th>
                <th style="padding: 12px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Localização</th>
                <th style="padding: 12px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Responsável</th>
                <th style="padding: 12px; text-align: center; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Status</th>
                <th style="padding: 12px; text-align: right; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($assets as $a)
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 12px; font-weight: 800; color:#475569;">{{ $a->code ?? '-' }}</td>
                    <td style="padding: 12px;">
                        <div style="font-weight: 900; color:#0f172a;">{{ $a->name }}</div>
                        <div style="color:#94a3b8; font-size:.82rem;">
                            Aquisição: {{ $a->acquisition_date ? \Carbon\Carbon::parse($a->acquisition_date)->format('d/m/Y') : '—' }}
                        </div>
                    </td>
                    <td style="padding: 12px; color:#475569;">{{ $a->location ?? '-' }}</td>
                    <td style="padding: 12px; color:#475569;">{{ $a->responsible ?? '-' }}</td>
                    <td style="padding: 12px; text-align:center;">
                        @php
                            $map = [
                                'active' => ['ATIVO', '#dcfce7', '#16a34a'],
                                'maintenance' => ['MANUTENÇÃO', '#fef9c3', '#ca8a04'],
                                'disposed' => ['BAIXADO', '#fecaca', '#dc2626'],
                                'lost' => ['BAIXADO', '#fecaca', '#dc2626'],
                            ];
                            [$lbl, $bg, $fg] = $map[$a->status] ?? ['—', '#f1f5f9', '#64748b'];
                        @endphp
                        <span style="background: {{ $bg }}; color: {{ $fg }}; padding: 4px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 900; letter-spacing:.06em;">
                            {{ $lbl }}
                        </span>
                    </td>
                    <td style="padding: 12px; text-align:right; font-weight:900; color:#0f172a;">
                        R$ {{ number_format((float)$a->value, 2, ',', '.') }}
                    </td>
                </tr>
            @endforeach
            @if($assets->isEmpty())
                <tr>
                    <td colspan="6" style="padding: 40px; text-align:center; color:#94a3b8;">
                        Nenhum item encontrado.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

<div class="vivensi-card" style="margin-top: 18px;">
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 18px;">
        <div style="text-align:center; border-top: 1px solid #0f172a; padding-top: 10px;">
            Responsável pela conferência
        </div>
        <div style="text-align:center; border-top: 1px solid #0f172a; padding-top: 10px;">
            Direção / Conselho
        </div>
    </div>
    <div style="margin-top: 14px; color:#64748b; font-size:.85rem;">
        Observações:
        <div style="margin-top: 8px; height: 44px; border:1px dashed #cbd5e1; border-radius: 12px;"></div>
    </div>
</div>

<style>
@media print {
    .btn-premium, #sidebar, .header-main { display: none !important; }
    .main-content { margin: 0 !important; width: 100% !important; border: none; }
    .vivensi-card { box-shadow: none; border: none; }
    table { page-break-inside: auto; }
    tr { page-break-inside: avoid; page-break-after: auto; }
}
</style>
@endsection

