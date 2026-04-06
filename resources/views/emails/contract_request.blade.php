@extends('emails.layout')

@section('title', 'Solicitação de Assinatura Eletrônica')

@section('content')
    <h1>Solicitação de Assinatura ✍️</h1>
    
    <p>Olá,</p>
    
    <p>A organização <strong>{{ $contract->tenant->name }}</strong> enviou um documento para sua revisão e assinatura eletrônica.</p>
    
    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; margin-bottom: 30px;">
        <p style="margin-bottom: 5px; font-size: 13px; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Documento:</p>
        <p style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">{{ $contract->title }}</p>
    </div>

    <p>Este processo é seguro, legalmente aceito e leva apenas alguns minutos. Clique no botão abaixo para visualizar o documento e realizar a assinatura:</p>

    <center>
        @component('emails.components.button', ['url' => $publicLink])
            Revisar e Assinar
        @endcomponent
    </center>

    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8;">
        <p><strong>Por que estou recebendo este e-mail?</strong><br>Este e-mail foi gerado automaticamente pela plataforma Vivensi a pedido de {{ $contract->tenant->name }}. Se você não esperava por este documento, por favor desconsidere.</p>
    </div>
@endsection
