@php
    $razaoSocial = $tenant->razao_social ?: $tenant->name;
    $cnpj        = $tenant->document ?: '__________________';
    $enderecoCompleto = trim(sprintf(
        '%s, %s%s - %s, %s/%s - CEP %s',
        $tenant->endereco ?: '',
        $tenant->numero_endereco ?: '',
        $tenant->complemento ? ' (' . $tenant->complemento . ')' : '',
        $tenant->bairro ?: '',
        $tenant->cidade ?: '',
        $tenant->estado ?: '',
        $tenant->cep ?: ''
    ));
    $emailContato = $tenant->email ?? ($tenant->contact_email ?? '__________________');
    $planoNome    = $plan->name ?? 'Plano nao definido';
    $isCourtesy   = (bool) ($plan->is_courtesy ?? false);
    $isAnual      = ($tenant->billing_cycle ?? 'monthly') === 'annual';
    $ciclo        = $isAnual ? 'Anual' : 'Mensal';
    $valor        = $isCourtesy
        ? '0,00 (CORTESIA)'
        : number_format((float) ($isAnual ? ($plan->annual_price ?? $plan->price ?? 0) : ($plan->price ?? 0)), 2, ',', '.');
    $modalidade   = $isCourtesy ? 'CORTESIA (sem cobranca)' : 'PAGA — recorrencia ' . strtolower($ciclo) . ' via AbacatePay';
    $hoje         = now()->format('d/m/Y');
    $versao       = $contract->version ?? '2.0';

    $temCebas = !empty($tenant->area_atuacao_cebas);
    $temCmas  = !empty($tenant->cmas_numero);
    $temCnas  = !empty($tenant->cnas_numero);
@endphp

<h2 style="text-align:center; margin:0 0 6px 0;">CONTRATO DE ADESAO — VIVENSI (OSC)</h2>
<p style="text-align:center; margin:0 0 6px 0; color:#64748b;">Termo especifico para Organizacoes da Sociedade Civil — Lei 13.019/2014 (MROSC) e LGPD</p>
<p style="text-align:center; margin:0 0 20px 0; color:#94a3b8; font-size: 9pt;">Versao {{ $versao }} — Vigencia a partir de 06/09/2026</p>

<p style="font-size: 10pt; color:#475569;">Este e um Contrato de Adesao, nos termos do art. 54 da Lei n 8.078/1990 (CDC) e do art. 425 do Codigo Civil. O aceite eletronico no ato do cadastro equivale, para todos os efeitos legais, a assinatura deste instrumento.</p>

<h3>1. PARTES</h3>

<p><strong>CONTRATADA:</strong> NC5 HUB DIGITAL LTDA, pessoa juridica de direito privado, inscrita no CNPJ sob n <strong>67.848.807/0001-50</strong>, com sede em Araraquara/SP, operadora da plataforma Vivensi ({{ config('app.url') }}), doravante denominada CONTRATADA ou OPERADOR.</p>

<div style="border: 1px solid #cbd5e1; background: #f8fafc; padding: 12px 14px; margin: 10px 0; border-radius: 4px;">
    <p style="margin: 0 0 6px 0;"><strong>CONTRATANTE (OSC):</strong></p>
    <p style="margin: 0;"><strong>Razao Social:</strong> {{ $razaoSocial }}</p>
    <p style="margin: 0;"><strong>CNPJ:</strong> {{ $cnpj }}</p>
    <p style="margin: 0;"><strong>Endereco:</strong> {{ $enderecoCompleto }}</p>
    <p style="margin: 0;"><strong>E-mail de contato:</strong> {{ $emailContato }}</p>
</div>

