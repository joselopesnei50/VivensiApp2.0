@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Patrimônio e Ativos</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Inventário de bens duráveis e localização.</p>
    </div>
    <div style="display:flex; gap: 10px; flex-wrap: wrap;">
        <a class="btn-premium" href="{{ url('/ngo/assets/export?'.http_build_query(request()->query())) }}" style="background:#4f46e5;">
            <i class="fas fa-file-csv"></i> Exportar CSV
        </a>
        <a class="btn-premium" href="{{ url('/ngo/assets/term?'.http_build_query(request()->query())) }}" style="background:#0ea5e9;">
            <i class="fas fa-file-signature"></i> Termo de Inventário
        </a>
        <a class="btn-premium" href="{{ url('/ngo/assets/term/pdf?'.http_build_query(request()->query())) }}" style="background:#0284c7;">
            <i class="fas fa-file-pdf"></i> PDF Inventário
        </a>
        <button type="button" onclick="window.print()" class="btn-premium" style="background:#475569;">
            <i class="fas fa-print"></i> Imprimir
        </button>
        <button onclick="document.getElementById('assetModal').style.display='flex'" class="btn-premium">
            <i class="fas fa-plus"></i> Novo Item
        </button>
    </div>
</div>

@php
    $totalValue = (float) ($stats['total_value'] ?? 0);
    $activeCount = (int) ($stats['active_count'] ?? 0);
    $maintCount = (int) ($stats['maintenance_count'] ?? 0);
    $disposedCount = (int) (($stats['disposed_count'] ?? 0) + ($stats['lost_count'] ?? 0));
@endphp

<div class="grid-2" style="margin-bottom: 18px;">
    <div class="vivensi-card" style="border-left: 5px solid #4f46e5;">
        <p style="text-transform: uppercase; font-size: 0.75rem; color: #64748b; font-weight: 800;">Valor Total do Patrimônio</p>
        <h3 style="margin: 10px 0; font-size: 1.9rem;">R$ {{ number_format($totalValue, 2, ',', '.') }}</h3>
        <p style="font-size: 0.9rem; color: #475569; margin:0;">Base: todos os itens cadastrados</p>
    </div>
    <div class="vivensi-card" style="border-left: 5px solid #16a34a;">
        <p style="text-transform: uppercase; font-size: 0.75rem; color: #64748b; font-weight: 800;">Status (itens)</p>
        <h3 style="margin: 10px 0; font-size: 1.9rem;">{{ number_format($activeCount) }} <span style="color:#64748b; font-size: 1rem; font-weight:800;">ativos</span></h3>
        <p style="font-size: 0.9rem; margin:0;">
            <span style="color:#ca8a04; font-weight:900;">{{ number_format($maintCount) }} em manutenção</span>
            <span style="color:#94a3b8;"> · </span>
            <span style="color:#dc2626; font-weight:900;">{{ number_format($disposedCount) }} baixados</span>
        </p>
    </div>
</div>

