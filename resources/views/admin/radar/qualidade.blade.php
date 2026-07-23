@extends('layouts.app')

@section('content')
@php
    $totalFeedbacks = array_sum(array_column($keywordStats, 'total')) + array_sum(array_column($sourceStats, 'total'));
    $flagRate       = round(config('radar.auto_approve.flag_nao_util_rate', 0.30) * 100);
    $minCount       = config('radar.auto_approve.min_feedback_count', 10);
    $minRate        = config('radar.auto_approve.min_util_rate', 0.70);

    // Overall util rate across all keywords
    $totalUtil    = array_sum(array_column($keywordStats, 'util_count'));
    $totalNaoUtil = array_sum(array_column($keywordStats, 'nao_util_count'));
    $totalKwFb    = $totalUtil + $totalNaoUtil;
    $overallRate  = $totalKwFb > 0 ? round($totalUtil / $totalKwFb * 100) : null;
@endphp
<div style="max-width:1100px;margin:0 auto;padding:24px 16px;">

    <div style="margin-bottom:22px;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <a href="{{ route('admin.radar.index') }}" style="color:#6366f1;font-size:0.78rem;font-weight:700;text-decoration:none;">
                <i class="fas fa-arrow-left me-1"></i>Voltar à Curadoria
            </a>
            <h2 style="margin:8px 0 3px;font-weight:950;font-size:1.6rem;letter-spacing:-1px;">
                <i class="fas fa-chart-bar me-2" style="color:#6366f1;"></i>Radar — Painel de Qualidade
            </h2>
            <p style="color:#64748b;font-size:0.84rem;margin:0;">Taxa de relevância por keyword e fonte. Sinalização quando "não útil" supera {{ $flagRate }}%.</p>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px 20px;border-top:3px solid #6366f1;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                    <div style="width:32px;height:32px;background:#ede9fe;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-magic" style="color:#6366f1;font-size:0.8rem;"></i>
                    </div>
                    <div style="font-size:0.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Auto-aprovados</div>
                </div>
                <div style="font-size:2rem;font-weight:900;color:#6366f1;line-height:1;">{{ $autoApproved }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px 20px;border-top:3px solid #f59e0b;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                    <div style="width:32px;height:32px;background:#fef3c7;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-hourglass-half" style="color:#f59e0b;font-size:0.8rem;"></i>
                    </div>
                    <div style="font-size:0.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Aguard. auto-aprov.</div>
                </div>
                <div style="font-size:2rem;font-weight:900;color:#f59e0b;line-height:1;">{{ $pendentes }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px 20px;border-top:3px solid {{ count($flaggedKeywords) > 0 ? '#ef4444' : '#10b981' }};">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                    <div style="width:32px;height:32px;background:{{ count($flaggedKeywords) > 0 ? '#fee2e2' : '#d1fae5' }};border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-{{ count($flaggedKeywords) > 0 ? 'exclamation-triangle' : 'check-circle' }}" style="color:{{ count($flaggedKeywords) > 0 ? '#ef4444' : '#10b981' }};font-size:0.8rem;"></i>
                    </div>
                    <div style="font-size:0.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Kw sinalizadas</div>
                </div>
                <div style="font-size:2rem;font-weight:900;color:{{ count($flaggedKeywords) > 0 ? '#ef4444' : '#10b981' }};line-height:1;">{{ count($flaggedKeywords) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px 20px;border-top:3px solid #0369a1;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                    <div style="width:32px;height:32px;background:#e0f2fe;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-comments" style="color:#0369a1;font-size:0.8rem;"></i>
                    </div>
                    <div style="font-size:0.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Total feedbacks</div>
                </div>
                <div style="font-size:2rem;font-weight:900;color:#0369a1;line-height:1;">{{ $totalKwFb }}</div>
            </div>
        </div>
    </div>

    {{-- Bruce IA Analysis --}}
    <div style="background:linear-gradient(135deg,#1e1b4b 0%,#312e81 55%,#4338ca 100%);border-radius:14px;padding:18px 22px;margin-bottom:22px;display:flex;align-items:center;gap:18px;flex-wrap:wrap;">
        <img src="{{ asset('img/bruce/bruceia-icone-fundo-claro.svg') }}" alt="Bruce IA"
             style="width:46px;height:46px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.2);flex-shrink:0;">
        <div style="flex:1;min-width:200px;">
            <div style="font-weight:800;font-size:0.88rem;color:#fff;margin-bottom:4px;"><i class="fas fa-robot me-2" style="color:#a5b4fc;"></i>Bruce IA — Diagnóstico do Radar</div>
            @if($totalKwFb === 0)
                <div style="font-size:0.8rem;color:#c7d2fe;">Nenhum feedback registrado ainda. Quando as ONGs começarem a avaliar os achados, mostrarei insights de qualidade aqui.</div>
            @elseif(count($flaggedKeywords) > 0)
                <div style="font-size:0.8rem;color:#fde68a;">
                    ⚠ Detectei {{ count($flaggedKeywords) }} {{ count($flaggedKeywords) === 1 ? 'keyword sinalizda' : 'keywords sinalizadas' }} com alta taxa de "não útil".
                    @if($overallRate !== null) Taxa geral útil: <strong style="color:#fff;">{{ $overallRate }}%</strong>.@endif
                    Considere ajustar as keywords sinalizadas em <code style="background:rgba(255,255,255,0.1);padding:1px 5px;border-radius:4px;">config/radar.php</code>.
                </div>
            @else
                <div style="font-size:0.8rem;color:#c7d2fe;">
                    Qualidade do radar está saudável.
                    @if($overallRate !== null) Taxa geral útil: <strong style="color:#a5f3fc;">{{ $overallRate }}%</strong>.@endif
                    {{ $totalKwFb }} {{ $totalKwFb === 1 ? 'feedback recebido' : 'feedbacks recebidos' }} no total.
                    @if($autoApproved > 0) <strong style="color:#fff;">{{ $autoApproved }} achados auto-aprovados.</strong>@endif
                </div>
            @endif
        </div>
        @if($overallRate !== null)
        <div style="text-align:center;background:rgba(255,255,255,0.1);border-radius:12px;padding:12px 20px;flex-shrink:0;">
            <div style="font-size:1.8rem;font-weight:900;color:{{ $overallRate >= 70 ? '#6ee7b7' : ($overallRate >= 50 ? '#fde68a' : '#fca5a5') }};line-height:1;">{{ $overallRate }}%</div>
            <div style="font-size:0.65rem;color:#c7d2fe;text-transform:uppercase;letter-spacing:.07em;margin-top:3px;">Taxa útil geral</div>
        </div>
        @endif
    </div>

    {{-- Keywords sinalizadas --}}
    @if(count($flaggedKeywords) > 0)
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:13px;padding:16px 20px;margin-bottom:22px;">
        <div style="font-weight:800;color:#991b1b;margin-bottom:8px;font-size:0.88rem;"><i class="fas fa-exclamation-triangle me-2"></i>Keywords para revisão</div>
        @foreach($flaggedKeywords as $kw => $s)
        <div style="font-size:0.83rem;color:#7f1d1d;margin-bottom:4px;">
            <strong>{{ $kw }}</strong> — {{ round($s['nao_util_rate'] * 100) }}% não útil ({{ $s['nao_util_count'] }}/{{ $s['total'] }})
        </div>
        @endforeach
        <div style="font-size:0.76rem;color:#b91c1c;margin-top:8px;">Remova ou ajuste em <code>config/radar.php</code> → <code>keywords</code>.</div>
    </div>
    @endif

    {{-- Por keyword --}}
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;margin-bottom:22px;">
        <div style="padding:14px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
            <div style="font-weight:800;font-size:0.88rem;color:#1e293b;"><i class="fas fa-tag me-2" style="color:#6366f1;"></i>Qualidade por Palavra-chave</div>
            <span style="font-size:0.72rem;color:#94a3b8;">Min. {{ $minCount }} fb · min. {{ round($minRate * 100) }}% útil p/ auto-aprov.</span>
        </div>
        @if(empty($keywordStats))
            <div style="padding:32px;text-align:center;color:#94a3b8;font-size:0.85rem;">Nenhum feedback ainda.</div>
        @else
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;min-width:580px;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:10px 20px;text-align:left;font-size:0.72rem;font-weight:700;color:#64748b;text-transform:uppercase;white-space:nowrap;">Keyword</th>
                    <th style="padding:10px 14px;text-align:center;font-size:0.72rem;font-weight:700;color:#64748b;text-transform:uppercase;">Total</th>
                    <th style="padding:10px 14px;text-align:center;font-size:0.72rem;font-weight:700;color:#10b981;text-transform:uppercase;">Útil</th>
                    <th style="padding:10px 14px;text-align:center;font-size:0.72rem;font-weight:700;color:#ef4444;text-transform:uppercase;">Não útil</th>
                    <th style="padding:10px 20px;text-align:left;font-size:0.72rem;font-weight:700;color:#64748b;text-transform:uppercase;min-width:160px;">Taxa útil</th>
                    <th style="padding:10px 14px;text-align:center;font-size:0.72rem;font-weight:700;color:#64748b;text-transform:uppercase;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($keywordStats as $kw => $s)
                @php
                    $utilPct  = round($s['util_rate'] * 100);
                    $barColor = $utilPct >= 70 ? '#10b981' : ($utilPct >= 50 ? '#f59e0b' : '#ef4444');
                    $eligible = $s['total'] >= $minCount && $s['util_rate'] >= $minRate;
                @endphp
                <tr style="border-top:1px solid #f1f5f9;{{ $s['flagged'] ? 'background:#fff9f9;' : '' }}">
                    <td style="padding:12px 20px;font-size:0.83rem;font-weight:600;color:#1e293b;">
                        {{ $kw }}
                        @if($s['flagged'])
                            <span style="font-size:0.65rem;color:#ef4444;font-weight:700;margin-left:5px;">⚠ SINALIZADA</span>
                        @endif
                    </td>
                    <td style="padding:12px 14px;text-align:center;font-size:0.83rem;color:#374151;font-weight:700;">{{ $s['total'] }}</td>
                    <td style="padding:12px 14px;text-align:center;font-size:0.83rem;color:#10b981;font-weight:700;">{{ $s['util_count'] }}</td>
                    <td style="padding:12px 14px;text-align:center;font-size:0.83rem;color:#ef4444;font-weight:700;">{{ $s['nao_util_count'] }}</td>
                    <td style="padding:12px 20px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="flex:1;background:#f1f5f9;border-radius:4px;height:6px;overflow:hidden;">
                                <div style="width:{{ $utilPct }}%;background:{{ $barColor }};height:100%;border-radius:4px;"></div>
                            </div>
                            <span style="font-size:0.78rem;font-weight:800;color:{{ $barColor }};min-width:36px;">{{ $utilPct }}%</span>
                        </div>
                    </td>
                    <td style="padding:12px 14px;text-align:center;">
                        @if($eligible)
                            <span style="font-size:0.68rem;background:#d1fae5;color:#065f46;padding:2px 9px;border-radius:10px;font-weight:700;white-space:nowrap;">✓ Auto-aprov.</span>
                        @elseif($s['total'] < $minCount)
                            <span style="font-size:0.68rem;background:#f1f5f9;color:#94a3b8;padding:2px 9px;border-radius:10px;white-space:nowrap;">Aguard. volume</span>
                        @else
                            <span style="font-size:0.68rem;background:#fee2e2;color:#991b1b;padding:2px 9px;border-radius:10px;font-weight:700;">Manual</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>

    {{-- Por fonte --}}
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
        <div style="padding:14px 20px;border-bottom:1px solid #f1f5f9;">
            <div style="font-weight:800;font-size:0.88rem;color:#1e293b;"><i class="fas fa-database me-2" style="color:#6366f1;"></i>Qualidade por Fonte</div>
        </div>
        @if(empty($sourceStats))
            <div style="padding:32px;text-align:center;color:#94a3b8;font-size:0.85rem;">Nenhum feedback ainda.</div>
        @else
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;min-width:480px;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:10px 20px;text-align:left;font-size:0.72rem;font-weight:700;color:#64748b;text-transform:uppercase;">Fonte</th>
                    <th style="padding:10px 14px;text-align:center;font-size:0.72rem;font-weight:700;color:#64748b;text-transform:uppercase;">Total</th>
                    <th style="padding:10px 14px;text-align:center;font-size:0.72rem;font-weight:700;color:#10b981;text-transform:uppercase;">Útil</th>
                    <th style="padding:10px 14px;text-align:center;font-size:0.72rem;font-weight:700;color:#ef4444;text-transform:uppercase;">Não útil</th>
                    <th style="padding:10px 20px;text-align:left;font-size:0.72rem;font-weight:700;color:#64748b;text-transform:uppercase;min-width:160px;">Taxa útil</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sourceStats as $src => $s)
                @php
                    $utilPct  = round($s['util_rate'] * 100);
                    $barColor = $utilPct >= 70 ? '#10b981' : ($utilPct >= 50 ? '#f59e0b' : '#ef4444');
                    $srcLabel = $src === 'transferegov' ? 'Transferegov' : 'Querido Diário';
                @endphp
                <tr style="border-top:1px solid #f1f5f9;">
                    <td style="padding:12px 20px;font-size:0.83rem;font-weight:700;color:#1e293b;">{{ $srcLabel }}</td>
                    <td style="padding:12px 14px;text-align:center;font-size:0.83rem;color:#374151;font-weight:700;">{{ $s['total'] }}</td>
                    <td style="padding:12px 14px;text-align:center;font-size:0.83rem;color:#10b981;font-weight:700;">{{ $s['util_count'] }}</td>
                    <td style="padding:12px 14px;text-align:center;font-size:0.83rem;color:#ef4444;font-weight:700;">{{ $s['nao_util_count'] }}</td>
                    <td style="padding:12px 20px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="flex:1;background:#f1f5f9;border-radius:4px;height:6px;overflow:hidden;">
                                <div style="width:{{ $utilPct }}%;background:{{ $barColor }};height:100%;border-radius:4px;"></div>
                            </div>
                            <span style="font-size:0.78rem;font-weight:800;color:{{ $barColor }};min-width:36px;">{{ $utilPct }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>

</div>
@endsection
