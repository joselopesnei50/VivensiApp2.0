@extends('layouts.app')
@section('title', isset($automation) ? 'Editar Automação' : 'Nova Automação')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('whatsapp.automations.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h2 class="fw-bold mb-0" style="font-size:1.6rem;">
                {{ $automation ? 'Editar Automação' : 'Nova Automação' }}
            </h2>
            <p class="text-muted small mb-0">Configure quando e o que enviar automaticamente.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <form action="{{ $automation ? route('whatsapp.automations.update', $automation) : route('whatsapp.automations.store') }}"
                          method="POST">
                        @csrf
                        @if($automation) @method('PUT') @endif

                        {{-- Nome --}}
                        <div class="mb-4">
                            <label class="form-label fw-600">Nome da automação <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-lg @error('name') is-invalid @enderror"
                                   placeholder="Ex: Reativar doadores inativos 30 dias"
                                   value="{{ old('name', $automation?->name) }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Gatilho --}}
                        <div class="mb-4">
                            <label class="form-label fw-600">Gatilho <span class="text-danger">*</span></label>
                            <select name="trigger" class="form-select form-select-lg @error('trigger') is-invalid @enderror" required>
                                <option value="">Selecione o evento que dispara o envio...</option>
                                @php
                                    $triggers = [
                                        'donor_inactive_days'    => '💚 Doador sem doação há X dias',
                                        'sponsorship_stale_days' => '🤝 Patrocínio parado em proposta há X dias',
                                        'no_contact_days'        => '📱 Contato sem interação há X dias',
                                        'open_conversation_days' => '💬 Conversa aberta sem resposta há X dias',
                                    ];
                                @endphp
                                @foreach($triggers as $val => $label)
                                    <option value="{{ $val }}" {{ old('trigger', $automation?->trigger) === $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('trigger') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Dias --}}
                        <div class="mb-4">
                            <label class="form-label fw-600">Disparar após quantos dias sem atividade? <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <input type="number" name="trigger_days" class="form-control @error('trigger_days') is-invalid @enderror"
                                       min="1" max="365" placeholder="Ex: 30"
                                       value="{{ old('trigger_days', $automation?->trigger_days) }}" required>
                                <span class="input-group-text">dias</span>
                            </div>
                            @error('trigger_days') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        {{-- Público --}}
                        <div class="mb-4">
                            <label class="form-label fw-600">Público-alvo</label>
                            <select name="audience" class="form-select @error('audience') is-invalid @enderror">
                                <option value="all"      {{ old('audience', $automation?->audience) === 'all'      ? 'selected' : '' }}>Todos os contatos</option>
                                <option value="donors"   {{ old('audience', $automation?->audience) === 'donors'   ? 'selected' : '' }}>Somente doadores</option>
                                <option value="sponsors" {{ old('audience', $automation?->audience) === 'sponsors' ? 'selected' : '' }}>Somente patrocinadores</option>
                                <option value="contacts" {{ old('audience', $automation?->audience) === 'contacts' ? 'selected' : '' }}>Somente contatos gerais</option>
                            </select>
                        </div>

                        {{-- Janela de envio --}}
                        <div class="mb-4">
                            <label class="form-label fw-600">Janela de envio seguro</label>
                            <div class="row g-2">
                                <div class="col">
                                    <label class="form-text">Das</label>
                                    <input type="time" name="send_window_start" class="form-control"
                                           value="{{ old('send_window_start', $automation?->send_window_start ?? '08:00') }}" required>
                                </div>
                                <div class="col">
                                    <label class="form-text">Até</label>
                                    <input type="time" name="send_window_end" class="form-control"
                                           value="{{ old('send_window_end', $automation?->send_window_end ?? '20:00') }}" required>
                                </div>
                            </div>
                            <div class="form-text">Mensagens só serão enviadas dentro deste horário.</div>
                        </div>

                        {{-- Mensagem --}}
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-600 mb-0">Mensagem <span class="text-danger">*</span></label>
                                <span class="text-muted" style="font-size:.7rem;" id="charCount">0 / 2000</span>
                            </div>
                            <textarea name="message_template" id="msgTemplate" class="form-control @error('message_template') is-invalid @enderror"
                                      rows="6" maxlength="2000" required
                                      placeholder="Ex: Olá {nome}! Sentimos sua falta. Seu apoio para a {organizacao} faz toda a diferença...">{{ old('message_template', $automation?->message_template) }}</textarea>
                            @error('message_template') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="d-flex gap-2 mt-2">
                                <button type="button" class="btn btn-xs btn-outline-secondary" style="font-size:.72rem;padding:3px 10px;"
                                        onclick="insertVar('{nome}')">+ {nome}</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary" style="font-size:.72rem;padding:3px 10px;"
                                        onclick="insertVar('{organizacao}')">+ {organizacao}</button>
                            </div>
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary btn-lg px-4 fw-bold">
                                <i class="fas fa-check me-2"></i>
                                {{ $automation ? 'Salvar alterações' : 'Criar automação' }}
                            </button>
                            <a href="{{ route('whatsapp.automations.index') }}" class="btn btn-outline-secondary btn-lg">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Painel de dicas --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4" style="background:#0f172a;color:#fff;">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:#a78bfa;font-size:.75rem;text-transform:uppercase;letter-spacing:.1em;">
                        <i class="fas fa-lightbulb me-1"></i> Como funciona
                    </h6>
                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex gap-3">
                            <div style="width:28px;height:28px;background:rgba(99,102,241,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.75rem;font-weight:800;color:#818cf8;">1</div>
                            <div>
                                <div style="font-size:.8rem;font-weight:700;color:#e2e8f0;">Você define a regra</div>
                                <div style="font-size:.72rem;color:rgba(255,255,255,.4);">Gatilho + quantos dias + mensagem personalizada.</div>
                            </div>
                        </div>
                        <div class="d-flex gap-3">
                            <div style="width:28px;height:28px;background:rgba(37,211,102,.15);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.75rem;font-weight:800;color:#4ade80;">2</div>
                            <div>
                                <div style="font-size:.8rem;font-weight:700;color:#e2e8f0;">Todo dia às 10h o sistema processa</div>
                                <div style="font-size:.72rem;color:rgba(255,255,255,.4);">Verifica quem se enquadra e envia automaticamente via WhatsApp.</div>
                            </div>
                        </div>
                        <div class="d-flex gap-3">
                            <div style="width:28px;height:28px;background:rgba(251,191,36,.15);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.75rem;font-weight:800;color:#fbbf24;">3</div>
                            <div>
                                <div style="font-size:.8rem;font-weight:700;color:#e2e8f0;">Anti-spam automático</div>
                                <div style="font-size:.72rem;color:rgba(255,255,255,.4);">Cada contato recebe no máximo 1 mensagem por automação a cada 24h.</div>
                            </div>
                        </div>
                    </div>

                    <hr style="border-color:rgba(255,255,255,.08);margin:20px 0;">

                    <h6 class="fw-bold mb-2" style="color:#a78bfa;font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;">
                        Variáveis disponíveis
                    </h6>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);">
                            <code style="color:#34d399;font-size:.78rem;">{nome}</code>
                            <span style="font-size:.72rem;color:rgba(255,255,255,.4);">Nome do contato</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);">
                            <code style="color:#34d399;font-size:.78rem;">{organizacao}</code>
                            <span style="font-size:.72rem;color:rgba(255,255,255,.4);">Nome da sua organização</span>
                        </div>
                    </div>

                    <div class="mt-3 p-3 rounded-3" style="background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.2);">
                        <p style="font-size:.72rem;color:#fbbf24;margin:0;line-height:1.6;">
                            <i class="fas fa-triangle-exclamation me-1"></i>
                            <strong>Meta 24h:</strong> Reativações após 24h de silêncio exigem Template Message aprovado pela Meta quando usar a API Oficial. Com a Evolution API não há essa restrição.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const msgEl    = document.getElementById('msgTemplate');
const countEl  = document.getElementById('charCount');

function updateCount() {
    countEl.textContent = msgEl.value.length + ' / 2000';
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
</script>

<style>.fw-600{font-weight:600;}.fw-800{font-weight:800;}</style>
@endsection
