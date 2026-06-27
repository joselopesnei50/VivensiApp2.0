@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 24px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:14px;">
        <div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: var(--primary-color); font-weight:800; text-transform:uppercase; margin:0; letter-spacing:2px; font-size:0.7rem;">WhatsApp / Formulários</h6>
            </div>
            <h2 style="margin:0; color:#1e293b; font-weight:900; font-size:2.4rem; letter-spacing:-1px;">{{ $form->name }}</h2>
            <p style="color:#64748b; margin-top:8px;">Edite os campos do formulário e gerencie as perguntas abaixo. A ordem é a sequência em que o bot manda no WhatsApp.</p>
        </div>
        <a href="{{ route('whatsapp.forms.index') }}" class="btn btn-outline-secondary rounded-3" style="padding:11px 20px;">← Voltar</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="row g-4">
    {{-- Dados do formulário --}}
    <div class="col-lg-5">
        <div class="vivensi-card" style="padding:24px;">
            <h5 class="fw-800 mb-3" style="color:#1e293b;">Dados do formulário</h5>
            <form action="{{ route('whatsapp.forms.update', $form->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label fw-700 small text-uppercase text-muted">Nome</label>
                    <input type="text" name="name" required maxlength="120" value="{{ old('name', $form->name) }}" class="form-control rounded-3">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-700 small text-uppercase text-muted">Descrição</label>
                    <textarea name="description" rows="3" maxlength="1000" class="form-control rounded-3">{{ old('description', $form->description) }}</textarea>
                </div>
                <div class="mb-3 form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1" class="form-check-input" {{ old('is_active', $form->is_active) ? 'checked' : '' }}>
                    <label for="is_active" class="form-check-label fw-700">Ativo</label>
                </div>
                <button type="submit" class="btn-premium" style="padding:10px 20px; font-size:.85rem;">
                    <i class="fas fa-save me-2"></i> Salvar dados
                </button>
            </form>
        </div>
    </div>

    {{-- Perguntas --}}
    <div class="col-lg-7">
        <div class="vivensi-card" style="padding:24px;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-800 m-0" style="color:#1e293b;">Perguntas</h5>
                <button type="button" class="btn btn-success btn-sm rounded-pill fw-bold" onclick="openQuestionModal()">
                    <i class="fas fa-plus me-1"></i> Nova pergunta
                </button>
            </div>

            <div id="qHelp" class="small text-muted mb-3" style="background:#f8fafc; border-radius:10px; padding:10px 12px;">
                <i class="fas fa-info-circle me-1" style="color:#6366f1;"></i>
                <strong>Field keys mágicos</strong>: ao usar <code>phone</code>, <code>email</code>, <code>name</code>, <code>city</code> ou <code>tags</code>, a resposta vai automaticamente pro CRM de Leads.
            </div>

            <div id="questionsList">
                @foreach($questions as $q)
                    @include('whatsapp.forms.partials.question_row', ['q' => $q])
                @endforeach
                @if($questions->isEmpty())
                    <div id="emptyState" style="text-align:center; padding:40px 20px; color:#94a3b8;">
                        Nenhuma pergunta ainda. Clique em <strong>Nova pergunta</strong> pra começar.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Modal pergunta --}}
