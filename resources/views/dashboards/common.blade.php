@extends('layouts.app')

@section('content')
@include('partials.onboarding')
<div class="header-page" style="margin-bottom: 40px; position: relative;">
    <div style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%); position: absolute; top: -30px; left: -30px; right: -30px; bottom: 0; z-index: -1;"></div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Meu Painel — {{ now()->translatedFormat('F \d\e Y') }}</h6>
            </div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.8rem; letter-spacing: -1.5px;">Olá, {{ explode(' ', auth()->user()->name)[0] }}!</h2>
            <p style="color: #64748b; margin: 8px 0 0 0; font-size: 1.1rem; font-weight: 500;">
                Visão financeira e operacional do seu dia.
                @if($overdueCount > 0)
                    <span style="margin-left: 10px; background: #fef2f2; color: #ef4444; font-size: 0.75rem; font-weight: 800; padding: 3px 10px; border-radius: 99px; border: 1px solid #fecaca;">
                        <i class="fas fa-circle-exclamation me-1"></i>{{ $overdueCount }} tarefa{{ $overdueCount > 1 ? 's' : '' }} vencida{{ $overdueCount > 1 ? 's' : '' }}
                    </span>
                @endif
            </p>
        </div>
        <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items:center;">
             <a href="{{ url('/personal/reconciliation') }}" class="btn-ds btn-ds-outline">
                <i class="fas fa-university me-2 text-primary"></i> Conciliar Banco
            </a>
             <a href="{{ url('/transactions/create') }}" class="btn-premium">
                <i class="fas fa-plus me-2"></i> Nova Transação
            </a>
            {{-- CTA principal — função monetizadora do painel. Estilo destacado pra
                 não se perder entre os outros botões do header. --}}
             <a href="{{ url('/personal/receipts/create') }}"
                style="display:inline-flex; align-items:center; gap:10px;
                       background: linear-gradient(135deg, #10b981 0%, #047857 100%);
                       color:#fff; font-weight:800; font-size:.92rem;
                       padding:13px 24px; border-radius:14px;
                       text-decoration:none; letter-spacing:.2px;
                       box-shadow: 0 12px 28px rgba(16,185,129,.38), 0 2px 6px rgba(16,185,129,.25);
                       border: 1px solid rgba(255,255,255,.12);
                       transition: transform .18s ease, box-shadow .18s ease;"
                onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 16px 36px rgba(16,185,129,.48), 0 3px 8px rgba(16,185,129,.30)';"
                onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 12px 28px rgba(16,185,129,.38), 0 2px 6px rgba(16,185,129,.25)';">
                 <span style="background:rgba(255,255,255,.18); width:30px; height:30px; border-radius:9px; display:inline-flex; align-items:center; justify-content:center;">
                     <i class="fas fa-file-invoice-dollar"></i>
                 </span>
                 Emitir Recibo
                 <span style="background:rgba(255,255,255,.16); font-size:.65rem; padding:3px 9px; border-radius:99px; letter-spacing:1px; text-transform:uppercase; font-weight:900;">Novo</span>
            </a>
        </div>
    </div>
</div>

{{-- Bloco quick_access (Novo Lançamento, Social AI Hub, Minhas Tarefas,
     Inteligência Territorial) removido a pedido — duplicava com o
     bloco "MEI no Controle" (que aparece só pra MEI de fato) e poluía
     a primeira tela do painel. --}}

{{-- ===== MEI no Controle: Termômetro · DAS · DRE ===== --}}
@if(!empty($meiTeto ?? null))
@php
    $tetoCorStatus = ['verde' => '#10b981', 'amarelo' => '#f59e0b', 'vermelho' => '#ef4444'][$meiTeto['status']] ?? '#10b981';
    $tetoMsgStatus = [
        'verde'    => 'Você está confortável dentro do limite.',
        'amarelo'  => 'Atenção — passou de 70% do teto. Acompanhe de perto.',
        'vermelho' => 'CRÍTICO — passou de 90% do teto. Pode perder o regime MEI.',
    ][$meiTeto['status']] ?? '';
    $dasUrgenciaCor = $meiDas['pago']
        ? '#10b981'
        : ($meiDas['dias_restantes'] <= 5 ? '#ef4444' : ($meiDas['dias_restantes'] <= 10 ? '#f59e0b' : '#6366f1'));
@endphp
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="vivensi-card" style="padding: 28px; background: linear-gradient(135deg,#312e81 0%,#1e293b 100%); color:#fff; border:none; border-radius:24px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:22px; flex-wrap:wrap; gap:14px;">
                <div>
                    <span style="color:rgba(255,255,255,0.5); font-weight:800; font-size:.7rem; text-transform:uppercase; letter-spacing:2px;">MEI no Controle</span>
                    <h4 style="margin:4px 0 0 0; font-weight:900; font-size:1.4rem;">Saúde do seu negócio em 1 olhar</h4>
                </div>
                <span style="font-size:.75rem; font-weight:700; color:rgba(255,255,255,0.5);">
                    Ano-base {{ $meiTeto['ano'] }}
                </span>
            </div>

            <div class="row g-3">
                {{-- TERMÔMETRO DO TETO --}}
                <div class="col-lg-5">
                    <div style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:18px; padding:22px;">
                        <div style="display:flex; justify-content:space-between; align-items:baseline;">
                            <span style="font-weight:800; font-size:.72rem; letter-spacing:1.5px; text-transform:uppercase; color:rgba(255,255,255,0.6);">Termômetro do Teto</span>
                            <span style="font-size:.8rem; font-weight:900; color:{{ $tetoCorStatus }};">{{ $meiTeto['percentual'] }}%</span>
                        </div>
                        <div style="font-size:1.7rem; font-weight:900; margin-top:8px;">
                            R$ {{ number_format($meiTeto['realizado_centavos']/100, 2, ',', '.') }}
                            <span style="font-size:.85rem; font-weight:700; color:rgba(255,255,255,0.4);">/ R$ {{ number_format($meiTeto['teto_centavos']/100, 0, ',', '.') }}</span>
                        </div>
                        <div style="height:10px; background:rgba(255,255,255,0.08); border-radius:99px; margin-top:14px; overflow:hidden;">
                            <div style="height:100%; width:{{ min(100, $meiTeto['percentual']) }}%; background:{{ $tetoCorStatus }}; border-radius:99px; transition:width .4s;"></div>
                        </div>
                        <div style="margin-top:10px; font-size:.78rem; color:rgba(255,255,255,0.7);">
                            {{ $tetoMsgStatus }}
                        </div>
                        <div style="margin-top:6px; font-size:.72rem; color:rgba(255,255,255,0.4);">
                            Restam <strong style="color:#fff;">R$ {{ number_format($meiTeto['faltam_centavos']/100, 0, ',', '.') }}</strong> até o limite anual.
                        </div>
                    </div>
                </div>

                {{-- LEMBRETE DAS --}}
                <div class="col-lg-3">
                    <div style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:18px; padding:22px; height:100%; display:flex; flex-direction:column; justify-content:space-between;">
                        <div>
                            <span style="font-weight:800; font-size:.72rem; letter-spacing:1.5px; text-transform:uppercase; color:rgba(255,255,255,0.6);">DAS — Próximo</span>
                            @if($meiDas['pago'])
                                <div style="margin-top:8px; font-size:1.05rem; font-weight:900; color:#10b981;"><i class="fas fa-check-circle me-1"></i> Pago este mês</div>
                                <div style="margin-top:6px; font-size:.75rem; color:rgba(255,255,255,0.5);">Próximo vencimento: {{ \Carbon\Carbon::parse($meiDas['vencimento'])->translatedFormat('d/m/Y') }}</div>
                            @else
                                <div style="margin-top:8px; font-size:1.7rem; font-weight:900; color:{{ $dasUrgenciaCor }};">
                                    {{ \Carbon\Carbon::parse($meiDas['vencimento'])->translatedFormat('d/m') }}
                                </div>
                                <div style="margin-top:4px; font-size:.78rem; color:rgba(255,255,255,0.7);">
                                    @if($meiDas['dias_restantes'] <= 0)
                                        <strong style="color:#ef4444;">VENCE HOJE</strong>
                                    @else
                                        Em {{ $meiDas['dias_restantes'] }} dia{{ $meiDas['dias_restantes']>1?'s':'' }}
                                    @endif
                                </div>
                                <div style="margin-top:6px; font-size:.85rem; font-weight:700;">R$ {{ number_format($meiDas['valor_centavos']/100, 2, ',', '.') }}</div>
                            @endif
                        </div>
                        @unless($meiDas['pago'])
                            <form action="{{ url('/personal/das/pago') }}" method="POST" style="margin-top:14px;">
                                @csrf
                                <button type="submit" class="btn-cta" style="width:100%; background:#10b981; color:#fff; border:none; padding:10px; font-weight:700; border-radius:10px; font-size:.78rem; cursor:pointer;">
                                    <i class="fas fa-check me-1"></i> Marcar como pago
                                </button>
                            </form>
                        @endunless
                    </div>
                </div>

                {{-- MINI-DRE --}}
                <div class="col-lg-4">
                    <div style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:18px; padding:22px;">
                        <span style="font-weight:800; font-size:.72rem; letter-spacing:1.5px; text-transform:uppercase; color:rgba(255,255,255,0.6);">Resultado do Mês</span>
                        <div style="display:flex; justify-content:space-between; margin-top:14px; font-size:.85rem;">
                            <span style="color:rgba(255,255,255,0.7);">Receita</span>
                            <strong style="color:#10b981;">R$ {{ number_format($meiDre['receita_centavos']/100, 2, ',', '.') }}</strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-top:6px; font-size:.85rem;">
                            <span style="color:rgba(255,255,255,0.7);">(−) Despesas</span>
                            <strong style="color:#f87171;">R$ {{ number_format($meiDre['despesa_centavos']/100, 2, ',', '.') }}</strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-top:6px; font-size:.85rem;">
                            <span style="color:rgba(255,255,255,0.7);">(−) DAS</span>
                            <strong style="color:#fbbf24;">R$ {{ number_format($meiDre['das_centavos']/100, 2, ',', '.') }}</strong>
                        </div>
                        <hr style="border-color:rgba(255,255,255,0.12); margin:14px 0;">
                        <div style="display:flex; justify-content:space-between; align-items:baseline;">
                            <span style="font-weight:800; font-size:.75rem; color:rgba(255,255,255,0.6); letter-spacing:1px; text-transform:uppercase;">Lucro Líquido</span>
                            <strong style="font-size:1.4rem; color:{{ $meiDre['lucro_centavos']>=0 ? '#34d399' : '#f87171' }};">
                                R$ {{ number_format($meiDre['lucro_centavos']/100, 2, ',', '.') }}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== Barra de Dossiê Fiscal (NFS-e) — incentiva o MEI a anexar ===== --}}
