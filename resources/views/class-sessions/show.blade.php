@extends('layouts.app')

@section('content')
@php
    $basePath = rtrim(request()->getBaseUrl(), '/');
    $canWrite = in_array(auth()->user()->role, ['manager','super_admin','ngo']);
@endphp

<div style="margin-bottom:30px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:15px;">
        <div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                <span style="background: var(--primary-color); width:12px; height:3px; border-radius:2px;"></span>
                <h6 style="color: var(--primary-color); font-weight:800; text-transform:uppercase; margin:0; letter-spacing:2px; font-size:0.7rem;">{{ $project->name }}</h6>
            </div>
            <h2 style="margin:0; color:#1e293b; font-weight:900; font-size:2rem; letter-spacing:-1px;">{{ $session->title }}</h2>
            <p style="color:#64748b; margin:6px 0 0 0;">
                {{ $session->date->format('d/m/Y') }}
                @if($session->start_time) — {{ substr($session->start_time,0,5) }}@endif
                @if($session->end_time) até {{ substr($session->end_time,0,5) }}@endif
                &nbsp;·&nbsp;Modo: <strong>{{ ucfirst($session->mode) }}</strong>
            </p>
        </div>
        <a href="{{ $basePath . '/projects/' . $project->id . '/class-sessions' }}" class="btn-ds btn-ds-ghost" style="text-decoration:none; font-weight:700;">
            <i class="fas fa-arrow-left me-2"></i> Voltar
        </a>
    </div>
</div>

@if(session('success'))
    <div style="background:#ecfdf5; color:#065f46; padding:14px 18px; border-radius:12px; margin-bottom:20px; border:1px solid #a7f3d0; font-weight:600;">
        {{ session('success') }}
    </div>
@endif

{{-- Painel de Link Publico (Fase 3) --}}
@if($canWrite)
    @php
        $hasToken = !empty($session->public_token_bidx);
        $publicUrl = $hasToken ? url('/chamada/' . $session->public_token) : null;
        $isExpired = $hasToken && $session->public_enabled_until && $session->public_enabled_until->isPast();
    @endphp
    <div class="vivensi-card" style="background:white; padding:22px 26px; border-radius:18px; box-shadow:0 8px 24px rgba(0,0,0,0.03); margin-bottom:24px; border:1px solid #eef2ff;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
            <div>
                <h4 style="margin:0 0 6px; color:#1e293b; font-weight:900; font-size:1.05rem;">
                    <i class="fas fa-link" style="color:#4f46e5;"></i>&nbsp; Link Público de Chamada
                </h4>
                <p style="margin:0; color:#64748b; font-size:0.85rem;">
                    Alunos marcam presença pelo celular. Modo <strong>{{ ucfirst($session->mode) }}</strong>{{ $session->mode === 'aberta' ? ' — permite autocadastro.' : ' — só matriculados ativos.' }}
                </p>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <form method="POST" action="{{ $basePath . '/projects/' . $project->id . '/class-sessions/' . $session->id . '/token/generate' }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn-ds btn-ds-primary" style="font-weight:800; font-size:0.85rem;">
                        <i class="fas fa-{{ $hasToken ? 'sync-alt' : 'plus' }}"></i>&nbsp; {{ $hasToken ? 'Regerar' : 'Gerar Link' }}
                    </button>
                </form>
                @if($hasToken)
                    <form method="POST" action="{{ $basePath . '/projects/' . $project->id . '/class-sessions/' . $session->id . '/token/revoke' }}" style="margin:0;" onsubmit="return confirm('Revogar o link público? Novos check-ins serão bloqueados.');">
                        @csrf
                        <button type="submit" class="btn-ds btn-ds-ghost" style="font-weight:700; font-size:0.85rem; color:#dc2626;">
                            <i class="fas fa-ban"></i>&nbsp; Revogar
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if($hasToken)
            <div style="margin-top:16px; padding:14px 16px; background:#f8fafc; border-radius:12px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <input type="text" readonly value="{{ $publicUrl }}"
                       id="public-url"
                       style="flex:1; min-width:250px; padding:10px 14px; border:1px solid #e2e8f0; border-radius:8px; background:white; font-family:monospace; font-size:0.85rem;">
                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('public-url').value); this.innerText='Copiado!';"
                        class="btn-ds btn-ds-ghost" style="font-size:0.8rem; font-weight:700;">
                    <i class="fas fa-copy"></i>&nbsp; Copiar
                </button>
            </div>
            <div style="margin-top:10px; font-size:0.8rem; color:{{ $isExpired ? '#b91c1c' : '#64748b' }};">
                @if($isExpired)
                    <i class="fas fa-exclamation-triangle"></i>&nbsp; Expirou em {{ $session->public_enabled_until->format('d/m/Y H:i') }} — clique em "Regerar" para novo link.
                @else
                    <i class="fas fa-clock"></i>&nbsp; Ativo até {{ $session->public_enabled_until->format('d/m/Y H:i') }}.
                @endif
            </div>
        @endif
    </div>
