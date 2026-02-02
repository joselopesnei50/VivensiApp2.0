@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 40px; position: relative;">
    <div style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%); position: absolute; top: -30px; left: -30px; right: -30px; bottom: 0; z-index: -1;"></div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: #6366f1; width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: #6366f1; font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Fluxo de Trabalho</h6>
            </div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.5rem; letter-spacing: -1.5px;">Minha Agenda & Missões</h2>
            <p style="color: #64748b; margin: 8px 0 0 0; font-size: 1.1rem; font-weight: 500;">Controle suas pendências e compromissos com precisão.</p>
        </div>
        <div style="display: flex; gap: 15px;">
             <a href="{{ url('/tasks/calendar') }}" class="btn-premium" style="background: white; color: #64748b; border: 1px solid #e2e8f0; text-decoration: none; font-weight: 700;">
                <i class="fas fa-calendar-days me-2"></i> Ver Calendário
            </a>
            <a href="{{ url('/tasks/create') }}" class="btn-premium btn-premium-shine" style="border: none; padding: 14px 28px; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-plus-circle"></i> Novo Lembrete
            </a>
        </div>
    </div>
</div>

<div class="vivensi-card" style="padding: 0; border-radius: 28px; background: white; border: 1px solid #f1f5f9; box-shadow: 0 15px 45px rgba(0,0,0,0.02); overflow: hidden;">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #f1f5f9;">
                    <th style="padding: 20px 25px; text-align: left; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Atividade</th>
                    <th style="padding: 20px 25px; text-align: left; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Prioridade</th>
                    <th style="padding: 20px 25px; text-align: left; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Data Limite</th>
                    <th style="padding: 20px 25px; text-align: left; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Status</th>
                    <th style="padding: 20px 25px; text-align: center; font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                <tr style="border-bottom: 1px solid #f8fafc; transition: background 0.2s;" onmouseover="this.style.background='#fbfcfe';" onmouseout="this.style.background='white';">
                    <td style="padding: 20px 25px;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 42px; height: 42px; border-radius: 12px; background: #eef2ff; color: #6366f1; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; border: 1px solid #e0e7ff;">
                                <i class="fas fa-check-double"></i>
                            </div>
                            <div>
                                <div style="font-weight: 900; color: #1e293b; font-size: 1rem; margin-bottom: 2px;">{{ $task->title }}</div>
                                <div style="font-size: 0.8rem; color: #64748b; font-weight: 500;">{{ Str::limit($task->description, 60) }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding: 20px 25px;">
                        @php
                            $prioColors = [
                                'high' => ['bg' => '#fef2f2', 'text' => '#ef4444', 'label' => 'Crítica'],
                                'medium' => ['bg' => '#fff7ed', 'text' => '#f59e0b', 'label' => 'Importante'],
                                'low' => ['bg' => '#f0fdf4', 'text' => '#10b981', 'label' => 'Normal']
                            ];
                            $p = $prioColors[$task->priority] ?? ['bg' => '#f1f5f9', 'text' => '#64748b', 'label' => $task->priority];
                        @endphp
                        <span style="background: {{ $p['bg'] }}; color: {{ $p['text'] }}; padding: 6px 14px; border-radius: 10px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
                            {{ $p['label'] }}
                        </span>
                    </td>
                    <td style="padding: 20px 25px;">
                        <div style="color: #475569; font-size: 0.9rem; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                            <i class="far fa-calendar-clock" style="color: #cbd5e1;"></i>
                            {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d M, Y') : '--' }}
                        </div>
                    </td>
                    <td style="padding: 20px 25px;">
                        <span style="padding: 6px 14px; border-radius: 10px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #e2e8f0; background: #f8fafc; color: #64748b;">
                            {{ ucfirst($task->status) }}
                        </span>
                    </td>
                    <td style="padding: 20px 25px; text-align: center;">
                        <div style="display: flex; justify-content: center; gap: 8px;">
                            <button class="btn btn-light btn-sm rounded-circle shadow-sm" style="width: 36px; height: 36px; border: 1px solid #f1f5f9;">
                                <i class="fas fa-edit" style="color: #6366f1; font-size: 0.85rem;"></i>
                            </button>
                            <button class="btn btn-light btn-sm rounded-circle shadow-sm" style="width: 36px; height: 36px; border: 1px solid #f1f5f9;">
                                <i class="fas fa-check" style="color: #10b981; font-size: 0.85rem;"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding: 100px 20px; text-align: center;">
                        <div style="width: 80px; height: 80px; background: #f8fafc; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px;">
                            <i class="fas fa-calendar-check" style="font-size: 2.5rem; color: #e2e8f0;"></i>
                        </div>
                        <h4 style="color: #1e293b; font-weight: 900; font-size: 1.4rem; margin-bottom: 8px;">Silêncio na Agenda</h4>
                        <p style="color: #94a3b8; font-size: 0.95rem; font-weight: 500; margin-bottom: 25px;">Tudo sobre controle. Não há pendências no radar no momento.</p>
                        <a href="{{ url('/tasks/create') }}" class="btn-premium" style="display: inline-block; text-decoration: none; padding: 12px 30px; font-weight: 800;">AGENDAR TAREFA</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($tasks->hasPages())
    <div style="padding: 25px; background: #f8fafc; border-top: 1px solid #f1f5f9;">
        {{ $tasks->links() }}
    </div>
    @endif
</div>
@endsection

