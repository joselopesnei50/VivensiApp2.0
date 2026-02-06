@extends('layouts.app')

@section('content')
@include('partials.onboarding')
<div class="header-page" style="margin-bottom: 40px; position: relative;">
    <div style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%); position: absolute; top: -30px; left: -30px; right: -30px; bottom: 0; z-index: -1;"></div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: var(--ngo-primary); width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: var(--ngo-primary); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Dashboard Terceiro Setor</h6>
            </div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.8rem; letter-spacing: -1.5px;">Impacto & Gestão</h2>
            <p style="color: #64748b; margin: 8px 0 0 0; font-size: 1.1rem; font-weight: 500;">Monitoramento em tempo real da sustentabilidade da organização.</p>
        </div>
        <div style="display: flex; gap: 12px;">
             <a href="{{ url('/ngo/audit') }}" class="btn-premium" style="background: white; color: #1e293b; border: 1px solid #e2e8f0; text-decoration: none; font-weight: 700;">
                <i class="fas fa-eye me-2 text-primary"></i> Central de Auditoria
            </a>
             <a href="{{ url('/ngo/grants/create') }}" class="btn-premium" style="background: #1e293b; text-decoration: none; border: none; font-weight: 700;">
                <i class="fas fa-plus me-2" style="color: #10b981;"></i> Novo Edital
            </a>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 32px;">
    <!-- Runway -->
    <div class="stat-card-premium stat-card-dark" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 24px; height: 180px; padding: 30px; position: relative; overflow: hidden; border: none;">
        <div style="position: absolute; top: -10px; right: -10px; font-size: 6rem; color: rgba(255,255,255,0.03); transform: rotate(-10deg);"><i class="fas fa-hourglass-half"></i></div>
        <div class="label" style="color: rgba(255,255,255,0.5); font-weight: 800; letter-spacing: 1px; margin-bottom: 15px;">RUNWAY ESTIMADA</div>
        <div>
            <div class="value" style="color: white; font-size: 3rem; font-weight: 900; letter-spacing: -1px;">{{ (int)$stats['runway'] }} <small style="font-size: 1rem; opacity: 0.7; font-weight: 600;">meses</small></div>
            <div class="progress-slim" style="height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; margin-top: 20px;">
                <div class="progress-bar-fill" style="width: {{ min((float)$stats['runway'] * 10, 100) }}%; background: {{ (float)$stats['runway'] < 3 ? '#ef4444' : '#10b981' }}; box-shadow: 0 0 15px {{ (float)$stats['runway'] < 3 ? 'rgba(239, 68, 68, 0.4)' : 'rgba(16, 185, 129, 0.4)' }};"></div>
            </div>
            <div style="font-size: 0.65rem; color: rgba(255,255,255,0.4); margin-top: 10px; font-weight: 700; text-transform: uppercase;">Autonomia de Caixa Atual</div>
        </div>
    </div>

    <!-- Monthly Income -->
    <div class="stat-card-premium" style="background: white; border-radius: 24px; height: 180px; padding: 30px; border: 1px solid #f1f5f9; box-shadow: 0 10px 25px rgba(0,0,0,0.02);">
        <div class="label" style="font-weight: 800; letter-spacing: 1px; color: #94a3b8; margin-bottom: 15px;">ARRECADAÇÃO (MÊS)</div>
        <div class="value" style="font-size: 2.2rem; font-weight: 900; color: #1e293b;">R$ {{ number_format($stats['monthly_income'], 0, ',', '.') }}</div>
        <div style="margin-top: 20px; color: #10b981; font-size: 0.8rem; font-weight: 700; display: flex; align-items: center; gap: 5px;">
            <i class="fas fa-trending-up"></i> +{{ number_format(15, 0) }}% vs mês anterior
        </div>
    </div>

    <!-- Volunteers -->
    <div class="stat-card-premium" style="background: white; border-radius: 24px; height: 180px; padding: 30px; border: 1px solid #f1f5f9; box-shadow: 0 10px 25px rgba(0,0,0,0.02);">
        <div class="label" style="font-weight: 800; letter-spacing: 1px; color: #94a3b8; margin-bottom: 15px;">TIME VOLUNTÁRIO</div>
        <div class="value" style="font-size: 2.2rem; font-weight: 900; color: #1e293b;">{{ $stats['volunteers_count'] }}</div>
        <div style="margin-top: 20px; color: #64748b; font-size: 0.8rem; font-weight: 700; display: flex; align-items: center; gap: 5px;">
            <i class="fas fa-users"></i> Membros Ativos
        </div>
    </div>

    <!-- Donors -->
    <div class="stat-card-premium" style="background: white; border-radius: 24px; height: 180px; padding: 30px; border: 1px solid #f1f5f9; box-shadow: 0 10px 25px rgba(0,0,0,0.02);">
        <div class="label" style="font-weight: 800; letter-spacing: 1px; color: #94a3b8; margin-bottom: 15px;">BASE DE DOADORES</div>
        <div class="value" style="font-size: 2.2rem; font-weight: 900; color: #1e293b;">{{ $stats['total_donors'] }}</div>
        <div style="margin-top: 20px; color: var(--ngo-primary); font-size: 0.8rem; font-weight: 700; display: flex; align-items: center; gap: 5px;">
            <i class="fas fa-heart"></i> Doações Recorrentes
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <!-- AI Insight Box (Premium Glassmorphism Dark) -->
        <div class="vivensi-card" style="padding: 35px; background: #0f172a; color: white; border: none; position: relative; overflow: hidden; border-radius: 24px; margin-bottom: 30px;">
             <!-- Decorative Glows -->
             <div style="position: absolute; top: -30px; right: -30px; width: 120px; height: 120px; background: var(--ngo-primary); filter: blur(60px); opacity: 0.4;"></div>
             <div style="position: absolute; bottom: -30px; left: -30px; width: 100px; height: 100px; background: var(--ngo-success); filter: blur(50px); opacity: 0.2;"></div>
            
            <div style="display: flex; align-items: center; margin-bottom: 25px; position: relative; z-index: 1;">
                <div style="position: relative; margin-right: 15px;">
                    <img src="{{ asset('img/bruce-ai.png') }}" alt="Bruce" style="width: 48px; height: 48px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.2); padding: 2px; object-fit: cover;">
                    <div style="position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; background: #10b981; border: 2px solid #0f172a; border-radius: 50%;"></div>
                </div>
                <div>
                    <h5 style="margin: 0; font-weight: 900; font-size: 1.1rem; letter-spacing: -0.5px;">Bruce AI Financial Advisor</h5>
                    <span style="font-size: 0.7rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Sincronizado via Gemini Pro</span>
                </div>
                <a href="{{ url('/smart-analysis') }}" class="btn-premium" style="margin-left: auto; background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(10px); font-size: 0.7rem; font-weight: 800; padding: 10px 20px;">ANÁLISE COGNITIVA</a>
            </div>
            
            <div style="position: relative; z-index: 1; padding: 20px; border-radius: 16px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(5px);">
                <p class="mb-0" style="color: #f1f5f9; font-size: 1rem; line-height: 1.6; font-weight: 500;">
                    "{{ $stats['ai_insight'] }}"
                </p>
            </div>
        </div>

        <div class="vivensi-card" style="padding: 35px; border-radius: 24px; min-height: 400px; background: white; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h4 style="margin: 0; font-size: 1.25rem; color: #1e293b; font-weight: 900; letter-spacing: -0.5px;">Linha do Tempo de Impacto</h4>
                <div style="display: flex; align-items: center; gap: 8px;">
                     <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; display: inline-block;"></span>
                     <span style="font-size: 0.75rem; color: #10b981; font-weight: 800; text-transform: uppercase;">Monitoramento Social Ativo</span>
                </div>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 0;">
                @forelse($stats['impactFeed'] as $item)
                    <div style="display: flex; align-items: center; gap: 20px; padding: 25px; border-bottom: 1px solid #f8fafc; transition: background 0.2s;" onmouseover="this.style.background='#fbfcfe';" onmouseout="this.style.background='white';">
                        <div style="width: 52px; height: 52px; min-width: 52px; border-radius: 16px; background: {{ $item['color'] }}10; color: {{ $item['color'] }}; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; border: 1px solid {{ $item['color'] }}20;">
                            <i class="fas {{ $item['icon'] }}"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 800; color: #1e293b; font-size: 1.05rem; margin-bottom: 3px;">{{ $item['title'] }}</div>
                             <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 700;">{{ $item['time'] }}</div>
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; padding: 80px 20px;">
                        <img src="{{ asset('img/bruce-ai.png') }}" style="width: 60px; height: 60px; filter: grayscale(1); opacity: 0.2; margin-bottom: 20px;">
                        <p style="color: #94a3b8; font-weight: 600;">O Bruce ainda não encontrou novos marcos sociais hoje.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="vivensi-card" style="padding: 35px; border-radius: 24px; background: white;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h4 style="margin: 0; font-size: 1.25rem; color: #1e293b; font-weight: 900; letter-spacing: -0.5px;">Editais & Convênios Recentes</h4>
                <a href="{{ url('/ngo/grants') }}" class="btn-premium" style="font-size: 0.7rem; font-weight: 800; background: #f8fafc; color: var(--ngo-primary); border: 1px solid #e2e8f0; text-decoration: none;">VER TODOS</a>
            </div>
            <div class="table-responsive">
                <table class="table" style="border-collapse: separate; border-spacing: 0 10px;">
                    <thead>
                        <tr>
                            <th style="border: none; color: #94a3b8; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; padding: 0 15px;">EDITAL / PROJETO</th>
                            <th style="border: none; color: #94a3b8; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; padding: 0 15px;">VALOR TOTAL</th>
                            <th style="border: none; color: #94a3b8; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; padding: 0 15px;">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stats['recent_grants'] as $grant)
                        <tr style="background: #f8fafc; transition: transform 0.2s; cursor: pointer;" onmouseover="this.style.transform='scale(1.01)';" onmouseout="this.style.transform='scale(1)';">
                            <td style="padding: 20px 15px; border: none; border-radius: 12px 0 0 12px; font-weight: 700; color: #1e293b;">{{ $grant->title }}</td>
                            <td style="padding: 20px 15px; border: none; font-weight: 800; color: var(--ngo-primary);">R$ {{ number_format($grant->value, 2, ',', '.') }}</td>
                            <td style="padding: 20px 15px; border: none; border-radius: 0 12px 12px 0;">
                                <span style="background: white; border: 1px solid #e2e8f0; color: #64748b; padding: 5px 12px; border-radius: 8px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">{{ $grant->status }}</span>
                            </td>
                        </tr>
                        @endforeach
                        @if(count($stats['recent_grants']) == 0)
                            <tr><td colspan="3" class="text-center text-muted py-5" style="border: none;">
                                <i class="fas fa-folder-open d-block mb-3" style="font-size: 2rem; opacity: 0.2;"></i>
                                Nenhum edital registrado até o momento.
                            </td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="vivensi-card" style="padding: 35px; border-radius: 24px; background: white; border: 1px solid #f1f5f9;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h4 style="margin: 0; font-size: 1.25rem; color: #1e293b; font-weight: 900; letter-spacing: -0.5px;">Campanhas Alpha</h4>
                <a href="{{ url('/ngo/campaigns') }}" style="font-size: 0.75rem; font-weight: 800; color: var(--ngo-primary); text-decoration: none;">HISTÓRICO</a>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 20px;">
                @foreach($stats['active_campaigns'] as $campaign)
                    <div style="padding: 20px; border-radius: 20px; background: #f8fafc; border: 1px solid #f1f5f9;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <div style="font-weight: 800; color: #1e293b; font-size: 1rem;">{{ $campaign->title }}</div>
                            <span style="font-size: 0.65rem; background: #ecfdf5; color: #10b981; padding: 4px 10px; border-radius: 10px; font-weight: 900;">ATIVO</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">Progresso</span>
                            <span style="font-size: 0.75rem; color: #1e293b; font-weight: 800;">R$ {{ number_format($campaign->current_amount, 0, ',', '.') }}</span>
                        </div>
                        <div style="height: 8px; background: white; border: 1px solid #e2e8f0; border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; background: linear-gradient(90deg, var(--ngo-primary) 0%, #818cf8 100%); width: {{ min(100, ($campaign->current_amount / max(1, (float)$campaign->target_amount)) * 100) }}%;"></div>
                        </div>
                    </div>
                @endforeach
                @if(count($stats['active_campaigns']) == 0)
                    <div style="text-align: center; padding: 40px; border: 2px dashed #f1f5f9; border-radius: 20px;">
                        <p class="text-muted small mb-0">Nenhuma campanha de captação ativa hoje.</p>
                    </div>
                @endif
            </div>
            
            <a href="{{ url('/ngo/campaigns/create') }}" class="btn-premium" style="width: 100%; text-align: center; display: block; margin-top: 30px; background: var(--ngo-primary); border: none; padding: 15px; font-weight: 700; font-size: 0.9rem;">
                <i class="fas fa-rocket me-2"></i> Lançar Nova Campanha
            </a>
        </div>

        <!-- Quick Tips (Material Design Style) -->
        <div class="mt-4" style="padding: 20px;">
            <h6 style="color: #94a3b8; font-weight: 800; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">PRÓXIMOS PASSOS</h6>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <div style="display: flex; gap: 12px; align-items: center;">
                    <div style="width: 8px; height: 8px; background: var(--ngo-primary); border-radius: 50%;"></div>
                    <span style="font-size: 0.85rem; color: #64748b; font-weight: 600;">Validar transparência de fev/2026</span>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <div style="width: 8px; height: 8px; background: #e2e8f0; border-radius: 50%;"></div>
                    <span style="font-size: 0.85rem; color: #94a3b8; font-weight: 600;">Exportar balancete social</span>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