@endif

<div class="vivensi-card" style="background:white; padding:0; border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,0.03); overflow:hidden;">
    @if($students->isEmpty())
        <div style="padding:60px 30px; text-align:center; color:#64748b;">
            <i class="fas fa-users" style="font-size:3rem; color:#cbd5e1; margin-bottom:15px;"></i>
            <p style="margin:0; font-weight:600;">Nenhum aluno ativo neste projeto.</p>
            <p style="margin:6px 0 0; font-size:0.9rem;">Cadastre pessoas no projeto para poder registrar chamada.</p>
        </div>
    @else
        <form method="POST" action="{{ $basePath . '/projects/' . $project->id . '/class-sessions/' . $session->id . '/attendance' }}">
            @csrf
            <table style="width:100%; border-collapse:collapse;">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th style="text-align:left; padding:16px 20px; font-size:0.75rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Aluno</th>
                        <th style="text-align:left; padding:16px 20px; font-size:0.75rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Status</th>
                        <th style="text-align:left; padding:16px 20px; font-size:0.75rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px;">Justificativa (se falta justificada)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $student)
                        @php
                            $prev = $existing[$student->id] ?? null;
                            // Decisão 1: pré-popula com 'presente'; se ja tem registro, usa o valor salvo.
                            $status = $prev?->status ?? 'presente';
                            $inactive = $student->enrollment_status === 'inativo';
                        @endphp
                        <tr style="border-top:1px solid #f1f5f9;">
                            <td style="padding:14px 20px; font-weight:700; color:#1e293b;">
                                {{ $student->name }}
                                @if($inactive)
                                    <span style="margin-left:8px; padding:2px 8px; border-radius:6px; font-size:0.7rem; background:#fef2f2; color:#b91c1c; font-weight:700;">inativo</span>
                                @endif
                            </td>
                            <td style="padding:14px 20px;">
                                <select name="attendance[{{ $student->id }}][status]"
                                        {{ $canWrite ? '' : 'disabled' }}
                                        onchange="toggleJustificativa(this, {{ $student->id }})"
                                        style="padding:10px 14px; border:2px solid #f1f5f9; border-radius:10px; background:white; font-weight:600;">
                                    <option value="presente" {{ $status === 'presente' ? 'selected' : '' }}>Presente</option>
                                    <option value="falta" {{ $status === 'falta' ? 'selected' : '' }}>Falta</option>
                                    <option value="falta_justificada" {{ $status === 'falta_justificada' ? 'selected' : '' }}>Falta justificada</option>
                                </select>
                            </td>
                            <td style="padding:14px 20px;">
                                <input type="text" name="attendance[{{ $student->id }}][justification]"
                                       id="just-{{ $student->id }}"
                                       value="{{ $prev?->justification }}"
                                       maxlength="500"
                                       placeholder="Motivo da ausência"
                                       {{ $canWrite ? '' : 'disabled' }}
                                       style="width:100%; padding:10px 14px; border:2px solid #f1f5f9; border-radius:10px; background:{{ $status === 'falta_justificada' ? '#fffbeb' : '#f8fafc' }}; font-weight:500; display:{{ $status === 'falta_justificada' ? 'block' : 'none' }};">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($canWrite)
                <div style="padding:20px; display:flex; justify-content:flex-end;">
                    <button type="submit" class="btn-ds btn-ds-primary" style="font-weight:800;">
                        <i class="fas fa-save me-2"></i> Salvar Chamada
                    </button>
                </div>
            @endif
        </form>
    @endif
</div>

<script>
function toggleJustificativa(sel, personId) {
    var input = document.getElementById('just-' + personId);
    if (!input) return;
    if (sel.value === 'falta_justificada') {
        input.style.display = 'block';
        input.style.background = '#fffbeb';
    } else {
        input.style.display = 'none';
        input.value = '';
    }
}
</script>
@endsection