@if(!empty($meiNfse) && ($meiNfse['total_receitas'] ?? 0) > 0)
@php
    $nfseCor = $meiNfse['percentual'] >= 90 ? '#10b981' : ($meiNfse['percentual'] >= 50 ? '#f59e0b' : '#ef4444');
@endphp
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="vivensi-card" style="padding:20px 28px; display:flex; align-items:center; gap:22px; flex-wrap:wrap;">
            <div style="width:54px; height:54px; border-radius:14px; background:rgba(79,70,229,.08); color:#4f46e5; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
                <i class="fas fa-file-circle-check"></i>
            </div>
            <div style="flex:1; min-width:260px;">
                <div style="display:flex; justify-content:space-between; align-items:baseline; gap:10px; flex-wrap:wrap;">
                    <div>
                        <div style="font-size:.78rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1.5px;">Dossiê fiscal {{ now()->year }}</div>
                        <div style="font-size:1rem; font-weight:800; color:#1e293b; margin-top:2px;">
                            <span style="color:{{ $nfseCor }};">{{ $meiNfse['com_nfse'] }}</span> de {{ $meiNfse['total_receitas'] }} receitas com NFS-e anexada
                            <span style="color:#94a3b8; font-weight:600; font-size:.85rem;"> · {{ number_format($meiNfse['percentual'], 0) }}%</span>
                        </div>
                    </div>
                    <a href="{{ url('/personal/receipts') }}" class="btn btn-sm" style="background:#eef2ff; color:#4f46e5; font-weight:800; font-size:.78rem; padding:8px 16px; border-radius:10px; text-decoration:none;">
                        <i class="fas fa-paperclip me-1"></i> Completar
                    </a>
                </div>
                <div style="height:7px; background:#f1f5f9; border-radius:99px; margin-top:10px; overflow:hidden;">
                    <div style="height:100%; width:{{ min(100, $meiNfse['percentual']) }}%; background:{{ $nfseCor }}; transition:width .4s;"></div>
                </div>
                @if($meiNfse['sem_nfse'] > 0)
                    <div style="margin-top:8px; font-size:.78rem; color:#64748b;">
                        Emita pelo portal gratuito do governo
                        <a href="https://www.nfse.gov.br" target="_blank" rel="noopener" style="color:#4f46e5; font-weight:700;">nfse.gov.br</a>
                        e anexe aqui pra manter o dossiê sempre auditável.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
