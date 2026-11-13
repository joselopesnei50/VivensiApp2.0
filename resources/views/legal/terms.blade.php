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

@php
    $empresa = config('legal.company_name'); $cnpj = config('legal.cnpj'); $produto = config('legal.product_name', 'Vivensi');
    $suporte = config('legal.email_support'); $dpo = config('legal.email_dpo');
    $reemb = config('legal.refund_days', 7); $ret = config('legal.retention_days_after_cancel', 90);
@endphp
<div class="legal-hero">
    <div class="legal-badge"><i class="fas fa-file-contract"></i> Termos & Condições</div>
    <h1>Termos de Uso</h1>
    <p>Última atualização: {{ config('legal.last_update') }}</p>
</div>

<div class="legal-body">

    <a href="{{ url('/') }}" class="back-link"><i class="fas fa-arrow-left"></i> Voltar para o início</a>

    <div class="legal-section">
        <h2><span class="num">1</span> Quem somos e o que estes Termos cobrem</h2>
        <p>A plataforma <strong>{{ $produto }}</strong> ({{ config('app.url') }}) é um produto da <strong>{{ $empresa }}</strong>, inscrita no CNPJ sob o nº {{ $cnpj }}, com sede em {{ config('legal.city') }} ("NC5", "nós").</p>
        <p>Estes Termos valem para o site e para a plataforma. Para as organizações que assinam um plano, vale também o <strong>Contrato de Adesão</strong> aceito eletronicamente no cadastro. Se houver diferença entre os dois, prevalece o Contrato de Adesão.</p>
        <p>Ao criar uma conta ou usar a plataforma, você declara que leu e concorda com estes Termos e com a <a href="{{ route('legal.privacy') }}">Política de Privacidade</a>. Quem cadastra uma organização declara ter poderes para representá-la.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">2</span> O serviço</h2>
        <p>O {{ $produto }} é um software de gestão para organizações do terceiro setor, oferecido pela internet (SaaS), acessado pelo navegador do computador ou do celular. Os módulos disponíveis são os do plano contratado, descritos na página de preços e no Anexo I do Contrato de Adesão.</p>
        <p>Podemos melhorar, mudar ou substituir funcionalidades. Se uma mudança retirar algo essencial do seu plano, avisaremos com 30 dias de antecedência, e você poderá cancelar sem custo.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">3</span> Conta, acesso e equipe</h2>
        <ul>
            <li>Os dados do cadastro (razão social, CNPJ, endereço e responsável) devem ser verdadeiros e mantidos atualizados.</li>
            <li>Login e senha são pessoais. Recomendamos ativar a verificação em duas etapas (2FA).</li>
            <li>O administrador da conta decide quem entra na equipe e com qual perfil (Administrador, Coordenação, Colaborador ou acesso a um projeto) e responde pelo uso que a equipe faz da plataforma.</li>
            <li>Se percebermos uso indevido ou risco à segurança, podemos suspender um acesso, avisando o administrador.</li>
        </ul>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">4</span> Planos, pagamento e renovação</h2>
        <ul>
            <li><strong>Períodos:</strong> a assinatura pode ser <strong>mensal (30 dias)</strong> ou <strong>anual</strong>, conforme escolhido na contratação, e se renova automaticamente por períodos iguais até ser cancelada.</li>
            <li><strong>Pagamento:</strong> por PIX, pela AbacatePay. A fatura do período seguinte é emitida 5 dias antes do vencimento e enviada por e-mail.</li>
            <li><strong>Nota fiscal:</strong> emitida para o CNPJ informado no cadastro.</li>
            <li><strong>Reajuste:</strong> uma vez por ano, pelo IPCA, na data de aniversário da contratação, com aviso de 30 dias.</li>
            <li><strong>Atraso:</strong> com 5 dias de atraso, o acesso fica restrito à tela de faturas até o pagamento. Os dados são preservados, e o acesso volta assim que o pagamento é confirmado. As regras completas de inadimplência estão no Contrato de Adesão.</li>
        </ul>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">5</span> Cancelamento e reembolso</h2>
        <p><strong>Cancelamento:</strong> você pode cancelar a qualquer momento, sem multa, em <strong>Minha conta → Faturas</strong> (administrador da conta) ou pelo e-mail <a href="mailto:{{ $suporte }}">{{ $suporte }}</a>. O acesso continua até o fim do período já pago, e não são emitidas novas faturas.</p>
        <p><strong>Reembolso (direito de arrependimento):</strong> pedidos feitos em até <strong>{{ $reemb }} dias</strong> da primeira contratação recebem a <strong>devolução integral</strong> do valor pago, sem precisar justificar (art. 49 do Código de Defesa do Consumidor). Depois desse prazo, não há reembolso do período em curso (mensal ou anual), que segue disponível até o fim.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">6</span> Seus dados depois do cancelamento</h2>
        <p>Depois do fim da assinatura, os dados da organização ficam guardados por <strong>{{ $ret }} dias</strong>. Nesse prazo é possível reativar a conta ou exportar os dados (planilhas e arquivos em formato aberto). Passados os {{ $ret }} dias, os dados são apagados de forma definitiva, inclusive das cópias de segurança no ciclo seguinte, exceto o que a lei nos obriga a manter (por exemplo, dados fiscais e de cobrança).</p>
        <p>Documentos que a própria organização é obrigada a guardar (como a prestação de contas de parcerias com o poder público) devem ser exportados por ela antes do fim desse prazo.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">7</span> Uso permitido</h2>
        <p>Não é permitido usar a plataforma para:</p>
        <ul>
            <li>enviar mensagens ou e-mails não solicitados (spam), ou descumprir as políticas do WhatsApp/Meta e dos provedores de e-mail;</li>
            <li>tratar dados pessoais sem base legal, ou publicar dados de beneficiários sem autorização;</li>
            <li>publicar conteúdo ilegal, discriminatório, enganoso ou que viole direitos de terceiros;</li>
            <li>tentar acessar dados de outras organizações, contornar limites técnicos ou atacar a plataforma.</li>
        </ul>
        <div class="legal-warning">O descumprimento pode levar à suspensão do recurso envolvido ou da conta, sem prejuízo das medidas legais cabíveis.</div>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">8</span> Dados pessoais</h2>
        <p>Nos dados que a organização cadastra (beneficiários, famílias, doadores, voluntários, equipe), <strong>a organização é a controladora</strong> e decide como e por que eles são usados; <strong>a NC5 é a operadora</strong> e trata esses dados apenas para prestar o serviço, conforme as instruções da organização e a Lei nº 13.709/2018 (LGPD).</p>
        <p>Nos dados de quem visita o site e de quem usa a conta (login, cobrança, suporte), a NC5 é a controladora. Os detalhes estão na <a href="{{ route('legal.privacy') }}">Política de Privacidade</a>.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">9</span> Recursos de inteligência artificial</h2>
        <p>Alguns recursos (Bruce IA, Sala de Estratégia, gerador de posts e de imagens, apoio a editais) usam provedores de IA. As respostas podem conter erros e devem ser revisadas antes de usadas em documentos, prestação de contas ou comunicação. Enviamos aos provedores só o necessário para a tarefa pedida e não usamos os dados das organizações para treinar modelos de IA.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">10</span> Propriedade intelectual</h2>
        <p>O software, a marca {{ $produto }}, o layout e os materiais da plataforma pertencem à NC5. Os dados e conteúdos da organização continuam sendo dela; para prestar o serviço, ela nos concede uma licença limitada, que termina com a exclusão dos dados.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">11</span> Disponibilidade e suporte</h2>
        <p>A meta de disponibilidade é de 99% ao mês, com créditos previstos no Contrato de Adesão quando não for atingida. Manutenções programadas são avisadas com 48 horas de antecedência. O suporte atende em dias úteis, das 9h às 18h (horário de Brasília), pelo e-mail <a href="mailto:{{ $suporte }}">{{ $suporte }}</a> e pelo WhatsApp informado no site.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">12</span> Limitação de responsabilidade</h2>
        <p>Salvo dolo ou culpa grave, a responsabilidade total da NC5 fica limitada ao valor efetivamente pago pela organização nos 12 meses anteriores ao fato. A NC5 não responde por danos indiretos ou lucros cessantes, por falhas causadas por uso indevido da conta, nem pelo conteúdo que a organização publica (páginas, campanhas e mensagens). Essa limitação não se aplica a violações da LGPD causadas exclusivamente pela NC5.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">13</span> Alterações destes Termos</h2>
        <p>Podemos atualizar estes Termos. Mudanças relevantes serão avisadas por e-mail e no painel com 30 dias de antecedência. Se você não concordar, pode cancelar sem custo antes de a mudança valer.</p>
    </div>

    <div class="legal-divider"></div>

    <div class="legal-section">
        <h2><span class="num">14</span> Lei aplicável e foro</h2>
        <p>Estes Termos seguem as leis brasileiras. Fica eleito o foro da comarca de <strong>{{ config('legal.forum') }}</strong> para resolver qualquer questão sobre eles.</p>
    </div>

    <div class="contact-card">
        <i class="fas fa-headset" style="font-size: 2rem; margin-bottom: 14px; display: block; opacity: 0.9;"></i>
        <h3>Ficou com dúvidas?</h3>
        <p>{{ $empresa }} · CNPJ {{ $cnpj }} · {{ config('legal.city') }}</p>
        <a href="mailto:{{ $suporte }}"><i class="fas fa-envelope me-2"></i>{{ $suporte }}</a>
    </div>

</div>
@endsection
