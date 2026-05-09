<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório — {{ $project->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #1e293b; font-size: 13px; line-height: 1.5; }

        .header { background: #0f172a; color: white; padding: 30px 40px; margin-bottom: 30px; }
        .header h1 { font-size: 22px; font-weight: bold; margin-bottom: 4px; letter-spacing: -0.5px; }
        .header .subtitle { color: rgba(255,255,255,0.5); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
        .header .meta { margin-top: 16px; display: flex; gap: 30px; }
        .header .meta-item { font-size: 10px; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 0.5px; }
        .header .meta-item strong { display: block; font-size: 13px; color: white; margin-top: 2px; }

        .badge { display: inline-block; padding: 3px 10px; border-radius: 50px; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-paused { background: #fef9c3; color: #854d0e; }
        .badge-completed { background: #ede9fe; color: #4c1d95; }
        .badge-canceled { background: #f1f5f9; color: #64748b; }

        .section { margin: 0 40px 28px; }
        .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; color: #94a3b8; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px; margin-bottom: 16px; }

        .stat-grid { display: table; width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .stat-cell { display: table-cell; width: 25%; padding: 16px 20px; border: 1px solid #f1f5f9; vertical-align: top; }
        .stat-label { font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; margin-bottom: 6px; }
        .stat-value { font-size: 20px; font-weight: bold; letter-spacing: -0.5px; }

        .progress-bar-wrap { background: #f1f5f9; border-radius: 4px; height: 6px; margin-top: 6px; }
        .progress-bar-fill { height: 6px; border-radius: 4px; }

        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th { background: #f8fafc; padding: 10px 14px; text-align: left; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; border-bottom: 2px solid #e2e8f0; }
        td { padding: 10px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }

        .task-badge { display: inline-block; padding: 2px 8px; border-radius: 50px; font-size: 10px; font-weight: bold; }
        .task-todo { background: #f1f5f9; color: #64748b; }
        .task-progress { background: #dbeafe; color: #1e40af; }
        .task-done { background: #dcfce7; color: #166534; }
        .task-overdue { background: #fee2e2; color: #991b1b; }

        .summary-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 20px; }
        .summary-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 12px; }
        .summary-row.total { font-weight: bold; border-top: 1px solid #e2e8f0; margin-top: 8px; padding-top: 12px; font-size: 14px; }

        .footer { margin-top: 40px; padding: 16px 40px; border-top: 1px solid #f1f5f9; font-size: 10px; color: #94a3b8; display: flex; justify-content: space-between; }
        .alert-box { border-left: 4px solid #f59e0b; background: #fffbeb; padding: 10px 14px; border-radius: 0 6px 6px 0; margin-bottom: 8px; }
        .alert-danger { border-color: #ef4444; background: #fef2f2; }

        .two-col { display: table; width: 100%; border-collapse: collapse; }
        .col-left { display: table-cell; width: 60%; padding-right: 20px; vertical-align: top; }
        .col-right { display: table-cell; width: 40%; vertical-align: top; }
    </style>
</head>
<body>

{{-- HEADER --}}
<div class="header">
    <div class="subtitle">Vivensi · Relatório Executivo de Projeto</div>
    <h1>{{ $project->name }}</h1>
    <div class="meta">
        <div class="meta-item">
            Status
            <strong>{{ ['active'=>'Ativo','paused'=>'Pausado','completed'=>'Concluído','canceled'=>'Cancelado'][$project->status] ?? $project->status }}</strong>
        </div>
        <div class="meta-item">
            Organização
            <strong>{{ $tenant->name ?? '—' }}</strong>
        </div>
        <div class="meta-item">
            Prazo Final
            <strong>{{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('d/m/Y') : 'Indefinido' }}</strong>
        </div>
        <div class="meta-item">
            Gerado em
            <strong>{{ now()->format('d/m/Y H:i') }}</strong>
        </div>
    </div>
</div>

{{-- ALERTAS --}}
@if($project->end_date && \Carbon\Carbon::parse($project->end_date)->isPast() && $project->status !== 'completed')
<div class="section">
    <div class="alert-box alert-danger">
        ⚠️ <strong>Projeto com prazo vencido!</strong> — Prazo: {{ \Carbon\Carbon::parse($project->end_date)->format('d/m/Y') }}
    </div>
</div>
@elseif($project->end_date && \Carbon\Carbon::parse($project->end_date)->diffInDays(now()) <= 7 && $project->status === 'active')
<div class="section">
    <div class="alert-box">
        🔔 <strong>Prazo se aproximando!</strong> — Restam {{ now()->diffInDays($project->end_date) }} dias
    </div>
</div>
@endif

@if($percentUsed >= 90)
<div class="section">
    <div class="alert-box alert-danger">
        💸 <strong>Orçamento crítico!</strong> — {{ $percentUsed }}% do budget foi consumido (R$ {{ number_format($totalSpent, 2, ',', '.') }} de R$ {{ number_format($project->budget, 2, ',', '.') }})
    </div>
</div>
@endif

{{-- RESUMO FINANCEIRO --}}
<div class="section">
    <div class="section-title">📊 Resumo Financeiro</div>
    <div class="stat-grid">
        <div class="stat-cell">
            <div class="stat-label">Orçamento Total</div>
            <div class="stat-value" style="color: #10b981;">R$ {{ number_format($project->budget, 0, ',', '.') }}</div>
        </div>
        <div class="stat-cell">
            <div class="stat-label">Gasto Confirmado</div>
            <div class="stat-value" style="color: #ef4444;">R$ {{ number_format($totalSpent, 0, ',', '.') }}</div>
        </div>
        <div class="stat-cell">
            <div class="stat-label">Saldo Disponível</div>
            <div class="stat-value" style="color: #6366f1;">R$ {{ number_format($project->budget - $totalSpent, 0, ',', '.') }}</div>
        </div>
        <div class="stat-cell">
            <div class="stat-label">Budget Consumido</div>
            <div class="stat-value" style="color: {{ $percentUsed >= 90 ? '#ef4444' : '#f59e0b' }};">{{ $percentUsed }}%</div>
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" style="width: {{ $percentUsed }}%; background: {{ $percentUsed >= 90 ? '#ef4444' : '#10b981' }};"></div>
            </div>
        </div>
    </div>
</div>

{{-- PROGRESSO DE TAREFAS --}}
<div class="section">
    <div class="section-title">✅ Progresso das Tarefas</div>
    <div class="stat-grid">
        <div class="stat-cell">
            <div class="stat-label">Total de Tarefas</div>
            <div class="stat-value">{{ $taskStats['total'] }}</div>
        </div>
        <div class="stat-cell">
            <div class="stat-label">Concluídas</div>
            <div class="stat-value" style="color: #10b981;">{{ $taskStats['done'] }}</div>
        </div>
        <div class="stat-cell">
            <div class="stat-label">Em Andamento</div>
            <div class="stat-value" style="color: #6366f1;">{{ $taskStats['in_progress'] }}</div>
        </div>
        <div class="stat-cell">
            <div class="stat-label">Progresso Geral</div>
            <div class="stat-value">{{ $taskStats['progress'] }}%</div>
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" style="width: {{ $taskStats['progress'] }}%; background: #6366f1;"></div>
            </div>
        </div>
    </div>
</div>

{{-- LISTA DE TAREFAS --}}
@if($tasks->count() > 0)
<div class="section">
    <div class="section-title">📋 Lista de Tarefas</div>
    <table>
        <thead>
            <tr>
                <th>Tarefa</th>
                <th>Status</th>
                <th>Prioridade</th>
                <th>Prazo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tasks->take(25) as $task)
            @php
                $isOverdue = $task->due_date && \Carbon\Carbon::parse($task->due_date)->isPast() && !in_array($task->status, ['done','completed']);
                $statusMap = ['todo'=>['Pendente','task-todo'],'in_progress'=>['Em Andamento','task-progress'],'review'=>['Revisão','task-progress'],'done'=>['Concluída','task-done'],'completed'=>['Concluída','task-done']];
                $sm = $statusMap[$task->status] ?? ['—','task-todo'];
            @endphp
            <tr>
                <td>{{ \Illuminate\Support\Str::limit($task->title, 60) }}</td>
                <td><span class="task-badge {{ $isOverdue ? 'task-overdue' : $sm[1] }}">{{ $isOverdue ? 'VENCIDA' : $sm[0] }}</span></td>
                <td>{{ ucfirst($task->priority ?? '—') }}</td>
                <td style="color: {{ $isOverdue ? '#ef4444' : '#64748b' }}; font-weight: {{ $isOverdue ? 'bold' : 'normal' }};">
                    {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') : '—' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($tasks->count() > 25)
        <p style="font-size: 10px; color: #94a3b8; margin-top: 8px;">* Exibindo 25 de {{ $tasks->count() }} tarefas.</p>
    @endif
</div>
@endif

{{-- MEMBROS --}}
@if($members->count() > 0)
<div class="section">
    <div class="section-title">👥 Equipe do Projeto</div>
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Função no Projeto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($members as $m)
            <tr>
                <td><strong>{{ $m->user->name ?? '—' }}</strong></td>
                <td>{{ $m->user->email ?? '—' }}</td>
                <td>{{ ucfirst($m->access_level ?? '—') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- TRANSAÇÕES --}}
@if($transactions->count() > 0)
<div class="section">
    <div class="section-title">💰 Últimas Movimentações Financeiras</div>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Descrição</th>
                <th>Tipo</th>
                <th style="text-align: right;">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $tx)
            <tr>
                <td>{{ \Carbon\Carbon::parse($tx->date)->format('d/m/Y') }}</td>
                <td>{{ \Illuminate\Support\Str::limit($tx->description, 50) }}</td>
                <td style="color: {{ $tx->type === 'income' ? '#10b981' : '#ef4444' }}; font-weight: bold;">
                    {{ $tx->type === 'income' ? 'Receita' : 'Despesa' }}
                </td>
                <td style="text-align: right; font-weight: bold; color: {{ $tx->type === 'income' ? '#10b981' : '#ef4444' }};">
                    {{ $tx->type === 'expense' ? '-' : '+' }}R$ {{ number_format($tx->amount, 2, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- FOOTER --}}
<div class="footer">
    <span>Gerado pelo Sistema Vivensi · {{ now()->format('d/m/Y \à\s H:i') }}</span>
    <span>Documento confidencial — uso interno</span>
</div>

</body>
</html>
