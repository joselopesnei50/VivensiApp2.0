@php
    // Espera $project. Nao renderiza nada se o projeto nao usa stages.
    $__stage = $project->current_stage ?? null;
@endphp

@if($__stage)
    @php
        $__prog = $__stage->progress_percent;
        $__basePath = rtrim(request()->getBaseUrl(), '/');
    @endphp
    <a href="{{ $__basePath . '/projects/' . $project->id . '/stages' }}"
       style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
              background:linear-gradient(90deg,#dbeafe 0%,#eef2ff 100%);
              border:1px solid #93c5fd; border-radius:16px; padding:14px 20px;
              margin-bottom:24px; text-decoration:none; color:#0f172a; font-weight:800;">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="background:#2563eb; color:white; width:36px; height:36px; border-radius:12px; display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-play"></i>
            </div>
            <div>
                <div style="font-size:.7rem; color:#1e3a8a; letter-spacing:1.2px; text-transform:uppercase;">Etapa em andamento</div>
                <div style="font-size:1.05rem; color:#0f172a; font-weight:900;">{{ $__stage->title }}</div>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap; font-size:.85rem; color:#1e3a8a;">
            @if($__stage->start_date && $__stage->end_date)
                <span><i class="far fa-calendar me-1"></i> {{ $__stage->start_date->format('d/m') }} → {{ $__stage->end_date->format('d/m/Y') }}</span>
            @endif
            @if($__prog !== null)
                <span><i class="fas fa-tasks me-1"></i> {{ $__prog }}%</span>
            @endif
            <span style="color:#0f172a; font-weight:900;">R$ {{ number_format((float) $__stage->planned_value, 2, ',', '.') }}</span>
            <span style="color:#2563eb;"><i class="fas fa-arrow-right"></i></span>
        </div>
    </a>
@endif