<div class="modal fade" id="questionModal" tabindex="-1" aria-modal="true" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:640px;">
        <div class="modal-content" style="border-radius:20px;">
            <div class="modal-header" style="border-bottom:1px solid #f1f5f9;">
                <h5 class="modal-title fw-800" id="qModalTitle">Nova pergunta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:24px;">
                <input type="hidden" id="qEditId" value="">

                <div class="mb-3">
                    <label class="form-label fw-700 small text-uppercase text-muted">Texto da pergunta</label>
                    <textarea id="qText" rows="2" maxlength="1000" class="form-control rounded-3" placeholder="Ex: Qual seu nome completo?"></textarea>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-700 small text-uppercase text-muted">Field key</label>
                        <input type="text" id="qFieldKey" maxlength="60" class="form-control rounded-3" placeholder="ex: name, email, age...">
                        <div class="form-text" style="font-size:.72rem;">snake_case. Use <code>phone, email, name, city, tags</code> pra mapear no CRM.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-700 small text-uppercase text-muted">Tipo</label>
                        <select id="qType" class="form-select rounded-3" onchange="toggleOptionsByType()">
                            <option value="text">Texto livre</option>
                            <option value="number">Número</option>
                            <option value="yes_no">Sim / Não</option>
                            <option value="buttons">Botões (até 3)</option>
                            <option value="list">Lista de opções</option>
                        </select>
                    </div>
                </div>

                {{-- Opções dinâmicas (buttons/list) --}}
                <div id="optionsBox" class="mb-3 d-none">
                    <label class="form-label fw-700 small text-uppercase text-muted">Opções <span class="text-muted">(label e id curto)</span></label>
                    <div id="optionsList"></div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="addOptionRow()">
                        <i class="fas fa-plus me-1"></i> Adicionar opção
                    </button>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-700 small text-uppercase text-muted">Mín. (número)</label>
                        <input type="number" id="qMin" class="form-control rounded-3" placeholder="—">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-700 small text-uppercase text-muted">Máx. (número)</label>
                        <input type="number" id="qMax" class="form-control rounded-3" placeholder="—">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-700 small text-uppercase text-muted">Regex</label>
                        <input type="text" id="qRegex" maxlength="200" class="form-control rounded-3" placeholder="opcional">
                    </div>
                </div>

                <div class="form-check form-switch">
                    <input type="checkbox" id="qRequired" class="form-check-input" checked>
                    <label for="qRequired" class="form-check-label fw-700">Obrigatória</label>
                </div>

                <div id="qError" class="alert alert-danger mt-3 d-none"></div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="qSaveBtn" class="btn-premium" onclick="saveQuestion()">
                    <i class="fas fa-save me-1"></i> Salvar pergunta
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    const FORM_ID = {{ $form->id }};
    const CSRF = '{{ csrf_token() }}';
    const URL_ADD     = '{{ route("whatsapp.forms.questions.add", $form->id) }}';
    const URL_REORDER = '{{ route("whatsapp.forms.questions.reorder", $form->id) }}';
    const URL_UPDATE_BASE = '/whatsapp/forms/' + FORM_ID + '/questions/';
    const URL_DELETE_BASE = '/whatsapp/forms/' + FORM_ID + '/questions/';

    // SortableJS para arrastar perguntas
    new Sortable(document.getElementById('questionsList'), {
        animation: 150,
        handle: '.q-drag-handle',
        onEnd: () => {
            const ids = [...document.querySelectorAll('#questionsList [data-question-id]')]
                .map(el => parseInt(el.dataset.questionId, 10));
            fetch(URL_REORDER, {
                method: 'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
                body: JSON.stringify({ ordered_ids: ids }),
            });
        }
    });

    function openQuestionModal(question = null) {
        document.getElementById('qError').classList.add('d-none');
        document.getElementById('qEditId').value = question ? question.id : '';
        document.getElementById('qModalTitle').textContent = question ? 'Editar pergunta' : 'Nova pergunta';
        document.getElementById('qText').value      = question?.text || '';
        document.getElementById('qFieldKey').value  = question?.field_key || '';
        document.getElementById('qType').value      = question?.type || 'text';
        document.getElementById('qMin').value       = question?.min_value ?? '';
        document.getElementById('qMax').value       = question?.max_value ?? '';
        document.getElementById('qRegex').value     = question?.validation_regex || '';
        document.getElementById('qRequired').checked = question ? !!question.required : true;
        document.getElementById('optionsList').innerHTML = '';
        if (question?.options && Array.isArray(question.options)) {
            question.options.forEach(o => addOptionRow(o.id, o.label));
        }
        toggleOptionsByType();
        new bootstrap.Modal(document.getElementById('questionModal')).show();
    }

    function toggleOptionsByType() {
        const t = document.getElementById('qType').value;
        const box = document.getElementById('optionsBox');
        if (t === 'buttons' || t === 'list') {
            box.classList.remove('d-none');
            if (!document.getElementById('optionsList').children.length) addOptionRow();
        } else {
            box.classList.add('d-none');
        }
    }

    function addOptionRow(id = '', label = '') {
        const wrap = document.createElement('div');
        wrap.className = 'd-flex gap-2 mb-2';
        wrap.innerHTML = `
            <input type="text" placeholder="id (ex: 1)" maxlength="60" value="${escapeAttr(id)}" class="form-control form-control-sm rounded-3" style="max-width:80px;">
            <input type="text" placeholder="rótulo visível" maxlength="120" value="${escapeAttr(label)}" class="form-control form-control-sm rounded-3">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        `;
        document.getElementById('optionsList').appendChild(wrap);
    }

    function collectOptions() {
        const rows = document.querySelectorAll('#optionsList > div');
        const out = [];
        rows.forEach(r => {
            const inputs = r.querySelectorAll('input');
            const id    = inputs[0].value.trim();
            const label = inputs[1].value.trim();
            if (id && label) out.push({ id, label });
        });
        return out;
    }

    async function saveQuestion() {
        const editId = document.getElementById('qEditId').value;
        const type   = document.getElementById('qType').value;
        const payload = {
            text:             document.getElementById('qText').value,
            field_key:        document.getElementById('qFieldKey').value,
            type:             type,
            required:         document.getElementById('qRequired').checked,
            min_value:        document.getElementById('qMin').value || null,
            max_value:        document.getElementById('qMax').value || null,
            validation_regex: document.getElementById('qRegex').value || null,
        };
        if (type === 'buttons' || type === 'list') {
            payload.options = collectOptions();
        }
        const url = editId ? (URL_UPDATE_BASE + editId) : URL_ADD;
        const method = editId ? 'PATCH' : 'POST';
        const btn = document.getElementById('qSaveBtn');
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Salvando...';
        try {
            const r = await fetch(url, {
                method,
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
                body: JSON.stringify(payload),
            });
            const d = await r.json();
            if (r.ok && d.success) {
                window.location.reload();
            } else {
                const errEl = document.getElementById('qError');
                errEl.classList.remove('d-none');
                errEl.textContent = d.message || (d.errors ? Object.values(d.errors).flat().join(' / ') : 'Erro ao salvar.');
                btn.disabled = false;
                btn.innerHTML = orig;
            }
        } catch (e) {
            document.getElementById('qError').classList.remove('d-none');
            document.getElementById('qError').textContent = 'Falha de rede.';
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    }

    async function deleteQuestion(id) {
        if (!confirm('Remover esta pergunta?')) return;
        const r = await fetch(URL_DELETE_BASE + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
        });
        if (r.ok) window.location.reload();
    }

    // O botão "Editar" passa o JSON via data-attr — escapamos no template Blade.
    function editQuestion(jsonStr) {
        try {
            const q = JSON.parse(jsonStr);
            openQuestionModal(q);
        } catch (e) {
            alert('Erro ao abrir editor: ' + e.message);
        }
    }

    function escapeAttr(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
</script>
@endpush
