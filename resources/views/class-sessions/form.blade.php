@extends('layouts.app')

@section('content')
@php
    $basePath = rtrim(request()->getBaseUrl(), '/');
    $isEdit = $session->exists;
    $action = $isEdit
        ? $basePath . '/projects/' . $project->id . '/class-sessions/' . $session->id
        : $basePath . '/projects/' . $project->id . '/class-sessions';
@endphp

<div style="margin-bottom: 30px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:15px; flex-wrap:wrap;">
        <div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius:2px;"></span>
                <h6 style="color: var(--primary-color); font-weight:800; text-transform:uppercase; margin:0; letter-spacing:2px; font-size:0.7rem;">{{ $project->name }}</h6>
            </div>
            <h2 style="margin:0; color:#1e293b; font-weight:900; font-size:2rem; letter-spacing:-1px;">
                {{ $isEdit ? 'Editar Sessão' : 'Nova Sessão' }}
            </h2>
        </div>
        <a href="{{ $basePath . '/projects/' . $project->id . '/class-sessions' }}" class="btn-ds btn-ds-ghost" style="text-decoration:none; font-weight:700;">
            <i class="fas fa-arrow-left me-2"></i> Voltar
        </a>
    </div>
</div>

<div class="vivensi-card" style="max-width:820px; margin:0 auto; background:white; padding:35px 40px; border-radius:24px; box-shadow:0 15px 40px rgba(0,0,0,0.03);">
    @if ($errors->any())
        <div style="background:#fef2f2; color:#dc2626; padding:16px 20px; border-radius:12px; margin-bottom:24px; border:1px solid #fecaca;">
            <ul style="margin:0; padding-left:18px; font-weight:600; font-size:0.9rem;">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div style="margin-bottom:22px;">
            <label style="display:block; font-weight:700; color:#1e293b; margin-bottom:8px; font-size:0.9rem;">Título da Sessão</label>
            <input type="text" name="title" required maxlength="255"
                   value="{{ old('title', $session->title) }}"
                   style="width:100%; padding:14px 18px; border:2px solid #f1f5f9; border-radius:14px; background:#f8fafc; font-weight:600; font-size:1rem;">
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label style="display:block; font-weight:700; color:#1e293b; margin-bottom:8px; font-size:0.9rem;">Data</label>
                <input type="date" name="date" required
                       value="{{ old('date', optional($session->date)->format('Y-m-d')) }}"
                       style="width:100%; padding:14px 18px; border:2px solid #f1f5f9; border-radius:14px; background:#f8fafc; font-weight:600;">
            </div>
            <div class="col-md-4">
                <label style="display:block; font-weight:700; color:#1e293b; margin-bottom:8px; font-size:0.9rem;">Início</label>
                <input type="time" name="start_time"
                       value="{{ old('start_time', $session->start_time ? substr($session->start_time,0,5) : '') }}"
                       style="width:100%; padding:14px 18px; border:2px solid #f1f5f9; border-radius:14px; background:#f8fafc; font-weight:600;">
            </div>
            <div class="col-md-4">
                <label style="display:block; font-weight:700; color:#1e293b; margin-bottom:8px; font-size:0.9rem;">Fim</label>
                <input type="time" name="end_time"
                       value="{{ old('end_time', $session->end_time ? substr($session->end_time,0,5) : '') }}"
                       style="width:100%; padding:14px 18px; border:2px solid #f1f5f9; border-radius:14px; background:#f8fafc; font-weight:600;">
            </div>
        </div>

        <div class="row g-3" style="margin-top:8px;">
            <div class="col-md-6">
                <label style="display:block; font-weight:700; color:#1e293b; margin-bottom:8px; font-size:0.9rem; margin-top:14px;">Modo da Lista</label>
                <select name="mode" required
                        style="width:100%; padding:14px 18px; border:2px solid #f1f5f9; border-radius:14px; background:#f8fafc; font-weight:600;">
                    <option value="fechada" {{ old('mode', $session->mode) === 'fechada' ? 'selected' : '' }}>Fechada (só matriculados)</option>
                    <option value="aberta"  {{ old('mode', $session->mode) === 'aberta'  ? 'selected' : '' }}>Aberta (autocadastro)</option>
                </select>
            </div>
            <div class="col-md-6">
                <label style="display:block; font-weight:700; color:#1e293b; margin-bottom:8px; font-size:0.9rem; margin-top:14px;">Professor (ID do usuário)</label>
                <input type="number" name="teacher_user_id" min="1"
                       value="{{ old('teacher_user_id', $session->teacher_user_id) }}"
                       style="width:100%; padding:14px 18px; border:2px solid #f1f5f9; border-radius:14px; background:#f8fafc; font-weight:600;">
            </div>
        </div>

        <div style="margin-top:22px;">
            <label style="display:block; font-weight:700; color:#1e293b; margin-bottom:8px; font-size:0.9rem;">Observações</label>
            <textarea name="notes" rows="3" maxlength="2000"
                      style="width:100%; padding:14px 18px; border:2px solid #f1f5f9; border-radius:14px; background:#f8fafc; font-weight:500;">{{ old('notes', $session->notes) }}</textarea>
        </div>

        <div style="margin-top:30px; display:flex; justify-content:flex-end; gap:12px;">
            <a href="{{ $basePath . '/projects/' . $project->id . '/class-sessions' }}" class="btn-ds btn-ds-ghost" style="text-decoration:none; font-weight:700;">Cancelar</a>
            <button type="submit" class="btn-ds btn-ds-primary" style="font-weight:800;">
                {{ $isEdit ? 'Atualizar Sessão' : 'Criar Sessão' }}
            </button>
        </div>
    </form>
</div>
@endsection