@endif

<div class="row g-4 mb-5">
    <!-- Saldo do Mês -->
    <div class="col-md-4">
        <div class="vivensi-card" style="padding: 35px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white; border: none; overflow: hidden; position: relative;">
            <div style="position: absolute; top: -20px; right: -20px; font-size: 8rem; color: rgba(255,255,255,0.03); transform: rotate(-15deg);"><i class="fas fa-wallet"></i></div>
            <span style="color: rgba(255,255,255,0.6); font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Saldo do Mês</span>
            <div style="font-size: 2.4rem; font-weight: 900; color: {{ $monthlyBalance >= 0 ? '#34d399' : '#f87171' }}; margin-top: 10px; text-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                R$ {{ number_format($monthlyBalance, 2, ',', '.') }}
            </div>
            <div style="margin-top: 8px; font-size: 0.75rem; color: rgba(255,255,255,0.4); font-weight: 600;">
                Acumulado total: R$ {{ number_format($balance, 0, ',', '.') }}
            </div>
            <div style="margin-top: 16px; font-size: 0.8rem; background: rgba(255,255,255,0.1); display: inline-flex; align-items: center; padding: 6px 12px; border-radius: 20px; backdrop-filter: blur(5px);">
                <i class="fas fa-calendar-day me-2" style="color: #818cf8;"></i> {{ ucfirst(now()->translatedFormat('F Y')) }}
            </div>
        </div>
    </div>

    <!-- Entradas do Mês -->
    <div class="col-md-4">
        <div class="vivensi-card" style="padding: 35px; background: white; border: 1px solid rgba(16,185,129,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="color: #64748b; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Entradas — Mês Atual</span>
                    <div style="font-size: 2.2rem; font-weight: 900; color: #10b981; margin-top: 10px;">
                        R$ {{ number_format($monthlyIncome, 2, ',', '.') }}
                    </div>
                    @if($incomeChange !== null)
                    <div style="font-size: 0.72rem; font-weight: 800; color: {{ $incomeChange >= 0 ? '#10b981' : '#ef4444' }}; margin-top: 5px;">
                        {{ $incomeChange >= 0 ? '▲' : '▼' }} {{ number_format(abs($incomeChange), 1) }}% vs mês anterior
                    </div>
                    @endif
                </div>
                <div style="width: 50px; height: 50px; background: #ecfdf5; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #10b981; font-size: 1.2rem;">
                    <i class="fas fa-arrow-down-long"></i>
                </div>
            </div>
            <div style="margin-top: 20px; font-size: 0.72rem; color: #94a3b8; font-weight: 600;">
                Total histórico: R$ {{ number_format($totalIncome, 0, ',', '.') }}
            </div>
            <div style="margin-top: 8px; height: 4px; background: #f1f5f9; border-radius: 2px; overflow: hidden;">
                <div style="height: 100%; background: #10b981; width: 100%;"></div>
            </div>
        </div>
    </div>

    <!-- Saídas do Mês -->
    <div class="col-md-4">
        <div class="vivensi-card" style="padding: 35px; background: white; border: 1px solid rgba(239,68,68,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="color: #64748b; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Saídas — Mês Atual</span>
                    <div style="font-size: 2.2rem; font-weight: 900; color: #ef4444; margin-top: 10px;">
                        R$ {{ number_format($monthlyExpense, 2, ',', '.') }}
                    </div>
                    @if($expenseChange !== null)
                    <div style="font-size: 0.72rem; font-weight: 800; color: {{ $expenseChange <= 0 ? '#10b981' : '#ef4444' }}; margin-top: 5px;">
                        {{ $expenseChange >= 0 ? '▲' : '▼' }} {{ number_format(abs($expenseChange), 1) }}% vs mês anterior
                    </div>
                    @endif
                </div>
                <div style="width: 50px; height: 50px; background: #fef2f2; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 1.2rem;">
                    <i class="fas fa-arrow-up-long"></i>
                </div>
            </div>
            <div style="margin-top: 20px; font-size: 0.72rem; color: #94a3b8; font-weight: 600;">
                Total histórico: R$ {{ number_format($totalExpense, 0, ',', '.') }}
            </div>
            <div style="margin-top: 8px; height: 4px; background: #f1f5f9; border-radius: 2px; overflow: hidden;">
                <div style="height: 100%; background: #ef4444; width: {{ $monthlyIncome > 0 ? min(100, ($monthlyExpense / $monthlyIncome) * 100) : 0 }}%;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Access CRM & Marketing -->
