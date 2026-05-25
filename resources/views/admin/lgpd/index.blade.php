@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 40px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: #d97706; width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: #d97706; font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Conformidade Legal</h6>
            </div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.2rem; letter-spacing: -1px;">Painel DPO — LGPD</h2>
            <p style="color: #64748b; margin: 6px 0 0 0; font-size: 1rem;">Lei 13.709/2018 · Direitos dos Titulares · Notificação de Incidentes</p>
        </div>
        @if($overdueBreaches->count())
            <div style="background: #fef2f2; border: 2px solid #fca5a5; border-radius: 14px; padding: 14px 20px; color: #dc2626; font-weight: 800; font-size: 0.9rem; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-triangle-exclamation"></i>
                {{ $overdueBreaches->count() }} incidente(s) com prazo ANPD vencido!
            </div>
        @endif
    </div>
</div>

@if(session('success'))
    <div style="background: #ecfdf5; color: #065f46; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #a7f3d0; font-weight: 700;">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    </div>
@endif

<div class="row g-4">

    {{-- ── Solicitações pendentes ──────────────────────────────────────────── --}}
    <div class="col-12 col-xl-6">
        <div class="vivensi-card" style="padding: 32px; border-radius: 24px; background: white; border: 1px solid #f1f5f9; height: 100%;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
                <div style="width: 42px; height: 42px; background: #eff6ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #3b82f6; font-size: 1rem;">
                    <i class="fas fa-inbox"></i>
                </div>
                <div>
                    <h4 style="margin: 0; font-weight: 900; color: #1e293b; font-size: 1.1rem;">Solicitações dos Titulares</h4>
                    <p style="margin: 0; color: #64748b; font-size: 0.78rem;">Arts. 18–20 · Prazo: 15 dias úteis</p>
                </div>
                @if($pending->count())
                    <span style="margin-left: auto; background: #ef4444; color: white; font-weight: 800; font-size: 0.75rem; padding: 4px 10px; border-radius: 20px;">
                        {{ $pending->count() }} pendente(s)
                    </span>
                @endif
            </div>

            @forelse($pending as $req)
                <div style="background: #f8fafc; border-radius: 14px; padding: 20px; margin-bottom: 14px; border: 1px solid #e2e8f0;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                        @php
                            $typeLabels = ['export' => ['cor'=>'#3b82f6','icon'=>'fa-download','label'=>'Exportação'],
                                           'delete' => ['cor'=>'#ef4444','icon'=>'fa-trash','label'=>'Exclusão'],
                                           'rectify' => ['cor'=>'#f59e0b','icon'=>'fa-pen','label'=>'Retificação'],
                                           'restrict' => ['cor'=>'#8b5cf6','icon'=>'fa-ban','label'=>'Restrição']];
                            $t = $typeLabels[$req->type] ?? ['cor'=>'#64748b','icon'=>'fa-circle','label'=>$req->type];
                        @endphp
                        <span style="background: {{ $t['cor'] }}20; color: {{ $t['cor'] }}; font-weight: 800; font-size: 0.75rem; padding: 4px 10px; border-radius: 20px;">
                            <i class="fas {{ $t['icon'] }} me-1"></i>{{ $t['label'] }}
                        </span>
                        <span style="color: #64748b; font-size: 0.78rem;">Protocolo #{{ $req->id }}</span>
                        <span style="margin-left: auto; color: #94a3b8; font-size: 0.75rem;">{{ $req->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div style="font-weight: 700; color: #1e293b; font-size: 0.9rem; margin-bottom: 4px;">
                        {{ $req->user->name ?? 'Usuário removido' }}
                        <span style="font-weight: 500; color: #64748b;">— {{ $req->user->email ?? '—' }}</span>
                    </div>
                    @if($req->notes)
                        <p style="color: #64748b; font-size: 0.8rem; margin: 6px 0 12px;">{{ $req->notes }}</p>
                    @endif
                    <div style="display: flex; gap: 8px; margin-top: 12px;">
                        <form action="{{ route('admin.lgpd.process', $req) }}" method="POST" style="flex:1" onsubmit="return confirm('Confirmar execução?')">
                            @csrf
                            <input type="hidden" name="action" value="complete">
                            <button type="submit" style="width:100%; padding: 10px; border-radius: 10px; border: none; background: #10b981; color: white; font-weight: 700; font-size: 0.82rem; cursor: pointer;">
                                <i class="fas fa-check me-1"></i>Executar
                            </button>
                        </form>
                        <form action="{{ route('admin.lgpd.process', $req) }}" method="POST" style="flex:1">
                            @csrf
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" style="width:100%; padding: 10px; border-radius: 10px; border: 2px solid #e2e8f0; background: white; color: #64748b; font-weight: 700; font-size: 0.82rem; cursor: pointer;">
                                <i class="fas fa-xmark me-1"></i>Rejeitar
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div style="text-align: center; padding: 40px 20px; color: #94a3b8;">
                    <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 12px; display: block; opacity: 0.4;"></i>
                    Nenhuma solicitação pendente.
                </div>
            @endforelse
        </div>
    </div>

    {{-- ── Registrar novo incidente ────────────────────────────────────────── --}}
    <div class="col-12 col-xl-6">
        <div class="vivensi-card" style="padding: 32px; border-radius: 24px; background: white; border: 1px solid #f1f5f9; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
                <div style="width: 42px; height: 42px; background: #fef2f2; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 1rem;">
                    <i class="fas fa-shield-exclamation"></i>
                </div>
                <div>
                    <h4 style="margin: 0; font-weight: 900; color: #1e293b; font-size: 1.1rem;">Registrar Incidente de Segurança</h4>
                    <p style="margin: 0; color: #64748b; font-size: 0.78rem;">Art. 48 LGPD · Notificação ANPD em até 2 dias úteis</p>
                </div>
            </div>

            <form action="{{ route('admin.lgpd.breach.store') }}" method="POST">
                @csrf
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div style="grid-column: 1/-1;">
                        <label style="display:block; font-weight:700; font-size:0.82rem; margin-bottom:6px; color:#1e293b;">Título do Incidente *</label>
                        <input type="text" name="title" required placeholder="Ex: Acesso não autorizado à base de dados"
                               style="width:100%; padding:12px 16px; border:2px solid #f1f5f9; border-radius:10px; font-size:0.88rem; box-sizing:border-box;">
                    </div>
                    <div>
                        <label for="severity" style="display:block; font-weight:700; font-size:0.82rem; margin-bottom:6px; color:#1e293b;">Severidade *</label>
                        <select name="severity" required style="width:100%; padding:12px 16px; border:2px solid #f1f5f9; border-radius:10px; font-size:0.88rem; background:white; box-sizing:border-box;" id="severity">
                            <option value="low">Baixa</option>
                            <option value="medium" selected>Média</option>
                            <option value="high">Alta</option>
                            <option value="critical">Crítica</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-weight:700; font-size:0.82rem; margin-bottom:6px; color:#1e293b;">Data de ocorrência</label>
                        <input type="datetime-local" name="occurred_at"
                               style="width:100%; padding:12px 16px; border:2px solid #f1f5f9; border-radius:10px; font-size:0.88rem; box-sizing:border-box;">
                    </div>
                    <div style="grid-column: 1/-1;">
                        <label style="display:block; font-weight:700; font-size:0.82rem; margin-bottom:6px; color:#1e293b;">Descrição *</label>
                        <textarea name="description" required rows="3" placeholder="Descreva o que ocorreu, dados afetados e ações imediatas tomadas..."
                                  style="width:100%; padding:12px 16px; border:2px solid #f1f5f9; border-radius:10px; font-size:0.88rem; resize:vertical; box-sizing:border-box;"></textarea>
                    </div>
                    <div>
                        <label style="display:block; font-weight:700; font-size:0.82rem; margin-bottom:6px; color:#1e293b;">Tipos de dados afetados</label>
                        <input type="text" name="affected_data_types" placeholder="Ex: nome, email, CPF"
                               style="width:100%; padding:12px 16px; border:2px solid #f1f5f9; border-radius:10px; font-size:0.88rem; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:700; font-size:0.82rem; margin-bottom:6px; color:#1e293b;">N.º estimado de titulares</label>
                        <input type="number" name="estimated_affected_count" min="0" placeholder="0"
                               style="width:100%; padding:12px 16px; border:2px solid #f1f5f9; border-radius:10px; font-size:0.88rem; box-sizing:border-box;">
                    </div>
                    <div style="grid-column: 1/-1;">
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:700; font-size:0.88rem; color:#1e293b;">
                            <input type="checkbox" name="anpd_required" value="1" style="width:18px; height:18px;" id="anpd_required">
                            Requer notificação à ANPD (Art. 48 LGPD)
                        </label>
                    </div>
                </div>
                <button type="submit" style="width:100%; padding:14px; border:none; border-radius:12px; background:#ef4444; color:white; font-weight:800; font-size:0.9rem; cursor:pointer;">
                    <i class="fas fa-shield-exclamation me-2"></i>Registrar Incidente
                </button>
            </form>
        </div>

        {{-- Lista de incidentes recentes --}}
        @if($breaches->count())
        <div class="vivensi-card" style="padding: 32px; border-radius: 24px; background: white; border: 1px solid #f1f5f9;">
            <h4 style="margin: 0 0 20px; font-weight: 900; color: #1e293b; font-size: 1rem;">Incidentes Registrados</h4>
            @foreach($breaches->take(5) as $breach)
                @php
                    $sev = ['low'=>['cor'=>'#10b981','label'=>'Baixa'],
                            'medium'=>['cor'=>'#f59e0b','label'=>'Média'],
                            'high'=>['cor'=>'#ef4444','label'=>'Alta'],
                            'critical'=>['cor'=>'#7c3aed','label'=>'Crítica']];
                    $s = $sev[$breach->severity] ?? ['cor'=>'#64748b','label'=>$breach->severity];
                    $stLabels = ['identified'=>'Identificado','contained'=>'Contido','notified_anpd'=>'ANPD Notificada','closed'=>'Encerrado'];
                @endphp
                <div style="border: 1px solid #f1f5f9; border-radius: 12px; padding: 16px; margin-bottom: 12px; {{ $breach->isOverdue() ? 'border-color:#fca5a5; background:#fff5f5;' : '' }}">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <span style="background: {{ $s['cor'] }}20; color: {{ $s['cor'] }}; font-weight: 800; font-size: 0.72rem; padding: 3px 8px; border-radius: 10px;">{{ $s['label'] }}</span>
                        @if($breach->isOverdue())
                            <span style="background: #fef2f2; color: #dc2626; font-weight: 800; font-size: 0.72rem; padding: 3px 8px; border-radius: 10px;"><i class="fas fa-clock"></i> ANPD vencido</span>
                        @endif
                        <span style="margin-left: auto; color: #94a3b8; font-size: 0.72rem;">{{ $breach->identified_at->format('d/m/Y') }}</span>
                    </div>
                    <div style="font-weight: 700; color: #1e293b; font-size: 0.88rem; margin-bottom: 6px;">{{ $breach->title }}</div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="color: #64748b; font-size: 0.75rem;">{{ $stLabels[$breach->status] ?? $breach->status }}</span>
                        <form action="{{ route('admin.lgpd.breach.status', $breach) }}" method="POST" style="margin-left:auto; display:flex; gap:6px; align-items:center;">
                            @csrf @method('PATCH')
                            <select name="status" style="padding:5px 10px; border:1px solid #e2e8f0; border-radius:8px; font-size:0.75rem; background:white;" id="status">
                                <option value="identified" {{ $breach->status=='identified'?'selected':'' }}>Identificado</option>
                                <option value="contained" {{ $breach->status=='contained'?'selected':'' }}>Contido</option>
                                <option value="notified_anpd" {{ $breach->status=='notified_anpd'?'selected':'' }}>ANPD Notificada</option>
                                <option value="closed" {{ $breach->status=='closed'?'selected':'' }}>Encerrado</option>
                            </select>
                            <button type="submit" style="padding:5px 10px; border:none; border-radius:8px; background:#4f46e5; color:white; font-size:0.75rem; font-weight:700; cursor:pointer;">OK</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    </div>

</div>
@endsection
