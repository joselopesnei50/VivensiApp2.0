@php
    $typeBadgeColor = ['text' => '#475569', 'number' => '#0284c7', 'yes_no' => '#15803d', 'buttons' => '#7c3aed', 'list' => '#c026d3'][$q->type] ?? '#475569';
    $isMagicKey = in_array($q->field_key, \App\Http\Controllers\WhatsappFormController::RESERVED_FIELD_KEYS, true);
@endphp
<div data-question-id="{{ $q->id }}" style="border:1px solid #e2e8f0; border-radius:14px; padding:14px 16px; margin-bottom:10px; background:#fff;">
    <div style="display:flex; align-items:flex-start; gap:12px;">
        <div class="q-drag-handle" style="cursor:grab; color:#cbd5e1; font-size:1rem; padding-top:4px;">
            <i class="fas fa-grip-vertical"></i>
        </div>
        <div style="flex:1;">
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-bottom:6px;">
                <span style="background:#f1f5f9; color:#475569; font-size:.65rem; font-weight:900; padding:2px 8px; border-radius:99px;">#{{ $loop->iteration ?? '' }}</span>
                <span style="background:{{ $typeBadgeColor }}1a; color:{{ $typeBadgeColor }}; font-size:.7rem; font-weight:800; padding:3px 10px; border-radius:99px; letter-spacing:.5px; text-transform:uppercase;">{{ $q->type }}</span>
                @if($isMagicKey)
                    <span style="background:#eef2ff; color:#4338ca; font-size:.7rem; font-weight:800; padding:3px 10px; border-radius:99px;" title="Mapeia automaticamente pro CRM de Leads">
                        <i class="fas fa-wand-magic-sparkles me-1"></i> {{ $q->field_key }}
                    </span>
                @else
                    <code style="background:#f8fafc; color:#475569; font-size:.7rem; padding:2px 8px; border-radius:6px;">{{ $q->field_key }}</code>
                @endif
                @if($q->required)
                    <span style="background:#fff7ed; color:#c2410c; font-size:.65rem; font-weight:800; padding:2px 8px; border-radius:99px;">Obrigatória</span>
                @endif
            </div>
            <div style="color:#1e293b; font-weight:600;">{{ $q->text }}</div>
            @if(in_array($q->type, ['buttons', 'list']) && is_array($q->options))
                <div style="margin-top:8px; font-size:.75rem; color:#64748b;">
                    Opções:
                    @foreach($q->options as $o)
                        <span style="background:#f1f5f9; padding:2px 8px; border-radius:6px; margin-right:4px;">{{ $o['id'] ?? '?' }} = {{ $o['label'] ?? '' }}</span>
                    @endforeach
                </div>
            @endif
        </div>
        <div style="display:flex; gap:6px; align-items:center;">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick='editQuestion(@json($q, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP))' style="font-size:.72rem;">
                <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteQuestion({{ $q->id }})" style="font-size:.72rem;">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
</div>
