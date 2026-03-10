@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Almoxarifado & Estoque</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Controle de doações físicas, cestas básicas e materiais.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <button onclick="openModal('itemModal')" class="btn-premium">
            <i class="fas fa-plus"></i> Novo Item
        </button>
    </div>
</div>

<!-- Tabs -->
<div style="margin-bottom: 20px; border-bottom: 1px solid #e2e8f0;">
    <button class="tab-btn active" onclick="showTab('stock')" id="btn-stock" style="padding: 10px 20px; border: none; background: none; font-weight: 600; color: #4f46e5; border-bottom: 2px solid #4f46e5; cursor: pointer;">Estoque Atual</button>
    <button class="tab-btn" onclick="showTab('history')" id="btn-history" style="padding: 10px 20px; border: none; background: none; font-weight: 600; color: #64748b; cursor: pointer;">Histórico de Movimentações</button>
</div>

<!-- Stock Section -->
<div id="tab-stock" class="tab-content">
    <div class="grid-3">
        @foreach($items as $item)
        <div class="vivensi-card" style="position: relative; border-top: 4px solid {{ $item->quantity <= $item->minimum_stock ? '#ef4444' : '#10b981' }};">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                <div>
                    <h4 style="margin: 0; font-size: 1.1rem; color: #1e293b;">{{ $item->name }}</h4>
                    <span style="font-size: 0.8rem; color: #64748b;">SKU: {{ $item->sku ?? 'N/A' }}</span>
                </div>
                <div style="text-align: right;">
                    <strong style="font-size: 1.5rem; color: {{ $item->quantity <= $item->minimum_stock ? '#ef4444' : '#1e293b' }};">{{ number_format($item->quantity, 2, ',', '.') }}</strong>
                    <span style="font-size: 0.8rem; color: #64748b; text-transform: lowercase;">{{ $item->unit }}</span>
                </div>
            </div>
            
            <p style="font-size: 0.85rem; color: #475569; margin-bottom: 15px; min-height: 40px;">
                {{ Str::limit($item->description ?? 'Sem descrição', 80) }}
            </p>

            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 15px;">
                <div style="font-size: 0.75rem; color: #94a3b8;">
                    Mínimo: {{ $item->minimum_stock }} {{ $item->unit }}
                </div>
                <div style="display: flex; gap: 5px;">
                    <button class="btn-premium" style="padding: 5px 10px; font-size: 0.8rem; background: #ef4444;" onclick="openMovementModal({{ $item->id }}, 'out', '{{ $item->name }}')">
                        <i class="fas fa-minus"></i> Saída
                    </button>
                    <button class="btn-premium" style="padding: 5px 10px; font-size: 0.8rem; background: #10b981;" onclick="openMovementModal({{ $item->id }}, 'in', '{{ $item->name }}')">
                        <i class="fas fa-plus"></i> Entrada
                    </button>
                    <form action="{{ url('/ngo/inventory/'.$item->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir o item?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-premium" style="padding: 5px 10px; font-size: 0.8rem; background: #94a3b8;" title="Excluir Item">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @if(count($items) == 0)
        <div style="text-align: center; padding: 40px; color: #94a3b8;">Nenhum item cadastrado no almoxarifado.</div>
    @endif
</div>

<!-- History Section -->
<div id="tab-history" class="tab-content" style="display: none;">
    <div class="vivensi-card" style="padding: 0; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                <tr>
                    <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Data</th>
                    <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Tipo</th>
                    <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Item</th>
                    <th style="padding: 15px; text-align: right; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Qtd</th>
                    <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Motivo/Destino</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentMovements as $mov)
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 15px; font-size: 0.9rem; color: #475569;">
                        {{ $mov->date->format('d/m/Y') }}
                    </td>
                    <td style="padding: 15px;">
                        @if($mov->type == 'in')
                            <span style="background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem;"><i class="fas fa-arrow-down"></i> ENTRADA</span>
                        @else
                            <span style="background: #fee2e2; color: #991b1b; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem;"><i class="fas fa-arrow-up"></i> SAÍDA</span>
                        @endif
                    </td>
                    <td style="padding: 15px; font-weight: 600; color: #334155;">
                        {{ optional($mov->item)->name ?? 'Item Removido' }}
                    </td>
                    <td style="padding: 15px; text-align: right; font-weight: 600; color: #334155;">
                        {{ number_format($mov->quantity, 2, ',', '.') }} {{ optional($mov->item)->unit }}
                    </td>
                    <td style="padding: 15px; font-size: 0.85rem; color: #64748b;">
                        {{ $mov->description }}
                        @if($mov->beneficiary_id)
                            <div style="color: #4f46e5;"><i class="fas fa-user"></i> {{ optional($mov->beneficiary)->name }}</div>
                        @endif
                        @if($mov->project_id)
                            <div style="color: #0ea5e9;"><i class="fas fa-diagram-project"></i> {{ optional($mov->project)->name }}</div>
                        @endif
                    </td>
                </tr>
                @endforeach
                @if(count($recentMovements) == 0)
                <tr><td colspan="5" style="padding: 30px; text-align: center; color: #94a3b8;">Nenhuma movimentação recente registrada.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Novo Item -->
