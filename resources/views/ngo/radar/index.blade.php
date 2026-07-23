@extends('layouts.app')

@section('content')
<div style="max-width:1100px;margin:0 auto;padding:24px 16px;">

    <div style="margin-bottom:24px;">
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

    {{-- Configuração do perfil de busca --}}
    @if(auth()->user()->role === 'admin')
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:20px;margin-bottom:28px;">
        <div style="font-weight:800;font-size:0.9rem;color:#1e293b;margin-bottom:12px;">
            <i class="fas fa-sliders-h me-2" style="color:#6366f1;"></i>Configurar perfil de busca
        </div>
        <form action="{{ route('ngo.radar.configurar') }}" method="POST">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label style="font-size:0.78rem;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">
                        Código IBGE do Município
                    </label>
                    <input type="text" name="radar_ibge_code" value="{{ $tenant->radar_ibge_code ?? '' }}"
                           placeholder="Ex: 3550308 (São Paulo)"
                           maxlength="7"
                           style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:0.85rem;">
                    <div style="font-size:0.72rem;color:#94a3b8;margin-top:3px;">7 dígitos. Busque em ibge.gov.br</div>
                </div>
                <div class="col-md-6">
                    <label style="font-size:0.78rem;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">
                        Áreas de Atuação <span style="font-weight:400;">(uma por linha)</span>
                    </label>
                    <textarea name="radar_areas" rows="3"
                              placeholder="assistência social&#10;criança e adolescente&#10;idoso"
                              style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:0.85rem;resize:vertical;">{{ implode("\n", $tenant->radar_areas ?? []) }}</textarea>
                </div>
                <div class="col-md-2">
                    <button type="submit" style="width:100%;background:#3b82f6;color:#fff;border:none;border-radius:8px;padding:10px;font-weight:700;font-size:0.85rem;cursor:pointer;">
                        Salvar
                    </button>
                </div>
            </div>
        </form>
    </div>
    @endif

    {{-- Sem configuração ainda --}}
    @if(!$tenant->radar_ibge_code && !$tenant->radar_areas)
        <div style="text-align:center;padding:60px 20px;color:#94a3b8;">
            <i class="fas fa-satellite-dish" style="font-size:3rem;margin-bottom:16px;display:block;opacity:.3;"></i>
            <p style="font-size:0.95rem;font-weight:700;color:#64748b;">Configure seu perfil para receber achados</p>
            <p style="font-size:0.85rem;">Informe o código IBGE do seu município e suas áreas de atuação acima.</p>
        </div>
    @elseif($matches->isEmpty())
        <div style="text-align:center;padding:60px 20px;color:#94a3b8;">
            <i class="fas fa-search" style="font-size:3rem;margin-bottom:16px;display:block;opacity:.3;"></i>
            <p style="font-size:0.95rem;font-weight:700;color:#64748b;">Nenhum achado disponível ainda</p>
            <p style="font-size:0.85rem;">Os achados são atualizados diariamente. Volte amanhã ou aguarde a próxima coleta.</p>
        </div>
    @else
        <div style="font-size:0.8rem;color:#64748b;margin-bottom:16px;">
            {{ $matches->total() }} {{ $matches->total() === 1 ? 'achado encontrado' : 'achados encontrados' }} para o seu perfil
        </div>

        <div style="display:flex;flex-direction:column;gap:12px;">
            @foreach($matches as $match)
            @php
                $finding     = $match->finding;
                $fb          = $feedbacks[$finding->id] ?? null;
                $sourceLabel = match($finding->source) {
                    'querido_diario' => 'Querido Diário',
                    'transferegov'   => 'Transferegov',
                    default          => $finding->source,
                };
                $scoreColor = $match->score >= 70 ? '#10b981' : ($match->score >= 40 ? '#f59e0b' : '#94a3b8');
            @endphp
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px 20px;">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div class="flex-grow-1" style="min-width:0;">
                        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                            {{-- Score --}}
                            <span style="font-size:0.75rem;font-weight:800;padding:3px 10px;border-radius:20px;background:{{ $scoreColor }}1a;color:{{ $scoreColor }};border:1px solid {{ $scoreColor }}40;">
                                {{ $match->score }}% relevante
                            </span>
                            <span style="font-size:0.72rem;font-weight:600;color:#6366f1;background:#ede9fe;padding:2px 8px;border-radius:10px;">
                                {{ $sourceLabel }}
                            </span>
                            @if($finding->keyword_matched)
                            <span style="font-size:0.72rem;color:#64748b;background:#f1f5f9;padding:2px 8px;border-radius:10px;">
                                <i class="fas fa-tag me-1"></i>{{ $finding->keyword_matched }}
                            </span>
                            @endif
                        </div>

                        <h6 style="font-weight:800;font-size:0.9rem;color:#1e293b;margin:0 0 6px;line-height:1.3;">
                            {{ $finding->title }}
                        </h6>

                        <p style="color:#64748b;font-size:0.82rem;line-height:1.6;margin-bottom:8px;">
                            {{ Str::limit($finding->excerpt, 300) }}
                        </p>

                        <div class="d-flex gap-3 flex-wrap align-items-center" style="font-size:0.75rem;color:#94a3b8;">
                            <span><i class="fas fa-calendar me-1"></i>{{ $finding->published_at ? $finding->published_at->format('d/m/Y') : '—' }}</span>
                            @if($finding->territory_ibge)
                                <span><i class="fas fa-map-marker-alt me-1"></i>IBGE {{ $finding->territory_ibge }}</span>
                            @endif
                            <a href="{{ $finding->source_url }}" target="_blank" rel="noopener noreferrer"
                               style="color:#3b82f6;text-decoration:none;font-weight:600;">
                                <i class="fas fa-external-link-alt me-1"></i>Ver edital
                            </a>

                            @if($match->score_reasons)
                            <span style="color:#94a3b8;">
                                <i class="fas fa-info-circle me-1"></i>
                                {{ implode(' · ', $match->score_reasons) }}
                            </span>
                            @endif
                        </div>
                    </div>

                    {{-- Feedback --}}
                    <div class="d-flex flex-column gap-2 flex-shrink-0">
                        @if($fb === 'util')
                            <span style="font-size:0.75rem;color:#10b981;font-weight:700;"><i class="fas fa-thumbs-up me-1"></i>Útil</span>
                        @elseif($fb === 'nao_util')
                            <span style="font-size:0.75rem;color:#ef4444;font-weight:700;"><i class="fas fa-thumbs-down me-1"></i>Não útil</span>
                        @else
                            <form action="{{ route('ngo.radar.feedback', $finding->id) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="feedback" value="util">
                                <button type="submit" style="background:#dcfce7;color:#166534;border:none;border-radius:8px;padding:5px 12px;font-size:0.75rem;font-weight:700;cursor:pointer;white-space:nowrap;">
                                    <i class="fas fa-thumbs-up me-1"></i>Útil
                                </button>
                            </form>
                            <form action="{{ route('ngo.radar.feedback', $finding->id) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="feedback" value="nao_util">
                                <button type="submit" style="background:#fee2e2;color:#991b1b;border:none;border-radius:8px;padding:5px 12px;font-size:0.75rem;font-weight:700;cursor:pointer;white-space:nowrap;">
                                    <i class="fas fa-thumbs-down me-1"></i>Não útil
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
