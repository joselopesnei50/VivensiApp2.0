{{--
    Custom fields do formulário público de landing page (2026-08-06).
    Espera:
      $customFields — array vindo de $section->content['custom_fields'] ?? []
      $cfStyle      — string CSS de estilo do input (mesma dos campos fixos)

    Cada campo: { id, key, label, type (text|email|tel|number|date|textarea|select), required, placeholder, options (string CSV OU array) }
    Todos os fields são submetidos com name="custom[<key>]" — o controller lê o schema
    da section e persiste em landing_page_leads.extra_data.custom[<key>].
--}}
@php
    $customFields = collect($customFields ?? [])
        ->filter(fn ($f) => is_array($f) && !empty($f['label']))
        ->values();
@endphp

@foreach($customFields as $cfIdx => $cf)
    @php
        $cfKey   = trim((string) ($cf['key'] ?? ''));
        if ($cfKey === '') {
            $cfKey = 'campo_' . ($cfIdx + 1);
        }
        // slug defensivo: só letras minúsculas, números e _
        $cfKey       = preg_replace('/[^a-z0-9_]+/', '_', mb_strtolower($cfKey));
        $cfKey       = trim($cfKey, '_') ?: ('campo_' . ($cfIdx + 1));
        $cfLabel     = (string) ($cf['label'] ?? '');
        $cfType      = in_array($cf['type'] ?? 'text', ['text','email','tel','number','date','textarea','select'], true) ? $cf['type'] : 'text';
        $cfRequired  = !empty($cf['required']);
        $cfPlace     = (string) ($cf['placeholder'] ?? $cfLabel);
        $cfOptions   = $cf['options'] ?? [];
        if (is_string($cfOptions)) {
            $cfOptions = array_values(array_filter(array_map('trim', explode(',', $cfOptions))));
        }
        $cfName = 'custom[' . $cfKey . ']';
    @endphp

    {{-- inputs date/select/textarea sem placeholder visivel — emite label acima --}}
    @if(in_array($cfType, ['date','select','textarea'], true))
        <label style="display:block; font-size:.78rem; margin:2px 4px 4px; font-weight:600; opacity:.85;">{{ $cfLabel }}{{ $cfRequired ? ' *' : '' }}</label>
    @endif

    @if($cfType === 'textarea')
        <textarea
            name="{{ $cfName }}"
            @if($cfRequired) required @endif
            rows="3"
            maxlength="2000"
            placeholder="{{ $cfPlace }}"
            style="{{ $cfStyle }}"
            aria-label="{{ $cfLabel }}"
        ></textarea>
    @elseif($cfType === 'select')
        <select
            name="{{ $cfName }}"
            @if($cfRequired) required @endif
            style="{{ $cfStyle }}"
            aria-label="{{ $cfLabel }}"
        >
            <option value="">{{ $cfPlace ?: $cfLabel }}</option>
            @foreach($cfOptions as $opt)
                <option value="{{ $opt }}">{{ $opt }}</option>
            @endforeach
        </select>
    @else
        <input
            type="{{ $cfType }}"
            name="{{ $cfName }}"
            @if($cfRequired) required @endif
            maxlength="500"
            placeholder="{{ $cfPlace }}"
            style="{{ $cfStyle }}"
            aria-label="{{ $cfLabel }}"
        >
    @endif
@endforeach