<h3>2. OBJETO E ENQUADRAMENTO REGULATORIO</h3>
<p>Contratacao dos servicos SaaS Vivensi para gestao integrada da OSC, incluindo cadastro de beneficiarios, projetos sociais, prestacao de contas, comunicacao (WhatsApp/e-mail), captacao, LGPD e demais funcionalidades disponiveis no plano contratado (conforme <strong>Anexo I</strong>).</p>
<p>A CONTRATANTE declara atuar em conformidade com o Marco Regulatorio das Organizacoes da Sociedade Civil (Lei 13.019/2014 — MROSC, Decreto 8.726/2016) e demais normas aplicaveis (Lei 9.790/1999 — OSCIP, Lei 12.101/2009 — CEBAS, Lei 8.069/1990 — ECA, Lei 8.742/1993 — LOAS/SUAS).</p>
<p>A plataforma Vivensi e ferramenta de apoio a gestao e nao substitui obrigacoes legais da OSC perante orgaos publicos, conselhos, MP, Receita Federal ou parceiros publicos em Termos de Fomento, Colaboracao ou Acordos de Cooperacao (MROSC).</p>

<h3>3. PLANO, VALORES E FORMA DE PAGAMENTO</h3>
<p>3.1. O plano, valor, modulos e periodicidade ficam congelados no <strong>Anexo I</strong>.</p>
<p>3.2. Cobranca via <strong>AbacatePay</strong> (PIX), em regime de recorrencia {{ strtolower($ciclo) }}.</p>
<p>3.3. Os valores pagos referem-se a licenca de uso da plataforma e nao caracterizam repasse assistencial ou parceria MROSC.</p>

<h3>4. REAJUSTE</h3>
<p>Reajuste anual pelo IPCA (IBGE) na data-base de aniversario do aceite, com notificacao de 30 dias. Discordancia permite rescisao sem onus.</p>

<h3>5. VIGENCIA E RENOVACAO AUTOMATICA</h3>
<p>Prazo indeterminado a partir do aceite eletronico, renovando-se automaticamente por confirmacao do pagamento.</p>

<h3>6. SUSPENSAO E CANCELAMENTO POR INADIMPLENCIA</h3>
<p>6.1. Notificacao em ate 24 horas do vencimento nao pago.</p>
<p>6.2. Suspensao do acesso apos 3 dias sem regularizacao (dados preservados).</p>
<p>6.3. Cancelamento apos 10 dias da suspensao (13 dias do vencimento), com aviso previo de 48 horas.</p>
<p>6.4. Reativacao ate 30 dias com quitacao + multa 2% + juros 1% a.m., sem perda de dados.</p>
<p>6.5. Purge definitivo apos 30 dias sem reativacao, ressalvadas retencoes legais de prestacao de contas MROSC (minimo 10 anos, art. 68 da Lei 13.019/2014).</p>

<h3>7. RESCISAO VOLUNTARIA</h3>
<p>A CONTRATANTE pode rescindir a qualquer tempo (cancelamento no gateway ou painel/e-mail contato@vivensi.app.br). A CONTRATADA pode rescindir com aviso de 30 dias, exceto em descumprimento grave (rescisao imediata). Apos rescisao, a CONTRATANTE tem 30 dias para exportar dados via {{ config('app.url') }}/eu/dados em formato aberto (JSON/CSV).</p>

<h3>8. NOTIFICACOES E COMUNICACOES</h3>
<p><strong>Da CONTRATADA:</strong> e-mail cadastrado, painel, WhatsApp (quando aplicavel).</p>
<p><strong>Da CONTRATANTE:</strong> contato@vivensi.app.br | dpo@vivensi.app.br (LGPD/Encarregado).</p>

<h3>9. LGPD — TRATAMENTO DE DADOS PESSOAIS (REFORCADA)</h3>
<p>9.1. <strong>Papeis:</strong> A CONTRATANTE atua como <strong>CONTROLADORA</strong> dos dados de beneficiarios, doadores, voluntarios, colaboradores. A CONTRATADA atua como <strong>OPERADORA</strong>, tratando dados exclusivamente conforme instrucoes documentadas da CONTRATANTE.</p>
<p>9.2. Cabe a CONTRATANTE indicar Encarregado (DPO), definir bases legais (arts. 7 e 11), gerir consentimentos, atender titulares (art. 18) e comunicar autoridades (ANPD) em incidentes.</p>
<p>9.3. <strong>Sub-operadores autorizados:</strong> AWS (sa-east-1), AbacatePay (pagamento), Meta Platforms Ireland Ltd. (WhatsApp Cloud API), DeepSeek AI (PLN), Together AI (imagens), Brevo (e-mail), Pusher Channels (websockets), Google (Gemini/reCAPTCHA). Lista atualizada em {{ config('app.url') }}/subprocessadores, com notificacao de mudancas em 30 dias.</p>
<p>9.4. <strong>Transferencia internacional:</strong> alguns sub-operadores processam dados fora do Brasil, mediante clausulas contratuais padrao (arts. 33 a 36 da LGPD).</p>
<p>9.5. <strong>Encarregado (DPO) da CONTRATADA:</strong> dpo@vivensi.app.br. <strong>Prazo de resposta a titulares:</strong> 15 dias.</p>
<p>9.6. Politica completa em {{ config('app.url') }}/privacidade, que integra este contrato.</p>

