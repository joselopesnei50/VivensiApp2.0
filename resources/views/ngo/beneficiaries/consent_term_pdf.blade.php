<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Termo de Consentimento – LGPD</title>
    <style>
        @page { margin: 28px 34px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #0f172a; font-size: 10.5px; line-height: 1.45; }
        h1 { margin: 0 0 2px 0; font-size: 14px; text-align: center; }
        h2 { margin: 12px 0 4px 0; font-size: 11px; text-transform: uppercase; color: #334155; }
        p { margin: 0 0 6px 0; text-align: justify; }
        ul { margin: 0 0 6px 0; padding-left: 16px; }
        li { margin-bottom: 2px; }
        .center { text-align: center; }
        .muted { color: #64748b; }
        .box { border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 10px; margin-top: 8px; }
        .field { border-bottom: 1px solid #94a3b8; display: inline-block; min-width: 180px; }
        .check { display: inline-block; width: 10px; height: 10px; border: 1px solid #334155; margin-right: 5px; vertical-align: -1px; }
        .sign td { padding-top: 34px; width: 50%; vertical-align: top; }
        .line { border-top: 1px solid #334155; padding-top: 3px; margin-right: 18px; font-size: 10px; }
    </style>
</head>
<body>
    <h1>TERMO DE CONSENTIMENTO PARA TRATAMENTO DE DADOS PESSOAIS</h1>
    <div class="center muted">Lei Geral de Proteção de Dados Pessoais – Lei nº 13.709/2018 (LGPD)</div>

    <div class="box">
        <strong>Organização (controladora dos dados):</strong> {{ $orgName }}
        @if($orgDocument) · CNPJ {{ $orgDocument }} @endif
        <br>
        <strong>Titular dos dados:</strong> {{ $beneficiary->name }}
        @if($beneficiary->maskedDocument()) · {{ $beneficiary->maskedDocument() }} @endif
        @if($beneficiary->birth_date) · nascimento {{ $beneficiary->birth_date->format('d/m/Y') }} @endif
        @if($minor)
            <br><strong>Responsável legal</strong> (obrigatório para menores de 18 anos – LGPD, art. 14):
            <br>Nome: <span class="field" style="min-width: 260px;">&nbsp;</span> CPF: <span class="field" style="min-width: 120px;">&nbsp;</span> Parentesco: <span class="field" style="min-width: 90px;">&nbsp;</span>
        @endif
    </div>

    <h2>1. Para que usamos seus dados</h2>
    <ul>
        <li>fazer o cadastro e o acompanhamento social da pessoa e da família;</li>
        <li>registrar atendimentos e a participação em projetos, oficinas e turmas (incluindo a frequência);</li>
        <li>entrar em contato por telefone, mensagem ou WhatsApp sobre atividades e atendimentos;</li>
        <li>prestar contas a financiadores, parceiros e órgãos públicos — sempre em números gerais, sem identificar ninguém, salvo quando a lei exigir.</li>
    </ul>

    <h2>2. Quais dados</h2>
    <p>Nome, documentos (CPF/NIS), data de nascimento, contato, endereço, composição familiar, escolaridade e o registro dos atendimentos.
        Também podem ser tratados <strong>dados sensíveis</strong> (LGPD, art. 11), como cor/raça e informações de saúde, somente quando necessários ao atendimento.</p>

    <h2>3. Com quem compartilhamos</h2>
    <p>Os dados não são vendidos nem cedidos para fins comerciais. Podem ser compartilhados apenas quando necessário para a proteção e o atendimento
        do titular, com a rede de proteção e assistência (por exemplo CRAS, CREAS, Conselho Tutelar, saúde e educação), ou quando houver obrigação legal ou determinação de autoridade.</p>

    <h2>4. Por quanto tempo guardamos</h2>
    <p>Enquanto durar o acompanhamento e, depois disso, pelo prazo exigido por lei ou pela prestação de contas dos projetos. Depois os dados são eliminados ou mantidos apenas de forma anônima.</p>

    <h2>5. Seus direitos (LGPD, art. 18)</h2>
    <p>A qualquer momento você pode pedir acesso aos seus dados, correção, informação sobre com quem foram compartilhados, eliminação
        e <strong>revogar este consentimento</strong>, sem custo. A revogação não afeta o que já foi feito antes dela nem os dados que a organização
        precise guardar por obrigação legal.
        @if($contactEmail || $contactPhone)
            Contato: {{ $contactEmail }}@if($contactEmail && $contactPhone) · @endif{{ $contactPhone }}.
        @else
            Contato: <span class="field">&nbsp;</span>.
        @endif
    </p>

    <h2>6. Consentimento</h2>
    <p>Declaro que li (ou que este termo foi lido para mim), que entendi as informações acima e que
        <strong>autorizo</strong> o tratamento dos dados pessoais{{ $minor ? ' da criança/adolescente sob minha responsabilidade' : '' }} para as finalidades descritas.</p>

    <h2>7. Uso de imagem e voz (opcional)</h2>
    <p>
        <span class="check"></span> <strong>Autorizo</strong>
        &nbsp;&nbsp;&nbsp;
        <span class="check"></span> <strong>Não autorizo</strong>
        &nbsp;o uso de fotos, vídeos e voz{{ $minor ? ' da criança/adolescente' : '' }} em materiais de divulgação e prestação de contas da organização
        (site, redes sociais, relatórios), sem fins comerciais. Esta autorização é independente do consentimento acima e também pode ser revogada.
    </p>

    <p style="margin-top: 14px;">{{ $orgCity ?: '______________________' }}, ______ de ____________________ de ________.</p>

    <table class="sign" style="width:100%; border-collapse: collapse;">
        <tr>
            <td><div class="line">{{ $minor ? 'Responsável legal' : 'Titular' }} (assinatura)</div></td>
            <td><div class="line">Técnico(a) responsável pelo atendimento</div></td>
        </tr>
    </table>

    <div class="muted" style="margin-top: 16px; font-size: 8.5px;">
        Modelo de termo gerado pela Vivensi (versão {{ $version }}). Recomenda-se a revisão pela assessoria jurídica da organização.
        Após a assinatura, anexe a cópia digitalizada em “Documentos” do cadastro e registre a assinatura no sistema.
    </div>
</body>
</html>
