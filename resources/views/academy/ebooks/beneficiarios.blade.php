<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Guia Completo — Modulo Beneficiarios</title>
    <style>
        @page { margin: 60px 50px 70px 50px; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11.5px;
            color: #1e293b;
            line-height: 1.6;
        }
        h1, h2, h3, h4 { color: #1e1b4b; margin: 0 0 10px; }
        h1 { font-size: 32px; }
        h2 { font-size: 22px; padding-bottom: 8px; border-bottom: 3px solid #6366f1; margin-top: 28px; }
        h3 { font-size: 16px; color: #4338ca; margin-top: 18px; }
        h4 { font-size: 13px; color: #475569; margin-top: 14px; }
        p { margin: 0 0 10px; }
        ul, ol { margin: 0 0 12px 20px; padding: 0; }
        li { margin-bottom: 5px; }
        code { background: #f1f5f9; padding: 1px 6px; border-radius: 4px; font-size: 10.5px; color: #6366f1; font-family: 'Courier New', monospace; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 9.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4px; }
        .b-info    { background: #dbeafe; color: #1e40af; }
        .b-warn    { background: #fef3c7; color: #92400e; }
        .b-ok      { background: #dcfce7; color: #166534; }
        .b-lgpd    { background: #fce7f3; color: #9d174d; }

        .cover {
            page-break-after: always;
            height: 90vh;
            padding: 80px 40px;
            background: linear-gradient(135deg, #1e1b4b, #4f46e5);
            color: white;
            box-sizing: border-box;
            border-radius: 12px;
        }
        .cover-eyebrow { font-size: 11px; letter-spacing: 3px; opacity: 0.7; margin-bottom: 30px; }
        .cover-title { font-size: 46px; font-weight: bold; margin: 0 0 20px; line-height: 1.15; }
        .cover-sub { font-size: 18px; opacity: 0.9; max-width: 500px; line-height: 1.5; }
        .cover-footer { position: absolute; bottom: 80px; left: 40px; right: 40px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.2); }
        .cover-footer-brand { font-size: 20px; font-weight: bold; }
        .cover-footer-tag { font-size: 12px; opacity: 0.7; margin-top: 4px; }

        .toc { page-break-after: always; padding-top: 20px; }
        .toc h1 { color: #1e1b4b; margin-bottom: 30px; }
        .toc-item { display: block; padding: 8px 0; border-bottom: 1px dotted #cbd5e1; font-size: 13px; }
        .toc-num { color: #6366f1; font-weight: bold; width: 30px; display: inline-block; }

        .callout {
            padding: 12px 16px;
            border-radius: 8px;
            margin: 14px 0;
            border-left: 4px solid;
        }
        .callout-info { background: #eff6ff; border-color: #3b82f6; color: #1e40af; }
        .callout-warn { background: #fffbeb; border-color: #f59e0b; color: #92400e; }
        .callout-lgpd { background: #fdf2f8; border-color: #ec4899; color: #9d174d; }
        .callout-tip  { background: #f0fdf4; border-color: #10b981; color: #166534; }
        .callout-title { font-weight: bold; margin-bottom: 4px; text-transform: uppercase; font-size: 10.5px; letter-spacing: 0.5px; }

        .step {
            margin: 12px 0 12px 0;
            padding: 10px 14px;
            background: #f8fafc;
            border-radius: 6px;
            border-left: 3px solid #6366f1;
        }
        .step-num {
            display: inline-block;
            background: #6366f1;
            color: white;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            line-height: 22px;
            margin-right: 8px;
        }

        table.grid { width: 100%; border-collapse: collapse; margin: 12px 0; }
        table.grid th, table.grid td { padding: 8px 12px; text-align: left; border: 1px solid #e2e8f0; font-size: 10.5px; }
        table.grid th { background: #f1f5f9; color: #1e293b; font-weight: bold; }

        .path { display: inline-block; background: #1e293b; color: #a5b4fc; padding: 2px 8px; border-radius: 4px; font-family: 'Courier New', monospace; font-size: 10.5px; }
        .divider { height: 1px; background: #e2e8f0; margin: 20px 0; }
        .footer-note { font-size: 9.5px; color: #94a3b8; text-align: center; margin-top: 30px; padding-top: 10px; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>

{{-- ══════════════════════════════════════════════════════════════════════════
     CAPA
     ══════════════════════════════════════════════════════════════════════════ --}}
<div class="cover">
    <div class="cover-eyebrow">VIVENSI ACADEMY  •  MODULO ONG</div>
    <div class="cover-title">Guia Completo do<br>Modulo Beneficiarios</div>
    <div class="cover-sub">
        Aprenda a cadastrar, acompanhar e relatar dados de beneficiarios da sua organizacao com seguranca e conformidade LGPD.
    </div>
    <div class="cover-footer">
        <div class="cover-footer-brand">Vivensi</div>
        <div class="cover-footer-tag">Plataforma de gestao para o Terceiro Setor</div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     SUMARIO
     ══════════════════════════════════════════════════════════════════════════ --}}
<div class="toc">
    <h1>Sumario</h1>
    <span class="toc-item"><span class="toc-num">01.</span> Apresentacao do modulo</span>
    <span class="toc-item"><span class="toc-num">02.</span> Cadastro de beneficiario (CRUD basico)</span>
    <span class="toc-item"><span class="toc-num">03.</span> Importacao em lote via CSV</span>
    <span class="toc-item"><span class="toc-num">04.</span> Ficha individual do beneficiario</span>
    <span class="toc-item"><span class="toc-num">05.</span> Registro de atendimentos</span>
    <span class="toc-item"><span class="toc-num">06.</span> Composicao familiar</span>
    <span class="toc-item"><span class="toc-num">07.</span> Vinculo com projetos (ProjectPerson)</span>
    <span class="toc-item"><span class="toc-num">08.</span> Relatorio Anual + exports</span>
    <span class="toc-item"><span class="toc-num">09.</span> Dashboard Insights (KPIs)</span>
    <span class="toc-item"><span class="toc-num">10.</span> LGPD, criptografia e boas praticas</span>
    <span class="toc-item"><span class="toc-num">11.</span> Perguntas frequentes (FAQ)</span>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     01. APRESENTACAO
     ══════════════════════════════════════════════════════════════════════════ --}}
<h2>01. Apresentacao do modulo</h2>

<p>O modulo <strong>Beneficiarios</strong> e o coracao operacional da sua ONG dentro do Vivensi. E aqui que voce mantem o cadastro das pessoas atendidas, registra cada atendimento, acompanha a evolucao ao longo do tempo e gera os relatorios que os editais, doadores e conselhos exigem.</p>

<h3>O que o modulo resolve</h3>
<ul>
    <li><strong>Prova de impacto:</strong> voce consegue mostrar, em segundos, quantas pessoas foram atendidas, em que projetos, com que frequencia.</li>
    <li><strong>Conformidade LGPD:</strong> CPF e NIS ficam criptografados em disco (AES-256), com indice de busca cego (blind index). Nem o DBA consegue ler os documentos sem a chave da aplicacao.</li>
    <li><strong>Historico continuo:</strong> quando um beneficiario retorna anos depois, todo o historico esta la — atendimentos, familia, projetos que ja participou.</li>
    <li><strong>Base para relatorios:</strong> o Relatorio Anual (obrigatorio para muitas certificacoes) e gerado em PDF direto da base, sem planilhas paralelas.</li>
</ul>

<h3>Estrutura de dados (visao rapida)</h3>
<table class="grid">
    <tr><th>Entidade</th><th>Descricao</th></tr>
    <tr><td>Beneficiario</td><td>Pessoa cadastrada com dados pessoais (nome, CPF, NIS, endereco, faixa etaria, escolaridade, genero, raca/cor).</td></tr>
    <tr><td>Familiar</td><td>Membros da familia do beneficiario (parentesco, nome, data de nascimento).</td></tr>
    <tr><td>Atendimento</td><td>Registro de cada interacao (data, tipo, descricao, responsavel).</td></tr>
    <tr><td>ProjectPerson</td><td>Vinculo opt-in do beneficiario com um projeto (matricula em turma, participacao em oficina).</td></tr>
</table>

<div class="callout callout-info">
    <div class="callout-title">Onde acessar</div>
    Menu lateral do painel Terceiro Setor > <span class="path">/ngo/beneficiaries</span>. Todos os usuarios com role <code>ngo</code> ou <code>manager</code> tem acesso.
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     02. CADASTRO
     ══════════════════════════════════════════════════════════════════════════ --}}
<h2>02. Cadastro de beneficiario</h2>

<p>O cadastro pode ser feito de duas formas: <strong>manualmente</strong> (para 1 pessoa por vez) ou <strong>em lote via CSV</strong> (para migrar uma base existente — coberto na secao 03).</p>

<h3>Passo a passo — cadastro manual</h3>

<div class="step"><span class="step-num">1</span><strong>Abra a lista de beneficiarios</strong><br>Menu lateral > "Beneficiarios" (ou acesse <span class="path">/ngo/beneficiaries</span>).</div>

<div class="step"><span class="step-num">2</span><strong>Clique em "Novo beneficiario"</strong><br>Botao no canto superior direito da lista. Vai abrir o formulario de cadastro.</div>

<div class="step"><span class="step-num">3</span><strong>Preencha os dados pessoais</strong><br>Campos obrigatorios: <code>nome</code>, <code>data de nascimento</code>. Campos opcionais mas altamente recomendados: <code>CPF</code>, <code>NIS</code> (Numero de Identificacao Social), <code>telefone</code>.</div>

<div class="step"><span class="step-num">4</span><strong>Complete o perfil social</strong><br>Selecione <code>genero</code> (5 opcoes), <code>raca/cor</code>, <code>escolaridade</code> (8 niveis do fundamental incompleto ao pos-graduacao). Esses dados alimentam os relatorios de politicas afirmativas.</div>

<div class="step"><span class="step-num">5</span><strong>Endereco estruturado</strong><br>Preencha CEP, rua, numero, complemento, bairro, cidade, estado. Se possivel, informe latitude/longitude (util para mapas de calor territorial).</div>

<div class="step"><span class="step-num">6</span><strong>Defina o status</strong><br><code>ativo</code> (padrao — pessoa em atendimento), <code>inativo</code> (deixou o programa), <code>arquivado</code> (historico, nao aparece nas listas padrao).</div>

<div class="step"><span class="step-num">7</span><strong>Clique em "Salvar"</strong><br>O sistema valida CPF (11 digitos), verifica duplicatas pelo blind index e persiste. Se ja existir um beneficiario com o mesmo CPF, voce recebe um aviso.</div>

<div class="callout callout-tip">
    <div class="callout-title">Dica</div>
    Para agilizar, cadastre primeiro os dados obrigatorios e complete o perfil (endereco, escolaridade) na ficha depois. O sistema aceita cadastro parcial.
</div>

<h3>Faixas etarias automaticas</h3>
<p>Voce nao precisa informar a "faixa etaria" — o sistema calcula automaticamente a partir da data de nascimento, seguindo o padrao:</p>

<table class="grid">
    <tr><th>Faixa</th><th>Idade</th></tr>
    <tr><td>Infantil</td><td>0 a 6 anos</td></tr>
    <tr><td>Crianca</td><td>7 a 14 anos</td></tr>
    <tr><td>Adolescente</td><td>15 a 17 anos</td></tr>
    <tr><td>Jovem</td><td>18 a 29 anos</td></tr>
    <tr><td>Adulto</td><td>30 a 59 anos</td></tr>
    <tr><td>Idoso</td><td>60 anos ou mais</td></tr>
</table>

{{-- ══════════════════════════════════════════════════════════════════════════
     03. IMPORT CSV
     ══════════════════════════════════════════════════════════════════════════ --}}
<h2>03. Importacao em lote via CSV</h2>

<p>Se sua ONG ja tem uma base em planilha (Excel, Google Sheets), voce pode migrar tudo de uma vez usando a importacao CSV. O sistema aceita 16 colunas normalizadas, ignora BOM (encoding), e detecta duplicatas antes de gravar.</p>

<h3>Passo a passo</h3>

<div class="step"><span class="step-num">1</span><strong>Baixe o modelo</strong><br>Na lista de beneficiarios, clique em "Importar CSV" > "Baixar modelo". Voce recebe um arquivo <code>modelo-beneficiarios.csv</code> com o cabecalho correto.</div>

<div class="step"><span class="step-num">2</span><strong>Preencha o modelo</strong><br>Abra no Excel/Sheets, preencha uma linha por beneficiario. Nao remova nem renomeie colunas. Salve como CSV (delimitado por virgula, UTF-8).</div>

<div class="step"><span class="step-num">3</span><strong>Faca o upload</strong><br>Volte na tela de importacao e clique em "Selecionar arquivo". Escolha o CSV preenchido.</div>

<div class="step"><span class="step-num">4</span><strong>Confirme os dados</strong><br>O sistema mostra um preview das primeiras 10 linhas para voce validar. Se estiver ok, clique em "Importar".</div>

<div class="step"><span class="step-num">5</span><strong>Revise o resultado</strong><br>O sistema retorna: total lido, total importado, duplicatas ignoradas (mesmo CPF ja existente), erros (linha X campo Y invalido).</div>

<div class="callout callout-warn">
    <div class="callout-title">Atencao</div>
    A importacao respeita o tenant — os beneficiarios importados ficam vinculados EXCLUSIVAMENTE a sua organizacao. Nao ha risco de dados vazarem para outras ONGs na plataforma.
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     04. FICHA
     ══════════════════════════════════════════════════════════════════════════ --}}
<h2>04. Ficha individual do beneficiario</h2>

<p>Ao clicar em qualquer beneficiario na lista, voce abre a ficha completa — a visao 360 graus da pessoa. E nesse tela que a equipe registra atendimentos, adiciona familiares, imprime documentos e acompanha o historico.</p>

<h3>O que voce ve na ficha</h3>
<ul>
    <li><strong>Cabecalho:</strong> nome, foto (se anexada), status, idade calculada, faixa etaria automatica.</li>
    <li><strong>Dados pessoais:</strong> CPF/NIS mascarados (mostram so os ultimos digitos), telefone, endereco completo, dados sociais (genero, raca/cor, escolaridade).</li>
    <li><strong>Composicao familiar:</strong> lista de familiares com botao para adicionar/remover.</li>
    <li><strong>Timeline de atendimentos:</strong> lista cronologica dos ultimos atendimentos com data, tipo e responsavel.</li>
    <li><strong>Projetos vinculados:</strong> em quais programas o beneficiario esta matriculado (opt-in via ProjectPerson).</li>
    <li><strong>Botoes de acao:</strong> Editar, Imprimir ficha, Baixar PDF (termo), Exportar historico, Registrar atendimento, Adicionar familiar.</li>
</ul>

<h3>Imprimir e exportar</h3>
<ul>
    <li><strong>Imprimir:</strong> gera uma versao formatada para impressao direto no navegador (<span class="path">/ngo/beneficiaries/{id}/print</span>).</li>
    <li><strong>PDF do termo:</strong> gera um termo de compromisso/consentimento em PDF com dados do beneficiario preenchidos.</li>
    <li><strong>Exportar historico:</strong> baixa CSV com todos os atendimentos daquela pessoa.</li>
</ul>

{{-- ══════════════════════════════════════════════════════════════════════════
     05. ATENDIMENTOS
     ══════════════════════════════════════════════════════════════════════════ --}}
<h2>05. Registro de atendimentos</h2>

<p>Cada interacao da equipe com o beneficiario deve virar um atendimento no sistema. E isso que sustenta o Relatorio Anual e prova o volume de acoes da ONG.</p>

<h3>Passo a passo — registrar atendimento</h3>

<div class="step"><span class="step-num">1</span><strong>Abra a ficha do beneficiario</strong><br>Lista de beneficiarios > clique no nome.</div>

<div class="step"><span class="step-num">2</span><strong>Clique em "Registrar atendimento"</strong><br>Botao na secao "Atendimentos" da ficha.</div>

<div class="step"><span class="step-num">3</span><strong>Preencha os campos</strong><br><code>data</code> (padrao hoje), <code>tipo</code> (visita domiciliar, orientacao, encaminhamento, entrega de cesta, etc — configuravel), <code>descricao</code> (texto livre com o que foi feito).</div>

<div class="step"><span class="step-num">4</span><strong>Salve</strong><br>O atendimento aparece imediatamente na timeline da ficha. O responsavel (voce) fica gravado automaticamente.</div>

<h3>Editar ou remover atendimento</h3>
<p>Na timeline, cada atendimento tem os icones de <code>editar</code> (lapis) e <code>excluir</code> (lixeira). A exclusao pede confirmacao — o registro e removido definitivamente e nao pode ser recuperado.</p>

<h3>Exportar atendimentos</h3>
<ul>
    <li><strong>De um beneficiario especifico:</strong> na ficha, botao "Exportar atendimentos" > CSV.</li>
    <li><strong>De todos os beneficiarios (bulk):</strong> em <span class="path">/ngo/beneficiaries/attendances/export</span>. Retorna CSV com todos os atendimentos do periodo escolhido, com filtros por tipo/responsavel.</li>
</ul>

<div class="callout callout-tip">
    <div class="callout-title">Boa pratica</div>
    Padronize os "tipos de atendimento" com a coordenacao antes de comecar. Exemplo: "Orientacao juridica", "Visita domiciliar", "Entrega de cesta basica", "Encaminhamento CRAS". Consistencia facilita os relatorios.
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     06. FAMILIA
     ══════════════════════════════════════════════════════════════════════════ --}}
<h2>06. Composicao familiar</h2>

<p>O modulo permite cadastrar os familiares do beneficiario sem precisar cria-los como beneficiarios separados. Util para contexto (quantas pessoas no domicilio, se tem crianca/idoso) e para relatorios de vulnerabilidade social.</p>

<h3>Passo a passo</h3>
<div class="step"><span class="step-num">1</span>Abra a ficha do beneficiario.</div>
<div class="step"><span class="step-num">2</span>Va na secao <strong>"Composicao familiar"</strong>.</div>
<div class="step"><span class="step-num">3</span>Clique em <strong>"Adicionar familiar"</strong>.</div>
<div class="step"><span class="step-num">4</span>Informe <code>nome</code>, <code>parentesco</code> (mae, pai, irmao, conjuge, filho, etc), <code>data de nascimento</code>.</div>
<div class="step"><span class="step-num">5</span>Salve. O familiar aparece na lista com a idade calculada.</div>

<p>Familiares podem ser removidos a qualquer momento pelo icone lixeira. A remocao e definitiva.</p>

<div class="callout callout-info">
    <div class="callout-title">Quando promover um familiar a beneficiario?</div>
    Se o familiar comeca a receber atendimentos formais da ONG, cadastre-o como beneficiario independente. A ligacao no cadastro familiar e apenas informativa.
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     07. PROJETOS
     ══════════════════════════════════════════════════════════════════════════ --}}
<h2>07. Vinculo com projetos</h2>

<p>Quando um beneficiario participa de um projeto (oficina, curso, turma), voce cria um vinculo chamado <strong>ProjectPerson</strong>. Isso permite: (a) mostrar quantas pessoas cada projeto atende, (b) gerar chamada e presenca por turma, (c) medir permanencia (quanto tempo em cada programa).</p>

<h3>Como vincular</h3>

<div class="step"><span class="step-num">1</span>Abra o projeto (menu Projetos > clique no nome).</div>
<div class="step"><span class="step-num">2</span>Va na aba <strong>"Pessoas & Contatos"</strong>.</div>
<div class="step"><span class="step-num">3</span>Clique em <strong>"Vincular beneficiario"</strong>.</div>
<div class="step"><span class="step-num">4</span>Busque pelo nome/CPF na caixa de pesquisa. O sistema completa automaticamente.</div>
<div class="step"><span class="step-num">5</span>Selecione e salve. O beneficiario passa a aparecer na lista do projeto e na aba "Projetos" da ficha dele.</div>

<div class="callout callout-lgpd">
    <div class="callout-title">LGPD — opt-in</div>
    O vinculo com projeto e opt-in: se o beneficiario recusar participar, nao vincule. Se depois quiser desvincular, use o botao "remover" na lista — o beneficiario permanece na base, apenas sai daquele projeto.
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     08. RELATORIO ANUAL
     ══════════════════════════════════════════════════════════════════════════ --}}
<h2>08. Relatorio Anual + exports</h2>

<p>O Relatorio Anual e uma das entregas mais importantes do modulo. E documento obrigatorio para CEBAS, MROSC, prestacao de contas ao conselho, editais publicos. O Vivensi gera esse relatorio automaticamente a partir dos atendimentos registrados.</p>

<h3>Como gerar</h3>

<div class="step"><span class="step-num">1</span>Menu Beneficiarios > <strong>"Relatorio Anual"</strong> (ou <span class="path">/ngo/beneficiaries/reports/annual</span>).</div>
<div class="step"><span class="step-num">2</span>Selecione o <strong>ano</strong> (padrao: ano corrente).</div>
<div class="step"><span class="step-num">3</span>Aplique filtros opcionais: <code>projeto</code>, <code>tipo de atendimento</code>, <code>responsavel</code>.</div>
<div class="step"><span class="step-num">4</span>Voce ve na tela: KPIs (total de atendimentos, top tipos, top responsaveis, top familias atendidas), grafico mensal.</div>
<div class="step"><span class="step-num">5</span>Baixe em PDF (<strong>"Exportar PDF"</strong>) para arquivar ou entregar como prestacao de contas.</div>

<h3>Formatos de CSV disponiveis</h3>
<table class="grid">
    <tr><th>Formato</th><th>Uso</th></tr>
    <tr><td>Detailed</td><td>Uma linha por atendimento (analise granular).</td></tr>
    <tr><td>Grouped</td><td>Agrupado por beneficiario com contagem.</td></tr>
    <tr><td>Grouped-simple</td><td>Versao enxuta (nome + total).</td></tr>
    <tr><td>Pivot-type</td><td>Cruzamento por tipo de atendimento.</td></tr>
    <tr><td>Pivot-user</td><td>Cruzamento por responsavel.</td></tr>
</table>

{{-- ══════════════════════════════════════════════════════════════════════════
     09. INSIGHTS
     ══════════════════════════════════════════════════════════════════════════ --}}
<h2>09. Dashboard Insights</h2>

<p>Enquanto o Relatorio Anual e retrospectivo, o dashboard <strong>Insights</strong> mostra o momento presente da operacao — o que aconteceu nos ultimos 90 dias, quem sao os beneficiarios mais atendidos, qual tipo de atendimento cresceu.</p>

<h3>O que voce encontra</h3>
<ul>
    <li><strong>KPI 90 dias:</strong> total de atendimentos, beneficiarios ativos, novos cadastros.</li>
    <li><strong>Serie mensal (12 meses):</strong> grafico de linha com a evolucao dos atendimentos mes a mes.</li>
    <li><strong>Top tipos de atendimento:</strong> ranking do que sua ONG mais fez.</li>
    <li><strong>Top responsaveis:</strong> quem esta atendendo mais.</li>
    <li><strong>Top familias:</strong> familias com maior demanda (util para casos de vulnerabilidade concentrada).</li>
</ul>

<p>Acesse em <span class="path">/ngo/beneficiaries/insights</span>. E util para reunioes de equipe e para identificar mudancas de perfil da demanda ao longo do ano.</p>

{{-- ══════════════════════════════════════════════════════════════════════════
     10. LGPD
     ══════════════════════════════════════════════════════════════════════════ --}}
<h2>10. LGPD, criptografia e boas praticas</h2>

<p>Beneficiarios sao dados pessoais sensiveis. O Vivensi trata isso com seriedade e implementa protecoes que voce nao precisa configurar — ja estao ativas por padrao.</p>

<h3>O que ja esta protegido</h3>
<ul>
    <li><strong>CPF e NIS criptografados em disco:</strong> AES-256-CBC. Se o banco de dados vazar, esses campos aparecem como texto embaralhado.</li>
    <li><strong>Blind index para busca:</strong> voce ainda pesquisa por CPF/NIS na barra de busca, porque geramos um hash-HMAC-SHA256 do valor pesquisado e comparamos com o hash armazenado — sem descriptografar os dados de todos.</li>
    <li><strong>Isolamento multi-tenant:</strong> beneficiarios da sua ONG so aparecem para usuarios da sua ONG. Uma organizacao <strong>NUNCA</strong> ve dados de outra.</li>
    <li><strong>Audit log automatico:</strong> toda alteracao em beneficiario e registrada no historico com quem fez, o que mudou, quando.</li>
    <li><strong>Codigo publico auditavel:</strong> em <span class="path">/eu/dados</span> qualquer titular pode exercer os direitos LGPD (art. 15 e 18).</li>
</ul>

<h3>Suas responsabilidades</h3>
<ol>
    <li>Colete apenas os dados necessarios (principio da minimizacao).</li>
    <li>Obtenha consentimento antes de cadastrar (verbal + registro em atendimento e valido).</li>
    <li>Nao compartilhe login com outros usuarios — cada pessoa tem a sua conta.</li>
    <li>Se um beneficiario pedir exclusao, use o "arquivar" (mantem historico para prestacao de contas) OU exclua definitivamente se ele desejar (respeitando o direito ao esquecimento).</li>
    <li>Revogue acesso de ex-funcionarios imediatamente (menu Equipe > desativar usuario).</li>
</ol>

<div class="callout callout-lgpd">
    <div class="callout-title">Menores de idade</div>
    Para beneficiarios com menos de 18 anos, o consentimento deve vir do responsavel legal. O sistema nao ocultara automaticamente esses dados, mas voce deve tratar com cuidado extra (evitar exportacoes desnecessarias, nao compartilhar em grupos de WhatsApp, etc).
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     11. FAQ
     ══════════════════════════════════════════════════════════════════════════ --}}
<h2>11. Perguntas frequentes</h2>

<h4>Posso cadastrar um beneficiario sem CPF?</h4>
<p>Sim. CPF e opcional. Muitos publicos atendidos (moradores de rua, criancas, populacao indigena) nao possuem documento. O sistema aceita.</p>

<h4>O que acontece se eu excluir um beneficiario que esta vinculado a um projeto?</h4>
<p>O vinculo (ProjectPerson) e automaticamente desassociado (beneficiary_id fica null), mas a linha do projeto permanece — o registro de que "alguem participou" nao se perde. O nome sim, porque o beneficiario foi excluido. Prefira usar "arquivar" em vez de excluir.</p>

<h4>Posso ver quem alterou os dados de um beneficiario?</h4>
<p>Sim. Toda mudanca gera audit log. A auditoria completa esta em <span class="path">/ngo/audit</span> (para roles com permissao).</p>

<h4>Como faco backup dos dados?</h4>
<p>O backup do banco e feito automaticamente pela plataforma Vivensi. Se quiser um backup <em>seu</em> em CSV, use a exportacao completa em Beneficiarios > Exportar CSV. Recomenda-se rodar mensalmente e guardar em local seguro.</p>

<h4>Ha limite de beneficiarios?</h4>
<p>Nao ha limite tecnico. O sistema esta preparado para ONGs de qualquer porte, desde grupos com 20 atendidos ate organizacoes com dezenas de milhares.</p>

<h4>Consigo cadastrar um beneficiario pelo celular?</h4>
<p>Sim. Todas as telas do modulo sao responsivas. O formulario de cadastro funciona bem em telas pequenas — util para cadastros em visitas domiciliares ou eventos externos.</p>

<div class="divider"></div>

<p style="text-align: center; color: #6366f1; font-weight: bold; font-size: 14px; margin-top: 30px;">
    Fim do guia — bom trabalho e obrigado por transformar vidas.
</p>

<div class="footer-note">
    Vivensi Academy • Guia Completo do Modulo Beneficiarios • Versao {{ now()->format('Y-m') }}<br>
    Documento gerado automaticamente. Para duvidas: suporte@vivensi.app.br
</div>

</body>
</html>
