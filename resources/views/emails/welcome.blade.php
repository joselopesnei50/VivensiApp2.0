@extends('emails.layout')

@section('title', 'Bem-vindo ao Vivensi')

@section('content')
    <h1 style="text-align: center;">Olá, {{ $user->name }}! 👋</h1>
    
    <p>É uma honra ter você conosco na plataforma <strong>Vivensi</strong>. Estamos comprometidos em fornecer as ferramentas mais avançadas para que sua organização alcance um novo patamar de impacto social.</p>
    
    <div style="background-color: #f1f5f9; border-radius: 12px; padding: 25px; margin-bottom: 30px; border-left: 4px solid #4f46e5;">
        <p style="margin-bottom: 10px; font-weight: 700; color: #1e293b;">Resumo da sua conta:</p>
        <ul style="margin: 0; padding-left: 20px; color: #475569;">
            <li><strong>Organização:</strong> {{ $user->tenant->name ?? 'Não informada' }}</li>
            <li><strong>Plano:</strong> {{ $planName }}</li>
            <li><strong>Período de Teste:</strong> 7 dias grátis</li>
        </ul>
    </div>

    <p>Para começar a explorar o seu <strong>Command Center</strong> e configurar seus projetos, clique no botão abaixo:</p>

    <center>
        @component('emails.components.button', ['url' => config('app.url') . '/dashboard'])
            Acessar Painel
        @endcomponent
    </center>

    <p style="margin-top: 30px;">Se precisar de qualquer ajuda, nossa equipe de suporte está à sua disposição.</p>
    
    <p>Atenciosamente,<br><strong>Equipe Vivensi</strong></p>
@endsection
