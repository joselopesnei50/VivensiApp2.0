@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h6 style="color: #ef4444; font-weight: 700; text-transform: uppercase; margin: 0 0 5px 0; letter-spacing: 1px;">Infraestrutura</h6>
        <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">Jobs Falhados</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">
            {{ $jobs->total() }} job(s) na fila de falhas.
            @if($jobs->total() > 0)
                <span style="color: #ef4444; font-weight: 600;">Requer atenção.</span>
            @else
                <span style="color: #16a34a; font-weight: 600;">Tudo limpo.</span>
            @endif
        </p>
    </div>
    @if($jobs->total() > 0)
    <div style="display: flex; gap: 10px; align-items: center;">
        <form method="POST" action="{{ route('admin.failed-jobs.retry-all') }}"
              onsubmit="return confirm('Retentar todos os {{ $jobs->total() }} jobs?')">
            @csrf
            <button type="submit" class="dash-btn-ghost">
                <i class="fas fa-rotate-right"></i> Retentar Todos
            </button>
        </form>
        <form method="POST" action="{{ route('admin.failed-jobs.flush') }}"
              onsubmit="return confirm('Apagar todos os {{ $jobs->total() }} jobs falhados? Esta ação não pode ser desfeita.')">
            @csrf @method('DELETE')
            <button type="submit" style="background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; padding: 8px 18px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                <i class="fas fa-trash"></i> Limpar Tudo
            </button>
        </form>
    </div>
    @endif
</div>

@if(session('success'))
<div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; padding: 14px 20px; border-radius: 10px; margin-bottom: 20px; font-weight: 600;">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background: #fef2f2; border: 1px solid #fecaca; color: #ef4444; padding: 14px 20px; border-radius: 10px; margin-bottom: 20px; font-weight: 600;">
    <i class="fas fa-times-circle me-2"></i>{{ session('error') }}
</div>
@endif

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    <div style="padding: 20px 25px; border-bottom: 1px solid #f1f5f9; background: #f8fafc;">
        <h4 style="margin: 0; font-size: 1rem; color: #334155;">
            <i class="fas fa-circle-exclamation me-2" style="color: #ef4444;"></i>
            Histórico de Falhas
        </h4>
    </div>

    <div class="table-responsive">
        <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead style="background: white; border-bottom: 1px solid #e2e8f0;">
                <tr>
                    <th style="padding: 14px 20px; text-align: left; font-size: 0.78rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Job</th>
                    <th style="padding: 14px 20px; text-align: left; font-size: 0.78rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Fila</th>
                    <th style="padding: 14px 20px; text-align: left; font-size: 0.78rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Erro</th>
                    <th style="padding: 14px 20px; text-align: left; font-size: 0.78rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Falhou em</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 0.78rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jobs as $job)
                <tr style="border-bottom: 1px solid #f1f5f9;" x-data="{ open: false }">
                    <td style="padding: 14px 20px;">
                        <div style="font-weight: 700; color: #1e293b; font-size: 0.88rem; font-family: monospace;">
                            {{ class_basename($job->job_class) }}
                        </div>
                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 2px; font-family: monospace;">
                            {{ $job->uuid }}
                        </div>
                    </td>
                    <td style="padding: 14px 20px;">
                        <span style="background: #e0f2fe; color: #0284c7; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                            {{ $job->queue }}
                        </span>
                    </td>
                    <td style="padding: 14px 20px; max-width: 320px;">
                        <div style="font-size: 0.82rem; color: #ef4444; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 320px;"
                             title="{{ $job->exception }}">
                            {{ Str::limit(explode("\n", $job->exception)[0], 100) }}
                        </div>
                        <button onclick="this.closest('tr').querySelector('.exception-full').classList.toggle('d-none')"
                                style="font-size: 0.7rem; color: #6366f1; background: none; border: none; cursor: pointer; padding: 0; margin-top: 4px;">
                            Ver completo
                        </button>
                        <pre class="exception-full d-none"
                             style="font-size: 0.72rem; background: #fef2f2; color: #7f1d1d; padding: 10px; border-radius: 6px; margin-top: 8px; white-space: pre-wrap; word-break: break-all; max-height: 200px; overflow-y: auto;">{{ $job->exception }}</pre>
                    </td>
                    <td style="padding: 14px 20px; color: #64748b; font-size: 0.85rem; white-space: nowrap;">
                        {{ \Carbon\Carbon::parse($job->failed_at)->format('d/m/Y H:i:s') }}
                        <div style="font-size: 0.75rem; color: #94a3b8;">
                            {{ \Carbon\Carbon::parse($job->failed_at)->diffForHumans() }}
                        </div>
                    </td>
                    <td style="padding: 14px 20px; text-align: right; white-space: nowrap;">
                        <form method="POST" action="{{ route('admin.failed-jobs.retry', $job->uuid) }}"
                              style="display: inline-block;">
                            @csrf
                            <button type="submit" title="Retentar"
                                    style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; padding: 6px 14px; border-radius: 6px; font-size: 0.78rem; font-weight: 600; cursor: pointer; margin-right: 6px;">
                                <i class="fas fa-rotate-right"></i> Retentar
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.failed-jobs.destroy', $job->uuid) }}"
                              style="display: inline-block;"
                              onsubmit="return confirm('Remover este job?')">
                            @csrf @method('DELETE')
                            <button type="submit" title="Remover"
                                    style="background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; padding: 6px 12px; border-radius: 6px; font-size: 0.78rem; cursor: pointer;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding: 60px; text-align: center; color: #94a3b8;">
                        <i class="fas fa-circle-check" style="font-size: 2.5rem; color: #22c55e; display: block; margin-bottom: 12px;"></i>
                        Nenhum job falhado. Sistema operando normalmente.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($jobs->hasPages())
    <div style="padding: 20px; border-top: 1px solid #f1f5f9;">
        {{ $jobs->links() }}
    </div>
    @endif
</div>
@endsection
