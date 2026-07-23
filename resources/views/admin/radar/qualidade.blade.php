@extends('layouts.app')

@section('content')
<div style="max-width:1100px;margin:0 auto;padding:24px 16px;">

    <div style="margin-bottom:24px;">
        <a href="{{ route('admin.radar.index') }}" style="color:#6366f1;font-size:0.8rem;font-weight:700;text-decoration:none;">
            <i class="fas fa-arrow-left me-1"></i> Voltar à Curadoria
        </a>
        <h2 style="margin:10px 0 4px;font-weight:950;font-size:1.6rem;letter-spacing:-1px;">
            <i class="fas fa-chart-bar me-2" style="color:#6366f1;"></i>Radar — Painel de Qualidade
        </h2>
        <p style="color:#64748b;font-size:0.85rem;">Taxa de relevância por keyword e fonte. Sinalização automática quando 'não útil' supera {{ round(config('radar.auto_approve.flag_nao_util_rate', 0.30) * 100) }}%.</p>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;text-align:center;">
                <div style="font-size:2rem;font-weight:900;color:#6366f1;">{{ $autoApproved }}</div>
                <div style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-top:4px;">Auto-aprovados</div>
            </div>
        </div>
        <div class="col-md-3">
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;text-align:center;">
                <div style="font-size:2rem;font-weight:900;color:#f59e0b;">{{ $pendentes }}</div>
                <div style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-top:4px;">Aguardando auto-aprob.</div>
            </div>
        </div>
        <div class="col-md-3">
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;text-align:center;">
                <div style="font-size:2rem;font-weight:900;color:#{{ count($flaggedKeywords) > 0 ? 'ef4444' : '10b981' }};">{{ count($flaggedKeywords) }}</div>
                <div style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-top:4px;">Keywords sinalizadas</div>
            </div>
        </div>
        <div class="col-md-3">
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;text-align:center;">
                <div style="font-size:2rem;font-weight:900;color:#0369a1;">{{ config('radar.auto_approve.min_feedback_count', 10) }}</div>
                <div style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-top:4px;">Min. feedbacks p/ auto-aprov.</div>
            </div>
        </div>
    </div>

    {{-- Keywords sinalizadas --}}
    @if(count($flaggedKeywords) > 0)
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:14px;padding:16px 20px;margin-bottom:24px;">
        <div style="font-weight:800;color:#991b1b;margin-bottom:8px;"><i class="fas fa-exclamation-triangle me-2"></i>Keywords sinalizadas para revisão</div>
        @foreach($flaggedKeywords as $kw => $s)
        <div style="font-size:0.85rem;color:#7f1d1d;margin-bottom:4px;">
            <strong>{{ $kw }}</strong> — {{ round($s['nao_util_rate'] * 100) }}% não útil ({{ $s['nao_util_count'] }}/{{ $s['total'] }})
        </div>
        @endforeach
        <div style="font-size:0.78rem;color:#b91c1c;margin-top:8px;">Considere remover ou ajustar essas keywords em <code>config/radar.php</code>.</div>
    </div>
    @endif

    {{-- Por keyword --}}
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;margin-bottom:24px;">
        <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;font-weight:800;font-size:0.9rem;color:#1e293b;">
            <i class="fas fa-tag me-2" style="color:#6366f1;"></i>Qualidade por Palavra-chave
        </div>
        @if(empty($keywordStats))
            <div style="padding:32px;text-align:center;color:#94a3b8;font-size:0.85rem;">Nenhum feedback registrado ainda.</div>
        @else
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:12px 20px;text-align:left;font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;">Keyword</th>
                    <th style="padding:12px 20px;text-align:center;font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;">Feedbacks</th>
                    <th style="padding:12px 20px;text-align:center;font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;">Útil</th>
                    <th style="padding:12px 20px;text-align:center;font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;">Não útil</th>
                    <th style="padding:12px 20px;text-align:left;font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;">Taxa útil</th>
                    <th style="padding:12px 20px;text-align:center;font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($keywordStats as $kw => $s)
                @php
                    $utilPct    = round($s['util_rate'] * 100);
                    $barColor   = $utilPct >= 70 ? '#10b981' : ($utilPct >= 50 ? '#f59e0b' : '#ef4444');
                    $minCount   = config('radar.auto_approve.min_feedback_count', 10);
                    $minRate    = config('radar.auto_approve.min_util_rate', 0.70);
                    $eligible   = $s['total'] >= $minCount && $s['util_rate'] >= $minRate;
                @endphp
                <tr style="border-top:1px solid #f1f5f9;">
                    <td style="padding:14px 20px;font-size:0.85rem;font-weight:600;color:#1e293b;">
                        {{ $kw }}
                        @if($s['flagged'])
                            <span style="font-size:0.7rem;color:#ef4444;font-weight:700;margin-left:6px;">⚠️ SINALIZADA</span>
                        @endif
                    </td>
                    <td style="padding:14px 20px;text-align:center;font-size:0.85rem;color:#374151;">{{ $s['total'] }}</td>
                    <td style="padding:14px 20px;text-align:center;font-size:0.85rem;color:#10b981;font-weight:700;">{{ $s['util_count'] }}</td>
                    <td style="padding:14px 20px;text-align:center;font-size:0.85rem;color:#ef4444;font-weight:700;">{{ $s['nao_util_count'] }}</td>
                    <td style="padding:14px 20px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="flex:1;background:#f1f5f9;border-radius:4px;height:6px;overflow:hidden;">
                                <div style="width:{{ $utilPct }}%;background:{{ $barColor }};height:100%;border-radius:4px;"></div>
                            </div>
                            <span style="font-size:0.8rem;font-weight:700;color:{{ $barColor }};min-width:36px;">{{ $utilPct }}%</span>
                        </div>
                    </td>
                    <td style="padding:14px 20px;text-align:center;">
                        @if($eligible)
                            <span style="font-size:0.72rem;background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:10px;font-weight:700;">✓ Auto-aprov.</span>
                        @elseif($s['total'] < $minCount)
                            <span style="font-size:0.72rem;background:#f1f5f9;color:#94a3b8;padding:2px 8px;border-radius:10px;">Aguardando volume</span>
                        @else
                            <span style="font-size:0.72rem;background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:10px;font-weight:700;">Manual</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Por fonte --}}
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;font-weight:800;font-size:0.9rem;color:#1e293b;">
            <i class="fas fa-database me-2" style="color:#6366f1;"></i>Qualidade por Fonte
        </div>
        @if(empty($sourceStats))
            <div style="padding:32px;text-align:center;color:#94a3b8;font-size:0.85rem;">Nenhum feedback registrado ainda.</div>
        @else
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:12px 20px;text-align:left;font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;">Fonte</th>
                    <th style="padding:12px 20px;text-align:center;font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;">Feedbacks</th>
                    <th style="padding:12px 20px;text-align:center;font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;">Útil</th>
                    <th style="padding:12px 20px;text-align:center;font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;">Não útil</th>
                    <th style="padding:12px 20px;text-align:left;font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;">Taxa útil</th>
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
                    <td style="padding:14px 20px;font-size:0.85rem;font-weight:700;color:#1e293b;">{{ $srcLabel }}</td>
                    <td style="padding:14px 20px;text-align:center;font-size:0.85rem;color:#374151;">{{ $s['total'] }}</td>
                    <td style="padding:14px 20px;text-align:center;font-size:0.85rem;color:#10b981;font-weight:700;">{{ $s['util_count'] }}</td>
                    <td style="padding:14px 20px;text-align:center;font-size:0.85rem;color:#ef4444;font-weight:700;">{{ $s['nao_util_count'] }}</td>
                    <td style="padding:14px 20px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="flex:1;background:#f1f5f9;border-radius:4px;height:6px;overflow:hidden;">
                                <div style="width:{{ $utilPct }}%;background:{{ $barColor }};height:100%;border-radius:4px;"></div>
                            </div>
                            <span style="font-size:0.8rem;font-weight:700;color:{{ $barColor }};min-width:36px;">{{ $utilPct }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

</div>
@endsection
