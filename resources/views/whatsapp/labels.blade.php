@extends('layouts.app')

@section('content')
<style>
    .labels-header {
        background: linear-gradient(135deg, #0f172a 0%, #1a2540 100%);
        border-radius: 24px;
        padding: 36px 40px;
        margin-bottom: 28px;
        border: 1px solid rgba(255,255,255,0.08);
        color: white;
        position: relative;
        overflow: hidden;
    }
    .labels-header::before {
        content: '';
        position: absolute; inset: 0;
        background: radial-gradient(circle at 20% 30%, rgba(99,102,241,0.15) 0%, transparent 45%);
        pointer-events: none;
    }
    .labels-header-content { position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px; }
    .labels-eyebrow { font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; color: #a5b4fc; margin-bottom: 8px; }
    .labels-title { font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 2.2rem; letter-spacing: -1px; line-height: 1; margin: 0; }
    .labels-sub { color: rgba(255,255,255,0.55); font-size: .95rem; margin-top: 10px; max-width: 600px; }
    .btn-new-label {
        background: white; color: #0f172a; border: 0; padding: 12px 22px; border-radius: 12px;
        font-weight: 800; font-size: .9rem; display: inline-flex; align-items: center; gap: 10px;
        box-shadow: 0 10px 24px rgba(0,0,0,0.18); cursor: pointer;
    }
    .btn-new-label i { color: #6366f1; }

    .labels-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
    }
    .label-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(15,23,42,.06);
        display: flex; flex-direction: column; gap: 12px;
    }
    .label-preview {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 6px 14px; border-radius: 99px;
        font-size: .82rem; font-weight: 700;
        align-self: flex-start;
    }
    .label-meta { font-size: .76rem; color: #64748b; }
    .label-meta strong { color: #0f172a; font-weight: 700; }
    .label-actions { display: flex; gap: 8px; margin-top: auto; padding-top: 8px; border-top: 1px solid #f1f5f9; }
    .label-actions button {
        flex: 1;
        background: #f8fafc; color: #475569; border: 1px solid #e2e8f0;
        padding: 7px 0; border-radius: 8px; font-size: .76rem; font-weight: 700;
        cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    }
    .label-actions button:hover { background: #eef2ff; color: #4f46e5; border-color: #c7d2fe; }
    .label-actions .btn-del:hover { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }

    .empty-state {
        background: white; border: 2px dashed #cbd5e1; border-radius: 16px;
        padding: 56px 32px; text-align: center; color: #64748b;
    }
    .empty-state i { font-size: 2.2rem; color: #cbd5e1; margin-bottom: 12px; }

    /* Modal */
    .lbl-modal-overlay {
        display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.55);
        align-items: center; justify-content: center; z-index: 2000;
    }
    .lbl-modal-overlay.open { display: flex; }
    .lbl-modal {
        background: white; border-radius: 20px; padding: 32px; width: 100%; max-width: 480px;
        box-shadow: 0 30px 60px rgba(0,0,0,0.3);
    }
    .lbl-modal h3 { margin: 0 0 20px; font-family: 'Outfit'; font-weight: 800; font-size: 1.3rem; }
    .lbl-form-group { margin-bottom: 16px; }
    .lbl-form-group label { display: block; font-size: .78rem; font-weight: 700; color: #475569; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px; }
    .lbl-form-group input[type="text"] {
        width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px;
        font-family: inherit; font-size: .92rem;
    }
    .lbl-form-group input[type="text"]:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99,102,241,0.1); }
    .lbl-color-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .lbl-color-input { display: flex; align-items: center; gap: 8px; padding: 6px 10px; border: 1.5px solid #e2e8f0; border-radius: 10px; }
    .lbl-color-input input[type="color"] { border: 0; background: none; width: 32px; height: 32px; padding: 0; cursor: pointer; }
    .lbl-color-input input[type="text"] { border: 0; padding: 0; font-family: monospace; font-size: .85rem; flex: 1; }
    .lbl-preview-bar { padding: 14px; background: #f8fafc; border-radius: 10px; text-align: center; margin: 14px 0; }
    .lbl-modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
    .lbl-btn-cancel { background: #f1f5f9; color: #475569; border: 0; padding: 10px 22px; border-radius: 10px; font-weight: 700; cursor: pointer; }
    .lbl-btn-save { background: #6366f1; color: white; border: 0; padding: 10px 22px; border-radius: 10px; font-weight: 700; cursor: pointer; }
    .lbl-btn-save:hover { background: #4f46e5; }
</style>

<div class="labels-header">
    <div class="labels-header-content">
        <div>
            <div class="labels-eyebrow">CRM WhatsApp</div>
            <h1 class="labels-title">Etiquetas</h1>
            <p class="labels-sub">Organize seus contatos por categoria — leads, doadores, voluntários, beneficiários. Etiquetas são exclusivas do seu tenant e podem ser usadas no chat e nas campanhas de transmissão.</p>
        </div>
        <button type="button" class="btn-new-label" onclick="openLabelModal()">
            <i class="fas fa-plus"></i> Nova etiqueta
        </button>
    </div>
</div>

@if($labels->isEmpty())
    <div class="empty-state">
        <i class="fas fa-tags"></i>
        <div style="font-weight: 700; color: #0f172a; margin-bottom: 4px;">Nenhuma etiqueta criada ainda</div>
        <div style="font-size: .9rem;">Clique em "Nova etiqueta" para começar a organizar seus contatos.</div>
    </div>
@else
    <div class="labels-grid">
        @foreach($labels as $label)
            <div class="label-card" data-id="{{ $label->id }}"
                 data-name="{{ $label->name }}"
                 data-color="{{ $label->color }}"
                 data-background="{{ $label->background }}">
                <span class="label-preview" style="background: {{ $label->background }}; color: {{ $label->color }};">
                    <i class="fas fa-tag" style="font-size: .7rem;"></i>
                    {{ $label->name }}
                </span>
                <div class="label-meta">
                    <strong>{{ $label->chats_count }}</strong> contato(s) etiquetado(s)
                </div>
                <div class="label-actions">
                    <button onclick="editLabel(this)"><i class="fas fa-pen"></i> Editar</button>
                    <button class="btn-del" onclick="deleteLabel({{ $label->id }}, '{{ addslashes($label->name) }}')"><i class="fas fa-trash"></i> Excluir</button>
                </div>
            </div>
        @endforeach
    </div>
@endif

<!-- Modal -->
<div class="lbl-modal-overlay" id="lblModal">
    <div class="lbl-modal">
        <h3 id="lblModalTitle">Nova etiqueta</h3>
        <input type="hidden" id="lblId" value="">

        <div class="lbl-form-group">
            <label>Nome <span style="color: #94a3b8; font-weight: 500;">(até 30 caracteres)</span></label>
            <input type="text" id="lblName" maxlength="30" placeholder="Ex: Doador VIP">
        </div>

        <div class="lbl-color-row">
            <div class="lbl-form-group" style="margin: 0;">
                <label>Cor do texto</label>
                <div class="lbl-color-input">
                    <input type="color" id="lblColor" value="#1d4ed8" oninput="syncColorText('lblColor')">
                    <input type="text" id="lblColorText" value="#1d4ed8" oninput="syncTextColor('lblColor')">
                </div>
            </div>
            <div class="lbl-form-group" style="margin: 0;">
                <label>Fundo do badge</label>
                <div class="lbl-color-input">
                    <input type="color" id="lblBackground" value="#dbeafe" oninput="syncColorText('lblBackground')">
                    <input type="text" id="lblBackgroundText" value="#dbeafe" oninput="syncTextColor('lblBackground')">
                </div>
            </div>
        </div>

        <div class="lbl-preview-bar">
            <span class="label-preview" id="lblPreview" style="background: #dbeafe; color: #1d4ed8;">
                <i class="fas fa-tag" style="font-size: .7rem;"></i> Pré-visualização
            </span>
        </div>

        <div class="lbl-modal-actions">
            <button type="button" class="lbl-btn-cancel" onclick="closeLabelModal()">Cancelar</button>
            <button type="button" class="lbl-btn-save" onclick="saveLabel()">Salvar</button>
        </div>
    </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

function openLabelModal(label = null) {
    document.getElementById('lblId').value         = label?.id ?? '';
    document.getElementById('lblName').value       = label?.name ?? '';
    document.getElementById('lblColor').value      = label?.color ?? '#1d4ed8';
    document.getElementById('lblColorText').value  = label?.color ?? '#1d4ed8';
    document.getElementById('lblBackground').value = label?.background ?? '#dbeafe';
    document.getElementById('lblBackgroundText').value = label?.background ?? '#dbeafe';
    document.getElementById('lblModalTitle').textContent = label ? 'Editar etiqueta' : 'Nova etiqueta';
    updatePreview();
    document.getElementById('lblModal').classList.add('open');
    document.getElementById('lblName').focus();
}

function closeLabelModal() {
    document.getElementById('lblModal').classList.remove('open');
}

function editLabel(btn) {
    const card = btn.closest('.label-card');
    openLabelModal({
        id:         card.dataset.id,
        name:       card.dataset.name,
        color:      card.dataset.color,
        background: card.dataset.background,
    });
}

function syncColorText(id) {
    const value = document.getElementById(id).value;
    document.getElementById(id + 'Text').value = value;
    updatePreview();
}

function syncTextColor(id) {
    const value = document.getElementById(id + 'Text').value.trim();
    if (/^#[0-9a-fA-F]{6}$/.test(value)) {
        document.getElementById(id).value = value;
        updatePreview();
    }
}

function updatePreview() {
    const color = document.getElementById('lblColor').value;
    const bg    = document.getElementById('lblBackground').value;
    const prev  = document.getElementById('lblPreview');
    prev.style.color = color;
    prev.style.background = bg;
    const name = document.getElementById('lblName').value.trim();
    prev.innerHTML = '<i class="fas fa-tag" style="font-size: .7rem;"></i> ' + (name || 'Pré-visualização');
}

document.getElementById('lblName').addEventListener('input', updatePreview);

async function saveLabel() {
    const id   = document.getElementById('lblId').value;
    const data = {
        name:       document.getElementById('lblName').value.trim(),
        color:      document.getElementById('lblColor').value,
        background: document.getElementById('lblBackground').value,
    };

    if (!data.name) {
        alert('Nome é obrigatório.');
        return;
    }

    const url    = id ? `/api/whatsapp/labels/${id}` : '/api/whatsapp/labels';
    const method = id ? 'PATCH' : 'POST';

    try {
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(data),
        });
        const json = await res.json();
        if (!res.ok) {
            alert('Erro ao salvar: ' + (json.message || JSON.stringify(json.errors || {})));
            return;
        }
        location.reload();
    } catch (e) {
        alert('Erro de rede: ' + e.message);
    }
}

async function deleteLabel(id, name) {
    if (!confirm(`Excluir a etiqueta "${name}"? Todos os contatos que tinham essa etiqueta perderão a marcação.`)) return;
    try {
        const res = await fetch(`/api/whatsapp/labels/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        if (!res.ok) {
            const json = await res.json().catch(() => ({}));
            alert('Erro ao excluir: ' + (json.message || res.statusText));
            return;
        }
        location.reload();
    } catch (e) {
        alert('Erro de rede: ' + e.message);
    }
}

document.getElementById('lblModal').addEventListener('click', (e) => {
    if (e.target.id === 'lblModal') closeLabelModal();
});
</script>
@endsection
