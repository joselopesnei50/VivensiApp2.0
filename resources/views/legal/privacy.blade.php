@extends('layouts.public')

@section('title', 'Política de Privacidade — Vivensi')

@section('content')
<style>
    .legal-hero {
        background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);
        padding: 60px 10% 50px;
        text-align: center;
        border-bottom: 1px solid #e2e8f0;
    }
    .legal-hero h1 {
        font-size: clamp(2rem, 4vw, 3rem);
        font-weight: 900;
        color: #1e293b;
        letter-spacing: -1.5px;
        margin: 0 0 12px;
    }
    .legal-hero p {
        color: #64748b;
        font-size: 1rem;
        margin: 0;
    }
    .legal-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(79,70,229,0.1);
        color: #4f46e5;
        font-weight: 700;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        padding: 8px 18px;
        border-radius: 50px;
        margin-bottom: 20px;
    }
    .legal-body {
        max-width: 860px;
        margin: 0 auto;
        padding: 60px 5%;
    }
    .legal-section {
        margin-bottom: 44px;
    }
    .legal-section h2 {
        font-size: 1.15rem;
        font-weight: 800;
        color: #1e293b;
        margin: 0 0 14px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .legal-section h2 .num {
        background: #4f46e5;
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 800;
        flex-shrink: 0;
    }
    .legal-section p, .legal-section li {
        color: #475569;
        line-height: 1.8;
        font-size: 0.95rem;
        margin: 0 0 10px;
    }
    .legal-section ul {
        padding-left: 20px;
        margin: 10px 0;
    }
    .legal-divider {
        height: 1px;
        background: #f1f5f9;
        margin: 40px 0;
    }
    .legal-highlight {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 12px;
        padding: 18px 22px;
        margin-bottom: 16px;
        color: #92400e;
        font-size: 0.88rem;
        line-height: 1.7;
    }
    .dpo-card {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        border-radius: 20px;
        padding: 32px 36px;
        color: white;
        margin-top: 48px;
        text-align: center;
    }
    .dpo-card h3 { margin: 0 0 8px; font-size: 1.2rem; font-weight: 800; }
    .dpo-card p  { margin: 0 0 20px; opacity: 0.85; font-size: 0.92rem; }
    .dpo-card a  {
        display: inline-block;
        background: white;
        color: #4f46e5;
        font-weight: 800;
        padding: 12px 24px;
        border-radius: 10px;
        text-decoration: none;
        font-size: 0.9rem;
        transition: opacity 0.2s;
    }
    .dpo-card a:hover { opacity: 0.9; }
    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #4f46e5;
        font-weight: 700;
        text-decoration: none;
        font-size: 0.9rem;
        margin-bottom: 40px;
    }
    .back-link:hover { text-decoration: underline; }
</style>

<div class="legal-hero">
    <div class="legal-badge"><i class="fas fa-shield-halved"></i> Privacidade & LGPD</div>
    <h1>Política de Privacidade</h1>
    <p>Última atualização: {{ config('legal.last_update', '15 de maio de 2026') }}</p>
</div>

