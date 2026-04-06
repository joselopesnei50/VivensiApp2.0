@extends('emails.layout')

@section('title', 'Confirmação de Assinatura')

@section('content')
    <h1>Assinatura Confirmada! ✅</h1>
    
    <p>Olá, <strong>{{ $recipientName }}</strong>,</p>
    
    <p>Sua assinatura foi registrada com sucesso no documento <strong>"{{ $contract->title }}"</strong>.</p>
    
    <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 25px; margin-bottom: 30px;">
        @if($code)
            <p style="margin-bottom: 5px; font-size: 13px; color: #166534; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Código de Autenticidade:</p>
            <p style="margin: 0; font-size: 18px; font-weight: 800; color: #14532d; font-family: monospace;">{{ $code }}</p>
        @endif
    </div>

    <p>Você pode visualizar ou baixar uma cópia do documento assinado a qualquer momento através do link oficial abaixo:</p>

    <center>
        @component('emails.components.button', ['url' => $publicLink])
            Visualizar Documento
        @endcomponent
    </center>

    <p style="margin-top: 30px; font-size: 14px; color: #64748b;">Este documento possui validade jurídica eletrônica e está armazenado de forma segura nos servidores da Vivensi.</p>
@endsection
