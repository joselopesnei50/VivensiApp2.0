@php
    // Data por extenso em PT-BR sem depender de locale global (config app.locale='en').
    $meses = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
    ];
    $issued = $certificate->issued_at;
    $dataExtenso = $issued->day . ' de ' . $meses[(int) $issued->month] . ' de ' . $issued->year;

    // Formata código em blocos de 4 para leitura humana: XXXX-XXXX-XXXX-XXXX.
    $codeFormatted = trim(chunk_split($certificate->code, 4, '-'), '-');

    // Iniciais para monograma central do selo (fallback caso logo não renderize).
    $studentName = trim($certificate->user->name ?? 'Aluno');
    $courseTitle = trim($certificate->course->title ?? 'Curso');
@endphp
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificado — {{ $courseTitle }}</title>
    <style>
        @page { margin: 0; size: A4 landscape; }
        * { box-sizing: border-box; }

        html, body {
            margin: 0; padding: 0;
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #0f172a;
            background: #ffffff;
        }

        /* ─── Página + moldura dupla ────────────────────────────────────── */
        .page {
            position: relative;
            width: 100%;
            height: 100vh;
            padding: 22px;
            background: #ffffff;
        }
        .frame-outer {
            position: relative;
            width: 100%;
            height: 100%;
            border: 3px double #1e1b4b;
            padding: 10px;
        }
        .frame-inner {
            position: relative;
            width: 100%;
            height: 100%;
            border: 1px solid #c7d2fe;
            padding: 30px 54px 30px 78px;
        }

        /* Faixa vertical fina institucional (identidade Vivensi) */
        .side-bar {
            position: absolute;
            top: 13px; bottom: 13px; left: 13px;
            width: 8px;
            background: #4338ca;
        }
        .side-bar::after {
            content: "";
            position: absolute;
            top: 0; bottom: 0; left: 8px;
            width: 2px;
            background: #a5b4fc;
        }

        /* Cantos decorativos discretos (linhas em L) */
        .corner {
            position: absolute;
            width: 44px;
            height: 44px;
            border-color: #4338ca;
        }
        .corner.tl { top: 18px;    left: 22px;   border-top: 2px solid #4338ca; border-left: 2px solid #4338ca; }
        .corner.tr { top: 18px;    right: 18px;  border-top: 2px solid #4338ca; border-right: 2px solid #4338ca; }
        .corner.bl { bottom: 18px; left: 22px;   border-bottom: 2px solid #4338ca; border-left: 2px solid #4338ca; }
        .corner.br { bottom: 18px; right: 18px;  border-bottom: 2px solid #4338ca; border-right: 2px solid #4338ca; }

        /* ─── Cabeçalho: logo + tagline ────────────────────────────────── */
        .header { width: 100%; margin-bottom: 6px; }
        .header table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: middle; }
        .logo-cell { width: 60%; }
        .logo-cell img { height: 42px; width: auto; }
        .logo-fallback {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 22px; font-weight: bold;
            color: #1e1b4b; letter-spacing: 6px;
        }
        .tag-cell {
            text-align: right;
            font-size: 9px;
            color: #4338ca;
            letter-spacing: 4px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .tag-cell .tag-sub {
            display: block;
            color: #64748b;
            font-size: 8px;
            letter-spacing: 2px;
            font-weight: normal;
            margin-top: 3px;
        }

        /* Separador horizontal ornamentado */
        .rule {
            margin: 14px auto 22px;
            width: 62%;
            text-align: center;
            font-size: 0;
        }
        .rule .line {
            display: inline-block;
            width: 42%;
            height: 1px;
            background: #cbd5e1;
            vertical-align: middle;
        }
        .rule .diamond {
            display: inline-block;
            width: 10px; height: 10px;
            background: #4338ca;
            transform: rotate(45deg);
            margin: 0 12px;
            vertical-align: middle;
        }

        /* ─── Corpo ────────────────────────────────────────────────────── */
        .body { text-align: center; }

        .kicker {
            font-size: 10px;
            color: #6366f1;
            letter-spacing: 6px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .title {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 46px;
            font-weight: bold;
            color: #1e1b4b;
            margin: 4px 0 0;
            letter-spacing: 12px;
            text-transform: uppercase;
        }
        .subtitle {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-style: italic;
            font-size: 15px;
            color: #64748b;
            margin-top: 4px;
            letter-spacing: 2px;
        }

        .intro {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-style: italic;
            font-size: 14px;
            color: #475569;
            margin: 22px 0 8px;
        }

        .student-name {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 40px;
            font-weight: bold;
            color: #4338ca;
            margin: 4px 0 0;
            line-height: 1.1;
        }
        .name-rule {
            margin: 10px auto 18px;
            width: 55%;
            height: 1px;
            background: #cbd5e1;
        }

        .conclusion {
            font-size: 13px;
            color: #475569;
            margin-bottom: 6px;
        }
        .course-title {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 22px;
            font-weight: bold;
            color: #0f172a;
            margin: 4px auto 10px;
            padding: 0 40px;
            line-height: 1.3;
        }

        .meta-line {
            font-size: 11px;
            color: #64748b;
            margin-top: 6px;
            letter-spacing: 0.5px;
        }
        .meta-line strong { color: #1e1b4b; font-weight: 600; }

        /* ─── Selo circular ────────────────────────────────────────────── */
        .seal {
            position: absolute;
            right: 60px;
            top: 130px;
            width: 108px;
            height: 108px;
            border: 2px solid #4338ca;
            border-radius: 54px;
            text-align: center;
            background: #ffffff;
            z-index: 3;
        }
        .seal-ring {
            position: absolute;
            top: 6px; left: 6px;
            width: 96px; height: 96px;
            border: 1px solid #c7d2fe;
            border-radius: 48px;
            padding-top: 14px;
        }
        .seal-mono {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 30px;
            font-weight: bold;
            color: #4338ca;
            line-height: 1;
            margin: 0;
        }
        .seal-hr {
            width: 30px;
            height: 1px;
            background: #4338ca;
            margin: 6px auto;
        }
        .seal-txt1 {
            font-size: 7.5px;
            font-weight: bold;
            color: #1e1b4b;
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }
        .seal-txt2 {
            font-size: 8.5px;
            font-weight: bold;
            color: #4338ca;
            letter-spacing: 2px;
            margin-top: 2px;
        }

        /* ─── Assinaturas ─────────────────────────────────────────────── */
        .signatures {
            position: absolute;
            bottom: 74px;
            left: 78px;
            right: 44px;
        }
        .signatures table { width: 100%; border-collapse: collapse; }
        .sig-cell {
            width: 50%;
            text-align: center;
            padding: 0 40px;
            vertical-align: bottom;
        }
        .sig-line {
            width: 80%;
            margin: 0 auto 6px;
            border-top: 1px solid #1e293b;
        }
        .sig-name {
            font-size: 12px;
            font-weight: bold;
            color: #1e1b4b;
            font-family: 'Georgia', 'Times New Roman', serif;
        }
        .sig-role {
            font-size: 9.5px;
            color: #64748b;
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* ─── Rodapé de validação ─────────────────────────────────────── */
        .footer {
            position: absolute;
            bottom: 22px;
            left: 78px;
            right: 44px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
        }
        .footer table { width: 100%; border-collapse: collapse; }
        .footer .f-left {
            text-align: left;
            font-size: 8.5px;
            color: #64748b;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .footer .f-left .code {
            display: block;
            margin-top: 3px;
            font-family: 'Courier New', 'Courier', monospace;
            font-weight: bold;
            color: #4338ca;
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: none;
        }
        .footer .f-right {
            text-align: right;
            font-size: 8.5px;
            color: #64748b;
            letter-spacing: 0.5px;
            vertical-align: bottom;
        }
        .footer .f-right .url {
            display: block;
            color: #4338ca;
            font-weight: bold;
            font-size: 10px;
            margin-top: 3px;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

<div class="page">
    <div class="frame-outer">
        <div class="frame-inner">

            <div class="side-bar"></div>
            <div class="corner tl"></div>
            <div class="corner tr"></div>
            <div class="corner bl"></div>
            <div class="corner br"></div>

            {{-- ─── Cabeçalho ─────────────────────────────────────────── --}}
            <div class="header">
                <table>
                    <tr>
                        <td class="logo-cell">
                            @if(function_exists('imagecreatetruecolor') && file_exists(public_path('img/novalogo.png')))
                                <img src="{{ public_path('img/novalogo.png') }}" alt="Vivensi">
                            @else
                                <span class="logo-fallback">VIVENSI</span>
                            @endif
                        </td>
                        <td class="tag-cell">
                            Vivensi Academy
                            <span class="tag-sub">Certificação Oficial de Conclusão</span>
                        </td>
                    </tr>
                </table>
            </div>

            {{-- ─── Selo circular ─────────────────────────────────────── --}}
            <div class="seal">
                <div class="seal-ring">
                    <div class="seal-mono">V</div>
                    <div class="seal-hr"></div>
                    <div class="seal-txt1">Vivensi Academy</div>
                    <div class="seal-txt2">{{ $issued->format('Y') }}</div>
                </div>
            </div>

            {{-- ─── Rule ornamentada ──────────────────────────────────── --}}
            <div class="rule">
                <span class="line"></span>
                <span class="diamond"></span>
                <span class="line"></span>
            </div>

            {{-- ─── Corpo ─────────────────────────────────────────────── --}}
            <div class="body">
                <div class="kicker">Vivensi Academy</div>
                <div class="title">Certificado</div>
                <div class="subtitle">de Conclusão de Curso</div>

                <div class="intro">Certificamos que</div>
                <div class="student-name">{{ $studentName }}</div>
                <div class="name-rule"></div>

                <div class="conclusion">
                    concluiu, com aproveitamento, todas as etapas do curso
                </div>
                <div class="course-title">&ldquo;{{ $courseTitle }}&rdquo;</div>

                <div class="meta-line">
                    oferecido pela <strong>Vivensi Academy</strong>
                    &nbsp;&middot;&nbsp;
                    Emitido em <strong>{{ $dataExtenso }}</strong>
                </div>
            </div>

            {{-- ─── Assinaturas ───────────────────────────────────────── --}}
            <div class="signatures">
                <table>
                    <tr>
                        <td class="sig-cell">
                            <div class="sig-line"></div>
                            <div class="sig-name">{{ $certificate->course->teacher_name ?? 'Direção Pedagógica' }}</div>
                            <div class="sig-role">Instrutor Responsável</div>
                        </td>
                        <td class="sig-cell">
                            <div class="sig-line"></div>
                            <div class="sig-name">Vivensi Academy</div>
                            <div class="sig-role">Certificação Institucional</div>
                        </td>
                    </tr>
                </table>
            </div>

            {{-- ─── Rodapé de validação ───────────────────────────────── --}}
            <div class="footer">
                <table>
                    <tr>
                        <td class="f-left">
                            Código de Validação
                            <span class="code">{{ $codeFormatted }}</span>
                        </td>
                        <td class="f-right">
                            Verifique a autenticidade em
                            <span class="url">vivensi.app.br/academy</span>
                        </td>
                    </tr>
                </table>
            </div>

        </div>
    </div>
</div>

</body>
</html>
