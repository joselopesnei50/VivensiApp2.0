@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-5">
                    <h1 class="mb-4 text-center fw-bold text-primary">Termos de Uso</h1>
                    <p class="text-muted text-center mb-5">Última atualização: {{ config('legal.last_update') }}</p>

                    <div class="legal-content">
                        <h4>1. Aceitação dos Termos</h4>
                        <p>Ao acessar e usar a plataforma {{ config('legal.company_name') }}, você concorda em cumprir e ficar vinculado aos seguintes termos e condições de uso.</p>

                        <h4>2. Descrição do Serviço</h4>
                        <p>A Vivensi fornece uma plataforma de gestão para ONGs, Gerenciamento de Projetos e Finanças Pessoais. Reservamo-nos o direito de modificar, suspender ou descontinuar qualquer aspecto do serviço a qualquer momento.</p>

                        <h4>3. Cadastro e Conta</h4>
                        <p>Para usar certos recursos, você deve se registrar. Você concorda em fornecer informações precisas e manter a segurança de sua senha.</p>
                        
                        <h4>4. Planos e Pagamentos</h4>
                        <p>O acesso a recursos premium requer uma assinatura. Os pagamentos são processados de forma segura e são recorrentes de acordo com o plano escolhido.</p>

                        <h4>5. Cancelamento</h4>
                        <p>Você pode cancelar sua assinatura a qualquer momento através do painel de controle. O cancelamento entrará em vigor no final do ciclo de faturamento atual.</p>

                        <h4>6. Responsabilidades</h4>
                        <p>A Vivensi não se responsabiliza por danos diretos, indiretos, incidentais ou consequenciais resultantes do uso ou da incapacidade de usar o serviço.</p>
                        
                        <h4>7. Contato</h4>
                        <p>Para dúvidas sobre estes termos, entre em contato através de nosso suporte em <strong>{{ config('legal.email_support') }}</strong>.</p>
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