<div class="row mb-5">
    <div class="col-12">
        <h4 style="margin: 0 0 20px 0; font-size: 1.4rem; color: #1e293b; font-weight: 900; letter-spacing: -0.5px;">Máquina de Vendas & Marketing</h4>
        <div class="row g-4">
            <div class="col-md-3">
                <a href="{{ url('/whatsapp/chat') }}" style="display: block; padding: 25px; background: white; border-radius: 20px; text-decoration: none; border: 1px solid #f1f5f9; transition: all 0.3s; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02);" onmouseover="this.style.borderColor='#25d366'; this.style.transform='translateY(-5px)';" onmouseout="this.style.borderColor='#f1f5f9'; this.style.transform='translateY(0)';">
                    <div style="width: 60px; height: 60px; background: #ecfdf5; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; color: #25d366; font-size: 1.8rem; margin-bottom: 15px;">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <h5 style="color: #1e293b; font-weight: 800; font-size: 1.1rem; margin-bottom: 5px;">WhatsApp CRM</h5>
                    <p style="color: #64748b; font-size: 0.8rem; margin: 0; font-weight: 500;">Atenda seus clientes</p>
                </a>
            </div>
            <div class="col-md-3">
                <a href="{{ route('marketing.index') }}" style="display: block; padding: 25px; background: white; border-radius: 20px; text-decoration: none; border: 1px solid #f1f5f9; transition: all 0.3s; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02);" onmouseover="this.style.borderColor='#4f46e5'; this.style.transform='translateY(-5px)';" onmouseout="this.style.borderColor='#f1f5f9'; this.style.transform='translateY(0)';">
                    <div style="width: 60px; height: 60px; background: #e0e7ff; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; color: #4f46e5; font-size: 1.8rem; margin-bottom: 15px;">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h5 style="color: #1e293b; font-weight: 800; font-size: 1.1rem; margin-bottom: 5px;">Hub de IA</h5>
                    <p style="color: #64748b; font-size: 0.8rem; margin: 0; font-weight: 500;">Estratégias de vendas</p>
                </a>
            </div>
            <div class="col-md-3">
                <a href="{{ url('/manager/landing-pages') }}" style="display: block; padding: 25px; background: white; border-radius: 20px; text-decoration: none; border: 1px solid #f1f5f9; transition: all 0.3s; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02);" onmouseover="this.style.borderColor='#0ea5e9'; this.style.transform='translateY(-5px)';" onmouseout="this.style.borderColor='#f1f5f9'; this.style.transform='translateY(0)';">
                    <div style="width: 60px; height: 60px; background: #e0f2fe; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; color: #0ea5e9; font-size: 1.8rem; margin-bottom: 15px;">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h5 style="color: #1e293b; font-weight: 800; font-size: 1.1rem; margin-bottom: 5px;">Landing Pages</h5>
                    <p style="color: #64748b; font-size: 0.8rem; margin: 0; font-weight: 500;">Páginas de captura</p>
                </a>
            </div>
            <div class="col-md-3">
                <a href="{{ route('whatsapp.broadcast.index') }}" style="display: block; padding: 25px; background: white; border-radius: 20px; text-decoration: none; border: 1px solid #f1f5f9; transition: all 0.3s; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02);" onmouseover="this.style.borderColor='#8b5cf6'; this.style.transform='translateY(-5px)';" onmouseout="this.style.borderColor='#f1f5f9'; this.style.transform='translateY(0)';">
                    <div style="width: 60px; height: 60px; background: #ede9fe; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; color: #8b5cf6; font-size: 1.8rem; margin-bottom: 15px;">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <h5 style="color: #1e293b; font-weight: 800; font-size: 1.1rem; margin-bottom: 5px;">Disparo em Massa</h5>
                    <p style="color: #64748b; font-size: 0.8rem; margin: 0; font-weight: 500;">Promoções e ofertas</p>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Financial Chart Section -->
