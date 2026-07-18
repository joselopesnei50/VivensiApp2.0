@php
    // Espera $project. Renderiza mini painel com contagem, alocacao e saldo do
    // budget contra a soma de planned_value das etapas nao canceladas.
    $__stages       = $project->stages()->get();
    $__hasStages    = $__stages->isNotEmpty();
    $__basePath     = rtrim(request()->getBaseUrl(), '/');
    $__canManage    = in_array(auth()->user()->role ?? '', ['manager', 'super_admin', 'ngo'], true);

    if ($__hasStages) {
        $__inProgress = $__stages->where('status', 'in_progress')->count();
        $__completed  = $__stages->where('status', 'completed')->count();
        $__allocated  = (float) $__stages->where('status', '!=', 'cancelled')->sum('planned_value');
        $__budget     = (float) ($project->budget ?? 0);
        $__saldo      = $__budget - $__allocated;
        $__pctAlloc   = $__budget > 0 ? min(100, (int) round(($__allocated / $__budget) * 100)) : 0;
        $__overBudget = $__budget > 0 && $__allocated > $__budget + 0.01;
    }
@endphp

@if($__hasStages)
    <div style="background:white; border:1px solid #e2e8f0; border-radius:20px; padding:18px 22px; margin-bottom:24px; box-shadow:0 2px 12px rgba(15,23,42,.04);">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="background:#0f172a; color:white; width:34px; height:34px; border-radius:10px; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <div style="font-size:.7rem; color:#64748b; letter-spacing:1.2px; text-transform:uppercase; font-weight:800;">Etapas do projeto</div>
                    <div style="font-weight:900; color:#0f172a; font-size:1.05rem;">
                        {{ $__stages->count() }} {{ $__stages->count() === 1 ? 'etapa' : 'etapas' }}
                        <span style="font-weight:700; color:#64748b; font-size:.9rem;">·
                            {{ $__inProgress }} em andamento · {{ $__completed }} concluída{{ $__completed === 1 ? '' : 's' }}
                        </span>
                    </div>
                </div>
            </div>
            <a href="{{ $__basePath . '/projects/' . $project->id . '/stages' }}"
               style="background:#0f172a; color:white; padding:8px 14px; border-radius:10px; font-weight:800; font-size:.82rem; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                <i class="fas fa-arrow-right"></i> Gerenciar etapas
            </a>
        </div>

        @if($__budget > 0)
            <div style="height:8px; background:#e2e8f0; border-radius:99px; overflow:hidden; margin-bottom:8px;">
                <div style="height:100%; width: {{ $__pctAlloc }}%; background: {{ $__overBudget ? '#dc2626' : ($__pctAlloc >= 90 ? '#f59e0b' : 'linear-gradient(90deg,#2563eb,#059669)') }};"></div>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; font-size:.85rem;">
                <div style="color:#475569; font-weight:700;">
                    <strong style="color:#0f172a;">R$ {{ number_format($__allocated, 2, ',', '.') }}</strong> alocado em etapas
                    <span style="color:#94a3b8;">de R$ {{ number_format($__budget, 2, ',', '.') }} do orçamento</span>
                </div>
                <div>
                    @if($__overBudget)
                        <span style="color:#dc2626; font-weight:900;">Ultrapassou em R$ {{ number_format(abs($__saldo), 2, ',', '.') }}</span>
                    @else
                        <span style="color:{{ $__saldo > 0 ? '#059669' : '#0f172a' }}; font-weight:900;">
                            Saldo disponível: R$ {{ number_format($__saldo, 2, ',', '.') }}
                        </span>
                    @endif
                </div>
            </div>
        @else
            <div style="font-size:.85rem; color:#64748b; font-style:italic;">
                Sem orçamento cadastrado no projeto — defina o valor previsto de cada etapa e o total sai automaticamente da lista.
            </div>
        @endif
    </div>
@elseif($__canManage)
    <a href="{{ $__basePath . '/projects/' . $project->id . '/stages' }}"
       style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
              background:#f8fafc; border:2px dashed #cbd5e1; border-radius:20px; padding:16px 22px;
              margin-bottom:24px; text-decoration:none; color:#334155; font-weight:800;">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="background:#e2e8f0; color:#0f172a; width:34px; height:34px; border-radius:10px; display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-layer-group"></i>
            </div>
            <div>
                <div style="color:#0f172a; font-size:1rem;">Divida este projeto em etapas</div>
                <div style="color:#64748b; font-weight:600; font-size:.82rem;">Cada etapa tem prazo, valor previsto e tarefas próprias — ideal para editais, obras e produções.</div>
            </div>
        </div>
        <span style="color:#0f172a;"><i class="fas fa-arrow-right"></i></span>
    </a>
@endif