<h3>10. DADOS SENSIVEIS DE BENEFICIARIOS VULNERAVEIS</h3>
<p>A CONTRATANTE reconhece que a plataforma pode armazenar dados sensiveis (art. 5 II da LGPD) e dados de grupos vulneraveis:</p>
<ul>
    <li>Criancas e adolescentes (art. 14 LGPD c/c ECA — Lei 8.069/1990);</li>
    <li>Pessoas em vulnerabilidade social atendidas por servicos socioassistenciais (SUAS);</li>
    <li>Pessoas idosas (Lei 10.741/2003), pessoas com deficiencia (Lei 13.146/2015);</li>
    <li>Dados de saude, orientacao sexual, origem racial/etnica, conviccao religiosa, filiacao sindical, dados geneticos ou biometricos.</li>
</ul>
<p><strong>Compromissos reforcados da CONTRATADA:</strong></p>
<ul>
    <li>Criptografia at-rest (AES-256) para campos sensiveis identificados (CPF, telefone, e-mail e documentos anexos);</li>
    <li>Registro de acesso (logs de auditoria) a prontuarios e atendimentos, com retencao minima de 6 meses;</li>
    <li>Segregacao logica por tenant (multi-tenancy fail-closed), impedindo vazamento cruzado entre OSCs;</li>
    <li>Consentimento parental obrigatorio para dados de criancas menores de 12 anos (art. 14 §1 LGPD);</li>
    <li>Notificacao a CONTRATANTE em ate 72 (setenta e duas) horas em incidente com risco relevante, contendo natureza, dados afetados, medidas tecnicas e cronograma, subsidiando a comunicacao a ANPD (art. 48 LGPD).</li>
</ul>

<h3>11. CONFIDENCIALIDADE</h3>
<p>A CONTRATADA mantem sigilo sobre todos os dados da CONTRATANTE, aplicando-se termos de confidencialidade a empregados, prestadores e subcontratados, mesmo apos o encerramento contratual, por prazo minimo de 5 anos. Descumprimento acarreta responsabilizacao civil e criminal.</p>

<h3>12. RESPONSABILIDADE POR INCIDENTES DE SEGURANCA</h3>
<p>12.1. <strong>Culpa da CONTRATANTE</strong> (compartilhamento de credenciais, ausencia de 2FA quando disponivel, configuracao inadequada, exposicao voluntaria, descumprimento da Clausula 14): responsabilidade exclusiva da CONTRATANTE.</p>
<p>12.2. <strong>Culpa da CONTRATADA</strong>: comunicacao a ANPD e titulares em ate 3 dias uteis da ciencia (art. 48 LGPD).</p>
<p>12.3. Divergencia sobre causa: pericia tecnica independente, custos igualmente repartidos.</p>

<h3>13. LIMITACAO DE RESPONSABILIDADE</h3>
<p>13.1. Salvo dolo ou culpa grave, a responsabilidade civil total da CONTRATADA fica limitada ao <strong>valor efetivamente pago pela CONTRATANTE nos 12 meses anteriores</strong> ao evento gerador do dano.</p>
<p>13.2. A CONTRATADA nao responde por: danos indiretos, lucros cessantes; prejuizos causados por sub-operadores, forca maior ou culpa da CONTRATANTE; perdas de dados atribuiveis a CONTRATANTE; danos a terceiros decorrentes do conteudo publicado pela CONTRATANTE (landing pages, campanhas, mensagens).</p>
<p>13.3. Limitacao nao se aplica a violacoes da LGPD imputaveis exclusivamente a CONTRATADA.</p>

