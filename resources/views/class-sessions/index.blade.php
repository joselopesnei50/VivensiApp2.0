@extends('layouts.app')

@section('content')
<div data-theme-scope>
@php $basePath = rtrim(request()->getBaseUrl(), '/'); @endphp

<div style="margin-bottom: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">{{ $project->name }}</h6>
            </div>
            <h2 style="margin: 0; color: var(--ds-text); font-weight: 900; font-size: 2rem; letter-spacing: -1px;">Lista de Presença</h2>
            <p style="color: var(--ds-text-muted); margin: 6px 0 0 0; font-size: 1rem;">Sessões e chamadas deste projeto.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="{{ $basePath . '/projects/' . $project->id }}" class="btn-ds btn-ds-ghost" style="text-decoration: none; font-weight: 700;">
                <i class="fas fa-arrow-left me-2"></i> Voltar ao Projeto
            </a>
            @if(in_array(auth()->user()->role, ['manager','super_admin','ngo']))
                <a href="{{ $basePath . '/projects/' . $project->id . '/class-sessions/create' }}" class="btn-ds btn-ds-primary" style="text-decoration: none; font-weight: 700;">
                    <i class="fas fa-plus me-2"></i> Nova Sessão
                </a>
            @endif
        </div>
    </div>
</div>

@if(session('success'))
    <div style="background:#ecfdf5; color:#065f46; padding:14px 18px; border-radius:12px; margin-bottom:20px; border:1px solid #a7f3d0; font-weight:600;">
        {{ session('success') }}
    </div>
@endif

<div class="vivensi-card" style="background: var(--ds-surface); padding:0; border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,0.03); overflow:hidden;">
    @if($sessions->isEmpty())
        <div style="padding:60px 30px; text-align:center; color: var(--ds-text-muted);">
            <i class="fas fa-clipboard-list" style="font-size:3rem; color:#cbd5e1; margin-bottom:15px;"></i>
            <p style="margin:0; font-weight:600;">Nenhuma sessão cadastrada ainda.</p>
        </div>
    @else
        <table style="width:100%; border-collapse:collapse;">
            <thead style="background: var(--vv-surface-soft);">
                <tr>
                    <th style="text-align:left; padding:16px 20px; font-size:0.75rem; font-weight:800; color: var(--ds-text-muted); text-transform:uppercase; letter-spacing:1px;">Data</th>
                    <th style="text-align:left; padding:16px 20px; font-size:0.75rem; font-weight:800; color: var(--ds-text-muted); text-transform:uppercase; letter-spacing:1px;">Título</th>
                    <th style="text-align:left; padding:16px 20px; font-size:0.75rem; font-weight:800; color: var(--ds-text-muted); text-transform:uppercase; letter-spacing:1px;">Horário</th>
                    <th style="text-align:left; padding:16px 20px; font-size:0.75rem; font-weight:800; color: var(--ds-text-muted); text-transform:uppercase; letter-spacing:1px;">Modo</th>
                    <th style="text-align:right; padding:16px 20px; font-size:0.75rem; font-weight:800; color: var(--ds-text-muted); text-transform:uppercase; letter-spacing:1px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sessions as $s)
                    <tr style="border-top: 1px solid var(--ds-border);">
                        <td style="padding:14px 20px; font-weight:700; color: var(--ds-text);">{{ $s->date->format('d/m/Y') }}</td>
                        <td style="padding:14px 20px; color: var(--ds-text);">{{ $s->title }}</td>
                        <td style="padding:14px 20px; color: var(--ds-text-muted); font-size:0.9rem;">
                            {{ $s->start_time ? substr($s->start_time,0,5) : '—' }}
                            @if($s->end_time) – {{ substr($s->end_time,0,5) }}@endif
                        </td>
                        <td style="padding:14px 20px;">
                            <span style="padding:4px 10px; border-radius:8px; font-size:0.75rem; font-weight:700; background:{{ $s->mode === 'fechada' ? '#eef2ff' : '#fef3c7' }}; color:{{ $s->mode === 'fechada' ? '#4338ca' : '#92400e' }};">
                                {{ ucfirst($s->mode) }}
                            </span>
                        </td>
                        <td style="padding:14px 20px; text-align:right; white-space:nowrap;">
                            <a href="{{ $basePath . '/projects/' . $project->id . '/class-sessions/' . $s->id }}" style="color: var(--vv-link); font-weight:700; text-decoration:none; margin-right:12px;">Chamada</a>
                            @if(in_array(auth()->user()->role, ['manager','super_admin','ngo']))
                                <a href="{{ $basePath . '/projects/' . $project->id . '/class-sessions/' . $s->id . '/edit' }}" style="color: var(--ds-text-muted); text-decoration:none; margin-right:12px;">Editar</a>
                                <form action="{{ $basePath . '/projects/' . $project->id . '/class-sessions/' . $s->id }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Remover esta sessão? As presenças registradas serão apagadas.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer; font-weight:700;">Excluir</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:16px 20px;">
            {{ $sessions->links() }}
        </div>
    @endif
</div>
</div>
@endsection
