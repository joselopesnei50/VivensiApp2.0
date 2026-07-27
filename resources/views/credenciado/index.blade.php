@extends('layouts.app')
@section('title', 'Meus Projetos')

@section('content')
<div style="margin-bottom:28px;">
    <h2 style="margin:0; color:#1e293b; font-weight:900; font-size:1.6rem; letter-spacing:-0.5px;">
        <i class="fas fa-folder-tree me-2" style="color:#6366f1;"></i>Meus Projetos
    </h2>
    <p style="color:#64748b; margin:6px 0 0; font-size:0.9rem;">
        Selecione o projeto onde você quer trabalhar.
    </p>
</div>

@if(session('warning'))
    <div style="background:#fffbeb; border:1px solid #fcd34d; color:#92400e; border-radius:12px; padding:14px 18px; margin-bottom:20px; font-size:0.88rem;">
        <i class="fas fa-triangle-exclamation me-2"></i>{{ session('warning') }}
    </div>
@endif

@if($memberships->isEmpty())
    <div class="vivensi-card" style="padding:48px 32px; text-align:center; border-radius:20px;">
        <div style="width:72px; height:72px; margin:0 auto 18px; background:#f1f5f9; border-radius:50%; display:flex; align-items:center; justify-content:center;">
            <i class="fas fa-folder-open" style="font-size:1.8rem; color:#94a3b8;"></i>
        </div>
        <h4 style="margin:0 0 8px; font-weight:800; color:#1e293b; font-size:1.05rem;">Nenhum projeto vinculado</h4>
        <p style="color:#64748b; font-size:0.88rem; margin:0 0 20px; line-height:1.6;">
            Você ainda não foi vinculado a nenhum projeto ativo.<br>
            Entre em contato com a coordenação da organização para receber acesso.
        </p>
    </div>
@else
    <div class="row g-4">
        @foreach($memberships as $m)
            @php
                $p = $m->project;
                $badge = match($m->access_level) {
                    'admin'  => ['label' => 'Administrador', 'bg' => '#fef3c7', 'color' => '#92400e'],
                    'editor' => ['label' => 'Editor',        'bg' => '#dbeafe', 'color' => '#1e40af'],
                    default  => ['label' => 'Leitor',        'bg' => '#f1f5f9', 'color' => '#475569'],
                };
                $status = $p->status ?? 'active';
                $statusBadge = match($status) {
                    'active'     => ['label' => 'Ativo',      'bg' => '#dcfce7', 'color' => '#166534'],
                    'paused'     => ['label' => 'Pausado',    'bg' => '#fef3c7', 'color' => '#92400e'],
                    'completed'  => ['label' => 'Concluido',  'bg' => '#e0e7ff', 'color' => '#3730a3'],
                    'cancelled'  => ['label' => 'Cancelado',  'bg' => '#fee2e2', 'color' => '#991b1b'],
                    default      => ['label' => ucfirst($status), 'bg' => '#f1f5f9', 'color' => '#475569'],
                };
            @endphp
            <div class="col-md-6 col-lg-4">
                <a href="{{ route('credenciado.project', $p->id) }}"
                   style="display:block; text-decoration:none; color:inherit; height:100%;">
                    <div class="vivensi-card" style="padding:24px; border-radius:18px; height:100%; display:flex; flex-direction:column; gap:14px; cursor:pointer;">
                        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px;">
                            <div style="min-width:0;">
                                <h5 style="margin:0; color:#1e293b; font-weight:800; font-size:1rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    {{ $p->name }}
                                </h5>
                                @if($p->start_date || $p->end_date)
                                    <div style="color:#94a3b8; font-size:0.72rem; margin-top:4px;">
                                        <i class="far fa-calendar me-1"></i>
                                        {{ $p->start_date?->format('d/m/Y') ?? '—' }} → {{ $p->end_date?->format('d/m/Y') ?? '—' }}
                                    </div>
                                @endif
                            </div>
                            <span style="background:{{ $statusBadge['bg'] }}; color:{{ $statusBadge['color'] }}; font-size:0.68rem; font-weight:800; padding:3px 8px; border-radius:8px; text-transform:uppercase; letter-spacing:0.5px; flex-shrink:0;">
                                {{ $statusBadge['label'] }}
                            </span>
                        </div>

                        @if($p->description)
                            <p style="color:#64748b; font-size:0.82rem; line-height:1.5; margin:0; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden;">
                                {{ $p->description }}
                            </p>
                        @endif

                        <div style="display:flex; align-items:center; justify-content:space-between; margin-top:auto; padding-top:12px; border-top:1px solid #f1f5f9;">
                            <span style="background:{{ $badge['bg'] }}; color:{{ $badge['color'] }}; font-size:0.7rem; font-weight:700; padding:4px 10px; border-radius:8px;">
                                <i class="fas fa-user-shield me-1"></i>{{ $badge['label'] }}
                            </span>
                            <span style="color:#6366f1; font-weight:800; font-size:0.82rem;">
                                Abrir <i class="fas fa-arrow-right ms-1"></i>
                            </span>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endif
@endsection
