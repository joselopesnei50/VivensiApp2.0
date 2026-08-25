@extends('layouts.app')
@section('title', $lesson ? 'Editar licao' : 'Nova licao')

@section('content')
<div class="container" style="max-width:800px; margin:24px auto;">
    <h1 style="margin:0 0 8px; color:#0f172a;">
        📚 {{ $lesson ? 'Editar licao' : 'Nova licao' }}
    </h1>
    <p style="color:#64748b; margin:0 0 20px;">
        Cadastre uma passagem de conversa que fechou/avancou venda. Bruno aprende com ela.
    </p>

    @if ($errors->any())
        <div style="background:#fee2e2; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:16px;">
            @foreach ($errors->all() as $error)
                <div>❌ {{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ $lesson ? route('admin.bruno.lessons.update', $lesson) : route('admin.bruno.lessons.store') }}"
          style="background:#fff; padding:24px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.05);">
        @csrf
        @if($lesson) @method('PUT') @endif

        <div style="margin-bottom:16px;">
            <label style="display:block; font-weight:600; margin-bottom:6px;">Titulo <span style="color:#dc2626;">*</span></label>
            <input type="text" name="title" value="{{ old('title', $lesson?->title) }}" required maxlength="200"
                   placeholder="Ex: ONG pequena de Araraquara, objecao verba, fechou pos-storytelling"
                   style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:6px;">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block; font-weight:600; margin-bottom:6px;">Tags (separadas por virgula)</label>
            <input type="text" name="tags"
                   value="{{ old('tags', $lesson ? implode(', ', (array) $lesson->tags) : '') }}"
                   placeholder="ong-pequena, objecao-verba, radar-editais"
                   style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:6px;">
            <div style="font-size:.78rem; color:#64748b; margin-top:6px;">
                Sugeridas: <span id="tagSuggestions"></span>
            </div>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block; font-weight:600; margin-bottom:6px;">Quando aplica? <span style="color:#dc2626;">*</span></label>
            <textarea name="situation" required maxlength="2000" rows="2"
                      placeholder="Descreva a situacao: ex 'lead ONG pequena falou que nao tem verba pra sistema'"
                      style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:6px;">{{ old('situation', $lesson?->situation) }}</textarea>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block; font-weight:600; margin-bottom:6px;">O que o lead disse</label>
            <textarea name="lead_said" maxlength="2000" rows="2"
                      placeholder="Ex: 'nossa ONG tem 3 voluntarios, a gente nao consegue pagar R$ 429/mes'"
                      style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:6px;">{{ old('lead_said', $lesson?->lead_said) }}</textarea>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block; font-weight:600; margin-bottom:6px;">Sua resposta que fechou <span style="color:#dc2626;">*</span></label>
            <textarea name="bruno_replied" required maxlength="4000" rows="4"
                      placeholder="A resposta que quebrou a objecao. Ex: puxou a origem de Araraquara + propos condicao especial via Cristiane"
                      style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:6px;">{{ old('bruno_replied', $lesson?->bruno_replied) }}</textarea>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block; font-weight:600; margin-bottom:6px;">Notas internas (nao vao pro prompt)</label>
            <textarea name="notes" maxlength="2000" rows="2"
                      placeholder="Contexto que voce quer lembrar: valor final, data, quem fechou"
                      style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:6px;">{{ old('notes', $lesson?->notes) }}</textarea>
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:block; font-weight:600; margin-bottom:6px;">Tenant especifico (deixe vazio pra licao global)</label>
            <input type="number" name="tenant_id" value="{{ old('tenant_id', $lesson?->tenant_id) }}" min="1"
                   style="width:200px; padding:10px 12px; border:1px solid #d1d5db; border-radius:6px;">
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:inline-flex; align-items:center; gap:8px;">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" {{ old('active', $lesson?->active ?? true) ? 'checked' : '' }}>
                <span style="font-weight:600;">Ativa (Bruno usa)</span>
            </label>
        </div>

        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <a href="{{ route('admin.bruno.lessons.index') }}"
               style="padding:10px 18px; background:#e5e7eb; color:#374151; border-radius:8px; text-decoration:none; font-weight:600;">
                Cancelar
            </a>
            <button type="submit"
                    style="padding:10px 18px; background:#10b981; color:#fff; border:0; border-radius:8px; font-weight:600; cursor:pointer;">
                Salvar
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Renderiza chips sugeridas — click adiciona no input tags
    (function () {
        var suggested = @json($suggestedTags);
        var box = document.getElementById('tagSuggestions');
        var input = document.querySelector('input[name="tags"]');
        if (!box || !input) return;
        box.innerHTML = '';
        suggested.forEach(function (t) {
            var chip = document.createElement('span');
            chip.textContent = t;
            chip.style.cssText = 'display:inline-block; background:#eff6ff; color:#1d4ed8; padding:2px 8px; border-radius:10px; font-size:.7rem; margin:2px; cursor:pointer;';
            chip.onclick = function () {
                var current = input.value.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
                if (current.indexOf(t) === -1) {
                    current.push(t);
                    input.value = current.join(', ');
                }
            };
            box.appendChild(chip);
        });
    })();
</script>
@endpush
@endsection
