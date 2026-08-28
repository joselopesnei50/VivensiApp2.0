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
    $planoNome    = $plan->name ?? 'Plano nao definido';
    $isCourtesy   = (bool) ($plan->is_courtesy ?? false);
    $valor        = $isCourtesy ? '0,00 (CORTESIA)' : number_format((float) ($plan->price ?? 0), 2, ',', '.');
    $modalidade   = $isCourtesy ? 'CORTESIA (sem cobranca)' : 'PAGA — recorrencia mensal via AbacatePay';
    $hoje         = now()->format('d/m/Y');

    $temCebas = !empty($tenant->area_atuacao_cebas);
    $temCmas  = !empty($tenant->cmas_numero);
    $temCnas  = !empty($tenant->cnas_numero);
@endphp

<h2 style="text-align:center; margin:0 0 6px 0;">CONTRATO DE ADESAO — VIVENSI SAAS</h2>
<p style="text-align:center; margin:0 0 24px 0; color:#64748b;">Termo especifico para Organizacoes da Sociedade Civil (OSC) — Lei 13.019/2014 (MROSC) e LGPD</p>

<h3>1. PARTES</h3>
<p><strong>CONTRATANTE:</strong> {{ $razaoSocial }}, Organizacao da Sociedade Civil (OSC) inscrita no CNPJ sob n {{ $cnpj }}, com sede em {{ $enderecoCompleto }}, doravante denominada CONTRATANTE ou OSC.</p>
<p><strong>CONTRATADA:</strong> NC5 HUB DIGITAL LTDA, inscrita no CNPJ 00.000.000/0001-00, operadora da plataforma Vivensi ({{ config('app.url') }}), doravante denominada CONTRATADA ou OPERADOR.</p>

<h3>2. OBJETO E ENQUADRAMENTO REGULATORIO</h3>
<p>Contratacao dos servicos SaaS Vivensi para gestao integrada da OSC, incluindo cadastro de beneficiarios, projetos sociais, prestacao de contas, comunicacao (WhatsApp/e-mail), captacao, LGPD e demais funcionalidades disponiveis no plano contratado.</p>
<p>A CONTRATANTE declara atuar em conformidade com o Marco Regulatorio das Organizacoes da Sociedade Civil — Lei n 13.019/2014 (MROSC), regulamentada pelo Decreto n 8.726/2016, e demais normas aplicaveis (Lei 9.790/1999 — OSCIP, Lei 12.101/2009 — CEBAS, Lei 8.069/1990 — ECA, Lei 8.742/1993 — LOAS/SUAS, quando cabivel).</p>
<p>A plataforma Vivensi e ferramenta de apoio a gestao e nao substitui obrigacoes legais da OSC perante orgaos publicos, conselhos de politicas publicas, Ministerio Publico, Receita Federal ou parceiros publicos em Termos de Fomento, Colaboracao ou Acordos de Cooperacao (MROSC).</p>

<h3>3. PLANO E VALORES</h3>
<p><strong>Plano contratado:</strong> {{ $planoNome }}<br>
<strong>Valor mensal:</strong> R$ {{ $valor }}<br>
<strong>Modalidade:</strong> {{ $modalidade }}</p>

@if(!$isCourtesy)
    <p>A cobranca sera realizada por meio do gateway AbacatePay via PIX. A ativacao dos servicos ocorrera apos a confirmacao do primeiro pagamento. A CONTRATANTE reconhece que os valores pagos referem-se a licenca de uso da plataforma e nao caracterizam repasse assistencial ou parceria MROSC.</p>
@else
    <p>Esta adesao esta na modalidade CORTESIA. A CONTRATANTE nao sera cobrada durante o periodo de cortesia. A CONTRATADA reserva-se o direito de encerrar a cortesia mediante aviso previo de 30 (trinta) dias.</p>
@endif

<h3>4. VIGENCIA</h3>
<p>Este contrato vigora por prazo indeterminado a partir da data de assinatura, podendo ser rescindido por qualquer das partes mediante aviso previo de 30 (trinta) dias, garantido a CONTRATANTE o direito de exportar integralmente seus dados antes do encerramento, nos termos da clausula 7.</p>