<h3>14. OBRIGACOES DA CONTRATANTE (OSC)</h3>
<ul>
    <li>Fornecer dados verdadeiros (razao social, CNPJ, endereco, estatuto quando solicitado) e mante-los atualizados;</li>
    <li>Utilizar a plataforma exclusivamente para atividades condizentes com sua missao estatutaria;</li>
    <li>Cumprir LGPD, MROSC (quando houver parceria com poder publico), LAI (Lei 12.527/2011), LOAS/SUAS, normas setoriais;</li>
    <li>Manter regularidade fiscal, trabalhista e cadastral (CEBAS, CMAS, CNAS, CNEAS quando aplicavel);</li>
    <li>Nao utilizar a plataforma para spam, abuso, discurso de odio ou conduta ilicita;</li>
    <li>Obter consentimento valido dos titulares ou identificar base legal apropriada (arts. 7/11 LGPD);</li>
    <li>Adotar praticas de seguranca, incluindo sigilo de credenciais e 2FA quando disponivel;</li>
    <li>Efetuar pagamentos nos prazos, sob pena da Clausula 6.</li>
</ul>

<h3>15. OBRIGACOES DA CONTRATADA (OPERADOR)</h3>
<ul>
    <li>Disponibilizar a plataforma conforme SLA (Clausula 16);</li>
    <li>Suporte tecnico em dias uteis, das 09h as 18h (Brasilia), com canal dedicado a OSCs;</li>
    <li>Backups automatizados diarios (retencao minima 7 dias) e medidas de seguranca compativeis com o estado da arte (criptografia, controle de acesso, WAF, monitoramento);</li>
    <li><strong>Nao utilizar dados da CONTRATANTE para treinamento de modelos de IA proprios ou de terceiros</strong> sem autorizacao expressa;</li>
    <li><strong>Nao comercializar, ceder ou compartilhar dados</strong> com terceiros, ressalvadas obrigacoes legais ou ordem judicial;</li>
    <li>Notificar incidentes (Clausula 10);</li>
    <li>Aviso de 48 horas sobre manutencao programada.</li>
</ul>

<h3>16. NIVEL DE SERVICO (SLA)</h3>
<p>16.1. <strong>Meta:</strong> 99,0% mensal, apurada em janelas de 5 minutos.</p>
<p>16.2. <strong>Exclusoes:</strong> manutencao programada; sub-operadores; forca maior; culpa da CONTRATANTE; ataques ciberneticos de terceiros.</p>
<p>16.3. <strong>Credito na proxima fatura</strong> por descumprimento: 5% (98-98,9%), 10% (95-97,9%), 25% (&lt;95%). Descumprimento por 3 meses consecutivos: rescisao sem onus.</p>

<h3>17. FORCA MAIOR</h3>
<p>Nenhuma parte responde por descumprimento decorrente de forca maior (pandemias, guerras, atos governamentais, indisponibilidade de AWS/gateways/Meta, ataques ciberneticos de larga escala). Notificacao em 48 horas; se perdurar por mais de 30 dias, qualquer parte pode rescindir sem onus.</p>

<h3>18. USO DE MARCA E CASES</h3>
<p>A CONTRATADA <strong>nao</strong> utilizara o nome, logo, marca ou informacoes da CONTRATANTE em materiais de marketing, cases publicos ou depoimentos, exceto mediante autorizacao expressa e por escrito da CONTRATANTE, revogavel a qualquer tempo.</p>

<h3>19. PROPRIEDADE INTELECTUAL</h3>
<p>19.1. A plataforma Vivensi (codigo, layout, marca) e da CONTRATADA.</p>
<p>19.2. <strong>Dados da CONTRATANTE</strong> permanecem de sua propriedade; a CONTRATADA obtem licenca limitada e revogavel para processa-los.</p>
<p>19.3. Sugestoes de melhoria, se implementadas, geram licenca perpetua, irrevogavel, nao-exclusiva e gratuita a CONTRATADA, sem cessao de direitos.</p>

