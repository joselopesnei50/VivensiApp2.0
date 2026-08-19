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
@endphp

<h2 style="text-align:center; margin:0 0 6px 0;">CONTRATO DE ADESAO — VIVENSI SAAS</h2>
<p style="text-align:center; margin:0 0 24px 0; color:#64748b;">Termo de prestacao de servicos por meio da plataforma Vivensi</p>

<h3>1. PARTES</h3>
<p><strong>CONTRATANTE:</strong> {{ $razaoSocial }}, inscrita no CNPJ/CPF sob n {{ $cnpj }}, com sede em {{ $enderecoCompleto }}, doravante denominada CONTRATANTE.</p>
<p><strong>CONTRATADA:</strong> NC5 HUB DIGITAL LTDA, inscrita no CNPJ 00.000.000/0001-00, operadora da plataforma Vivensi ({{ config('app.url') }}), doravante denominada CONTRATADA.</p>

<h3>2. OBJETO</h3>
<p>Contratacao dos servicos SaaS Vivensi para gestao integrada de organizacoes, incluindo modulos administrativos, financeiros, WhatsApp, marketing, LGPD e demais funcionalidades disponiveis no plano contratado.</p>

<h3>3. PLANO E VALORES</h3>
<p><strong>Plano contratado:</strong> {{ $planoNome }}<br>
<strong>Valor mensal:</strong> R$ {{ $valor }}<br>
<strong>Modalidade:</strong> {{ $modalidade }}</p>

@if(!$isCourtesy)
    <p>A cobranca sera realizada por meio do gateway AbacatePay via PIX. A ativacao dos servicos ocorrera apos a confirmacao do primeiro pagamento.</p>
@else
    <p>Esta adesao esta na modalidade CORTESIA. A CONTRATANTE nao sera cobrada durante o periodo de cortesia. A CONTRATADA reserva-se o direito de encerrar a cortesia mediante aviso previo de 30 (trinta) dias.</p>
@endif

<h3>4. VIGENCIA</h3>
<p>Este contrato vigora por prazo indeterminado a partir da data de assinatura, podendo ser rescindido por qualquer das partes mediante aviso previo de 30 (trinta) dias.</p>

<h3>5. LGPD E TRATAMENTO DE DADOS</h3>
<p>A CONTRATADA atua como operadora de dados pessoais em nome da CONTRATANTE, conforme Lei 13.709/2018. As responsabilidades de controladoria, bases legais e finalidades sao da CONTRATANTE. Politica de privacidade completa disponivel em {{ config('app.url') }}/privacidade.</p>

<h3>6. OBRIGACOES DA CONTRATANTE</h3>
<ul>
    <li>Fornecer dados verdadeiros no cadastro e mante-los atualizados;</li>
    <li>Nao utilizar a plataforma para fins ilicitos, spam ou abuso de terceiros;</li>
    <li>Cumprir a legislacao aplicavel, especialmente LGPD, Marco Civil, CDC e legislacao do Terceiro Setor quando cabivel;</li>
    <li>Efetuar pagamentos nos prazos acordados (quando aplicavel).</li>
</ul>

<h3>7. OBRIGACOES DA CONTRATADA</h3>
<ul>
    <li>Disponibilizar a plataforma com disponibilidade mensal alvo de 99%;</li>
    <li>Prover suporte tecnico em horario comercial;</li>
    <li>Manter backups automatizados e adotar medidas razoaveis de seguranca;</li>
    <li>Notificar incidentes de seguranca relevantes conforme LGPD.</li>
</ul>

<h3>8. FORO</h3>
<p>Fica eleito o foro da comarca de Sao Paulo/SP para dirimir quaisquer questoes oriundas deste contrato.</p>

<p style="margin-top: 32px;"><strong>Data:</strong> {{ $hoje }}</p>
<p><strong>Signatario:</strong> {{ $contract->signer_name ?? '' }}
    @if($contract->signer_cpf) — CPF {{ $contract->signer_cpf }}@endif
</p>
