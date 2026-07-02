@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 32px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: #4f46e5; width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: #4f46e5; font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Diretoria Executiva Virtual</h6>
            </div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.5rem; letter-spacing: -1.5px;">Sala de Estratégia</h2>
            <p style="color: #64748b; margin: 8px 0 0 0; font-size: 1.05rem; font-weight: 500; max-width: 640px;">
                4 agentes IA (Financeiro, Inteligência, Mobilização, Estrategista-Chefe) debatem ações do seu painel usando dado real, com rastreabilidade fato-a-fato.
            </p>
        </div>
        <form action="{{ route('strategy-room.store') }}" method="POST" style="margin: 0;">
            @csrf
            <button type="submit" class="btn-premium" style="border: none; padding: 14px 28px; font-weight: 800; border-radius: 12px; background: #1e293b; color: white; font-size: 0.9rem; letter-spacing: 0.2px;">
                <i class="fas fa-play me-2"></i> Iniciar novo debate
            </button>
        </form>
    </div>
</div>

@if(session('success'))
    <div style="background: #ecfdf5; color: #065f46; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #a7f3d0; font-weight: 600; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

@if($sessions->isEmpty())
    <div class="vivensi-card" style="padding: 60px 40px; border-radius: 24px; background: white; border: 2px dashed #e2e8f0; text-align: center;">
        <div style="width: 80px; height: 80px; background: #f8fafc; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; color: #94a3b8; font-size: 1.8rem;">
            <i class="fas fa-comments"></i>
        </div>
        <h3 style="color: #1e293b; font-weight: 900; font-size: 1.4rem; margin-bottom: 10px;">Nenhum debate ainda</h3>
        <p style="color: #64748b; max-width: 440px; margin: 0 auto; font-size: 0.95rem;">
            Clique em "Iniciar novo debate" acima. Os agentes vão consultar seu painel, cruzar dado real e sintetizar uma recomendação em 40-90 segundos.
        </p>
    </div>
@else
    <div class="vivensi-card" style="padding: 0; border-radius: 20px; background: white; border: 1px solid #f1f5f9; overflow: hidden;">
        <table class="table" style="margin: 0;">
            <thead style="background: #f8fafc;">
                <tr>
                    <th style="padding: 16px 20px; font-size: 0.72rem; font-weight: 900; color: #475569; text-transform: uppercase; letter-spacing: 1px;">Sessão</th>
                    <th style="padding: 16px 20px; font-size: 0.72rem; font-weight: 900; color: #475569; text-transform: uppercase; letter-spacing: 1px;">Gatilho</th>
                    <th style="padding: 16px 20px; font-size: 0.72rem; font-weight: 900; color: #475569; text-transform: uppercase; letter-spacing: 1px;">Status</th>
                    <th style="padding: 16px 20px; font-size: 0.72rem; font-weight: 900; color: #475569; text-transform: uppercase; letter-spacing: 1px;">Falas</th>
                    <th style="padding: 16px 20px; font-size: 0.72rem; font-weight: 900; color: #475569; text-transform: uppercase; letter-spacing: 1px;">Iniciada</th>
                    <th style="padding: 16px 20px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($sessions as $s)
                    @php
                        $statusColor = $s->status === 'concluida' ? '#10b981' : '#f59e0b';
                        $statusBg    = $s->status === 'concluida' ? '#ecfdf5' : '#fffbeb';
                        $triggerLabel = match($s->trigger_type){
                            'manual_ui'      => 'UI',
                            'manual_debate'  => 'CLI (debate)',
                            'manual_test'    => 'CLI (teste)',
                            default          => $s->trigger_type,
                        };
                    @endphp
                    <tr style="border-top: 1px solid #f1f5f9;">
                        <td style="padding: 14px 20px; font-weight: 800; color: #1e293b;">#{{ $s->id }}</td>
                        <td style="padding: 14px 20px; color: #64748b; font-size: 0.88rem;">{{ $triggerLabel }}</td>
                        <td style="padding: 14px 20px;">
                            <span style="background: {{ $statusBg }}; color: {{ $statusColor }}; padding: 4px 12px; border-radius: 99px; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
                                {{ $s->status }}
                            </span>
                        </td>
                        <td style="padding: 14px 20px; color: #64748b; font-size: 0.88rem;">{{ $s->messages_count }}</td>
                        <td style="padding: 14px 20px; color: #64748b; font-size: 0.88rem;">{{ $s->created_at->diffForHumans() }}</td>
                        <td style="padding: 14px 20px; text-align: right;">
                            <a href="{{ route('strategy-room.show', $s->id) }}" style="color: #4f46e5; font-weight: 700; text-decoration: none; font-size: 0.85rem;">
                                Abrir <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