<div class="vivensi-card" style="margin-bottom: 14px;">
    <form method="GET" action="{{ url('/ngo/assets') }}" style="display:flex; gap: 10px; flex-wrap: wrap; align-items:end;">
        <div style="flex: 1; min-width: 220px;">
            <label style="display:block; font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom: 6px;">Busca</label>
            <input type="text" name="q" value="{{ request('q') }}" class="form-control-vivensi" placeholder="Nome, código, local, responsável...">
        </div>
        <div style="min-width: 180px;">
            <label style="display:block; font-size:.75rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom: 6px;">Status</label>
            <select name="status" class="form-control-vivensi">
                <option value="">Todos</option>
                <option value="active" {{ request('status')==='active'?'selected':'' }}>Ativo</option>
                <option value="maintenance" {{ request('status')==='maintenance'?'selected':'' }}>Manutenção</option>
                <option value="disposed" {{ request('status')==='disposed'?'selected':'' }}>Baixado/Doado</option>
                <option value="lost" {{ request('status')==='lost'?'selected':'' }}>Extraviado/Roubado</option>
            </select>
        </div>
        <div style="display:flex; gap: 10px;">
            <button type="submit" class="btn-premium" style="justify-content:center;">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            <a href="{{ url('/ngo/assets') }}" class="btn-premium" style="background:#f1f5f9; color:#0f172a; border:1px solid #e2e8f0;">
                Limpar
            </a>
        </div>
    </form>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <tr>
                <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Plaqueta / Código</th>
                <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Descrição do Bem</th>
                <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Localização</th>
                <th style="padding: 15px; text-align: right; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Valor (R$)</th>
                <th style="padding: 15px; text-align: center; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Status</th>
                <th style="padding: 15px; text-align: center; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach($assets as $asset)
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 15px; font-weight: 600; color: #475569;">
                    {{ $asset->code ?? '-' }}
                </td>
                <td style="padding: 15px;">
                    <strong style="display: block; color: #1e293b;">{{ $asset->name }}</strong>
                    <span style="font-size: 0.8rem; color: #64748b;">Resp: {{ $asset->responsible ?? 'N/A' }}</span>
                </td>
                <td style="padding: 15px; color: #475569;">
                    {{ $asset->location ?? '-' }}
                </td>
                <td style="padding: 15px; text-align: right; font-weight: 600; color: #334155;">
                    R$ {{ number_format($asset->value, 2, ',', '.') }}
                </td>
                <td style="padding: 15px; text-align: center;">
                    @if($asset->status == 'active')
                        <span style="background: #dcfce7; color: #16a34a; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem;">ATIVO</span>
                    @elseif($asset->status == 'maintenance')
                        <span style="background: #fef9c3; color: #ca8a04; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem;">MANUTENÇÃO</span>
                    @else
                        <span style="background: #fecaca; color: #dc2626; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem;">BAIXADO</span>
                    @endif
                </td>
                <td style="padding: 15px; text-align: center;">
                    <form action="{{ url('/ngo/assets/'.$asset->id) }}" method="POST" onsubmit="return confirm('Excluir este item?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer;"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            @endforeach
            @if($assets->isEmpty())
                <tr>
                    <td colspan="6" style="padding: 40px; text-align: center; color: #94a3b8;">
                        Nenhum item encontrado com os filtros atuais.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
    <div style="padding: 20px;">
        {{ $assets->links() }}
    </div>
</div>

<!-- Modal -->
<div id="assetModal" class="custom-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div class="vivensi-card" style="width: 90%; max-width: 600px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>Registrar Novo Bem</h3>
            <button onclick="document.getElementById('assetModal').style.display='none'" style="border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form action="{{ url('/ngo/assets') }}" method="POST">
            @csrf
            
            <div class="grid-2" style="gap: 15px;">
                <div class="form-group"><label>Nome do Bem</label><input type="text" name="name" class="form-control-vivensi" required></div>
                <div class="form-group"><label>Cód. Patrimônio</label><input type="text" name="code" class="form-control-vivensi"></div>
            </div>

            <div class="grid-2" style="gap: 15px;">
                <div class="form-group"><label>Data Aquisição</label><input type="date" name="acquisition_date" class="form-control-vivensi" required></div>
                <div class="form-group"><label>Valor de Compra (R$)</label><input type="text" name="value" class="form-control-vivensi" placeholder="0,00" required></div>
            </div>

            <div class="grid-2" style="gap: 15px;">
                <div class="form-group"><label>Localização</label><input type="text" name="location" class="form-control-vivensi" placeholder="Ex: Sala 01"></div>
                <div class="form-group"><label>Responsável</label><input type="text" name="responsible" class="form-control-vivensi"></div>
            </div>

            <div class="form-group">
                <label>Status Atual</label>
                <select name="status" class="form-control-vivensi">
                    <option value="active">Ativo / Em uso</option>
                    <option value="maintenance">Em Manutenção</option>
                    <option value="disposed">Descartado / Doado</option>
                    <option value="lost">Extraviado / Roubado</option>
                </select>
            </div>
            
            <button type="submit" class="btn-premium" style="width: 100%; justify-content: center; margin-top: 10px;">Salvar Item</button>
        </form>
    </div>
</div>

<style>
@media print {
    .btn-premium, form, #sidebar, .header-main { display: none !important; }
    .main-content { margin: 0 !important; width: 100% !important; border: none; }
    .vivensi-card { box-shadow: none; border: none; }
}
</style>
@endsection
