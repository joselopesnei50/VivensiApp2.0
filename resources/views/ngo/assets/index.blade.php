@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Patrimônio e Ativos</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Inventário de bens duráveis e localização.</p>
    </div>
    <button onclick="document.getElementById('assetModal').style.display='flex'" class="btn-premium">
        <i class="fas fa-plus"></i> Novo Item
    </button>
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
@endsection
