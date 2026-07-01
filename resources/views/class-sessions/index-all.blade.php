@extends('layouts.app')

@section('content')
@php $basePath = rtrim(request()->getBaseUrl(), '/'); @endphp

<div style="margin-bottom: 30px;">
    <div>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
            <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px;"></span>
            <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Lista de Presença</h6>
        </div>
        <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2rem; letter-spacing: -1px;">Sessões de Todos os Projetos</h2>
        <p style="color: #64748b; margin: 6px 0 0 0;">Para criar sessão nova, entre no projeto desejado.</p>
    </div>
</div>

@if(session('success'))
    <div style="background:#ecfdf5; color:#065f46; padding:14px 18px; border-radius:12px; margin-bottom:20px; border:1px solid #a7f3d0; font-weight:600;">
        {{ session('success') }}
    </div>
@endif

<div class="vivensi-card" style="background:white; padding:0; border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,0.03); overflow:hidden;">
    @if($sessions->isEmpty())
        <div style="padding:60px 30px; text-align:center; color:#64748b;">
            <i class="fas fa-clipboard-list" style="font-size:3rem; color:#cbd5e1; margin-bottom:15px;"></i>
            <p style="margin:0; font-weight:600;">Nenhuma sessão cadastrada ainda em nenhum projeto.</p>
        </div>
    @else
        <table style="width:100%; border-collapse:collapse;">
            <thead style="background:#f8fafc;">
                <tr>
                    <th style="text-align:left; padding:16px 20px; font-size:0.75rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Data</th>
                    <th style="text-align:left; padding:16px 20px; font-size:0.75rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Projeto</th>
                    <th style="text-align:left; padding:16px 20px; font-size:0.75rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Sessão</th>
                    <th style="text-align:right; padding:16px 20px; font-size:0.75rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sessions as $s)
                    <tr style="border-top:1px solid #f1f5f9;">
                        <td style="padding:14px 20px; font-weight:700; color:#1e293b;">{{ $s->date->format('d/m/Y') }}</td>
                        <td style="padding:14px 20px; color:#334155;">{{ $s->project?->name ?? '—' }}</td>
                        <td style="padding:14px 20px; color:#334155;">{{ $s->title }}</td>
                        <td style="padding:14px 20px; text-align:right; white-space:nowrap;">
                            <a href="{{ $basePath . '/projects/' . $s->project_id . '/class-sessions/' . $s->id }}" style="color:#4f46e5; font-weight:700; text-decoration:none;">Abrir Chamada</a>
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
@endsection
