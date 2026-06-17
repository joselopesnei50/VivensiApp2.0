@extends('layouts.app')

@section('content')
<style>
    .kb-board { display:flex; gap:14px; overflow-x:auto; padding:8px 4px 18px; align-items:flex-start; }
    .kb-col { flex:0 0 290px; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:14px; padding:12px; display:flex; flex-direction:column; max-height:calc(100vh - 220px); }
    .kb-col-head { display:flex; align-items:center; gap:8px; margin-bottom:10px; }
    .kb-col-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
    .kb-col-title { color:#1e293b; font-weight:800; flex:1; font-size:.92rem; }
    .kb-col-count { background:#e2e8f0; color:#475569; font-size:.7rem; font-weight:700; padding:2px 8px; border-radius:99px; }
    .kb-col-del { background:none; border:none; color:#94a3b8; cursor:pointer; padding:2px 6px; font-size:.8rem; }
    .kb-col-del:hover { color:#ef4444; }
    .kb-cards { flex:1; overflow-y:auto; min-height:50px; margin-bottom:10px; }
    .kb-card { background:white; border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px; margin-bottom:8px; box-shadow:0 1px 2px rgba(0,0,0,.03); cursor:grab; }
    .kb-card:active { cursor:grabbing; }
    .kb-card-title { font-weight:700; color:#1e293b; font-size:.88rem; line-height:1.3; }
    .kb-card-desc  { color:#64748b; font-size:.76rem; margin-top:4px; line-height:1.3; }
    .kb-card-meta  { display:flex; align-items:center; gap:8px; margin-top:6px; font-size:.7rem; color:#94a3b8; }
    .kb-card-meta a { color:#10b981; font-weight:700; text-decoration:none; }
    .kb-quick-add input { width:100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px; font-size:.82rem; }
    .kb-quick-add button { background:#6366f1; color:white; border:none; padding:8px 10px; border-radius:8px; font-weight:700; font-size:.78rem; cursor:pointer; margin-top:6px; width:100%; }
    .kb-col-add { flex:0 0 280px; background:#fff; border:2px dashed #cbd5e1; border-radius:14px; padding:14px; color:#64748b; font-weight:700; font-size:.85rem; cursor:pointer; text-align:center; align-self:stretch; min-height:80px; display:flex; align-items:center; justify-content:center; }
    .kb-col-add:hover { border-color:#6366f1; color:#6366f1; }
    .kb-sortable-ghost { opacity:.4; background:#e0e7ff; }
    .kb-sortable-drag { transform:rotate(2deg); }
</style>

<div class="container-fluid px-4 py-3" style="max-width:1600px;">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h4 mb-1" style="font-weight:900;">
                <i class="fas fa-columns me-2" style="color: {{ $board->color ?: '#6366f1' }};"></i>{{ $board->name }}
            </h1>
            <p class="text-muted mb-0" style="font-size:.85rem;">
                Arraste cards entre colunas. Templates adaptados ao Perfil Operacional do tenant.
            </p>
        </div>
    </div>

    <div class="kb-board" id="kbBoard" data-board-id="{{ $board->id }}">
        @foreach($columns as $col)
            <div class="kb-col" data-column-id="{{ $col->id }}">
                <div class="kb-col-head">
                    <span class="kb-col-dot" style="background: {{ $col->color ?: '#cbd5e1' }};"></span>
                    <span class="kb-col-title">{{ $col->name }}</span>
                    <span class="kb-col-count" data-count>{{ $col->cards->count() }}</span>
                    <button class="kb-col-del" title="Excluir coluna" onclick="deleteColumn({{ $col->id }})"><i class="fas fa-trash"></i></button>
                </div>

                <div class="kb-cards" data-cards>
                    @foreach($col->cards as $card)
                        <div class="kb-card" data-card-id="{{ $card->id }}" onclick="openCardModal({{ $card->id }})">
                            <div class="kb-card-title">{{ $card->title }}</div>
                            @if($card->description)
                                <div class="kb-card-desc">{{ \Illuminate\Support\Str::limit($card->description, 100) }}</div>
                            @endif
                            <div class="kb-card-meta">
                                @if($card->whatsapp_chat_id)
                                    <a href="{{ url('/whatsapp/chat?chat_id=' . $card->whatsapp_chat_id) }}" onclick="event.stopPropagation();"><i class="fab fa-whatsapp"></i> Ver conversa</a>
                                @endif
                                @if($card->due_date)
                                    <span><i class="fas fa-calendar"></i> {{ \Carbon\Carbon::parse($card->due_date)->format('d/m') }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="kb-quick-add">
                    <input type="text" placeholder="Novo card — Enter para criar" data-quick-add-input>
                </div>
            </div>
        @endforeach

        <div class="kb-col-add" onclick="addColumn()">
            <span><i class="fas fa-plus me-1"></i>Nova coluna</span>
        </div>
    </div>
</div>

{{-- Modal de edição de card --}}
<div class="modal fade" id="kbCardModal" role="dialog" aria-modal="true" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
        <div class="modal-content" style="border-radius:18px;">
            <div style="padding:22px 26px 0; display:flex; justify-content:space-between;">
                <h5 style="font-weight:900; margin:0; color:#1e293b;"><i class="fas fa-edit me-2" style="color:#6366f1;"></i>Editar card</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div style="padding:18px 26px 8px;">
                <label style="font-size:.8rem; font-weight:700; color:#475569;">Título</label>
                <input id="kbCardTitle" type="text" maxlength="200" style="width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:.9rem; margin-bottom:14px;">

                <label style="font-size:.8rem; font-weight:700; color:#475569;">Descrição</label>
                <textarea id="kbCardDesc" rows="4" maxlength="5000" style="width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:.88rem; margin-bottom:14px; resize:vertical;"></textarea>

                <label style="font-size:.8rem; font-weight:700; color:#475569;">Prazo</label>
                <input id="kbCardDue" type="date" style="width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:.9rem; margin-bottom:14px;">

                <div id="kbCardError" style="color:#ef4444; font-size:.8rem; display:none;"></div>
            </div>
            <div style="padding:0 26px 22px; display:flex; gap:10px;">
                <button onclick="saveCard()" id="kbSaveBtn" style="flex:1; background:#6366f1; color:white; border:none; padding:12px; border-radius:10px; font-weight:700; cursor:pointer;">
                    <i class="fas fa-save me-2"></i>Salvar
                </button>
                <button onclick="archiveCard()" title="Arquivar card" style="background:#fef2f2; color:#dc2626; border:1px solid #fecaca; padding:12px 14px; border-radius:10px; font-weight:700; cursor:pointer;">
                    <i class="fas fa-archive"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    const KB_CSRF = '{{ csrf_token() }}';
    const KB_BOARD_ID = {{ $board->id }};
    const KB_HEADERS = { 'Content-Type':'application/json', 'X-CSRF-TOKEN': KB_CSRF, 'Accept':'application/json' };
    let KB_ACTIVE_CARD = null;

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.kb-col').forEach(col => {
            const cardsEl = col.querySelector('[data-cards]');
            new Sortable(cardsEl, {
                group: 'kb-cards',
                animation: 150,
                ghostClass: 'kb-sortable-ghost',
                dragClass:  'kb-sortable-drag',
                onEnd: handleDragEnd,
            });

            const input = col.querySelector('[data-quick-add-input]');
            input.addEventListener('keydown', async (e) => {
                if (e.key === 'Enter' && input.value.trim()) {
                    await quickAddCard(col, input.value.trim());
                    input.value = '';
                }
            });
        });
    });

    async function handleDragEnd(evt) {
        const cardEl       = evt.item;
        const targetColEl  = evt.to.closest('.kb-col');
        const cardId       = parseInt(cardEl.dataset.cardId, 10);
        const targetColId  = parseInt(targetColEl.dataset.columnId, 10);
        const orderedIds   = Array.from(evt.to.children)
            .map(el => parseInt(el.dataset.cardId, 10))
            .filter(Boolean);

        recountColumns();

        try {
            const r = await fetch(`{{ url('/manager/kanban/cards') }}/${cardId}/move`, {
                method: 'PATCH',
                headers: KB_HEADERS,
                body: JSON.stringify({ column_id: targetColId, ordered_card_ids: orderedIds }),
            });
            if (!r.ok) throw new Error('move-failed');
        } catch (e) {
            alert('Não foi possível mover o card. A página será recarregada.');
            location.reload();
        }
    }

    async function quickAddCard(colEl, title) {
        const columnId = parseInt(colEl.dataset.columnId, 10);
        try {
            const r = await fetch(`{{ url('/manager/kanban/columns') }}/${columnId}/cards`, {
                method: 'POST', headers: KB_HEADERS,
                body: JSON.stringify({ title }),
            });
            const d = await r.json();
            if (!r.ok || !d.card) {
                alert('Erro ao criar card.');
                return;
            }
            const cards = colEl.querySelector('[data-cards]');
            const div = document.createElement('div');
            div.className = 'kb-card';
            div.dataset.cardId = d.card.id;
            div.innerHTML = `<div class="kb-card-title">${escapeHtml(d.card.title)}</div>`;
            div.onclick = () => openCardModal(d.card.id);
            cards.appendChild(div);
            recountColumns();
        } catch (e) {
            alert('Falha de comunicação ao criar card.');
        }
    }

    async function addColumn() {
        const name = prompt('Nome da nova coluna:');
        if (!name || !name.trim()) return;
        try {
            const r = await fetch(`{{ url('/manager/kanban/boards') }}/${KB_BOARD_ID}/columns`, {
                method: 'POST', headers: KB_HEADERS,
                body: JSON.stringify({ name: name.trim() }),
            });
            if (!r.ok) { alert('Erro ao criar coluna.'); return; }
            location.reload();
        } catch (e) { alert('Falha de comunicação.'); }
    }

    async function deleteColumn(id) {
        if (!confirm('Excluir esta coluna? Cards dentro dela também serão excluídos.')) return;
        try {
            const r = await fetch(`{{ url('/manager/kanban/columns') }}/${id}`, {
                method: 'DELETE', headers: KB_HEADERS,
            });
            if (!r.ok) { alert('Erro ao excluir coluna.'); return; }
            location.reload();
        } catch (e) { alert('Falha de comunicação.'); }
    }

    function openCardModal(cardId) {
        const el = document.querySelector(`.kb-card[data-card-id="${cardId}"]`);
        if (!el) return;
        KB_ACTIVE_CARD = cardId;
        document.getElementById('kbCardTitle').value = el.querySelector('.kb-card-title')?.textContent ?? '';
        document.getElementById('kbCardDesc').value  = el.querySelector('.kb-card-desc')?.textContent ?? '';
        document.getElementById('kbCardDue').value   = el.dataset.due || '';
        document.getElementById('kbCardError').style.display = 'none';
        new bootstrap.Modal(document.getElementById('kbCardModal')).show();
    }

    async function saveCard() {
        if (!KB_ACTIVE_CARD) return;
        const title = document.getElementById('kbCardTitle').value.trim();
        const description = document.getElementById('kbCardDesc').value;
        const due_date = document.getElementById('kbCardDue').value || null;
        const errEl = document.getElementById('kbCardError');
        const btn = document.getElementById('kbSaveBtn');

        if (!title) {
            errEl.textContent = 'O título é obrigatório.';
            errEl.style.display = 'block';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvando...';
        try {
            const r = await fetch(`{{ url('/manager/kanban/cards') }}/${KB_ACTIVE_CARD}`, {
                method: 'PATCH', headers: KB_HEADERS,
                body: JSON.stringify({ title, description, due_date }),
            });
            if (!r.ok) {
                errEl.textContent = 'Erro ao salvar.';
                errEl.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-2"></i>Salvar';
                return;
            }
            location.reload();
        } catch (e) {
            errEl.textContent = 'Falha de comunicação.';
            errEl.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-2"></i>Salvar';
        }
    }

    async function archiveCard() {
        if (!KB_ACTIVE_CARD) return;
        if (!confirm('Arquivar este card? Ele some do board mas pode ser recuperado depois.')) return;
        try {
            const r = await fetch(`{{ url('/manager/kanban/cards') }}/${KB_ACTIVE_CARD}/archive`, {
                method: 'POST', headers: KB_HEADERS,
            });
            if (!r.ok) { alert('Erro ao arquivar.'); return; }
            location.reload();
        } catch (e) { alert('Falha de comunicação.'); }
    }

    function recountColumns() {
        document.querySelectorAll('.kb-col').forEach(col => {
            const n = col.querySelectorAll('.kb-card').length;
            col.querySelector('[data-count]').textContent = n;
        });
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[c]));
    }
</script>
@endsection