<h3>20. AUDITORIA</h3>
<p>A CONTRATANTE, com aviso previo de 30 dias, pode auditar as praticas de seguranca e privacidade da CONTRATADA, diretamente ou por terceiro independente, sem prejuizo do acesso aos relatorios de auditoria interna e certificacoes disponiveis.</p>

<h3>21. CESSAO DO CONTRATO</h3>
<p>A CONTRATADA pode ceder em fusao/aquisicao/reorganizacao societaria com aviso de 30 dias. A CONTRATANTE nao pode ceder sem consentimento previo por escrito.</p>

<h3>22. DISPOSICOES GERAIS</h3>
<p>Atualizacoes com aviso de 30 dias (continuidade do uso implica aceite; discordancia permite rescisao sem onus). Aceite eletronico valido nos termos da MP 2.200-2/2001 e art. 219 do CC. Registro de data, hora, IP, user-agent e hash HMAC-SHA256 como prova. Nulidade de clausula nao afeta as demais. Contrato regido pelas leis brasileiras.</p>

<h3>23. FORO</h3>
<p>Fica eleito o foro da comarca de <strong>Araraquara/SP</strong> para dirimir questoes oriundas deste contrato, com renuncia expressa a qualquer outro por mais privilegiado que seja.</p>

@if($temCebas || $temCmas || $temCnas)
    <h3>24. QUALIFICACOES DA OSC (DECLARADAS)</h3>
    <ul>
        @if($temCebas)
            <li>CEBAS — Area de atuacao: {{ $tenant->area_atuacao_cebas }}</li>
        @endif
        @if($temCmas)
            <li>CMAS — Registro n {{ $tenant->cmas_numero }}@if($tenant->cmas_validade), valido ate {{ optional($tenant->cmas_validade)->format('d/m/Y') }}@endif</li>
        @endif
        @if($temCnas)
            <li>CNAS — Registro n {{ $tenant->cnas_numero }}@if($tenant->cnas_validade), valido ate {{ optional($tenant->cnas_validade)->format('d/m/Y') }}@endif</li>
        @endif
    </ul>
@endif

<hr style="border: none; border-top: 2px solid #0f172a; margin: 30px 0 20px 0;">

<h3 style="text-align:center;">ANEXO I — SNAPSHOT DO PLANO CONTRATADO</h3>
<p style="text-align:center; font-size: 9pt; color:#64748b; margin: 0 0 12px 0;">Congelado no ato do aceite eletronico. Integra este contrato para todos os fins.</p>

<div style="border: 1px solid #cbd5e1; background: #f8fafc; padding: 12px 14px; margin: 10px 0; border-radius: 4px;">
    <p style="margin: 0;"><strong>Plano contratado:</strong> {{ $planoNome }}</p>
    <p style="margin: 0;"><strong>Valor:</strong> R$ {{ $valor }}</p>
    <p style="margin: 0;"><strong>Periodicidade:</strong> {{ $ciclo }}</p>
    <p style="margin: 0;"><strong>Modalidade:</strong> {{ $modalidade }}</p>
    <p style="margin: 0;"><strong>Data de inicio da vigencia:</strong> {{ $hoje }}</p>
    <p style="margin: 0;"><strong>Data-base para reajuste:</strong> {{ now()->addYear()->format('d/m/Y') }} (aniversario do aceite)</p>
    <p style="margin: 0;"><strong>Gateway de pagamento:</strong> AbacatePay (PIX)</p>
</div>

<p style="margin-top: 24px;"><strong>Data:</strong> {{ $hoje }}</p>
<p><strong>Signatario:</strong> {{ $contract->signer_name ?? '' }}
    @if($contract->signer_cpf) — CPF {{ $contract->signer_cpf }}@endif
</p>
<p style="color:#64748b; font-size: 9pt;">Signatario declara ter poderes estatutarios para representar a OSC nos termos deste contrato.</p>