<div class="row mb-5">
    <div class="col-12">
        <div class="vivensi-card p-5" style="background: white; border-radius: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px;">
                <div>
                    <h4 style="margin: 0; font-size: 1.4rem; color: #1e293b; font-weight: 900; letter-spacing: -0.5px;">Performance Semestral</h4>
                    <p style="margin: 5px 0 0 0; color: #64748b; font-size: 0.9rem;">Análise comparativa de fluxo de caixa</p>
                </div>
                <div style="display: flex; gap: 20px; background: #f8fafc; padding: 10px 20px; border-radius: 12px; border: 1px solid #f1f5f9;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="width: 10px; height: 10px; background: #10b981; border-radius: 50%;"></span>
                        <span style="font-size: 0.8rem; color: #1e293b; font-weight: 800;">Receitas</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="width: 10px; height: 10px; background: #ef4444; border-radius: 50%;"></span>
                        <span style="font-size: 0.8rem; color: #1e293b; font-weight: 800;">Despesas</span>
                    </div>
                </div>
            </div>
            <div style="height: 350px; width: 100%;">
                <canvas id="financialChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('financialChart').getContext('2d');
        
        // Gradient for Income
        const gradientIncome = ctx.createLinearGradient(0, 0, 0, 350);
        gradientIncome.addColorStop(0, 'rgba(16, 185, 129, 0.15)');
        gradientIncome.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

        // Gradient for Expense
        const gradientExpense = ctx.createLinearGradient(0, 0, 0, 350);
        gradientExpense.addColorStop(0, 'rgba(239, 68, 68, 0.15)');
        gradientExpense.addColorStop(1, 'rgba(239, 68, 68, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!},
                datasets: [
                    {
                        label: 'Receitas',
                        data: {!! json_encode($chartIncome, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!},
                        borderColor: '#10b981',
                        backgroundColor: gradientIncome,
                        borderWidth: 4,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#10b981',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Despesas',
                        data: {!! json_encode($chartExpense, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!},
                        borderColor: '#ef4444',
                        backgroundColor: gradientExpense,
                        borderWidth: 4,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#ef4444',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#1e293b',
                        titleColor: '#ffffff',
                        titleFont: { family: "'Outfit', sans-serif", weight: '900', size: 14 },
                        bodyColor: '#f1f5f9',
                        bodyFont: { family: "'Inter', sans-serif", weight: '500' },
                        padding: 15,
                        displayColors: true,
                        boxPadding: 5,
                        usePointStyle: true,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 12, family: "'Outfit', sans-serif", weight: '600' } }
                    },
                    y: {
                        grid: { color: '#f1f5f9', drawBorder: false },
                        ticks: { 
                            color: '#94a3b8', 
                            font: { size: 11, weight: '700' },
                            padding: 10,
                            callback: function(value) {
                                return 'R$ ' + (value >= 1000 ? (value / 1000) + 'k' : value);
                            }
                        },
                        beginAtZero: true
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    });
</script>


<div class="row g-4">
    <!-- Últimas Transações -->
    <div class="col-md-7">
        <div class="vivensi-card" style="padding: 35px; min-height: 520px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h4 style="margin: 0; font-size: 1.25rem; color: #1e293b; font-weight: 900; letter-spacing: -0.5px;">Atividade Recente</h4>
                <a href="{{ url('/transactions') }}" class="btn-ds btn-ds-outline" style="font-size: 0.75rem; padding: 6px 14px;">VER HISTÓRICO</a>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                @forelse($recentTransactions as $tx)
                <div style="display: flex; align-items: center; padding: 20px; background: white; border: 1px solid #f1f5f9; border-radius: 20px; transition: all 0.2s; cursor: pointer;" onmouseover="this.style.borderColor='var(--primary-color)'; this.style.transform='translateX(5px)';" onmouseout="this.style.borderColor='#f1f5f9'; this.style.transform='translateX(0)';">
                    <div style="width: 52px; height: 52px; border-radius: 16px; background: {{ $tx->type === 'income' ? '#ecfdf5' : '#fef2f2' }}; display: flex; align-items: center; justify-content: center; margin-right: 20px; color: {{ $tx->type === 'income' ? '#10b981' : '#ef4444' }}; font-size: 1.1rem; border: 2px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.03);">
                        <i class="fas {{ $tx->type === 'income' ? 'fa-square-plus' : 'fa-square-minus' }}"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 800; color: #1e293b; font-size: 1rem; margin-bottom: 4px;">{{ $tx->description }}</div>
                        <div style="font-size: 0.8rem; color: #94a3b8; font-weight: 600;">{{ \Carbon\Carbon::parse($tx->date)->translatedFormat('d \d\e F') }}</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 900; color: {{ $tx->type === 'income' ? '#10b981' : '#ef4444' }}; font-size: 1.1rem; letter-spacing: -0.5px;">
                            {{ $tx->type === 'income' ? '+' : '-' }} R$ {{ number_format($tx->amount, 2, ',', '.') }}
                        </div>
                        <span style="font-size: 0.65rem; color: #94a3b8; font-weight: 800; text-transform: uppercase;">{{ $tx->type === 'income' ? 'Crédito' : 'Débito' }}</span>
                    </div>
                </div>
                @empty
                <div style="text-align: center; padding: 60px; border: 2px dashed #f1f5f9; border-radius: 24px;">
                    <i class="fas fa-receipt d-block mb-3" style="font-size: 3rem; color: #e2e8f0;"></i>
                    <p style="color: #94a3b8; font-weight: 600;">Aguardando seu primeiro lançamento.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Agenda e AI -->
    <div class="col-md-5">
        <div class="vivensi-card" style="padding: 35px; min-height: 250px; background: white; margin-bottom: 24px;">
             <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h4 style="margin: 0; font-size: 1.25rem; color: #1e293b; font-weight: 900; letter-spacing: -0.5px;">Pendências</h4>
                <a href="{{ url('/tasks') }}" style="font-size: 0.75rem; font-weight: 800; color: var(--primary-color); text-decoration: none;">AGENDA <i class="fas fa-arrow-right ms-1"></i></a>
            </div>

            <div style="display: flex; flex-direction: column; gap: 15px;">
                @forelse($pendingTasks as $task)
                @php
                    $isOverdue = $task->due_date && \Carbon\Carbon::parse($task->due_date)->isPast();
                    $prioMap = [
                        'critical' => ['#7f1d1d', '#fef2f2', 'Crítica'],
                        'high'     => ['#ef4444', '#fef2f2', 'Alta'],
                        'medium'   => ['#f59e0b', '#fffbeb', 'Média'],
                        'low'      => ['#10b981', '#ecfdf5', 'Baixa'],
                    ];
                    $pc = $prioMap[$task->priority] ?? ['#6366f1', '#e0e7ff', ucfirst($task->priority ?? '—')];
                    $borderColor = $isOverdue ? '#ef4444' : $pc[0];
                @endphp
                <div style="padding: 15px 20px; background: {{ $isOverdue ? '#fef2f2' : '#f8fafc' }}; border-radius: 16px; border-left: 6px solid {{ $borderColor }}; border-top: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                        <span style="font-weight: 800; color: #1e293b; font-size: 0.95rem;">{{ $task->title }}</span>
                        <span style="font-size: 0.65rem; background: {{ $pc[1] }}; padding: 4px 10px; border-radius: 20px; font-weight: 900; color: {{ $pc[0] }}; text-transform: uppercase; white-space: nowrap;">
                            {{ $pc[2] }}
                        </span>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 0.75rem; color: {{ $isOverdue ? '#ef4444' : '#94a3b8' }}; font-weight: 700;">
                            <i class="far fa-{{ $isOverdue ? 'calendar-xmark' : 'calendar-check' }} me-1"></i>
                            {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') : 'Rotina' }}
                            @if($isOverdue) <strong>(VENCIDA)</strong> @endif
                        </span>
                        <i class="fas fa-chevron-right" style="color: #cbd5e1; font-size: 0.8rem;"></i>
                    </div>
                </div>
                @empty
                 <div style="text-align: center; padding: 40px; border: 1px dashed #f1f5f9; border-radius: 16px;">
                    <p style="color: #94a3b8; margin: 0; font-weight: 600; font-size: 0.9rem;">Nada urgente por hoje! ✨</p>
                </div>
                @endforelse
            </div>
            
            <a href="{{ url('/tasks/create') }}" class="btn-premium" style="width: 100%; margin-top: 25px; text-align: center; display: block;">
                <i class="fas fa-plus-circle me-2"></i> Adicionar Tarefa
            </a>
        </div>

        <!-- Bruce AI Insight Box (Glassmorphism Dark) -->
        <div class="vivensi-card" style="padding: 35px; background: #0f172a; color: white; border: none; position: relative; overflow: hidden; border-radius: 24px; min-height: 240px;">
            <!-- Decorative Glows -->
            <div style="position: absolute; top: -30px; right: -30px; width: 120px; height: 120px; background: var(--primary-color); filter: blur(60px); opacity: 0.4;"></div>
            <div style="position: absolute; bottom: -30px; left: -30px; width: 100px; height: 100px; background: #10b981; filter: blur(50px); opacity: 0.2;"></div>
            
            <div style="display: flex; align-items: center; margin-bottom: 25px; position: relative; z-index: 1;">
                <div style="position: relative; margin-right: 15px;">
                    <img loading="lazy" src="{{ asset('img/bruce/bruceia-icone-fundo-claro.svg') }}" alt="Bruce" style="width: 48px; height: 48px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.2); padding: 2px; object-fit: cover;">
                    <div style="position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; background: #10b981; border: 2px solid #0f172a; border-radius: 50%;"></div>
                </div>
                <div>
                    <h5 style="margin: 0; font-weight: 900; font-size: 1.1rem; letter-spacing: -0.5px;">Bruce AI Advisor</h5>
                    <span style="font-size: 0.7rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Sincronizado via Gemini Pro</span>
                </div>
            </div>
            
            <div id="dashboard-ai-tips" style="position: relative; z-index: 1; padding: 15px; border-radius: 16px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(5px);">
                <div style="display: flex; gap: 12px; align-items: center; color: #f1f5f9; font-size: 0.95rem; line-height: 1.5; font-weight: 500;">
                    <i class="fas fa-circle-notch fa-spin text-primary"></i> <span>Digerindo dados financeiros...</span>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px; position: relative; z-index: 1;">
                <a href="{{ url('/personal/budget') }}" style="color: var(--primary-light); font-size: 0.75rem; font-weight: 800; text-decoration: none; text-transform: uppercase; letter-spacing: 1px;">
                    Análise Profunda <i class="fas fa-chevron-right ms-1"></i>
                </a>
                <span style="font-size: 0.6rem; color: rgba(255,255,255,0.3); font-weight: 600;">{{ strtoupper(now()->translatedFormat('F Y')) }}</span>
            </div>
        </div>

        <!-- Linha do Tempo de Impacto (Pessoa Comum) -->
        <div class="vivensi-card" style="padding: 35px; border-radius: 24px; background: white; margin-top: 24px; border: 1px solid #f1f5f9;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h4 style="margin: 0; font-size: 1.25rem; color: #1e293b; font-weight: 900; letter-spacing: -0.5px;">Linha da Prosperidade</h4>
                <div style="width: 32px; height: 32px; background: #eef2ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary-color);">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 0;">
                @forelse($impactFeed as $item)
                    <div style="display: flex; align-items: center; gap: 15px; padding: 18px 0; border-bottom: 1px solid #f8fafc;">
                        <div style="width: 42px; height: 42px; min-width: 42px; border-radius: 12px; background: {{ $item['color'] }}10; color: {{ $item['color'] }}; display: flex; align-items: center; justify-content: center; font-size: 1rem; border: 1px solid {{ $item['color'] }}20;">
                            <i class="fas {{ $item['icon'] }}"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; color: #1e293b; font-size: 0.95rem; margin-bottom: 3px;">{{ $item['title'] }}</div>
                             <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 700;">{{ $item['time'] }}</div>
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; padding: 40px 10px;">
                        <p style="color: #94a3b8; font-weight: 600; font-size: 0.9rem;">Lance novos gastos ou metas para ver sua evolução!</p>
                    </div>
                @endforelse
            </div>
        </div>

        <script>
            async function loadDashboardAi() {
                const container = document.getElementById('dashboard-ai-tips');
                try {
                    const res  = await fetch('/api/bruce/insight', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
                    const data = await res.json();
                    const text = data.insight || '';
                    if (text) {
                        // Auditoria 2026-08-29 P2: sanitiza resposta de LLM antes do innerHTML
                        const html = text.replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>').replace(/\n/g,'<br>');
                        container.innerHTML = `<p style="color:#e2e8f0;font-size:0.875rem;line-height:1.6;font-weight:500;margin:0;">${DOMPurify.sanitize(html)}</p>`;
                    } else {
                        container.innerHTML = '<p style="font-size:0.8rem;color:#94a3b8;">Adicione mais transações para gerar insights.</p>';
                    }
                } catch(e) {
                    container.innerHTML = '<p style="font-size:0.7rem;color:#94a3b8;">Insight indisponível no momento.</p>';
                }
            }
            document.addEventListener('DOMContentLoaded', loadDashboardAi);
        </script>
    </div>
</div>
@endsection