<h3>5. LGPD — TRATAMENTO DE DADOS PESSOAIS (REFORCADA)</h3>
<p>Para os fins deste contrato e da Lei n 13.709/2018 (LGPD):</p>
<ul>
    <li>A CONTRATANTE atua como <strong>CONTROLADORA</strong> dos dados pessoais de beneficiarios, doadores, voluntarios, colaboradores e demais titulares tratados na plataforma;</li>
    <li>A CONTRATADA atua como <strong>OPERADORA</strong>, tratando dados exclusivamente conforme instrucoes documentadas da CONTRATANTE e para as finalidades estritas da prestacao do servico;</li>
    <li>Cabe a CONTRATANTE indicar Encarregado (DPO), definir bases legais (art. 7 e art. 11 da LGPD), gerir consentimentos, atender titulares (art. 18) e comunicar autoridades (ANPD) em incidentes;</li>
    <li>A CONTRATADA apoia a CONTRATANTE com ferramentas de portabilidade, eliminacao, correcao e registro de operacoes, mas nao decide sobre finalidades ou bases legais.</li>
</ul>

<h3>6. DADOS SENSIVEIS DE BENEFICIARIOS VULNERAVEIS</h3>
<p>A CONTRATANTE reconhece que a plataforma pode armazenar dados pessoais sensiveis (art. 5 II da LGPD) e dados de grupos vulneraveis, incluindo:</p>
<ul>
    <li>Criancas e adolescentes (art. 14 da LGPD c/c ECA — Lei 8.069/1990);</li>
    <li>Pessoas em situacao de vulnerabilidade social atendidas por servicos socioassistenciais (SUAS);</li>
    <li>Pessoas idosas (Lei 10.741/2003), pessoas com deficiencia (Lei 13.146/2015) e demais grupos protegidos;</li>
    <li>Dados de saude, orientacao sexual, origem racial ou etnica, conviccao religiosa, filiacao a sindicato, dados geneticos ou biometricos.</li>
</ul>
<p><strong>Compromissos reforcados da CONTRATADA:</strong></p>
<ul>
    <li>Criptografia at-rest (AES-256) para campos sensiveis identificados (CPF, telefone, e-mail e documentos anexos);</li>
    <li>Registro de acesso (logs de auditoria) a prontuarios e atendimentos, com retencao minima de 6 (seis) meses;</li>
    <li>Segregacao logica de dados por tenant (multi-tenancy fail-closed), impedindo vazamento cruzado entre OSCs;</li>
    <li>Consentimento parental obrigatorio para dados de criancas menores de 12 anos (art. 14 §1 da LGPD), operacionalizado por termo de consentimento vinculado ao cadastro do beneficiario;</li>
    <li>Notificacao a CONTRATANTE em ate 72 (setenta e duas) horas em caso de incidente de seguranca com risco relevante, contendo natureza, dados afetados, medidas tecnicas e cronograma de resposta, subsidiando a comunicacao a ANPD (art. 48 da LGPD).</li>
</ul>

<h3>7. PORTABILIDADE, EXPORTACAO E ELIMINACAO</h3>
<p>A CONTRATANTE pode a qualquer tempo:</p>
<ul>
    <li>Exportar integralmente os dados tratados na plataforma em formato estruturado (CSV/JSON), via painel proprio ou solicitacao formal atendida em ate 15 (quinze) dias uteis;</li>
    <li>Solicitar a eliminacao de dados de titulares, respeitados prazos legais de guarda (fiscais, trabalhistas, prestacao de contas MROSC — minimo 10 anos para parcerias com poder publico, conforme art. 68 da Lei 13.019/2014);</li>
    <li>Ao encerrar o contrato, a CONTRATADA mantera backup criptografado por 30 (trinta) dias para fins de contingencia e, apos, eliminara definitivamente os dados, salvo obrigacao legal de retencao.</li>
</ul>

