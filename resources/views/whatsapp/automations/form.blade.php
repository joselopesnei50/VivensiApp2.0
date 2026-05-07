@extends('layouts.app')
@section('title', $automation ? 'Editar Automação' : 'Nova Automação')

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center gap-3 mb-5">
        <a href="{{ route('whatsapp.automations.index') }}"
           class="btn btn-sm btn-outline-secondary rounded-3 d-flex align-items-center gap-1"
           style="padding:6px 14px;font-size:.82rem;">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <div>
            <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;">
                WhatsApp / Automações / {{ $automation ? 'Editar' : 'Nova' }}
            </div>
            <h2 class="fw-800 mb-0" style="font-size:1.6rem;color:#0f172a;line-height:1.2;">
                {{ $automation ? $automation->name : 'Nova Automação' }}
            </h2>
        </div>
    </div>

    <form action="{{ $automation ? route('whatsapp.automations.update', $automation) : route('whatsapp.automations.store') }}"
          method="POST" id="automationForm">
        @csrf
        @if($automation) @method('PUT') @endif

        <div class="row g-4 align-items-start">

            {{-- ── LEFT COLUMN: Form ── --}}
            <div class="col-lg-7">

                {{-- Section 1: Identificação --}}
                <div class="card border-0 shadow-sm rounded-4 mb-3">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="step-badge">1</div>
                            <div>
                                <div class="fw-700 text-dark" style="font-size:.92rem;">Identificação</div>
                                <div class="text-muted" style="font-size:.75rem;">Dê um nome claro para reconhecer esta regra.</div>
                            </div>
                        </div>
                        <input type="text" name="name"
                               class="form-control form-control-lg rounded-3 @error('name') is-invalid @enderror"
                               placeholder="Ex: Reativar doadores sem doação há 30 dias"
                               value="{{ old('name', $automation?->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Section 2: Gatilho --}}
                <div class="card border-0 shadow-sm rounded-4 mb-3">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="step-badge">2</div>
                            <div>
                                <div class="fw-700 text-dark" style="font-size:.92rem;">Gatilho</div>
                                <div class="text-muted" style="font-size:.75rem;">Quando e para quem a mensagem deve ser disparada.</div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label fw-600 small">Evento disparador <span class="text-danger">*</span></label>
                                @php
                                    $triggers = [
                                        'donor_inactive_days'    => ['label' => 'Doador sem doação',             'icon' => '💚'],
                                        'sponsorship_stale_days' => ['label' => 'Patrocínio parado em proposta', 'icon' => '🤝'],
                                        'no_contact_days'        => ['label' => 'Contato sem interação',         'icon' => '📱'],
                                        'open_conversation_days' => ['label' => 'Conversa sem resposta',         'icon' => '💬'],
                                    ];
                                @endphp
                                <select name="trigger" class="form-select rounded-3 @error('trigger') is-invalid @enderror" required>
                                    <option value="">Selecione o evento...</option>
                                    @foreach($triggers as $val => $t)
                                        <option value="{{ $val }}" {{ old('trigger', $automation?->trigger) === $val ? 'selected' : '' }}>
                                            {{ $t['icon'] }} {{ $t['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('trigger') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-600 small">Após quantos dias? <span class="text-danger">*</span></label>
                                <div class="input-group rounded-3">
                                    <input type="number" name="trigger_days"
                                           class="form-control rounded-start-3 @error('trigger_days') is-invalid @enderror"
                                           min="1" max="365" placeholder="Ex: 30"
                                           value="{{ old('trigger_days', $automation?->trigger_days) }}" required>
                                    <span class="input-group-text rounded-end-3" style="font-size:.82rem;">dias</span>
                                </div>
                                @error('trigger_days') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label fw-600 small">Público-alvo</label>
                            <div class="d-flex gap-2 flex-wrap" id="audiencePicker">
                                @php
                                    $audiences = [
                                        'all'      => ['label' => 'Todos',           'icon' => 'users'],
                                        'donors'   => ['label' => 'Doadores',        'icon' => 'heart'],
                                        'sponsors' => ['label' => 'Patrocinadores',  'icon' => 'handshake'],
                                        'contacts' => ['label' => 'Contatos gerais', 'icon' => 'address-book'],
                                    ];
                                    $selectedAudience = old('audience', $automation?->audience ?? 'all');
                                @endphp
                                @foreach($audiences as $val => $a)
                                    <label class="audience-pill {{ $selectedAudience === $val ? 'selected' : '' }}" for="aud_{{ $val }}">
                                        <input type="radio" name="audience" id="aud_{{ $val }}" value="{{ $val }}"
                                               {{ $selectedAudience === $val ? 'checked' : '' }} style="display:none;">
                                        <i class="fas fa-{{ $a['icon'] }} me-1"></i> {{ $a['label'] }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 3: Mensagem --}}
                <div class="card border-0 shadow-sm rounded-4 mb-3">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="step-badge">3</div>
                            <div>
                                <div class="fw-700 text-dark" style="font-size:.92rem;">Mensagem</div>
                                <div class="text-muted" style="font-size:.75rem;">Texto que será enviado via WhatsApp. Suporta variáveis dinâmicas.</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex gap-2">
                                <button type="button" class="var-chip" onclick="insertVar('{nome}')">
                                    <i class="fas fa-at" style="font-size:.65rem;"></i> {nome}
                                </button>
                                <button type="button" class="var-chip" onclick="insertVar('{organizacao}')">
                                    <i class="fas fa-building" style="font-size:.65rem;"></i> {organizacao}
                                </button>
                            </div>
                            <span id="charCount" style="font-size:.7rem;color:#94a3b8;">0 / 2000</span>
                        </div>
                        <textarea name="message_template" id="msgTemplate" rows="7"
                                  class="form-control rounded-3 @error('message_template') is-invalid @enderror"
                                  maxlength="2000" required
                                  placeholder="Olá {nome}! Sentimos sua falta. Seu apoio à {organizacao} faz toda a diferença...">{{ old('message_template', $automation?->message_template) }}</textarea>
                        @error('message_template') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Section 4: Configurações --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="step-badge">4</div>
                            <div>
                                <div class="fw-700 text-dark" style="font-size:.92rem;">Configurações</div>
                                <div class="text-muted" style="font-size:.75rem;">Horário de envio e status da automação.</div>
                            </div>
                        </div>

                        {{-- Send window --}}
                        <label class="form-label fw-600 small">Janela de envio seguro</label>
                        <div class="d-flex align-items-center gap-3 mb-1">
                            <div class="flex-1">
                                <label class="form-text mb-1">Das</label>
                                <input type="time" name="send_window_start" class="form-control rounded-3"
                                       value="{{ old('send_window_start', $automation?->send_window_start ?? '08:00') }}" required>
                            </div>
                            <div class="text-muted pt-3" style="font-size:.8rem;flex-shrink:0;">até</div>
                            <div class="flex-1">
                                <label class="form-text mb-1">Às</label>
                                <input type="time" name="send_window_end" class="form-control rounded-3"
                                       value="{{ old('send_window_end', $automation?->send_window_end ?? '20:00') }}" required>
                            </div>
                        </div>
                        <div class="form-text mb-4">Mensagens só são enviadas dentro deste intervalo, respeitando o horário do contato.</div>

                        {{-- is_active toggle --}}
                        <div class="d-flex align-items-center justify-content-between p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                            <div>
                                <div class="fw-600 small text-dark">Ativar automação ao salvar</div>
                                <div class="text-muted" style="font-size:.75rem;">Quando ativa, o sistema processa esta regra todos os dias.</div>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                       id="isActiveSwitch"
                                       style="width:2.6em;height:1.35em;cursor:pointer;"
                                       {{ old('is_active', $automation?->is_active ?? true) ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold rounded-3">
                        <i class="fas fa-check me-2"></i>
                        {{ $automation ? 'Salvar alterações' : 'Criar automação' }}
                    </button>
                    <a href="{{ route('whatsapp.automations.index') }}" class="btn btn-outline-secondary btn-lg rounded-3">
                        Cancelar
                    </a>
                </div>
            </div>

            {{-- ── RIGHT COLUMN: Help panel (sticky) ── --}}
            <div class="col-lg-5" style="position:sticky;top:80px;align-self:start;">
                <div class="card border-0 shadow-sm rounded-4" style="background:#0f172a;color:#fff;">
                    <div class="card-body p-4">

                        <h6 class="fw-bold mb-3" style="color:#a78bfa;font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;">
                            <i class="fas fa-lightbulb me-1"></i> Como funciona
                        </h6>
                        <div class="d-flex flex-column gap-3 mb-4">
                            @foreach([
                                ['num' => '1', 'color_bg' => 'rgba(99,102,241,.2)',   'color_txt' => '#818cf8', 'title' => 'Você define a regra',           'desc' => 'Gatilho + quantos dias + mensagem personalizada.'],
                                ['num' => '2', 'color_bg' => 'rgba(37,211,102,.15)',  'color_txt' => '#4ade80', 'title' => 'O sistema processa todo dia',    'desc' => 'Verifica quem se enquadra e envia via WhatsApp às 10h.'],
                                ['num' => '3', 'color_bg' => 'rgba(251,191,36,.15)',  'color_txt' => '#fbbf24', 'title' => 'Anti-spam automático',           'desc' => 'Cada contato recebe no máximo 1 mensagem por automação em 24h.'],
                            ] as $step)
                                <div class="d-flex gap-3 align-items-start">
                                    <div style="width:26px;height:26px;background:{{ $step['color_bg'] }};border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.72rem;font-weight:800;color:{{ $step['color_txt'] }};">{{ $step['num'] }}</div>
                                    <div>
                                        <div style="font-size:.8rem;font-weight:700;color:#e2e8f0;">{{ $step['title'] }}</div>
                                        <div style="font-size:.72rem;color:rgba(255,255,255,.4);line-height:1.5;">{{ $step['desc'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <hr style="border-color:rgba(255,255,255,.08);margin:0 0 16px;">

                        <h6 class="fw-bold mb-2" style="color:#a78bfa;font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;">
                            Variáveis disponíveis
                        </h6>
                        <div class="d-flex flex-column gap-2 mb-4">
                            @foreach(['{nome}' => 'Nome do contato', '{organizacao}' => 'Nome da organização'] as $var => $desc)
                                <div class="d-flex justify-content-between align-items-center p-2 rounded-3"
                                     style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);">
                                    <code style="color:#34d399;font-size:.78rem;">{{ $var }}</code>
                                    <span style="font-size:.72rem;color:rgba(255,255,255,.4);">{{ $desc }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="p-3 rounded-3" style="background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.15);">
                            <p style="font-size:.72rem;color:#fbbf24;margin:0;line-height:1.6;">
                                <i class="fas fa-triangle-exclamation me-1"></i>
                                <strong>Meta 24h:</strong> Reativações após 24h de silêncio exigem Template Message aprovado. Com a Evolution API não há essa restrição.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
const msgEl   = document.getElementById('msgTemplate');
const countEl = document.getElementById('charCount');

function updateCount() {
    const len = msgEl.value.length;
    countEl.textContent = len + ' / 2000';
    countEl.style.color = len > 1800 ? '#ef4444' : '#94a3b8';
}
msgEl.addEventListener('input', updateCount);
updateCount();

function insertVar(variable) {
    const start = msgEl.selectionStart;
    const end   = msgEl.selectionEnd;
    msgEl.value = msgEl.value.substring(0, start) + variable + msgEl.value.substring(end);
    msgEl.selectionStart = msgEl.selectionEnd = start + variable.length;
    msgEl.focus();
    updateCount();
}

// Audience pill toggle
document.querySelectorAll('.audience-pill').forEach(pill => {
    pill.addEventListener('click', () => {
        document.querySelectorAll('.audience-pill').forEach(p => p.classList.remove('selected'));
        pill.classList.add('selected');
    });
});
</script>

<style>
.fw-600 { font-weight: 600; }
.fw-700 { font-weight: 700; }
.fw-800 { font-weight: 800; }
.flex-1 { flex: 1; }

.step-badge {
    width: 28px; height: 28px;
    border-radius: 8px;
    background: rgba(99,102,241,.1);
    color: #6366f1;
    font-size: .78rem;
    font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

.var-chip {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px;
    font-size: .72rem; font-weight: 600;
    background: rgba(99,102,241,.08);
    color: #4f46e5;
    border: 1px solid rgba(99,102,241,.2);
    border-radius: 20px;
    cursor: pointer;
    transition: background .15s;
}
.var-chip:hover { background: rgba(99,102,241,.15); }

.audience-pill {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px;
    font-size: .8rem; font-weight: 600;
    background: #f8fafc;
    color: #64748b;
    border: 1.5px solid #e2e8f0;
    border-radius: 24px;
    cursor: pointer;
    transition: all .15s;
    user-select: none;
}
.audience-pill:hover { border-color: #6366f1; color: #4f46e5; background: rgba(99,102,241,.05); }
.audience-pill.selected { background: rgba(99,102,241,.1); color: #4f46e5; border-color: #6366f1; }
</style>
@endsection
