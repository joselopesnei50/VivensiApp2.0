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

@php
    $empresa = config('legal.company_name'); $cnpj = config('legal.cnpj'); $produto = config('legal.product_name', 'Vivensi');
    $suporte = config('legal.email_support'); $dpo = config('legal.email_dpo'); $dpoNome = config('legal.dpo_name');
    $ret = config('legal.retention_days_after_cancel', 90);
    $provedores = [
        ['Amazon Web Services (AWS)', 'Hospedagem da plataforma, banco de dados, arquivos e cópias de segurança; envio de e-mails (Amazon SES)', 'Brasil (São Paulo); e-mails pelos EUA'],
        ['AbacatePay', 'Cobrança da assinatura por PIX e emissão de cobranças', 'Brasil'],
        ['Brevo', 'Envio de e-mails do sistema (confirmações, faturas, convites)', 'França (União Europeia)'],
        ['Meta Platforms (WhatsApp Business API)', 'Mensagens de WhatsApp enviadas e recebidas pela organização', 'EUA / Irlanda'],
        ['Evolution API', 'Conexão alternativa de WhatsApp, quando a organização usa esse modo', 'Servidor contratado pela NC5'],
        ['Google (Gemini)', 'Recursos de inteligência artificial (textos, análises e assistente)', 'EUA'],
        ['DeepSeek', 'Recursos de inteligência artificial (textos e análises)', 'China'],
        ['Together AI', 'Geração de imagens por inteligência artificial', 'EUA'],
        ['Google (reCAPTCHA, Analytics e Tag Manager)', 'Proteção contra robôs nos formulários e estatísticas de uso do site', 'EUA'],
        ['Meta Platforms (Pixel)', 'Medição de campanhas de divulgação do site', 'EUA / Irlanda'],
        ['Pusher', 'Notificações em tempo real dentro do painel', 'EUA / União Europeia'],
        ['Sentry', 'Registro de erros técnicos do sistema, para corrigir falhas (sem dados de beneficiários)', 'EUA'],
    ];
