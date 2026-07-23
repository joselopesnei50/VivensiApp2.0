@extends('layouts.app')

@section('content')
@php
    $highRelevance  = $matches->filter(fn($m) => $m->score >= 70)->count();
    $withDeadline   = $matches->filter(function($m) {
        $d = $m->finding?->deadline;
        return $d && $d->isFuture() && now()->diffInDays($d, false) <= 30;
    })->count();
    $totalMatches   = $matches->total();
    $hasConfig      = $tenant->radar_ibge_code || !empty($tenant->radar_areas);
    $configOpen     = !$tenant->radar_ibge_code;
@endphp
<div style="max-width:1100px;margin:0 auto;padding:24px 16px;">

    <div style="margin-bottom:20px;">
        <h2 style="margin:0 0 4px;font-weight:950;font-size:1.6rem;letter-spacing:-1px;">
            <i class="fas fa-satellite-dish me-2" style="color:#3b82f6;"></i>Radar de Editais
        </h2>
        <p style="color:#64748b;font-size:0.85rem;margin:0;">Chamamentos públicos e editais relevantes para a sua organização, atualizados diariamente.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Bruce IA Insights --}}
    <div style="background:linear-gradient(135deg,#1e1b4b 0%,#312e81 55%,#4338ca 100%);border-radius:16px;padding:20px 24px;margin-bottom:22px;display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
        <div style="flex-shrink:0;">
            <img src="{{ asset('img/bruce/bruceia-icone-fundo-claro.svg') }}" alt="Bruce IA"
                 style="width:54px;height:54px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.2);box-shadow:0 0 20px rgba(99,102,241,0.4);">
        </div>
        <div style="flex:1;min-width:180px;">
            <div style="font-weight:800;font-size:0.92rem;color:#fff;margin-bottom:4px;">
                <i class="fas fa-robot me-2" style="color:#a5b4fc;font-size:0.8rem;"></i>Bruce IA — Análise do Radar
            </div>
            @if(!$hasConfig)
                <div style="font-size:0.82rem;color:#c7d2fe;line-height:1.5;">
                    Configure seu município e áreas de atuação para que eu encontre os editais mais relevantes para a sua organização.
                </div>
            @elseif($totalMatches === 0)
                <div style="font-size:0.82rem;color:#c7d2fe;line-height:1.5;">
                    Perfil configurado. Nenhum edital encontrado ainda — a coleta acontece diariamente às 06h. Volte amanhã.
                </div>
            @else
                <div style="font-size:0.82rem;color:#c7d2fe;line-height:1.6;">
                    Encontrei <strong style="color:#fff;">{{ $totalMatches }} {{ $totalMatches === 1 ? 'edital' : 'editais' }}</strong> para o seu perfil.
                    @if($highRelevance > 0)
                        <strong style="color:#a5f3fc;">{{ $highRelevance }} {{ $highRelevance === 1 ? 'é de alta relevância' : 'são de alta relevância' }}</strong> (score ≥ 70%).
                    @endif
                    @if($withDeadline > 0)
                        <strong style="color:#fde68a;">⚠ {{ $withDeadline }} {{ $withDeadline === 1 ? 'tem prazo' : 'têm prazo' }} nos próximos 30 dias.</strong>
                    @endif
                </div>
            @endif
        </div>
        @if($hasConfig && $totalMatches > 0)
        <div style="display:flex;gap:10px;flex-wrap:wrap;flex-shrink:0;">
            <div style="text-align:center;background:rgba(255,255,255,0.1);border-radius:12px;padding:10px 16px;min-width:64px;">
                <div style="font-size:1.5rem;font-weight:900;color:#fff;line-height:1;">{{ $totalMatches }}</div>
                <div style="font-size:0.65rem;color:#c7d2fe;text-transform:uppercase;letter-spacing:.07em;margin-top:3px;">Editais</div>
            </div>
            @if($highRelevance > 0)
            <div style="text-align:center;background:rgba(16,185,129,0.18);border:1px solid rgba(16,185,129,0.3);border-radius:12px;padding:10px 16px;min-width:64px;">
                <div style="font-size:1.5rem;font-weight:900;color:#6ee7b7;line-height:1;">{{ $highRelevance }}</div>
                <div style="font-size:0.65rem;color:#a7f3d0;text-transform:uppercase;letter-spacing:.07em;margin-top:3px;">Alta relev.</div>
            </div>
            @endif
            @if($withDeadline > 0)
            <div style="text-align:center;background:rgba(251,191,36,0.18);border:1px solid rgba(251,191,36,0.3);border-radius:12px;padding:10px 16px;min-width:64px;">
                <div style="font-size:1.5rem;font-weight:900;color:#fde68a;line-height:1;">{{ $withDeadline }}</div>
                <div style="font-size:0.65rem;color:#fef08a;text-transform:uppercase;letter-spacing:.07em;margin-top:3px;">Prazo próx.</div>
            </div>
            @endif
        </div>
        @endif
    </div>

    {{-- Config Panel --}}
    @if(auth()->user()->role === 'ngo')
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;margin-bottom:22px;overflow:hidden;">
        <button onclick="var p=document.getElementById('radarCfg');p.style.display=p.style.display==='none'?'block':'none';this.querySelector('.cfgArrow').style.transform=p.style.display==='none'?'':'rotate(180deg)'"
                style="width:100%;background:none;border:none;padding:13px 20px;display:flex;align-items:center;gap:10px;cursor:pointer;text-align:left;">
            <i class="fas fa-sliders-h" style="color:#6366f1;font-size:0.85rem;"></i>
            <span style="font-weight:800;font-size:0.87rem;color:#1e293b;">Configurar perfil de busca</span>
            @if($tenant->radar_ibge_code)
                <span style="font-size:0.75rem;color:#10b981;margin-left:2px;"><i class="fas fa-check-circle me-1"></i>IBGE {{ $tenant->radar_ibge_code }}</span>
            @else
                <span style="font-size:0.75rem;color:#f59e0b;margin-left:2px;"><i class="fas fa-exclamation-circle me-1"></i>Sem município</span>
            @endif
            <i class="fas fa-chevron-down cfgArrow" style="margin-left:auto;color:#94a3b8;font-size:0.72rem;transition:transform .2s;{{ $configOpen ? 'transform:rotate(180deg)' : '' }}"></i>
        </button>
        <div id="radarCfg" style="display:{{ $configOpen ? 'block' : 'none' }};border-top:1px solid #e2e8f0;padding:20px;">

            {{-- Banner Bruce IA ajuda --}}
            <a href="{{ route('ngo.radar.ajuda') }}"
               style="display:flex;align-items:center;gap:12px;background:linear-gradient(135deg,#ede9fe,#e0f2fe);border:1px solid #c4b5fd;border-radius:10px;padding:11px 16px;margin-bottom:18px;text-decoration:none;">
                <img src="{{ asset('img/bruce/bruceia-icone-fundo-claro.svg') }}" alt="Bruce IA"
                     style="width:34px;height:34px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid #a5b4fc;">
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:800;font-size:0.82rem;color:#3730a3;">Bruce IA — Guia de configuração</div>
                    <div style="font-size:0.75rem;color:#5b21b6;margin-top:1px;">Como encontrar seu IBGE, quais áreas informar e como o Radar funciona.</div>
                </div>
                <i class="fas fa-arrow-right" style="color:#6366f1;font-size:0.8rem;flex-shrink:0;"></i>
            </a>

            <form action="{{ route('ngo.radar.configurar') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label style="font-size:0.78rem;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">Código IBGE do Município</label>
                        <input type="text" name="radar_ibge_code" value="{{ $tenant->radar_ibge_code ?? '' }}"
                               placeholder="Ex: 3550308" maxlength="7"
                               style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:0.85rem;">
                        <div style="font-size:0.7rem;color:#94a3b8;margin-top:3px;">7 dígitos — ibge.gov.br</div>
                    </div>
                    <div class="col-md-5">
                        <label style="font-size:0.78rem;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">Áreas de Atuação <span style="font-weight:400;">(uma por linha)</span></label>
                        <textarea name="radar_areas" rows="3"
                                  placeholder="assistência social&#10;criança e adolescente&#10;idoso"
                                  style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:0.85rem;resize:vertical;">{{ implode("\n", $tenant->radar_areas ?? []) }}</textarea>
                    </div>
                    <div class="col-md-3">
                        <label style="font-size:0.78rem;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">Digest semanal</label>
                        <select name="radar_digest_channel" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:0.85rem;background:#fff;">
                            <option value="desligado" {{ !$tenant->radar_digest_channel ? 'selected' : '' }}>Desligado</option>
                            <option value="email"     {{ $tenant->radar_digest_channel === 'email'    ? 'selected' : '' }}>E-mail (semanal)</option>
                            <option value="whatsapp"  {{ $tenant->radar_digest_channel === 'whatsapp' ? 'selected' : '' }}>WhatsApp (semanal)</option>
                        </select>
                        <div style="margin-top:8px;">
                            <label style="font-size:0.78rem;font-weight:700;color:#64748b;">Score mínimo</label>
                            <input type="number" name="radar_min_score" value="{{ $tenant->radar_min_score ?? 30 }}"
                                   min="0" max="100"
                                   style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:6px 10px;font-size:0.85rem;margin-top:3px;">
                        </div>
                    </div>
                    <div class="col-12 d-flex align-items-center gap-3 flex-wrap">
                        <button type="submit" style="background:#3b82f6;color:#fff;border:none;border-radius:8px;padding:9px 22px;font-weight:700;font-size:0.85rem;cursor:pointer;">
                            <i class="fas fa-save me-1"></i>Salvar configurações
                        </button>
                        @if($tenant->radar_last_digest_at)
                        <span style="font-size:0.75rem;color:#94a3b8;">
                            <i class="fas fa-clock me-1"></i>Último digest: {{ $tenant->radar_last_digest_at->format('d/m/Y H:i') }}
                        </span>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Empty states --}}
    @if(!$hasConfig)
        <div style="text-align:center;padding:64px 20px;background:#fff;border:1px dashed #c7d2fe;border-radius:16px;">
            <i class="fas fa-satellite-dish" style="font-size:2.8rem;margin-bottom:14px;display:block;color:#a5b4fc;"></i>
            <p style="font-size:0.95rem;font-weight:800;color:#1e293b;margin-bottom:6px;">Configure seu perfil para começar</p>
            <p style="font-size:0.85rem;color:#64748b;margin:0;">Informe o IBGE do município e as áreas de atuação no painel acima.</p>
        </div>
    @elseif($matches->isEmpty())
        <div style="text-align:center;padding:64px 20px;background:#fff;border:1px solid #e2e8f0;border-radius:16px;">
            <i class="fas fa-satellite-dish" style="font-size:2.8rem;margin-bottom:14px;display:block;color:#c7d2fe;"></i>
            <p style="font-size:0.95rem;font-weight:800;color:#1e293b;margin-bottom:6px;">Nenhum edital encontrado ainda</p>
            <p style="font-size:0.85rem;color:#64748b;margin:0;">A coleta acontece diariamente às 06h. Volte amanhã ou aguarde o primeiro ciclo.</p>
        </div>
    @else
        <div style="font-size:0.8rem;color:#64748b;margin-bottom:14px;font-weight:600;">
            {{ $matches->total() }} {{ $matches->total() === 1 ? 'edital encontrado' : 'editais encontrados' }}
        </div>

        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach($matches as $match)
            @php
                $finding      = $match->finding;
                $fb           = $feedbacks[$finding->id] ?? null;
                $sourceLabel  = match($finding->source) {
                    'querido_diario' => 'Querido Diário',
                    'transferegov'   => 'Transferegov',
                    default          => $finding->source,
                };
                $scoreColor   = $match->score >= 70 ? '#10b981' : ($match->score >= 40 ? '#f59e0b' : '#94a3b8');
                $deadlineDays = $finding->deadline ? (int) now()->diffInDays($finding->deadline, false) : null;
                $deadlineUrgent = $deadlineDays !== null && $deadlineDays >= 0 && $deadlineDays <= 30;
                $deadlineExpired = $deadlineDays !== null && $deadlineDays < 0;
            @endphp
            <div style="background:#fff;border:1px solid {{ $deadlineUrgent ? '#fcd34d' : '#e2e8f0' }};border-radius:14px;padding:16px 20px;border-left:4px solid {{ $scoreColor }};transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.06)'" onmouseout="this.style.boxShadow='none'">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div class="flex-grow-1" style="min-width:0;">

                        {{-- Badges --}}
                        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                            <span style="font-size:0.73rem;font-weight:800;padding:3px 10px;border-radius:20px;background:{{ $scoreColor }}18;color:{{ $scoreColor }};border:1px solid {{ $scoreColor }}40;">
                                {{ $match->score }}% relev.
                            </span>
                            <span style="font-size:0.7rem;font-weight:600;color:#6366f1;background:#ede9fe;padding:2px 8px;border-radius:8px;">
                                {{ $sourceLabel }}
                            </span>
                            @if($finding->value_total)
                            <span style="font-size:0.7rem;font-weight:700;color:#0369a1;background:#e0f2fe;padding:2px 8px;border-radius:8px;">
                                R$ {{ number_format($finding->value_total, 0, ',', '.') }}
                            </span>
                            @endif
                            @if($finding->deadline && !$deadlineExpired)
                            <span style="font-size:0.7rem;font-weight:700;color:{{ $deadlineUrgent ? '#92400e' : '#374151' }};background:{{ $deadlineUrgent ? '#fef3c7' : '#f8fafc' }};padding:2px 8px;border-radius:8px;{{ $deadlineUrgent ? 'border:1px solid #fcd34d;' : '' }}">
                                <i class="fas fa-clock me-1"></i>{{ $finding->deadline->format('d/m/Y') }}{{ $deadlineUrgent ? ' · ' . $deadlineDays . 'd' : '' }}
                            </span>
                            @elseif($deadlineExpired)
                            <span style="font-size:0.7rem;font-weight:600;color:#9ca3af;background:#f3f4f6;padding:2px 8px;border-radius:8px;text-decoration:line-through;">
                                Prazo encerrado
                            </span>
                            @endif
                        </div>

                        <h6 style="font-weight:800;font-size:0.92rem;color:#1e293b;margin:0 0 5px;line-height:1.35;">{{ $finding->title }}</h6>

                        {{-- AI summary or excerpt --}}
                        @if($finding->object_summary)
                        <p style="color:#374151;font-size:0.81rem;line-height:1.55;margin-bottom:7px;">
                            <i class="fas fa-robot me-1" style="color:#6366f1;font-size:0.68rem;"></i><em>{{ $finding->object_summary }}</em>
                        </p>
                        @else
                        <p style="color:#64748b;font-size:0.81rem;line-height:1.55;margin-bottom:7px;">
                            {{ Str::limit($finding->excerpt, 260) }}
                        </p>
                        @endif

                        {{-- Areas --}}
                        @if($finding->areas)
                        <div style="margin-bottom:9px;display:flex;flex-wrap:wrap;gap:4px;">
                            @foreach($finding->areas as $area)
                                <span style="background:#ede9fe;color:#4c1d95;padding:2px 8px;border-radius:8px;font-size:0.68rem;font-weight:600;">{{ $area }}</span>
                            @endforeach
                        </div>
                        @endif

                        {{-- Score bar --}}
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:9px;">
                            <div style="width:120px;background:#f1f5f9;border-radius:4px;height:4px;overflow:hidden;flex-shrink:0;">
                                <div style="width:{{ $match->score }}%;background:{{ $scoreColor }};height:100%;border-radius:4px;"></div>
                            </div>
                            @if($match->score_reasons)
                            <span style="font-size:0.68rem;color:#94a3b8;">{{ implode(' · ', $match->score_reasons) }}</span>
                            @endif
                        </div>

                        {{-- Meta --}}
                        <div class="d-flex gap-3 flex-wrap align-items-center" style="font-size:0.73rem;color:#94a3b8;">
                            <span><i class="fas fa-calendar me-1"></i>{{ $finding->published_at?->format('d/m/Y') ?? '—' }}</span>
                            @if($finding->territory_ibge)
                                <span><i class="fas fa-map-marker-alt me-1"></i>{{ $finding->territory_ibge }}</span>
                            @endif
                            @if($finding->keyword_matched)
                                <span><i class="fas fa-tag me-1"></i>{{ $finding->keyword_matched }}</span>
                            @endif
                            <a href="{{ $finding->source_url }}" target="_blank" rel="noopener noreferrer"
                               style="color:#3b82f6;text-decoration:none;font-weight:700;">
                                <i class="fas fa-external-link-alt me-1"></i>Ver edital
                            </a>
                        </div>
                    </div>

                    {{-- Feedback --}}
                    <div class="d-flex flex-column gap-2 flex-shrink-0" style="min-width:90px;">
                        @if($fb === 'util')
                            <div style="background:#d1fae5;border:1px solid #6ee7b7;border-radius:10px;padding:8px 12px;text-align:center;">
                                <i class="fas fa-thumbs-up" style="color:#10b981;display:block;font-size:1rem;margin-bottom:2px;"></i>
                                <span style="font-size:0.7rem;color:#065f46;font-weight:700;">Útil</span>
                            </div>
                        @elseif($fb === 'nao_util')
                            <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:10px;padding:8px 12px;text-align:center;">
                                <i class="fas fa-thumbs-down" style="color:#ef4444;display:block;font-size:1rem;margin-bottom:2px;"></i>
                                <span style="font-size:0.7rem;color:#991b1b;font-weight:700;">Não útil</span>
                            </div>
                        @else
                            <form action="{{ route('ngo.radar.feedback', $finding->id) }}" method="POST">
                                @csrf <input type="hidden" name="feedback" value="util">
                                <button type="submit" style="width:100%;background:#dcfce7;color:#166534;border:1px solid #86efac;border-radius:10px;padding:8px 10px;font-size:0.73rem;font-weight:700;cursor:pointer;text-align:center;">
                                    <i class="fas fa-thumbs-up d-block mb-1" style="font-size:0.9rem;"></i>Útil
                                </button>
                            </form>
                            <form action="{{ route('ngo.radar.feedback', $finding->id) }}" method="POST">
                                @csrf <input type="hidden" name="feedback" value="nao_util">
                                <button type="submit" style="width:100%;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:10px;padding:8px 10px;font-size:0.73rem;font-weight:700;cursor:pointer;text-align:center;">
                                    <i class="fas fa-thumbs-down d-block mb-1" style="font-size:0.9rem;"></i>Não útil
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $matches->links() }}
        </div>
    @endif

</div>
@endsection
