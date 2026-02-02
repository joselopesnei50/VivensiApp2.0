@extends('layouts.public')

@section('title', $page->title . ' - Vivensi')

@section('content')
<div class="header-page" style="margin-bottom: 30px; text-align: center;">
    <h1 style="margin: 0; color: #111827; font-weight: 800; font-size: 2.5rem;">{{ $page->title }}</h1>
    <p style="color: #6b7280; margin: 10px 0 0 0;">Vivensi App - Transparência e Compromisso</p>
</div>

<div class="vivensi-card" style="padding: 40px; line-height: 1.8; color: #334155; max-width: 900px; margin: 0 auto;">
    {!! nl2br($page->content) !!}
</div>

<div style="text-align: center; margin-top: 40px; margin-bottom: 60px;">
    <a href="{{ url('/') }}" class="btn-premium">Voltar para a Home</a>
</div>

<style>
    .vivensi-card h1, .vivensi-card h2, .vivensi-card h3 { color: #0f172a; margin-top: 30px; margin-bottom: 15px; font-weight: 800; }
    .vivensi-card p { margin-bottom: 20px; }
    .vivensi-card ul { margin-bottom: 20px; padding-left: 20px; }
    .vivensi-card li { margin-bottom: 10px; }
</style>
@endsection
