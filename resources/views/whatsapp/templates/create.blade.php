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
    .fbtn { padding: 10px 18px; border-radius: 8px; font-size: .95rem; border: 0; cursor: pointer; font-weight: 600; }
    .fbtn-primary { background: #10b981; color: #fff; }
    .fbtn-secondary { background: #e5e7eb; color: #374151; }
    .fnote { background: #fffbeb; color: #92400e; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid #f59e0b; font-size: .9rem; }
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
                <textarea name="body" id="body" class="ftextarea" required maxlength="1024" placeholder="Ex: Olá {{ '{{' }}1{{ '}}' }}, seu pedido {{ '{{' }}2{{ '}}' }} está pronto para retirada.">{{ old('body') }}</textarea>
                <div class="fhint">Use <code>{{ '{{' }}1{{ '}}' }}</code>, <code>{{ '{{' }}2{{ '}}' }}</code>, etc. para variáveis. Máximo 1024 caracteres.</div>
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
@endsection

@push('scripts')
<script>
    const body   = document.getElementById('body');
    const footer = document.getElementById('footer');
    const prev   = document.getElementById('preview');

    function updatePreview() {
        let text = body.value.trim() || 'Digite o corpo pra ver a prévia...';
        if (footer.value.trim()) {
            text += '\n\n' + footer.value.trim();
        }
        prev.textContent = text;
    }

    body.addEventListener('input', updatePreview);
    footer.addEventListener('input', updatePreview);
</script>
@endpush
