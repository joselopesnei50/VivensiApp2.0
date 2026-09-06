@php
    $razaoSocial = $tenant->razao_social ?: $tenant->name;
    $documento   = $tenant->document ?: '__________________';
    $tipoDoc     = strlen(preg_replace('/\D/', '', $tenant->document ?? '')) === 14 ? 'CNPJ' : 'CPF';
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
@endphp

<h2 style="text-align:center; margin:0 0 6px 0;">CONTRATO DE ADESAO — VIVENSI</h2>
<p style="text-align:center; margin:0 0 6px 0; color:#64748b;">Termo de Prestacao de Servicos por meio da Plataforma Vivensi</p>
<p style="text-align:center; margin:0 0 20px 0; color:#94a3b8; font-size: 9pt;">Versao {{ $versao }} — Vigencia a partir de 06/09/2026</p>

<p style="font-size: 10pt; color:#475569;">Este e um Contrato de Adesao, nos termos do art. 54 da Lei n 8.078/1990 (CDC) e do art. 425 do Codigo Civil. Suas clausulas sao previamente estabelecidas pela CONTRATADA e aplicam-se a todos os assinantes, independentemente de negociacao individual. O aceite eletronico no momento do cadastro e da confirmacao de pagamento equivale, para todos os efeitos legais, a assinatura deste instrumento.</p>

<h3>1. PARTES</h3>

<p><strong>CONTRATADA:</strong> NC5 HUB DIGITAL LTDA, pessoa juridica de direito privado, inscrita no CNPJ sob n <strong>67.848.807/0001-50</strong>, com sede em Araraquara/SP, operadora da plataforma Vivensi ({{ config('app.url') }}), doravante denominada CONTRATADA.</p>

<div style="border: 1px solid #cbd5e1; background: #f8fafc; padding: 12px 14px; margin: 10px 0; border-radius: 4px;">
    <p style="margin: 0 0 6px 0;"><strong>CONTRATANTE:</strong></p>
    <p style="margin: 0;"><strong>Nome / Razao Social:</strong> {{ $razaoSocial }}</p>
    <p style="margin: 0;"><strong>{{ $tipoDoc }}:</strong> {{ $documento }}</p>
    <p style="margin: 0;"><strong>Endereco:</strong> {{ $enderecoCompleto }}</p>
    <p style="margin: 0;"><strong>E-mail de contato:</strong> {{ $emailContato }}</p>
</div>

<h3>2. OBJETO</h3>
<p>Contratacao dos servicos SaaS Vivensi para gestao integrada de organizacoes, incluindo modulos administrativos, financeiros, de WhatsApp, marketing, LGPD e demais funcionalidades disponiveis no plano contratado, conforme descricao vigente em {{ config('app.url') }} e conforme registrado no <strong>Anexo I — Snapshot do Plano Contratado</strong>, gerado automaticamente no ato do aceite.</p>

<h3>3. PLANO, VALORES E FORMA DE PAGAMENTO</h3>
<p>3.1. O plano, valor, modulos e periodicidade ficam congelados no Anexo I na data do aceite.</p>
<p>3.2. A cobranca e realizada pelo gateway <strong>AbacatePay</strong>, via PIX, em regime de recorrencia {{ strtolower($ciclo) }}.</p>
<p>3.3. A ativacao e manutencao dos servicos estao condicionadas a confirmacao do pagamento.</p>
<p>3.4. Alteracoes de plano: <strong>upgrade</strong> tem efeito imediato com cobranca proporcional; <strong>downgrade</strong> tem efeito ao final do ciclo vigente, sem restituicao de valores pagos.</p>

<h3>4. REAJUSTE</h3>
<p>4.1. Os valores contratados serao reajustados anualmente, na data-base de aniversario do aceite, pela variacao positiva do <strong>IPCA</strong> (IBGE) acumulado nos 12 meses anteriores, ou por indice que venha a substitui-lo.</p>
<p>4.2. A CONTRATADA notificara a CONTRATANTE com antecedencia minima de 30 dias sobre o reajuste, pelos canais da Clausula 8.</p>
<p>4.3. A CONTRATANTE que nao concordar podera rescindir sem onus ate a data de aniversario.</p>