@endphp
<style>
    .legal-table { width:100%; border-collapse:collapse; margin:12px 0; font-size:.88rem; }
    .legal-table th, .legal-table td { text-align:left; padding:10px 12px; border-bottom:1px solid #e2e8f0; vertical-align:top; color:#475569; }
    .legal-table th { background:#f8fafc; color:#1e293b; font-weight:800; }
    .legal-table-wrap { overflow-x:auto; }
</style>

<div class="legal-hero">
    <div class="legal-badge"><i class="fas fa-shield-halved"></i> Privacidade & LGPD</div>
    <h1>Política de Privacidade</h1>
    <p>Última atualização: {{ config('legal.last_update') }}</p>
</div>

<div class="legal-body">

    <a href="{{ url('/') }}" class="back-link"><i class="fas fa-arrow-left"></i> Voltar para o início</a>

    <div class="legal-section">
        <h2><span class="num">1</span> Quem é o responsável</h2>
        <p>O {{ $produto }} é um produto da <strong>{{ $empresa }}</strong>, CNPJ {{ $cnpj }}, com sede em {{ config('legal.city') }} ("NC5", "nós"). Esta Política explica quais dados pessoais tratamos, por quê, com quem compartilhamos, por quanto tempo guardamos e como você exerce os seus direitos, conforme a Lei nº 13.709/2018 (LGPD).</p>
    </div>

    <div class="legal-section">
        <h2><span class="num">2</span> Dois papéis diferentes</h2>
        <ul>
            <li><strong>Dados cadastrados pelas organizações</strong> (beneficiários e famílias, atendimentos, doadores, voluntários, equipe, contatos de WhatsApp e e-mail): a <strong>organização é a controladora</strong> e define finalidades e bases legais; a <strong>NC5 é a operadora</strong> e trata esses dados apenas para prestar o serviço, seguindo as instruções da organização e o Contrato de Adesão. Se você é beneficiário, doador ou voluntário de uma organização, fale primeiro com ela para exercer seus direitos — nós ajudamos a organização a atender o pedido.</li>
            <li><strong>Dados de visitantes do site e de quem usa a conta</strong> (cadastro, login, cobrança, suporte, chat do site e agendamento de demonstração): a <strong>NC5 é a controladora</strong>.</li>
        </ul>
    </div>

    <div class="legal-section">
        <h2><span class="num">3</span> Dados que tratamos como controladora</h2>
        <ul>
            <li><strong>Cadastro e contrato:</strong> nome, e-mail, telefone, cargo; razão social, CNPJ e endereço da organização; nome, CPF e e-mail de quem assina o Contrato de Adesão, com data, hora, IP e assinatura.</li>
            <li><strong>Cobrança:</strong> faturas, valores e situação do pagamento. Os dados do PIX ficam com a AbacatePay; não guardamos dados bancários.</li>
            <li><strong>Uso e segurança:</strong> registros de acesso (IP, data e hora, navegador), ações realizadas e tentativas de login.</li>
            <li><strong>Atendimento:</strong> mensagens do chat do site, do WhatsApp e de e-mail, e dados de agendamento.</li>
            <li><strong>Navegação:</strong> cookies e dados de estatística do site (ver item 9).</li>
        </ul>
    </div>

    <div class="legal-section">
        <h2><span class="num">4</span> Para que usamos e com que base legal</h2>
        <div class="legal-table-wrap"><table class="legal-table">
            <tr><th>Finalidade</th><th>Base legal (LGPD)</th></tr>
            <tr><td>Criar e manter a conta, prestar o serviço e o suporte</td><td>Execução de contrato (art. 7º, V)</td></tr>
            <tr><td>Cobrança, nota fiscal e obrigações contábeis</td><td>Obrigação legal (art. 7º, II) e execução de contrato</td></tr>
            <tr><td>Segurança, prevenção a fraudes e guarda de registros de acesso</td><td>Obrigação legal (Marco Civil da Internet, art. 15) e legítimo interesse (art. 7º, IX)</td></tr>
            <tr><td>Avisos sobre o serviço (faturas, mudanças, incidentes)</td><td>Execução de contrato</td></tr>
            <tr><td>Novidades e conteúdos da Vivensi por e-mail ou WhatsApp</td><td>Consentimento (art. 7º, I), que pode ser retirado a qualquer momento</td></tr>
            <tr><td>Estatísticas do site e medição de campanhas</td><td>Consentimento (cookies não essenciais)</td></tr>
            <tr><td>Defesa em processos e pedidos de autoridades</td><td>Exercício regular de direitos (art. 7º, VI)</td></tr>
        </table></div>
    </div>

    <div class="legal-section">
        <h2><span class="num">5</span> Empresas que tratam dados para nós</h2>
        <p>Para a plataforma funcionar, usamos os provedores abaixo. Cada um recebe apenas os dados necessários para a sua função e está sujeito a obrigações de confidencialidade e segurança. Mudanças nesta lista são avisadas às organizações clientes com 30 dias de antecedência.</p>
        <div class="legal-table-wrap"><table class="legal-table">
            <tr><th>Empresa</th><th>Para quê</th><th>Onde</th></tr>
            @foreach($provedores as [$nome, $para, $onde])
                <tr><td><strong>{{ $nome }}</strong></td><td>{{ $para }}</td><td>{{ $onde }}</td></tr>
            @endforeach
        </table></div>
        <p>Também podemos compartilhar dados quando exigido por lei, ordem judicial ou autoridade competente. <strong>Não vendemos dados pessoais.</strong></p>
    </div>

    <div class="legal-section">
        <h2><span class="num">6</span> Transferência para outros países</h2>
        <p>Alguns provedores processam dados fora do Brasil (EUA, União Europeia e China, conforme a tabela acima). Essas transferências se apoiam no art. 33 da LGPD, com cláusulas contratuais e garantias de proteção compatíveis com a lei brasileira, nos termos da regulamentação da ANPD.</p>
    </div>

    <div class="legal-section" id="ia">
        <h2><span class="num">7</span> Inteligência artificial</h2>
        <p>Quando você usa um recurso de IA, enviamos ao provedor o pedido e só os dados necessários para respondê-lo. Os dados das organizações não são usados para treinar modelos de IA. Recomendamos não incluir dados sensíveis de beneficiários (saúde, por exemplo) nos pedidos ao assistente.</p>
    </div>

    <div class="legal-section">
        <h2><span class="num">8</span> Por quanto tempo guardamos</h2>
        <ul>
            <li><strong>Conta e dados da organização:</strong> enquanto a assinatura estiver ativa e por <strong>{{ $ret }} dias</strong> após o cancelamento (para reativação ou exportação). Depois, são apagados de forma definitiva.</li>
            <li><strong>Cópias de segurança:</strong> diárias, guardadas por até 7 dias; dados apagados deixam de existir nelas no ciclo seguinte.</li>
            <li><strong>Registros de acesso:</strong> 6 meses (Marco Civil da Internet).</li>
            <li><strong>Cobrança, notas fiscais e contrato de adesão:</strong> pelo prazo legal (em regra, 5 anos após o fim do contrato).</li>
            <li><strong>Mensagens de WhatsApp:</strong> até 365 dias, salvo configuração diferente da organização.</li>
        </ul>
    </div>

    <div class="legal-section">
        <h2><span class="num">9</span> Cookies</h2>
        <ul>
            <li><strong>Necessários:</strong> mantêm o login, a segurança e as preferências (idioma, tema). Não podem ser desligados.</li>
            <li><strong>Estatística e divulgação:</strong> Google Analytics, Google Tag Manager e Meta Pixel, usados para entender o uso do site e medir campanhas. Você pode recusá-los no aviso de cookies ou bloqueá-los no navegador.</li>
        </ul>
    </div>

    <div class="legal-section" id="seguranca">
        <h2><span class="num">10</span> Segurança</h2>
        <p>Usamos conexão criptografada (HTTPS), criptografia de campos sensíveis no banco de dados (como CPF e telefone), separação dos dados de cada organização, controle de acesso por perfil, verificação em duas etapas, registros de auditoria e cópias de segurança diárias. Em caso de incidente com risco relevante, avisamos as organizações afetadas e, quando for o caso, a ANPD e os titulares, conforme a regulamentação da ANPD.</p>
    </div>

    <div class="legal-section">
        <h2><span class="num">11</span> Crianças e adolescentes</h2>
        <p>A plataforma é usada por organizações, não por crianças. Quando uma organização cadastra dados de crianças e adolescentes atendidos, cabe a ela obter o consentimento de pai, mãe ou responsável, ou usar outra base legal adequada, sempre no melhor interesse da criança (art. 14 da LGPD e ECA). A plataforma oferece o termo de consentimento com campos do responsável legal.</p>
    </div>

    <div class="legal-section" id="direitos">
        <h2><span class="num">12</span> Seus direitos</h2>
        <p>Você pode pedir: confirmação de que tratamos seus dados, acesso, correção, anonimização, bloqueio ou eliminação de dados desnecessários, portabilidade, informação sobre compartilhamento, revogação do consentimento e revisão de decisões automatizadas (art. 18 da LGPD).</p>
        <p>Quem tem conta pode baixar ou pedir a exclusão dos próprios dados em <a href="{{ url('/eu/dados') }}">{{ url('/eu/dados') }}</a>. Os demais pedidos podem ser feitos pelo e-mail do Encarregado abaixo. Respondemos em até 15 dias. Você também pode reclamar à Autoridade Nacional de Proteção de Dados (ANPD), em <a href="https://www.gov.br/anpd" target="_blank" rel="noopener">gov.br/anpd</a>.</p>
    </div>

    <div class="legal-section">
        <h2><span class="num">13</span> Alterações</h2>
        <p>Podemos atualizar esta Política. A data no topo mostra a última versão; mudanças relevantes são avisadas por e-mail e no painel.</p>
    </div>

    <div class="dpo-card">
        <h3><i class="fas fa-user-shield me-2"></i>Encarregado de Proteção de Dados</h3>
        <p>@if($dpoNome){{ $dpoNome }} · @endif{{ $empresa }} · CNPJ {{ $cnpj }}</p>
        <a href="mailto:{{ $dpo }}"><i class="fas fa-envelope me-2"></i>{{ $dpo }}</a>
    </div>

</div>
@endsection
