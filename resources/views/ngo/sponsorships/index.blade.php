@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">CRM de Patrocínios B2B</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Pipeline visual (Kanban) de empresas parceiras e editais.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <div style="background: #eef2ff; color:#4338ca; padding: 10px 15px; border-radius: 8px; font-weight:800; font-size: .9rem;">
            Pipeline Total: R$ {{ number_format($deals->sum('expected_value'), 2, ',', '.') }}
        </div>
        <button onclick="openModal('dealModal')" class="btn-premium">
            <i class="fas fa-plus"></i> Novo Patrocínio
        </button>
    </div>
</div>

<style>
    .kanban-board {
        display: flex;
        gap: 20px;
        overflow-x: auto;
        padding-bottom: 20px;
        min-height: calc(100vh - 250px);
    }
    .kanban-column {
        min-width: 300px;
        max-width: 300px;
        background: #f1f5f9;
        border-radius: 12px;
        padding: 15px;
        border: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
    }
    .kanban-header {
        font-weight: 800;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        font-size: 0.95rem;
    }
    .kanban-cards {
        flex: 1;
        min-height: 100px;
        border-radius: 8px;
    }
    .k-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        cursor: grab;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative;
    }
    .k-card:active {
        cursor: grabbing;
    }
    .k-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    .k-card .company {
        font-weight: 800;
        color: #1e293b;
        font-size: 1rem;
        margin-bottom: 5px;
    }
    .k-card .value {
        color: #10b981;
        font-weight: 700;
        font-size: 0.95rem;
        margin: 8px 0;
    }
    .k-card .person {
        font-size: 0.8rem;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .kanban-cards.drag-over {
        background: #e2e8f0;
        border: 2px dashed #94a3b8;
    }
    .k-delete {
        position: absolute;
        top: 10px;
        right: 10px;
        color: #ef4444;
        background: #fee2e2;
        border: none;
        width: 24px;
        height: 24px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.2s;
    }
    .k-card:hover .k-delete {
        opacity: 1;
    }
</style>

<div class="kanban-board">
    <!-- Colunas -->
    @php
        $cols = [
            'prospecting' => ['title' => '🔍 Prospecção', 'color' => '#64748b'],
            'meeting_scheduled' => ['title' => '📅 Reunião Marcada', 'color' => '#f59e0b'],
            'negotiating' => ['title' => '🤝 Em Negociação', 'color' => '#3b82f6'],
            'won' => ['title' => '🏆 Ganho / Patrocinador', 'color' => '#10b981'],
            'lost' => ['title' => '❌ Perdido / Pausado', 'color' => '#ef4444'],
        ];
    @endphp

    @foreach($cols as $stage => $col)
        <div class="kanban-column" style="border-top: 4px solid {{ $col['color'] }}">
            <div class="kanban-header">
                <span style="color: {{ $col['color'] }}">{{ $col['title'] }}</span>
                <span style="background: #e2e8f0; padding: 2px 8px; border-radius: 999px; font-size: 0.8rem; color: #475569;">
                    {{ $groupedDeals[$stage]->count() }}
                </span>
            </div>
            <div class="kanban-cards" data-stage="{{ $stage }}" id="col-{{ $stage }}">
                @foreach($groupedDeals[$stage] as $deal)
                    <div class="k-card" draggable="true" data-id="{{ $deal->id }}" id="deal-{{ $deal->id }}">
                        <form action="{{ url('/ngo/sponsorships/'.$deal->id) }}" method="POST" class="k-delete-form" onsubmit="return confirm('Excluir negociação?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="k-delete" title="Excluir"><i class="fas fa-trash-alt"></i></button>
                        </form>
                        
                        <div class="company">{{ $deal->company_name }}</div>
                        @if($deal->contact_person)
                            <div class="person"><i class="fas fa-user"></i> {{ $deal->contact_person }}</div>
                        @endif
                        <div class="value">R$ {{ number_format($deal->expected_value, 2, ',', '.') }}</div>
                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 5px;">
                            Criado em: {{ $deal->created_at->format('d/m/y') }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<!-- Modal Novo Deal -->
<div id="dealModal" class="custom-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000; overflow-y: auto;">
    <div class="vivensi-card" style="width: 95%; max-width: 500px; margin: 40px auto; position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>Nova Empresa / Patrocínio</h3>
            <button onclick="closeModal('dealModal')" style="border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form action="{{ url('/ngo/sponsorships') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Empresa ou Organização Privada</label>
                <input type="text" name="company_name" class="form-control-vivensi" required placeholder="Ex: Itaú BBA S.A.">
            </div>
            <div class="grid-2" style="gap: 15px;">
                <div class="form-group"><label>Contato (Pessoa)</label><input type="text" name="contact_person" class="form-control-vivensi" placeholder="Ex: Maria das Graças"></div>
                <div class="form-group"><label>Telefone / WhatsApp</label><input type="text" name="phone" class="form-control-vivensi"></div>
            </div>
            <div class="form-group"><label>E-mail corporativo</label><input type="email" name="email" class="form-control-vivensi"></div>
            
            <div class="grid-2" style="gap: 15px;">
                <div class="form-group"><label>Valor Esperado de Retorno (R$)</label><input type="number" step="0.01" name="expected_value" class="form-control-vivensi" required></div>
                <div class="form-group"><label>Data da Próx. Reunião</label><input type="date" name="contact_date" class="form-control-vivensi"></div>
            </div>
            <div class="form-group">
                <label>Contexto Inicial (Anotações)</label>
                <textarea name="notes" class="form-control-vivensi" rows="3" placeholder="Em que fase estamos com eles?"></textarea>
            </div>
            
            <button type="submit" class="btn-premium" style="width: 100%; justify-content: center;">Criar Card no Funil</button>
        </form>
    </div>
</div>

<script>
    function openModal(id) { 
        document.getElementById(id).style.display = 'block';
    }
    function closeModal(id) { 
        document.getElementById(id).style.display = 'none';
    }

    // Kanban Drag and Drop Logic
    document.addEventListener('DOMContentLoaded', () => {
        const cards = document.querySelectorAll('.k-card');
        const columns = document.querySelectorAll('.kanban-cards');

        let draggedCard = null;

        cards.forEach(card => {
            card.addEventListener('dragstart', function(e) {
                // Previne o drag caso esteja clicando no botão deletar do card
                if(e.target.closest('.k-delete-form')) {
                    e.preventDefault();
                    return;
                }
                draggedCard = card;
                setTimeout(() => card.style.opacity = '0.5', 0);
            });

            card.addEventListener('dragend', function() {
                setTimeout(() => {
                    draggedCard.style.opacity = '1';
                    draggedCard = null;
                }, 0);
            });
        });

        columns.forEach(column => {
            column.addEventListener('dragover', function(e) {
                e.preventDefault(); // Necessary to allow dropping
                this.classList.add('drag-over');
            });

            column.addEventListener('dragleave', function() {
                this.classList.remove('drag-over');
            });

            column.addEventListener('drop', function(e) {
                this.classList.remove('drag-over');
                if (draggedCard) {
                    this.appendChild(draggedCard); // Appends visualmente
                    let dealId = draggedCard.getAttribute('data-id');
                    let newStage = this.getAttribute('data-stage');
                    updateDealStage(dealId, newStage);
                }
            });
        });
    });

    function updateDealStage(id, stage) {
        fetch("{{ url('/ngo/sponsorships') }}/" + id + "/stage", {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ stage: stage })
        }).then(res => res.json())
        .then(data => {
            if(!data.success) alert('Erro ao atualizar no banco de dados.');
        }).catch(err => {
            console.error(err);
        });
    }
</script>
@endsection