<h3>5. VIGENCIA E RENOVACAO AUTOMATICA</h3>
<p>Este contrato vigora por prazo indeterminado a partir do aceite eletronico, renovando-se automaticamente a cada ciclo mediante confirmacao do pagamento, sem novo aceite.</p>

<h3>6. SUSPENSAO E CANCELAMENTO POR INADIMPLENCIA</h3>
<p>6.1. <strong>Notificacao:</strong> Nao confirmado o pagamento na data de vencimento, a CONTRATADA notificara a CONTRATANTE pelo e-mail cadastrado em ate 24 horas.</p>
<p>6.2. <strong>Suspensao:</strong> Decorridos 3 dias corridos sem regularizacao, o acesso sera suspenso; os dados permanecem integros e o acesso e restaurado em ate 24 horas apos confirmacao do pagamento.</p>
<p>6.3. <strong>Cancelamento automatico:</strong> Persistindo a inadimplencia por 10 dias corridos apos a suspensao (13 dias do vencimento), o contrato sera cancelado, com aviso previo de 48 horas.</p>
<p>6.4. <strong>Reativacao:</strong> Ate 30 dias apos o cancelamento, mediante quitacao acrescida de multa de 2% e juros de mora de 1% ao mes, sem perda de dados.</p>
<p>6.5. <strong>Purge definitivo:</strong> Apos 30 dias sem reativacao, os dados serao eliminados, ressalvadas retencoes legais obrigatorias.</p>

<h3>7. RESCISAO VOLUNTARIA E EFEITOS</h3>
<p>7.1. A CONTRATANTE pode rescindir a qualquer tempo, cancelando a recorrencia no gateway ou solicitando via painel/e-mail contato@vivensi.app.br.</p>
<p>7.2. A CONTRATADA pode rescindir mediante aviso previo de 30 dias, exceto em descumprimento grave (art. 474 do CC), que enseja rescisao imediata.</p>
<p>7.3. Apos o cancelamento, a CONTRATANTE tera acesso ao modulo de autoatendimento LGPD ({{ config('app.url') }}/eu/dados) por 30 dias para exportar dados em formato aberto (JSON/CSV).</p>
<p>7.4. Sem multa rescisoria em planos mensais. Planos anuais com desconto: rescisao antecipada implica pagamento pro-rata do desconto concedido.</p>

<h3>8. NOTIFICACOES E COMUNICACOES</h3>
<p><strong>Da CONTRATADA para a CONTRATANTE:</strong> e-mail cadastrado, painel da plataforma, WhatsApp cadastrado (quando aplicavel).</p>
<p><strong>Da CONTRATANTE para a CONTRATADA:</strong> contato@vivensi.app.br (geral) | dpo@vivensi.app.br (LGPD/Encarregado).</p>
<p>E obrigacao da CONTRATANTE manter os dados de contato atualizados no painel; notificacoes nao recebidas por dados desatualizados nao perdem eficacia.</p>

<h3>9. LGPD E TRATAMENTO DE DADOS</h3>
<p>9.1. A CONTRATADA atua como <strong>operadora</strong> em nome da CONTRATANTE (<strong>controladora</strong>), nos termos da Lei 13.709/2018.</p>
<p>9.2. <strong>Sub-operadores autorizados:</strong> AWS (infraestrutura, sa-east-1), AbacatePay (pagamento), Meta Platforms Ireland Ltd. (WhatsApp Cloud API), DeepSeek AI (PLN), Together AI (imagens), Brevo (e-mail), Pusher Channels (websockets), Google (Gemini/reCAPTCHA). Lista atualizada em {{ config('app.url') }}/subprocessadores. Mudancas sao notificadas com 30 dias de antecedencia.</p>
<p>9.3. <strong>Transferencia internacional:</strong> A CONTRATANTE reconhece que alguns sub-operadores podem processar dados fora do Brasil, mediante clausulas contratuais padrao (arts. 33 a 36 da LGPD).</p>
<p>9.4. <strong>Direitos dos titulares:</strong> autoatendimento em {{ config('app.url') }}/eu/dados, com resposta em ate 15 dias.</p>
<p>9.5. <strong>Encarregado (DPO):</strong> dpo@vivensi.app.br.</p>
<p>9.6. Politica de privacidade completa: {{ config('app.url') }}/privacidade, que integra este contrato.</p>

