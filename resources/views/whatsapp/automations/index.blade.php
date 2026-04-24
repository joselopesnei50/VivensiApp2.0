@extends('layouts.app')
@section('title', 'Automações WhatsApp')

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <p class="mb-1" style="color:#6366f1;font-weight:700;text-transform:uppercase;font-size:.72rem;letter-spacing:1.5px;">
                <i class="fab fa-whatsapp me-1"></i> WhatsApp
            </p>
            <h2 class="fw-800 mb-0" style="font-size:1.8rem;color:#111827;">Automações de Reativação</h2>
            <p class="text-muted small mb-0">Envie mensagens automáticas para contatos inativos no momento certo.</p>
        </div>
        <a href="{{ route('whatsapp.automations.create') }}" class="btn btn-primary fw-bold px-4 py-2">
            <i class="fas fa-plus me-2"></i> Nova Automação
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 rounded-3 mb-4">{{ session('success') }}</div>
    @endif

    @if($automations->isEmpty())
        <div class="text-center py-5" style="background:#f8faff;border:2px dashed #e0e7ff;border-radius:20px;">
            <div style="width:64px;height:64px;background:#ede9fe;border-radius:16px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                <i class="fas fa-robot" style="font-size:1.6rem;color:#7c3aed;"></i>
            </div>
            <h5 class="fw-bold text-dark">Nenhuma automação criada ainda</h5>
            <p class="text-muted small mb-4">Crie regras para reativar doadores, patrocinadores e contatos automaticamente.</p>
            <a href="{{ route('whatsapp.automations.create') }}" class="btn btn-primary px-4 fw-bold">
                <i class="fas fa-plus me-2"></i> Criar primeira automação
            </a>
        </div>
    @else
        <div class="row g-3">
            @foreach($automations as $automation)
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 {{ $automation->is_active ? '' : 'opacity-75' }}">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">

                            {{-- Info principal --}}
                            <div class="d-flex gap-3 align-items-start flex-1">
                                <div style="width:44px;height:44px;border-radius:12px;background:{{ $automation->is_active ? 'rgba(37,211,102,.12)' : 'rgba(100,116,139,.1)' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fab fa-whatsapp" style="font-size:1.2rem;color:{{ $automation->is_active ? '#25d366' : '#94a3b8' }};"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">{{ $automation->name }}</div>
                                    <div class="text-muted small mt-1">
                                        @php
                                            $triggerLabels = [
                                                'donor_inactive_days'      => 'Doador sem doação',
                                                'sponsorship_stale_days'   => 'Patrocínio parado em proposta',
                                                'no_contact_days'          => 'Contato sem interação',
                                                'open_conversation_days'   => 'Conversa aberta sem resposta',
                                            ];
                                        @endphp
                                        <i class="fas fa-clock me-1"></i>
                                        {{ $triggerLabels[$automation->trigger] ?? $automation->trigger }}
                                        há <strong>{{ $automation->trigger_days }} dias</strong>
                                        &nbsp;·&nbsp;
                                        <i class="fas fa-clock me-1"></i> Envia entre {{ $automation->send_window_start }} e {{ $automation->send_window_end }}
                                    </div>
                                    <div class="mt-2 p-2 rounded-3 small text-muted" style="background:#f8fafc;border:1px solid #e2e8f0;max-width:520px;font-size:.78rem;font-style:italic;">
                                        "{{ Str::limit($automation->message_template, 100) }}"
                                    </div>
                                </div>
                            </div>

                            {{-- Stats + Ações --}}
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div class="text-center">
                                    <div class="fw-bold" style="font-size:1.2rem;color:#4f46e5;">{{ $automation->logs_count }}</div>
                                    <div style="font-size:.65rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">enviados</div>
                                </div>

                                <span class="badge rounded-pill px-3 py-2" style="font-size:.7rem;background:{{ $automation->is_active ? 'rgba(16,185,129,.1)' : 'rgba(100,116,139,.1)' }};color:{{ $automation->is_active ? '#059669' : '#64748b' }};">
                                    {{ $automation->is_active ? 'Ativa' : 'Pausada' }}
                                </span>

                                <div class="d-flex gap-2">
                                    <form action="{{ route('whatsapp.automations.toggle', $automation) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm {{ $automation->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $automation->is_active ? 'Pausar' : 'Ativar' }}">
                                            <i class="fas fa-{{ $automation->is_active ? 'pause' : 'play' }}"></i>
                                        </button>
                                    </form>
                                    <a href="{{ route('whatsapp.automations.edit', $automation) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form action="{{ route('whatsapp.automations.destroy', $automation) }}" method="POST"
                                          onsubmit="return confirm('Remover esta automação?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Remover">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Legenda --}}
        <div class="mt-4 p-3 rounded-3 small text-muted" style="background:#f8faff;border:1px solid #e0e7ff;">
            <i class="fas fa-circle-info me-1 text-primary"></i>
            As automações são processadas automaticamente todos os dias às <strong>10:00</strong>.
            Use as variáveis <code>{nome}</code> e <code>{organizacao}</code> nas mensagens para personalização.
        </div>
    @endif
</div>
@endsection
