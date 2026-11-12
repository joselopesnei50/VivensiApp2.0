@extends('layouts.app')

{{-- Painel do Colaborador da ONG: atendimento do dia a dia, sem valores financeiros. --}}
@section('content')
@php
    $u = auth()->user();
    $first = explode(' ', trim($u->name))[0] ?? $u->name;
    $hour = (int) now()->format('H');
    $greet = $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite');
    $shortcuts = [
        ['url' => url('/ngo/beneficiaries'),        'icon' => 'fa-users',            'title' => 'Beneficiários',       'text' => 'Cadastro, famílias, atendimentos e termo LGPD.'],
        ['url' => url('/ngo/beneficiaries/create'), 'icon' => 'fa-user-plus',        'title' => 'Novo beneficiário',   'text' => 'Cadastrar uma pessoa atendida.'],
        ['url' => url('/class-sessions'),           'icon' => 'fa-clipboard-check',  'title' => 'Chamada',             'text' => 'Turmas, oficinas e lista de frequência.'],
        ['url' => url('/projects'),                 'icon' => 'fa-project-diagram',  'title' => 'Projetos',            'text' => 'Projetos e turmas em que você atua.'],
        ['url' => url('/academy'),                  'icon' => 'fa-graduation-cap',   'title' => 'Vivensi Academy',     'text' => 'Cursos e certificados para a equipe.'],
        ['url' => url('/profile'),                  'icon' => 'fa-user-shield',      'title' => 'Meu perfil',          'text' => 'Seus dados, senha e verificação em duas etapas.'],
    ];
@endphp

<style>
    .tm-head { margin-bottom: 26px; }
    .tm-eyebrow { display:inline-flex; align-items:center; gap:8px; font-size:.72rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:#4F6EF7; margin-bottom:8px; }
    .tm-eyebrow i { width:6px; height:6px; border-radius:50%; background:#22c55e; display:block; }
    .tm-title { font-size:1.7rem; font-weight:800; letter-spacing:-.02em; color:var(--text-primary, #0f172a); margin:0 0 4px; }
    .tm-sub { color:var(--text-secondary, #64748b); margin:0; }
    .tm-stats { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:14px; margin-bottom:26px; }
    .tm-stat { border:1px solid var(--border-color, #e5e7eb); border-radius:14px; padding:16px 18px; background:var(--bg-surface, #fff); }
    .tm-stat b { display:block; font-size:1.7rem; font-weight:900; letter-spacing:-.03em; color:var(--text-primary, #0f172a); }
    .tm-stat span { font-size:.8rem; color:var(--text-secondary, #64748b); }
    .tm-stat.warn b { color:#b45309; }
    .tm-grid { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:14px; }
    .tm-card { display:flex; gap:14px; align-items:flex-start; padding:18px; border-radius:14px; border:1px solid var(--border-color, #e5e7eb); background:var(--bg-surface, #fff); text-decoration:none; transition:border-color .15s, transform .15s, box-shadow .15s; }
    .tm-card:hover { border-color:#4F6EF7; transform:translateY(-1px); box-shadow:0 8px 24px rgba(79,110,247,.1); }
    .tm-ic { flex:none; width:40px; height:40px; border-radius:10px; background:rgba(79,110,247,.1); color:#4F6EF7; display:flex; align-items:center; justify-content:center; }
    .tm-card strong { display:block; color:var(--text-primary, #0f172a); font-size:.95rem; margin-bottom:2px; }
    .tm-card small { color:var(--text-secondary, #64748b); line-height:1.45; }
    .tm-note { margin-top:22px; font-size:.82rem; color:var(--text-secondary, #64748b); }
    @media (max-width: 992px) { .tm-stats { grid-template-columns:repeat(2, minmax(0,1fr)); } .tm-grid { grid-template-columns:repeat(2, minmax(0,1fr)); } }
    @media (max-width: 560px) { .tm-grid { grid-template-columns:1fr; } }
</style>

<div class="tm-head">
    <div class="tm-eyebrow"><i></i> {{ \App\Support\TeamAccess::label($u->role) }} · {{ optional($u->tenant)->name }}</div>
    <h1 class="tm-title">{{ $greet }}, {{ $first }}!</h1>
    <p class="tm-sub">Por onde vamos começar hoje?</p>
</div>

<div class="tm-stats">
    <div class="tm-stat"><b>{{ number_format($stats['beneficiaries'], 0, ',', '.') }}</b><span>beneficiários ativos</span></div>
    <div class="tm-stat"><b>{{ $stats['sessions_today'] }}</b><span>{{ $stats['sessions_today'] === 1 ? 'chamada marcada para hoje' : 'chamadas marcadas para hoje' }}</span></div>
    <div class="tm-stat"><b>{{ number_format($stats['attendances_mon'], 0, ',', '.') }}</b><span>atendimentos neste mês</span></div>
    <div class="tm-stat {{ $stats['consent_pending'] ? 'warn' : '' }}"><b>{{ number_format($stats['consent_pending'], 0, ',', '.') }}</b><span>termos LGPD pendentes</span></div>
</div>

<div class="tm-grid">
    @foreach($shortcuts as $s)
        <a href="{{ $s['url'] }}" class="tm-card">
            <span class="tm-ic"><i class="fas {{ $s['icon'] }}"></i></span>
            <span><strong>{{ $s['title'] }}</strong><small>{{ $s['text'] }}</small></span>
        </a>
    @endforeach
</div>

<p class="tm-note"><i class="fas fa-info-circle me-1"></i> Precisa de acesso a outra área? Fale com o administrador da organização.</p>
@endsection
