@php $c = $section->content ?? []; @endphp

<div class="section-wrapper"
     data-section-id="{{ $section->id }}"
     data-section-type="{{ $section->type }}"
     data-content="{{ json_encode($c) }}"
     onclick="selectSection(this, {{ $section->id }})">

    {{-- Controles flutuantes (visíveis no hover/active) --}}
    <div class="section-controls">
        <button class="sc-btn sc-btn-del"
                onclick="event.stopPropagation();deleteSection({{ $section->id }})"
                title="Remover bloco">
            <i class="fas fa-trash"></i>
        </button>
    </div>

    {{-- Inner container — alvo do update via AJAX --}}
    <div id="si{{ $section->id }}" style="width:100%;height:100%;">
        @include('banners.partials.section_html', [
            'c'      => $c,
            'type'   => $section->type,
            'banner' => $banner ?? $section->banner,
        ])
    </div>
</div>