<h3>10. CONFIDENCIALIDADE</h3>
<p>As partes mantem sigilo sobre informacoes confidenciais a que tiverem acesso em razao deste contrato, obrigacao vigente por 5 anos apos o encerramento. Excluem-se: informacoes de dominio publico, obtidas de terceiros sem sigilo, desenvolvidas independentemente ou exigidas por autoridade competente.</p>

<h3>11. RESPONSABILIDADE POR INCIDENTES DE SEGURANCA</h3>
<p>11.1. Cada parte responde, na forma da LGPD, pelos danos que der causa em incidentes de seguranca.</p>
<p>11.2. <strong>Culpa da CONTRATANTE</strong> (compartilhamento de credenciais, ausencia de 2FA quando disponivel, configuracao inadequada, exposicao voluntaria de dados, descumprimento da Clausula 13): responsabilidade exclusiva da CONTRATANTE.</p>
<p>11.3. <strong>Culpa da CONTRATADA</strong>: comunicacao a ANPD e titulares em ate 3 dias uteis da ciencia do incidente (art. 48 LGPD).</p>
<p>11.4. Divergencia sobre causa: pericia tecnica independente com custos repartidos igualmente.</p>

<h3>12. LIMITACAO DE RESPONSABILIDADE</h3>
<p>12.1. Salvo dolo ou culpa grave comprovada, a responsabilidade civil total da CONTRATADA fica limitada ao <strong>valor efetivamente pago pela CONTRATANTE nos 12 meses anteriores</strong> ao evento gerador do dano.</p>
<p>12.2. A CONTRATADA nao responde por: danos indiretos, lucros cessantes, perda de chance; prejuizos causados por sub-operadores, forca maior ou culpa da CONTRATANTE; perdas de dados atribuiveis a CONTRATANTE (exclusoes manuais, importacoes incorretas); danos a terceiros decorrentes do conteudo publicado pela CONTRATANTE.</p>
<p>12.3. A limitacao nao se aplica a violacoes da LGPD imputaveis exclusivamente a CONTRATADA (Clausula 11.3).</p>

<h3>13. OBRIGACOES DA CONTRATANTE</h3>
<ul>
    <li>Fornecer dados verdadeiros no cadastro e mante-los atualizados;</li>
    <li>Nao utilizar a plataforma para fins ilicitos, spam, difamacao, discriminacao ou pratica vedada pela legislacao;</li>
    <li>Cumprir LGPD, Marco Civil da Internet, CDC e legislacao setorial aplicavel;</li>
    <li>Obter consentimento previo e valido dos titulares, respeitando politicas de opt-in do WhatsApp Business e e-mail marketing (Brevo);</li>
    <li>Adotar praticas de seguranca no uso da plataforma, incluindo sigilo de credenciais e ativacao de 2FA quando disponivel;</li>
    <li>Efetuar pagamentos nos prazos, sob pena de suspensao e cancelamento (Clausula 6);</li>
    <li>Notificar imediatamente uso nao autorizado das credenciais ou incidente de seguranca;</li>
    <li>Nao realizar engenharia reversa, copia nao autorizada ou tentativa de acesso a areas restritas;</li>
    <li>Indenizar a CONTRATADA por prejuizos, incluindo honorarios advocaticios, decorrentes de acao de terceiros motivada pelo conteudo publicado ou uso indevido da plataforma.</li>
</ul>

<h3>14. OBRIGACOES DA CONTRATADA</h3>
<ul>
    <li>Disponibilizar a plataforma conforme SLA da Clausula 15;</li>
    <li>Prover suporte tecnico em dias uteis, das 09h as 18h (horario de Brasilia);</li>
    <li>Manter backups automatizados diarios com retencao minima de 7 dias;</li>
    <li>Adotar medidas tecnicas e organizacionais de seguranca, incluindo criptografia em transito (TLS 1.2+) e em repouso para dados sensiveis;</li>
    <li>Notificar incidentes de seguranca (Clausulas 9 e 11);</li>
    <li>Publicar, com no minimo 48 horas de antecedencia, aviso sobre janelas de manutencao programada.</li>
