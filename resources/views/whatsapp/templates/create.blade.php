@extends('layouts.app')

@section('title', 'Criar template WhatsApp Cloud API')

@push('styles')
<style>
    .fcard { background: #fff; border-radius: 10px; padding: 22px; box-shadow: 0 2px 8px rgba(0,0,0,.05); margin-bottom: 16px; }
    .fgroup { margin-bottom: 16px; }
    .flabel { display: block; font-weight: 600; margin-bottom: 6px; color: #0f172a; }
    .fhint { color: #6b7280; font-size: .8rem; margin-top: 4px; }
    .finput, .fselect, .ftextarea { width: 100%; padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: .95rem; }
    .ftextarea { min-height: 120px; resize: vertical; font-family: inherit; }
    .fpreview { background: #dcf8c6; padding: 12px 16px; border-radius: 10px 10px 10px 2px; margin-top: 8px; white-space: pre-wrap; color: #0f172a; font-size: .95rem; }
    .fpreview mark { background: #fef3c7; color: #92400e; font-weight: 600; padding: 1px 4px; border-radius: 3px; }
    .fbtn { padding: 10px 18px; border-radius: 8px; font-size: .95rem; border: 0; cursor: pointer; font-weight: 600; }
    .fbtn-primary { background: #10b981; color: #fff; }
    .fbtn-secondary { background: #e5e7eb; color: #374151; }
    .fnote { background: #fffbeb; color: #92400e; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #f59e0b; font-size: .9rem; }
    .fvar-row { display: grid; grid-template-columns: 60px 1fr; gap: 12px; align-items: center; margin-bottom: 10px; }
    .fvar-badge { background: #eff6ff; color: #1d4ed8; padding: 6px 10px; border-radius: 6px; font-weight: 700; text-align: center; font-family: monospace; font-size: .9rem; }
    .fvar-empty { color: #6b7280; font-size: .85rem; font-style: italic; }
    .fsamples-header { background: #eff6ff; color: #1e3a8a; padding: 10px 14px; border-radius: 6px; margin-bottom: 12px; font-size: .85rem; border-left: 3px solid #3b82f6; }
</style>
@endpush

@section('content')
<div class="container" style="max-width: 720px; margin: 24px auto;">
    <h1 style="margin: 0 0 16px;"><i class="fab fa-whatsapp" style="color: #25d366;"></i> Novo template</h1>

    <div class="fnote">
        <strong>Atenção:</strong> a Meta analisa o template em até 24h. Templates com nomes ou conteúdo promocional podem ser rejeitados — evite palavras como "grátis", "urgente", uso excessivo de emojis, e ofertas exageradas.
    </div>

    @if ($errors->any())
        <div style="background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px;">
            @foreach ($errors->all() as $error)
                <div><i class="fas fa-times-circle"></i> {{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if (session('error'))
        <div style="background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px;">
            <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('whatsapp.templates.cloud.store') }}">
        @csrf

        <div class="fcard">
            <div class="fgroup">
                <label class="flabel" for="whatsapp_instance_id">Instância</label>
                <select name="whatsapp_instance_id" id="whatsapp_instance_id" class="fselect" required>
                    @foreach ($instances as $inst)
                        <option value="{{ $inst->id }}">{{ $inst->instance_name }} ({{ $inst->phone_number ?? 'sem número' }})</option>
                    @endforeach
                </select>
                <div class="fhint">Cada instância = uma WABA. Template é gerenciado no nível WABA.</div>
            </div>

            <div class="fgroup">
                <label class="flabel" for="name">Nome do template</label>
                <input type="text" name="name" id="name" class="finput" value="{{ old('name') }}" placeholder="order_confirmation" pattern="[a-z0-9_]+" required>
                <div class="fhint">Só letras minúsculas, números e underscore. Ex: <code>order_confirmation</code>, <code>appointment_reminder</code>.</div>
            </div>

            <div class="fgroup">
                <label class="flabel" for="language">Idioma</label>
                <select name="language" id="language" class="fselect" required>
                    <option value="pt_BR" {{ old('language', 'pt_BR') === 'pt_BR' ? 'selected' : '' }}>Português (Brasil)</option>
                    <option value="en_US" {{ old('language') === 'en_US' ? 'selected' : '' }}>Inglês (EUA)</option>
                    <option value="es_ES" {{ old('language') === 'es_ES' ? 'selected' : '' }}>Espanhol (Espanha)</option>
                    <option value="es_MX" {{ old('language') === 'es_MX' ? 'selected' : '' }}>Espanhol (México)</option>
                </select>
            </div>

            <div class="fgroup">
                <label class="flabel" for="category">Categoria</label>
                <select name="category" id="category" class="fselect" required>
                    <option value="UTILITY"        {{ old('category', 'UTILITY') === 'UTILITY' ? 'selected' : '' }}>UTILITY — confirmações, atualizações, alertas transacionais</option>
                    <option value="MARKETING"      {{ old('category') === 'MARKETING' ? 'selected' : '' }}>MARKETING — promoções, ofertas, novidades</option>
                    <option value="AUTHENTICATION" {{ old('category') === 'AUTHENTICATION' ? 'selected' : '' }}>AUTHENTICATION — códigos OTP, verificação</option>
                </select>
                <div class="fhint">Categoria afeta custo por conversa (MARKETING é mais caro).</div>
            </div>
        </div>

        <div class="fcard">
            <div class="fgroup">
                <label class="flabel" for="body">Corpo da mensagem</label>
                <textarea name="body" id="body" class="ftextarea" required maxlength="1024" placeholder="Ex: Olá! Entramos em contato com a @{{1}} para apresentar o Vivensi.">{{ old('body') }}</textarea>
                <div class="fhint">Use <code>@{{1}}</code>, <code>@{{2}}</code>, etc. para variáveis. Máximo 1024 caracteres.</div>
            </div>

            {{-- Bloco de amostras — renderizado dinamicamente por JS. Preenchido no submit
                 com o que o operador digitou. Aparece apenas quando o corpo tem @{{n}}. --}}
            <div class="fgroup" id="samples-block" style="display: none;">
                <label class="flabel">Amostras das variáveis <span style="color: #ef4444;">*</span></label>
                <div class="fsamples-header">
                    A Meta exige um exemplo real para cada variável. Sem isso o template é rejeitado com <em>"Variáveis de modelo sem texto de amostra"</em>.
                </div>
                <div id="samples-list"></div>
            </div>

            <div class="fgroup">
                <label class="flabel" for="footer">Rodapé (opcional)</label>
                <input type="text" name="footer" id="footer" class="finput" value="{{ old('footer') }}" maxlength="60" placeholder="Ex: Sua ONG · Não responder">
                <div class="fhint">Máximo 60 caracteres. Aparece em cinza abaixo do corpo.</div>
            </div>

            <div class="fgroup">
                <label class="flabel">Prévia</label>
                <div class="fpreview" id="preview">Digite o corpo pra ver a prévia...</div>
            </div>
        </div>

        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <a href="{{ route('whatsapp.templates.cloud.index') }}" class="fbtn fbtn-secondary" style="text-decoration: none; display: inline-flex; align-items: center;">Cancelar</a>
            <button type="submit" class="fbtn fbtn-primary">
                <i class="fas fa-paper-plane"></i> Enviar para aprovação Meta
            </button>
        </div>
    </form>
</div>

{{-- Estado inicial das amostras (repopular apos validation failure). Precisa
     estar fora do <script> pro Blade nao interferir com regex do JS. --}}
<script id="initial-samples" type="application/json">@json(old('variable_samples', []))</script>
@endsection

@push('scripts')
{{-- @verbatim desliga a interpretacao Blade dentro do <script>. Sem isso o
     Blade tenta compilar {{n}} que aparece em comentarios/regex do JS. --}}
@verbatim
<script>
(function () {
    const body      = document.getElementById('body');
    const footer    = document.getElementById('footer');
    const preview   = document.getElementById('preview');
    const block     = document.getElementById('samples-block');
    const list      = document.getElementById('samples-list');
    const initial   = JSON.parse(document.getElementById('initial-samples').textContent || '{}');

    // Placeholders contextuais — dicas mais uteis que "digite aqui".
    const PLACEHOLDER_HINTS = {
        1: 'Instituto Caminhos',
        2: 'Araraquara',
        3: 'R$ 429,90',
        4: '10/09/2026',
    };

    /**
     * Extrai variaveis {{n}} do corpo em ordem crescente, sem duplicatas.
     * Espelho do CloudApiTemplateService::extractVariables (backend). Se
     * mudar aqui, mude la — as duas regexes precisam bater.
     */
    function extractVariables(text) {
        const matches = [...text.matchAll(/\{\{(\d+)\}\}/g)];
        const nums = matches.map(m => parseInt(m[1], 10));
        return [...new Set(nums)].sort((a, b) => a - b);
    }

    /**
     * Escapa HTML pra render seguro no preview (o body pode conter <, >, & etc).
     */
    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[c]));
    }

    /**
     * Sincroniza os campos de amostra com as variaveis do corpo.
     * — Variavel nova: cria input vazio (ou repopula do old() se veio de validation failure)
     * — Variavel removida: remove o input correspondente
     * — Amostra ja digitada: preserva (nao apaga o que o operador digitou)
     *
     * Usa document.createElement pra evitar template literals com {{...}}
     * que colidiriam com a sintaxe de echo do Blade na compilacao.
     */
    function syncSamples() {
        const vars = extractVariables(body.value);

        // Captura valores atuais antes de re-renderizar (preserva digitacao)
        const current = {};
        list.querySelectorAll('input[data-var]').forEach(inp => {
            current[inp.dataset.var] = inp.value;
        });

        list.innerHTML = '';
        vars.forEach(n => {
            const val = current[n] ?? initial[n] ?? '';

            const row = document.createElement('div');
            row.className = 'fvar-row';

            const badge = document.createElement('div');
            badge.className = 'fvar-badge';
            badge.textContent = '{' + '{' + n + '}' + '}'; // literal {{n}} sem chamar Blade
            row.appendChild(badge);

            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'finput';
            input.name = 'variable_samples[' + n + ']';
            input.setAttribute('data-var', n);
            input.value = val;
            input.placeholder = PLACEHOLDER_HINTS[n] || 'ex: um valor real';
            input.maxLength = 255;
            input.required = true;
            row.appendChild(input);

            list.appendChild(row);
        });

        block.style.display = vars.length > 0 ? '' : 'none';
    }

    /**
     * Renderiza preview substituindo {{n}} pela amostra ou por marca de pendencia.
     */
    function updatePreview() {
        const text = body.value;
        if (!text.trim()) {
            preview.textContent = 'Digite o corpo pra ver a prévia...';
            return;
        }
        const samples = {};
        list.querySelectorAll('input[data-var]').forEach(inp => {
            samples[inp.dataset.var] = inp.value.trim();
        });

        let html = escapeHtml(text).replace(/\{\{(\d+)\}\}/g, (_, n) => {
            const v = samples[n];
            if (v) return escapeHtml(v);
            // Concat manual pra evitar {{...}} literal dentro do Blade
            return '<mark>' + '{' + '{' + n + '}' + '}' + '</mark>';
        });

        const fv = footer.value.trim();
        if (fv) html += '\n\n' + escapeHtml(fv);

        preview.innerHTML = html;
    }

    // Debounce curto pra evitar re-render a cada tecla (menos jank em corpo grande).
    let bodyTimer = null;
    body.addEventListener('input', () => {
        clearTimeout(bodyTimer);
        bodyTimer = setTimeout(() => {
            syncSamples();
            updatePreview();
        }, 200);
    });

    // Amostras e footer: sem debounce (poucos chars, feedback instantaneo)
    list.addEventListener('input', updatePreview);
    footer.addEventListener('input', updatePreview);

    // Bootstrap inicial (repopula amostras se veio de validation failure)
    syncSamples();
    updatePreview();
})();
</script>
@endverbatim
@endpush
