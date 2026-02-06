@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 40px; position: relative;">
    <div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(239, 68, 68, 0.05) 100%); position: absolute; top: -30px; left: -30px; right: -30px; bottom: 0; z-index: -1;"></div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: #10b981; width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: #10b981; font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Controle de Fluxo</h6>
            </div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.5rem; letter-spacing: -1.5px;">Extrato Financeiro</h2>
            <p style="color: #64748b; margin: 8px 0 0 0; font-size: 1.1rem; font-weight: 500;">Monitoramento de receitas e auditoria de caixa.</p>
        </div>
        <div style="display: flex; gap: 15px;">
            <a href="{{ url('/transactions/export') }}" class="btn-premium" style="background: white; color: #64748b; border: 1px solid #e2e8f0; text-decoration: none; font-weight: 700;">
                <i class="fas fa-file-export me-2"></i> Exportar
            </a>
            <a href="{{ url('/transactions/create') }}" class="btn-premium btn-premium-shine" style="border: none; padding: 14px 28px; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-plus-circle"></i> Novo Lançamento
            </a>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="vivensi-card" style="padding: 25px; border-radius: 24px; position: relative; overflow: hidden; background: white; border: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 20px;">
                <div style="width: 56px; height: 56px; background: #ecfdf5; color: #10b981; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; border: 1px solid #d1fae5;">
                    <i class="fas fa-arrow-trend-up"></i>
                </div>
                <div>
                    <span style="display: block; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Receitas Acumuladas</span>
                    <h3 style="margin: 0; font-weight: 900; color: #065f46; font-size: 1.4rem; letter-spacing: -0.5px;">R$ {{ number_format($stats['income'], 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="vivensi-card" style="padding: 25px; border-radius: 24px; position: relative; overflow: hidden; background: white; border: 1px solid #f1f5f9;">
             <div style="display: flex; align-items: center; gap: 20px;">
                <div style="width: 56px; height: 56px; background: #fef2f2; color: #ef4444; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; border: 1px solid #fee2e2;">
                    <i class="fas fa-arrow-trend-down"></i>
                </div>
                <div>
                    <span style="display: block; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Despesas Acumuladas</span>
                    <h3 style="margin: 0; font-weight: 900; color: #991b1b; font-size: 1.4rem; letter-spacing: -0.5px;">R$ {{ number_format($stats['expense'], 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="vivensi-card" style="padding: 25px; border-radius: 24px; position: relative; overflow: hidden; background: #1e293b; color: white;">
             <div style="display: flex; align-items: center; gap: 20px;">
                <div style="width: 56px; height: 56px; background: rgba(255,255,255,0.1); color: white; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; border: 1px solid rgba(255,255,255,0.2);">
                    <i class="fas fa-vault"></i>
                </div>
                <div>
                    <span style="display: block; font-size: 0.7rem; font-weight: 800; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Saldo Operacional</span>
                    <h3 style="margin: 0; font-weight: 900; color: white; font-size: 1.4rem; letter-spacing: -0.5px;">R$ {{ number_format($stats['balance'], 0, ',', '.') }}</h3>
                </div>
            </div>
            <div style="position: absolute; top: -10px; right: -10px; width: 60px; height: 60px; background: var(--primary-color); filter: blur(40px); opacity: 0.3;"></div>
        </div>
    </div>
</div>

@if(session('success'))
    <div style="background: #ecfdf5; color: #065f46; padding: 20px; border-radius: 16px; margin-bottom: 30px; border: 1px solid #a7f3d0; font-weight: 700; display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-circle-check" style="font-size: 1.2rem;"></i> {{ session('success') }}
    </div>
@endif

<div class="vivensi-card" style="padding: 0; border-radius: 28px; background: white; border: 1px solid #f1f5f9; box-shadow: 0 15px 45px rgba(0,0,0,0.02); overflow: hidden;">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #f1f5f9;">
                    <th style="padding: 20px 25px; text-align: left; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Cronologia</th>
                    <th style="padding: 20px 25px; text-align: left; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Beneficiário / Origem</th>
                    <th style="padding: 20px 25px; text-align: left; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Classificação</th>
                    <th style="padding: 20px 25px; text-align: right; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Montante</th>
                    <th style="padding: 20px 25px; text-align: center; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $t)
                <tr style="border-bottom: 1px solid #f8fafc; transition: background 0.2s;" onmouseover="this.style.background='#fbfcfe';" onmouseout="this.style.background='white';">
                    <td style="padding: 20px 25px;">
                        <div style="font-weight: 900; color: #1e293b; font-size: 0.95rem;">{{ $t->date->format('d/m') }}</div>
                        <div style="font-size: 0.7rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">{{ $t->date->format('Y') }}</div>
                    </td>
                    <td style="padding: 20px 25px;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 42px; height: 42px; border-radius: 12px; background: {{ $t->type == 'income' ? '#ecfdf5' : '#fef2f2' }}; color: {{ $t->type == 'income' ? '#10b981' : '#ef4444' }}; display: flex; align-items: center; justify-content: center; font-size: 1rem; border: 1px solid {{ $t->type == 'income' ? '#d1fae5' : '#fee2e2' }};">
                                <i class="fas {{ $t->type == 'income' ? 'fa-plus' : 'fa-minus' }}"></i>
                            </div>
                            <div>
                                <div style="font-weight: 800; color: #1e293b; font-size: 0.95rem; margin-bottom: 2px;">{{ $t->description }}</div>
                                <span style="font-size: 0.65rem; background: {{ $t->type == 'income' ? '#dcfce7' : '#fee2e2' }}; color: {{ $t->type == 'income' ? '#166534' : '#991b1b' }}; padding: 3px 10px; border-radius: 20px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
                                    {{ $t->type == 'income' ? 'Receita' : 'Despesa' }}
                                </span>
                            </div>
                        </div>
                    </td>
                    <td style="padding: 20px 25px;">
                        <div style="color: #475569; font-size: 0.85rem; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-tag" style="font-size: 0.7rem; color: #cbd5e1;"></i> {{ $t->category->name ?? 'Geral' }}
                        </div>
                        @if($t->project)
                            <div style="margin-top: 5px; color: var(--primary-color); font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">
                                <i class="fas fa-project-diagram me-1"></i> {{ $t->project->name }}
                            </div>
                        @endif
                    </td>
                    <td style="padding: 20px 25px; text-align: right;">
                        <div style="font-weight: 900; color: {{ $t->type == 'income' ? '#10b981' : '#ef4444' }}; font-size: 1.1rem; letter-spacing: -0.5px;">
                            {{ $t->type == 'income' ? '+' : '-' }} R$ {{ number_format($t->amount, 2, ',', '.') }}
                        </div>
                        <div style="font-size: 0.6rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-top: 2px;">Conciliado</div>
                    </td>
                    <td style="padding: 20px 25px; text-align: center;">
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm rounded-circle" type="button" data-bs-toggle="dropdown" style="width: 36px; height: 36px; border: 1px solid #f1f5f9;">
                                <i class="fas fa-ellipsis-v" style="font-size: 0.8rem; color: #64748b;"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-4 p-2">
                                <li><a class="dropdown-item py-2 px-3 fw-bold text-dark rounded-3" href="#" style="font-size: 0.85rem;"><i class="fas fa-eye me-2 text-muted"></i> Visualizar</a></li>
                                @if($t->type == 'income')
                                    <li><a class="dropdown-item py-2 px-3 fw-bold text-dark rounded-3" href="#" onclick="shareReceipt('{{ $t->description }}', '{{ route('public.receipt', $t->public_receipt_token) }}')" style="font-size: 0.85rem;"><i class="fab fa-whatsapp me-2 text-success"></i> Zap Recibo</a></li>
                                @endif
                                <li><a class="dropdown-item py-2 px-3 fw-bold text-dark rounded-3" href="#" style="font-size: 0.85rem;"><i class="fas fa-paperclip me-2 text-muted"></i> Comprovante</a></li>
                                <li><hr class="dropdown-divider opacity-50"></li>
                                <li>
                                    <form action="{{ url('/transactions/'.$t->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja apagar permanentemente?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item py-2 px-3 fw-bold text-danger rounded-3" style="font-size: 0.85rem;">
                                            <i class="fas fa-trash-alt me-2"></i> Excluir Registro
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding: 100px 20px; text-align: center;">
                        <div style="width: 80px; height: 80px; background: #f8fafc; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px;">
                            <i class="fas fa-receipt" style="font-size: 2.5rem; color: #e2e8f0;"></i>
                        </div>
                        <h4 style="color: #1e293b; font-weight: 900; font-size: 1.4rem; margin-bottom: 8px;">Nenhum lançamento</h4>
                        <p style="color: #94a3b8; font-size: 0.95rem; font-weight: 500; margin-bottom: 25px;">Seu fluxo de caixa está aguardando os primeiros dados para gerar insights.</p>
                        <a href="{{ url('/transactions/create') }}" class="btn-premium" style="display: inline-block; text-decoration: none; padding: 12px 30px; font-weight: 800;">LANÇAR AGORA</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($transactions->hasPages())
    <div style="padding: 25px; background: #f8fafc; border-top: 1px solid #f1f5f9;">
        {{ $transactions->links() }}
    </div>
    @endif
</div>

<script>
    function shareReceipt(name, url) {
        let text = `Olá ${name}, muito obrigado por sua contribuição! Você pode baixar seu recibo aqui: ${url}`;
        let waLink = `https://wa.me/?text=${encodeURIComponent(text)}`;
        window.open(waLink, '_blank');
    }
</script>
@endsection