</ul>

<h3>15. NIVEL DE SERVICO (SLA)</h3>
<p>15.1. <strong>Meta:</strong> 99,0% mensal, apurada em janelas de 5 minutos ao final de cada mes calendario.</p>
<p>15.2. <strong>Exclusoes:</strong> manutencao programada notificada; indisponibilidades causadas por sub-operadores; forca maior; culpa da CONTRATANTE; ataques ciberneticos de terceiros sem culpa da CONTRATADA.</p>
<p>15.3. <strong>Credito na proxima fatura</strong> por descumprimento (deduzidas exclusoes):</p>
<ul>
    <li>5% se disponibilidade entre 98,0% e 98,9%;</li>
    <li>10% se entre 95,0% e 97,9%;</li>
    <li>25% se abaixo de 95,0%.</li>
</ul>
<p>15.4. O credito e o unico e exclusivo remedio por indisponibilidade, salvo descumprimento por 3 meses consecutivos, hipotese em que cabe rescisao sem onus.</p>

<h3>16. FORCA MAIOR E CASO FORTUITO</h3>
<p>Nenhuma das partes responde por descumprimento decorrente de forca maior ou caso fortuito, incluindo pandemias, calamidades publicas, guerras, atos governamentais, indisponibilidade prolongada de provedores essenciais (AWS, gateways, Meta), ataques ciberneticos de larga escala. Notificacao em 48 horas do evento; se perdurar por mais de 30 dias, qualquer parte pode rescindir sem onus.</p>

<h3>17. PROPRIEDADE INTELECTUAL</h3>
<p>17.1. <strong>Plataforma Vivensi</strong> (codigo, layout, marca, logotipo, documentacao) e de propriedade exclusiva da CONTRATADA.</p>
<p>17.2. <strong>Dados da CONTRATANTE</strong> permanecem de sua propriedade; a CONTRATADA obtem apenas licenca limitada, nao-exclusiva e revogavel para processa-los conforme necessario ao servico.</p>
<p>17.3. <strong>Sugestoes e melhorias:</strong> nao geram obrigacao de implementacao nem remuneracao. Caso implementadas, a CONTRATANTE concede licenca perpetua, irrevogavel, nao-exclusiva e gratuita a CONTRATADA, sem cessao de direitos nem obrigacao de mencao de autoria. Ressalva-se o direito da CONTRATANTE de uso interno das sugestoes.</p>

<h3>18. CESSAO DO CONTRATO</h3>
<p>A CONTRATADA pode ceder o contrato em caso de fusao, aquisicao, cisao ou reorganizacao societaria, com comunicacao de 30 dias. A CONTRATANTE nao pode ceder sem consentimento previo por escrito.</p>

<h3>19. DISPOSICOES GERAIS</h3>
<p>19.1. Este contrato pode ser atualizado com aviso previo de 30 dias em canais oficiais; continuidade do uso implica aceite. Discordancia permite rescisao sem onus ate a vigencia da nova versao.</p>
<p>19.2. Aceite eletronico e valido nos termos da MP 2.200-2/2001 (art. 10, §2) e do art. 219 do CC, dispensando assinatura manuscrita.</p>
<p>19.3. Sao registrados data, hora, IP, user-agent e hash HMAC-SHA256 do documento como prova da manifestacao de vontade.</p>
<p>19.4. Nulidade de qualquer clausula nao afeta as demais.</p>
<p>19.5. Tolerancia a descumprimento nao constitui novacao ou renuncia.</p>
<p>19.6. Contrato regido pelas leis brasileiras.</p>

<h3>20. FORO</h3>
@if($tipoDoc === 'CNPJ')
    <p>Fica eleito o foro da comarca de <strong>Araraquara/SP</strong> para dirimir quaisquer questoes oriundas deste contrato, com renuncia expressa a qualquer outro por mais privilegiado que seja.</p>
@else
    <p>Nos termos do art. 101, I do Codigo de Defesa do Consumidor, prevalece o foro do <strong>domicilio da CONTRATANTE</strong>, sendo facultado a esta optar pelo foro de Araraquara/SP.</p>
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
