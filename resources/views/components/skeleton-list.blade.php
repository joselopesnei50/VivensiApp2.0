{{--
    Skeleton list — linhas de carregamento para listas/tabelas.
    Props: rows (default 4)
    Uso: <x-skeleton-list :rows="3" />
--}}
@props(['rows' => 4])
<div {{ $attributes }}>
    @for($i = 0; $i < $rows; $i++)
    <div style="padding:14px 0; border-bottom:1px solid rgba(255,255,255,0.04); display:flex; align-items:center; gap:12px;">
        <div class="ds-skeleton-dark" style="width:36px; height:36px; border-radius:10px; flex-shrink:0;"></div>
        <div style="flex:1;">
            <div class="ds-skeleton-dark" style="height:12px; width:{{ 50 + ($i * 10 % 30) }}%; margin-bottom:6px; border-radius:6px;"></div>
            <div class="ds-skeleton-dark" style="height:9px; width:{{ 30 + ($i * 7 % 25) }}%; border-radius:6px;"></div>
        </div>
        <div class="ds-skeleton-dark" style="height:22px; width:60px; border-radius:99px;"></div>
    </div>
    @endfor
</div>
