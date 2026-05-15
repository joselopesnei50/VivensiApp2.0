@extends('layouts.public')

@section('title', 'Termos de Uso — Vivensi')

@section('content')
<style>
    .legal-hero {
        background: linear-gradient(135deg, #f8fafc 0%, #f0fdf4 100%);
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
    .legal-hero p { color: #64748b; font-size: 1rem; margin: 0; }
    .legal-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(16,185,129,0.1);
        color: #059669;
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
    .legal-section { margin-bottom: 44px; }
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
        background: #10b981;
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
    .legal-section ul { padding-left: 20px; margin: 10px 0; }
    .legal-divider { height: 1px; background: #f1f5f9; margin: 40px 0; }
    .legal-warning {
        background: #fef2f2;
        border: 1px solid #fca5a5;
        border-radius: 12px;
        padding: 18px 22px;
        margin-bottom: 16px;
        color: #991b1b;
        font-size: 0.88rem;
        line-height: 1.7;
    }
    .legal-note {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 12px;
        padding: 18px 22px;
        margin: 12px 0;
        color: #92400e;
        font-size: 0.88rem;
        line-height: 1.7;
    }
    .contact-card {
        background: linear-gradient(135deg, #0f172a, #1e293b);
        border-radius: 20px;
        padding: 32px 36px;
        color: white;
        margin-top: 48px;
        text-align: center;
    }
    .contact-card h3 { margin: 0 0 8px; font-size: 1.2rem; font-weight: 800; }
    .contact-card p  { margin: 0 0 20px; opacity: 0.75; font-size: 0.92rem; }
    .contact-card a  {
        display: inline-block;
        background: #10b981;
        color: white;
        font-weight: 800;
        padding: 12px 24px;
        border-radius: 10px;
        text-decoration: none;
        font-size: 0.9rem;
        transition: opacity 0.2s;
    }
    .contact-card a:hover { opacity: 0.9; }
    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #059669;
        font-weight: 700;
        text-decoration: none;
        font-size: 0.9rem;
        margin-bottom: 40px;
    }
    .back-link:hover { text-decoration: underline; }
</style>

<div class="legal-hero">
    <div class="legal-badge"><i class="fas fa-file-contract"></i> Termos & Condições</div>
    <h1>Termos de Uso</h1>
    <p>Última atualização: {{ config('legal.last_update', '15 de maio de 2026') }}</p>
</div>

<div class="legal-body">

    <a href="{{ url('/') }}" class="back-link"><i class="fas fa-arrow-left"></i> Voltar para o início</a>

    <div class="legal-section">
        <h2><span class="num">1</span> Aceitação dos termos</h2>
        <p>Ao acessar e usar a plataforma <strong>{{ config('legal.company_name', 'Vivensi') }}</strong>, você declara ter lido, compreendido e concordado com estes Termos de Uso. Caso não concorde com qualquer disposição, não utilize o serviço.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">2</span> Descrição do serviço</h2>
        <p>A Vivensi oferece uma plataforma SaaS de gestão para ONGs, gestores de projetos e pessoas físicas, incluindo módulos de:</p>
        <ul>
            <li>Gestão financeira e de projetos;</li>
            <li>Comunicação via WhatsApp Business API;</li>
            <li>Marketing digital e automações com inteligência artificial;</li>
            <li>Transparência e portais públicos para doadores.</li>
        </ul>
        <p>Reservamo-nos o direito de modificar, suspender ou descontinuar funcionalidades mediante aviso prévio de 30 dias, exceto em casos de urgência técnica ou legal.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">3</span> Cadastro e responsabilidade da conta</h2>
        <p>Para utilizar recursos autenticados, é necessário criar uma conta. Você se compromete a:</p>
        <ul>
            <li>Fornecer informações verídicas e atualizadas;</li>
            <li>Manter a confidencialidade de sua senha;</li>
            <li>Notificar imediatamente sobre qualquer uso não autorizado da conta;</li>
            <li>Ser o único responsável pelas ações realizadas em sua conta.</li>
        </ul>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">4</span> Planos e pagamentos</h2>
        <p>O acesso a recursos premium exige assinatura ativa. Os pagamentos são processados por provedores certificados PCI-DSS. A assinatura é renovada automaticamente no ciclo contratado (mensal ou anual) até o cancelamento.</p>
        <div class="legal-note">
            <strong><i class="fas fa-info-circle me-1"></i> Reembolso:</strong> Solicitações de reembolso dentro de 7 dias corridos da contratação inicial serão avaliadas caso a caso. Após esse período, não há reembolso por período não utilizado.
        </div>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">5</span> Cancelamento</h2>
        <p>Você pode cancelar a assinatura a qualquer momento pelo painel de controle. O acesso permanece ativo até o fim do ciclo de faturamento vigente. Após o cancelamento, os dados são mantidos por 90 dias para eventual reativação e, em seguida, excluídos conforme a Política de Privacidade.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">6</span> Política anti-spam e uso do WhatsApp</h2>
        <div class="legal-warning">
            <strong><i class="fas fa-triangle-exclamation me-1"></i> Importante:</strong> O envio de mensagens não solicitadas (spam) é estritamente proibido e pode resultar no bloqueio imediato da conta sem direito a reembolso.
        </div>
        <p>Ao utilizar os módulos de mensageria WhatsApp, o usuário declara e garante que:</p>
        <ul>
            <li>Possui <strong>opt-in explícito e documentado</strong> de todos os contatos que receberão mensagens;</li>
            <li>As mensagens enviadas respeitam as políticas de uso da Meta (WhatsApp Business API);</li>
            <li>Não utilizará a plataforma para envio de conteúdo fraudulento, enganoso ou ilícito;</li>
            <li>Monitorará ativamente as taxas de bloqueio e denúncia de seus envios.</li>
        </ul>
        <p>A Vivensi aplica monitoramento de qualidade de envios. Em caso de excesso de denúncias, envios fora de conformidade ou bloqueios pela Meta, reservamo-nos o direito de suspender o módulo de mensageria ou cancelar a conta imediatamente.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">7</span> Uso da inteligência artificial</h2>
        <p>Recursos de IA (geração de textos, análises) processam dados inseridos pelo usuário utilizando serviços de terceiros (DeepSeek, conforme Política de Privacidade). O usuário é responsável por revisar e validar todo conteúdo gerado por IA antes de publicar ou utilizar. A Vivensi não garante a precisão ou adequação do conteúdo gerado automaticamente.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">8</span> Propriedade intelectual</h2>
        <p>A plataforma, seu código-fonte, design, marcas e conteúdos produzidos pela Vivensi são protegidos por leis de propriedade intelectual. É vedado copiar, distribuir, modificar ou criar obras derivadas sem autorização expressa por escrito. Os dados inseridos pelo usuário permanecem de sua propriedade.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">9</span> Limitação de responsabilidade</h2>
        <p>A Vivensi não se responsabiliza por danos indiretos, incidentais, consequenciais ou punitivos decorrentes do uso ou da incapacidade de uso do serviço. A responsabilidade total da Vivensi, em qualquer caso, não excederá o valor pago pelo usuário nos últimos 3 meses.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">10</span> Disponibilidade e SLA</h2>
        <p>Buscamos disponibilidade de 99,5% ao mês. Manutenções programadas serão comunicadas com antecedência mínima de 24 horas. Incidentes não planejados serão comunicados em nosso canal de status.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">11</span> Alterações dos termos</h2>
        <p>Podemos atualizar estes Termos a qualquer momento. Notificaremos alterações relevantes por e-mail com antecedência de 15 dias. O uso continuado após a vigência das alterações implica aceitação.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">12</span> Foro e legislação aplicável</h2>
        <p>Estes Termos são regidos pelas leis da República Federativa do Brasil. Fica eleito o foro da comarca de São Paulo/SP para dirimir quaisquer controvérsias, com renúncia expressa a qualquer outro.</p>
    </div>

    <div class="contact-card">
        <i class="fas fa-headset" style="font-size: 2rem; margin-bottom: 14px; display: block; opacity: 0.9;"></i>
        <h3>Ficou com dúvidas?</h3>
        <p>Nossa equipe de suporte está disponível para esclarecer qualquer ponto destes termos.</p>
        <a href="mailto:{{ config('legal.email_support', 'suporte@vivensi.com.br') }}">
            <i class="fas fa-envelope me-2"></i>{{ config('legal.email_support', 'suporte@vivensi.com.br') }}
        </a>
    </div>

</div>
@endsection
