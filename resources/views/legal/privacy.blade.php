@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-5">
                    <h1 class="mb-4 text-center fw-bold text-primary">Política de Privacidade</h1>
                    <p class="text-muted text-center mb-5">Última atualização: {{ config('legal.last_update') }}</p>

                    <div class="legal-content">
                        <h4>1. Coleta de Informações</h4>
                        <p>Coletamos informações que você nos fornece diretamente, como nome, e-mail e dados da organização, bem como dados de uso coletados automaticamente através de cookies e tecnologias similares.</p>

                        <h4>2. Uso das Informações</h4>
                        <p>Utilizamos suas informações para fornecer, manter e melhorar nossos serviços, processar transações, enviar comunicações técnicas e responder aos seus comentários e perguntas.</p>

                        <h4>3. Compartilhamento de Dados</h4>
                        <p>Não vendemos seus dados pessoais. Podemos compartilhar informações com prestadores de serviços terceirizados que nos ajudam a operar nossa plataforma, sempre sob obrigações de confidencialidade.</p>
                        
                        <h4>4. Segurança de Dados</h4>
                        <p>Implementamos medidas de segurança técnicas e organizacionais para proteger seus dados. Utilizamos criptografia SSL e servidores seguros (AWS).</p>

                        <h4>5. Seus Direitos</h4>
                        <p>Você tem o direito de acessar, corrigir ou excluir suas informações pessoais. Entre em contato conosco para exercer esses direitos.</p>

                        <h4>6. LGPD</h4>
                        <p>Nossa plataforma está em conformidade com a Lei Geral de Proteção de Dados (LGPD) do Brasil, garantindo transparência e controle sobre seus dados.</p>
                        
                        <h4>7. Contato</h4>
                        <p>Para questões sobre privacidade, contate nosso Encarregado de Proteção de Dados (DPO) através do e-mail <strong>{{ config('legal.email_dpo') }}</strong>.</p>
                    </div>
                    
                    <div class="text-center mt-5">
                        <a href="{{ url('/') }}" class="btn btn-outline-primary rounded-pill px-4">Voltar para Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .legal-content h4 {
        color: #1e293b;
        margin-top: 30px;
        margin-bottom: 15px;
        font-weight: 700;
    }
    .legal-content p {
        color: #64748b;
        line-height: 1.7;
        margin-bottom: 15px;
    }
</style>
@endsection