<div class="legal-body">

    <a href="{{ url('/') }}" class="back-link"><i class="fas fa-arrow-left"></i> Voltar para o início</a>

    <div class="legal-highlight">
        <strong><i class="fas fa-info-circle me-1"></i> Resumo simples:</strong>
        Nós coletamos apenas os dados necessários para prestar o serviço. Não vendemos suas informações. Você tem direito de ver, corrigir e excluir seus dados a qualquer momento. Leia os detalhes abaixo.
    </div>

    <div class="legal-section">
        <h2><span class="num">1</span> Quem somos</h2>
        <p><strong>Vivensi Tecnologia</strong> é a controladora dos dados pessoais tratados nesta plataforma. Nosso Encarregado de Proteção de Dados (DPO) pode ser contatado em <strong>{{ config('legal.email_dpo', 'privacidade@vivensi.com.br') }}</strong>.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">2</span> Quais dados coletamos</h2>
        <p>Coletamos os seguintes dados pessoais:</p>
        <ul>
            <li><strong>Dados de cadastro:</strong> nome, e-mail, telefone/WhatsApp e dados da organização.</li>
            <li><strong>Dados de uso:</strong> IP de acesso, páginas visitadas, horário e dispositivo (logs de sistema).</li>
            <li><strong>Dados financeiros:</strong> registros de transações inseridos pelo titular na plataforma.</li>
            <li><strong>Dados de comunicação:</strong> conteúdo de mensagens enviadas via integração WhatsApp.</li>
            <li><strong>Dados de beneficiários/voluntários:</strong> inseridos pela organização (ONG) usuária do sistema.</li>
        </ul>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">3</span> Para que usamos os dados</h2>
        <ul>
            <li>Prestar, manter e melhorar os serviços contratados.</li>
            <li>Processar pagamentos e emitir recibos.</li>
            <li>Enviar notificações operacionais e alertas de segurança.</li>
            <li>Gerar análises e relatórios por meio de inteligência artificial.</li>
            <li>Cumprir obrigações legais e regulatórias.</li>
        </ul>
        <p>A base legal principal é a <strong>execução de contrato</strong> (art. 7º, V, LGPD). Para envios de marketing, a base é o <strong>consentimento</strong> (art. 7º, I, LGPD), que pode ser revogado a qualquer momento.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">4</span> Compartilhamento e transferências internacionais</h2>
        <p>Compartilhamos dados com os seguintes processadores, sob contratos de confidencialidade:</p>
        <ul>
            <li><strong>AWS (Amazon Web Services) — EUA:</strong> hospedagem dos servidores e banco de dados.</li>
            <li><strong>Brevo (ex-Sendinblue) — França:</strong> envio de e-mails transacionais.</li>
            <li><strong>DeepSeek — China:</strong> geração de textos por inteligência artificial (dados enviados são anonimizados ou pseudonimizados sempre que possível).</li>
            <li><strong>Meta (WhatsApp Business API) — EUA:</strong> envio e recebimento de mensagens WhatsApp.</li>
            <li><strong>Evolution API — Brasil:</strong> gateway de mensagens WhatsApp para instâncias próprias.</li>
        </ul>
        <p>As transferências internacionais ocorrem com base nas salvaguardas previstas no art. 33 da LGPD. Não vendemos dados a terceiros.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">5</span> Retenção de dados</h2>
        <ul>
            <li>Dados de conta de usuário ativo: pelo período do contrato + 5 anos (obrigação fiscal).</li>
            <li>Mensagens WhatsApp: 365 dias a partir do envio.</li>
            <li>Logs de acesso: 6 meses (Marco Civil da Internet).</li>
            <li>Dados financeiros: 5 anos (legislação tributária).</li>
        </ul>
        <p>Após o término dos prazos, os dados são apagados ou anonimizados de forma irreversível.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">6</span> Seus direitos (LGPD — Art. 18)</h2>
        <p>Como titular, você tem direito a:</p>
        <ul>
            <li>Confirmação de existência de tratamento e acesso aos dados;</li>
            <li>Correção de dados incompletos, inexatos ou desatualizados;</li>
            <li>Portabilidade — exportar seus dados em formato estruturado;</li>
            <li>Eliminação dos dados tratados com base no consentimento;</li>
            <li>Revogação do consentimento a qualquer tempo;</li>
            <li>Oposição ao tratamento em caso de descumprimento da lei.</li>
        </ul>
        <p>Para exportar ou solicitar a exclusão dos seus dados, acesse <strong>Configurações → Perfil → Privacidade & Direitos LGPD</strong> dentro da plataforma.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">7</span> Exclusão de dados — como solicitar</h2>
        <p>Você pode pedir a exclusão dos seus dados pessoais a qualquer momento, por três canais:</p>
        <ul>
            <li><strong>Pela plataforma</strong> — logado, acesse <em>Configurações → Perfil → Privacidade & Direitos LGPD</em> e clique em "Solicitar exclusão".</li>
            <li><strong>Por e-mail</strong> — envie um pedido para <a href="mailto:{{ config('legal.email_dpo', 'privacidade@vivensi.com.br') }}">{{ config('legal.email_dpo', 'privacidade@vivensi.com.br') }}</a> a partir do e-mail cadastrado na conta.</li>
            <li><strong>Via aplicativos conectados</strong> — se você conectou seu Facebook ou Instagram ao Vivensi, pode revogar o acesso e pedir exclusão diretamente pelas configurações do Facebook. Ao fazer isso, receberemos automaticamente o pedido e você poderá acompanhar o status pelo link informado no próprio Facebook.</li>
        </ul>
        <p><strong>Prazo:</strong> processamos pedidos de exclusão em até 15 (quinze) dias corridos, conforme o art. 19 da LGPD.</p>
        <p><strong>O que é excluído:</strong> perfil, credenciais, mensagens, tokens de integrações e conteúdo produzido por você. Alguns dados podem ser retidos, de forma anonimizada, quando exigido por obrigação legal (registros fiscais e logs de acesso — ver seção 5).</p>
        <p><strong>Confirmação:</strong> após o processamento, enviamos confirmação por e-mail para o endereço cadastrado.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">8</span> Cookies</h2>
        <p>Utilizamos cookies essenciais (necessários para o funcionamento do sistema) e cookies de análise (com seu consentimento). Você pode gerenciar suas preferências no banner de cookies exibido no primeiro acesso.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">9</span> Segurança</h2>
        <p>Adotamos medidas técnicas e organizacionais para proteger seus dados, incluindo:</p>
        <ul>
            <li>Criptografia em trânsito (TLS 1.2+);</li>
            <li>Controle de acesso baseado em papéis (RBAC) com isolamento por organização (tenant);</li>
            <li>Tokens de API sensíveis armazenados com criptografia AES-256;</li>
            <li>Monitoramento de logs de acesso e alertas de incidentes.</li>
        </ul>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">10</span> Incidentes de segurança</h2>
        <p>Em caso de vazamento ou incidente que possa afetar seus direitos, comunicaremos a ANPD e os titulares afetados nos prazos legais estabelecidos pelo art. 48 da LGPD (72 horas para a ANPD).</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">11</span> Alterações desta política</h2>
        <p>Podemos atualizar esta política periodicamente. Notificaremos alterações relevantes por e-mail ou aviso na plataforma. O uso continuado após a notificação implica aceitação das alterações.</p>
    </div>

    <div class="dpo-card">
        <i class="fas fa-user-shield" style="font-size: 2rem; margin-bottom: 14px; display: block; opacity: 0.9;"></i>
        <h3>Fale com nosso DPO</h3>
        <p>Encarregado de Proteção de Dados — disponível para dúvidas, solicitações e reclamações sobre privacidade.</p>
        <a href="mailto:{{ config('legal.email_dpo', 'privacidade@vivensi.com.br') }}">
            <i class="fas fa-envelope me-2"></i>{{ config('legal.email_dpo', 'privacidade@vivensi.com.br') }}
        </a>
    </div>

</div>
@endsection