<h3>8. SIGILO E CONFIDENCIALIDADE</h3>
<p>A CONTRATADA obriga-se a manter sigilo absoluto sobre todos os dados da CONTRATANTE, aplicando-se termos de confidencialidade a seus empregados, prestadores e subcontratados que tenham acesso, mesmo apos o encerramento contratual, por prazo minimo de 5 (cinco) anos. O descumprimento acarreta responsabilizacao civil e criminal nos termos da lei.</p>

<h3>9. OBRIGACOES DA CONTRATANTE (OSC)</h3>
<ul>
    <li>Fornecer dados verdadeiros no cadastro (razao social, CNPJ, endereco, estatuto quando solicitado) e mante-los atualizados;</li>
    <li>Utilizar a plataforma exclusivamente para atividades condizentes com sua missao estatutaria;</li>
    <li>Cumprir a legislacao aplicavel: LGPD, MROSC quando houver parceria com poder publico, LAI (Lei 12.527/2011) quando receber recurso publico, LOAS/SUAS quando prestar servico socioassistencial, e demais normas setoriais;</li>
    <li>Manter regularidade fiscal, trabalhista e cadastral (CEBAS, CMAS, CNAS, CNEAS quando aplicavel);</li>
    <li>Nao utilizar a plataforma para spam, abuso, discurso de odio ou qualquer conduta ilicita;</li>
    <li>Obter consentimento valido dos titulares antes de inserir dados pessoais (art. 7/11 da LGPD) ou identificar base legal apropriada;</li>
    <li>Efetuar pagamentos nos prazos acordados (quando aplicavel).</li>
</ul>

<h3>10. OBRIGACOES DA CONTRATADA (OPERADOR)</h3>
<ul>
    <li>Disponibilizar a plataforma com disponibilidade mensal alvo de 99%;</li>
    <li>Prover suporte tecnico em horario comercial, com canal dedicado a OSCs;</li>
    <li>Manter backups automatizados diarios e adotar medidas tecnicas e administrativas de seguranca compativeis com o estado da arte (criptografia, controle de acesso, WAF, monitoramento);</li>
    <li>Nao utilizar dados da CONTRATANTE para treinamento de modelos de IA proprios ou de terceiros sem autorizacao expressa;</li>
    <li>Nao comercializar, ceder ou compartilhar dados da CONTRATANTE com terceiros, ressalvadas obrigacoes legais ou ordem judicial;</li>
    <li>Notificar incidentes de seguranca conforme clausula 6.</li>
</ul>

<h3>11. USO DE MARCA E CASES</h3>
<p>A CONTRATADA <strong>nao</strong> utilizara o nome, logo, marca ou informacoes da CONTRATANTE em materiais de marketing, cases publicos ou depoimentos, exceto mediante autorizacao expressa e por escrito da CONTRATANTE, revogavel a qualquer tempo.</p>

<h3>12. SUBOPERADORES</h3>
<p>A CONTRATADA pode contratar suboperadores (hospedagem em nuvem, gateway de pagamento, provedores de e-mail e WhatsApp) para prestacao do servico, garantindo que estes cumpram nivel de protecao de dados equivalente ao previsto neste contrato e na LGPD.</p>

<h3>13. AUDITORIA</h3>
<p>A CONTRATANTE, mediante aviso previo de 30 (trinta) dias, pode auditar as praticas de seguranca e privacidade da CONTRATADA, diretamente ou por terceiro independente, sem prejuizo do acesso aos relatorios de auditoria interna e certificacoes eventualmente disponiveis.</p>

<h3>14. FORO</h3>
<p>Fica eleito o foro da comarca de Sao Paulo/SP para dirimir quaisquer questoes oriundas deste contrato, com renuncia expressa a qualquer outro, por mais privilegiado que seja.</p>

@if($temCebas || $temCmas || $temCnas)
    <h3>15. QUALIFICACOES DA OSC (DECLARADAS)</h3>
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

<p style="margin-top: 32px;"><strong>Data:</strong> {{ $hoje }}</p>
<p><strong>Signatario:</strong> {{ $contract->signer_name ?? '' }}
    @if($contract->signer_cpf) — CPF {{ $contract->signer_cpf }}@endif
</p>
<p style="color:#64748b; font-size: 9pt;">Signatario declara ter poderes estatutarios para representar a OSC nos termos deste contrato.</p>