<div id="itemModal" class="custom-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000; overflow-y: auto;">
    <div class="vivensi-card" style="width: 95%; max-width: 500px; margin: 40px auto; position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>Novo Item de Estoque</h3>
            <button onclick="closeModal('itemModal')" style="border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form action="{{ url('/ngo/inventory') }}" method="POST">
            @csrf
            <div class="form-group"><label>Nome do Item</label><input type="text" name="name" class="form-control-vivensi" required placeholder="Ex: Cesta Básica, Cobertor"></div>
            <div class="form-group"><label>Descrição / Especificação</label><input type="text" name="description" class="form-control-vivensi"></div>
            
            <div class="grid-2" style="gap: 15px;">
                <div class="form-group"><label>SKU (Código)</label><input type="text" name="sku" class="form-control-vivensi"></div>
                <div class="form-group"><label>Unidade de Medida</label>
                    <select name="unit" class="form-control-vivensi" required>
                        <option value="Unidade">Unidade</option>
                        <option value="Caixa">Caixa</option>
                        <option value="Kg">Quilo (Kg)</option>
                        <option value="Litro">Litro (L)</option>
                        <option value="Pacote">Pacote</option>
                    </select>
                </div>
            </div>
            <div class="grid-2" style="gap: 15px;">
                <div class="form-group"><label>Estoque Mínimo de Alerta</label><input type="number" step="0.01" name="minimum_stock" class="form-control-vivensi" value="0" required></div>
                <div class="form-group"><label>Valor Estimado (R$ / opcional)</label><input type="number" step="0.01" name="value_per_unit" class="form-control-vivensi"></div>
            </div>
            
            <button type="submit" class="btn-premium" style="width: 100%; justify-content: center;">Salvar Item</button>
        </form>
    </div>
</div>

<!-- Modal Movimentação -->
<div id="movementModal" class="custom-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000; overflow-y: auto;">
    <div class="vivensi-card" style="width: 95%; max-width: 500px; margin: 40px auto; position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 id="movTitle">Movimentar Estoque</h3>
            <button onclick="closeModal('movementModal')" style="border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form id="movementForm" method="POST" action="">
            @csrf
            <input type="hidden" name="type" id="movType">
            
            <div style="background: #f8fafc; padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center;">
                <strong id="movItemName" style="font-size: 1.1rem; color: #1e293b;">Item</strong>
            </div>

            <div class="grid-2" style="gap: 15px;">
                <div class="form-group">
                    <label>Quantidade</label>
                    <input type="number" step="0.01" name="quantity" class="form-control-vivensi" required min="0.01">
                </div>
                <div class="form-group">
                    <label>Data</label>
                    <input type="date" name="date" class="form-control-vivensi" required value="{{ date('Y-m-d') }}">
                </div>
            </div>

            <div class="form-group">
                <label>Descrição / Origem / Motivo</label>
                <input type="text" name="description" class="form-control-vivensi" placeholder="Ex: Doação Recebida da Empresa X, Entrega para a Família Y...">
            </div>

            <div id="outFields" style="display:none;">
                <div class="form-group">
                    <label>Vincular a Beneficiário (Opcional)</label>
                    <select name="beneficiary_id" class="form-control-vivensi">
                        <option value="">— Selecione —</option>
                        @php $bens = \App\Models\Beneficiary::where('tenant_id', auth()->user()->tenant_id)->get(); @endphp
                        @foreach($bens as $b)
                            <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->document ?? 'S/ CPF' }})</option>
                        @endforeach
                    </select>
                </div>
                <!-- Vínculo com projeto (opcional) -->
                <div class="form-group">
                    <label>Vincular a Projeto (Opcional)</label>
                    <select name="project_id" class="form-control-vivensi">
                        <option value="">— Selecione —</option>
                        @php $projs = \App\Models\Project::where('tenant_id', auth()->user()->tenant_id)->get(); @endphp
                        @foreach($projs as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <button type="submit" id="movSubmitBtn" class="btn-premium" style="width: 100%; justify-content: center;">Confirmar Movimentação</button>
        </form>
    </div>
</div>

<script>
    function showTab(tabName) {
        document.getElementById('tab-stock').style.display = 'none';
        document.getElementById('tab-history').style.display = 'none';
        document.getElementById('btn-stock').style.borderBottom = 'none';
        document.getElementById('btn-stock').style.color = '#64748b';
        document.getElementById('btn-history').style.borderBottom = 'none';
        document.getElementById('btn-history').style.color = '#64748b';

        document.getElementById('tab-' + tabName).style.display = 'block';
        document.getElementById('btn-' + tabName).style.borderBottom = '2px solid #4f46e5';
        document.getElementById('btn-' + tabName).style.color = '#4f46e5';
    }

    function openModal(id) { 
        document.getElementById(id).style.display = 'block';
    }
    function closeModal(id) { 
        document.getElementById(id).style.display = 'none';
    }

    function openMovementModal(itemId, type, itemName) {
        document.getElementById('movementForm').action = "{{ url('/ngo/inventory') }}/" + itemId + "/movement";
        document.getElementById('movType').value = type;
        document.getElementById('movItemName').innerText = itemName;
        
        if (type === 'in') {
            document.getElementById('movTitle').innerText = 'Registrar Entrada de Estoque';
            document.getElementById('movSubmitBtn').innerText = 'Confirmar Entrada';
            document.getElementById('movSubmitBtn').style.background = '#10b981';
            document.getElementById('outFields').style.display = 'none';
        } else {
            document.getElementById('movTitle').innerText = 'Registrar Saída de Estoque';
            document.getElementById('movSubmitBtn').innerText = 'Confirmar Saída';
            document.getElementById('movSubmitBtn').style.background = '#ef4444';
            document.getElementById('outFields').style.display = 'block';
        }
        
        openModal('movementModal');
    }
</script>
@endsection
