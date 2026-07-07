@extends('layouts.public')

@section('title', 'Status da solicitação de exclusão — Vivensi')

@section('content')
<div class="legal-body">
    <div class="legal-hero">
        <h1>Solicitação de exclusão de dados</h1>
        <p>Código de confirmação: <code>{{ $code }}</code></p>
    </div>

    @if ($request)
        <div class="legal-section">
            <h2>Status</h2>
            @php
                $statusLabels = [
                    'received'   => 'Recebida — aguardando processamento',
                    'processing' => 'Em processamento',
                    'completed'  => 'Concluída',
                    'rejected'   => 'Rejeitada (assinatura inválida)',
                ];
            @endphp
            <p><strong>{{ $statusLabels[$request->status] ?? $request->status }}</strong></p>
            <p>Recebida em: {{ $request->created_at->format('d/m/Y H:i') }}</p>
            @if ($request->processed_at)
                <p>Processada em: {{ $request->processed_at->format('d/m/Y H:i') }}</p>
            @endif
        </div>

        <div class="legal-section">
            <h2>O que acontece a seguir</h2>
            <p>Sua solicitação foi registrada e será processada em até <strong>15 (quinze) dias corridos</strong>, conforme o art. 19 da Lei Geral de Proteção de Dados (LGPD).</p>
            <p>Você receberá confirmação por e-mail assim que a exclusão for concluída.</p>
            <p>Para acompanhar ou tirar dúvidas, entre em contato com nosso Encarregado de Proteção de Dados (DPO): <a href="mailto:{{ config('legal.email_dpo', 'privacidade@vivensi.com.br') }}">{{ config('legal.email_dpo', 'privacidade@vivensi.com.br') }}</a></p>
        </div>
    @else
        <div class="legal-section">
            <h2>Código não encontrado</h2>
            <p>O código de confirmação informado não corresponde a nenhuma solicitação registrada.</p>
            <p>Se você acredita que isso é um erro, entre em contato com <a href="mailto:{{ config('legal.email_dpo', 'privacidade@vivensi.com.br') }}">{{ config('legal.email_dpo', 'privacidade@vivensi.com.br') }}</a> informando o código acima.</p>
        </div>
    @endif
</div>
@endsection
