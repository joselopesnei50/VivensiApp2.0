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

    $studentName = trim($certificate->user->name ?? 'Aluno');
    $courseTitle = trim($certificate->course->title ?? 'Curso');
    $teacherName = trim($certificate->course->teacher_name ?? 'Direção Pedagógica');
@endphp
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificado — {{ $courseTitle }}</title>
    <style>
        /* DomPDF: usar mm fixos, evitar vh/vw. Página A4 landscape = 297x210mm. */
        @page { margin: 0; size: 297mm 210mm; }

        html, body {
            margin: 0;
            padding: 0;
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #0f172a;
            background: #ffffff;
        }

        /* Container único de tamanho fixo. Todo posicionamento é absoluto
           relativo a ele — assim o conteúdo NUNCA transborda pra pág. 2. */
        .sheet {
            position: relative;
            width: 297mm;
            height: 210mm;
            overflow: hidden;
            background: #ffffff;
        }

        /* ─── Moldura dupla ─────────────────────────────────────────────── */
        .frame {
            position: absolute;
            top: 8mm; left: 8mm; right: 8mm; bottom: 8mm;
            border: 2.5pt double #1e1b4b;
        }
        .frame-inner {
            position: absolute;
            top: 3mm; left: 3mm; right: 3mm; bottom: 3mm;
            border: 0.5pt solid #c7d2fe;
        }

        /* Faixa vertical institucional (linha fina do lado esquerdo) */
        .side-bar {
            position: absolute;
            top: 12mm; bottom: 12mm; left: 6mm;
            width: 2.5mm;
            background: #4338ca;
        }
        .side-bar-hair {
            position: absolute;
            top: 12mm; bottom: 12mm; left: 9mm;
            width: 0.6mm;
            background: #a5b4fc;
        }

        /* Cantos decorativos em L (dentro da moldura interna) */
        .corner {
            position: absolute;
            width: 12mm;
            height: 12mm;
        }
        .corner.tl { top: 15mm; left: 15mm; border-top: 1pt solid #4338ca; border-left: 1pt solid #4338ca; }
        .corner.tr { top: 15mm; right: 15mm; border-top: 1pt solid #4338ca; border-right: 1pt solid #4338ca; }
        .corner.bl { bottom: 15mm; left: 15mm; border-bottom: 1pt solid #4338ca; border-left: 1pt solid #4338ca; }
        .corner.br { bottom: 15mm; right: 15mm; border-bottom: 1pt solid #4338ca; border-right: 1pt solid #4338ca; }

        /* ─── Cabeçalho ────────────────────────────────────────────────── */
        /* right=54mm reserva espaço p/ o selo circular no canto sup. dir. */
        .header {
            position: absolute;
            top: 22mm; left: 30mm; right: 54mm;
            height: 16mm;
        }
        .header table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: middle; }
        .logo-cell { width: 55%; }
        .logo-cell img { height: 14mm; width: auto; }
        .logo-fallback {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 18pt;
            font-weight: bold;
            color: #1e1b4b;
            letter-spacing: 6pt;
        }
        .tag-cell {
            text-align: right;
            font-size: 8pt;
            color: #4338ca;
            letter-spacing: 3pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .tag-cell .tag-sub {
            display: block;
            color: #64748b;
            font-size: 7pt;
            letter-spacing: 1.5pt;
            font-weight: normal;
            margin-top: 2pt;
        }

        /* ─── Rule ornamentada ─────────────────────────────────────────── */
        .rule {
            position: absolute;
            top: 46mm; left: 60mm; right: 60mm;
            text-align: center;
            font-size: 0;
        }
        .rule .line {
            display: inline-block;
            width: 45%;
            height: 0.5pt;
            background: #cbd5e1;
            vertical-align: middle;
        }
        .rule .diamond {
            display: inline-block;
            width: 3mm; height: 3mm;
            background: #4338ca;
            transform: rotate(45deg);
            margin: 0 4mm;
            vertical-align: middle;
        }

        /* ─── Corpo centralizado ───────────────────────────────────────── */
        .body {
            position: absolute;
            top: 52mm; left: 30mm; right: 30mm;
            text-align: center;
        }
        .kicker {
            font-size: 8pt;
            color: #6366f1;
            letter-spacing: 5pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }
        .title {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 34pt;
            font-weight: bold;
            color: #1e1b4b;
            margin: 3mm 0 0;
            letter-spacing: 10pt;
            text-transform: uppercase;
            line-height: 1;
        }
        .subtitle {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-style: italic;
            font-size: 12pt;
            color: #64748b;
            margin: 2mm 0 0;
            letter-spacing: 2pt;
        }
        .intro {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-style: italic;
            font-size: 11pt;
            color: #475569;
            margin: 8mm 0 2mm;
        }
        .student-name {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 30pt;
            font-weight: bold;
            color: #4338ca;
            margin: 1mm 0 0;
            line-height: 1.1;
        }
        .name-rule {
            width: 55%;
            height: 0.5pt;
            background: #cbd5e1;
            margin: 3mm auto 0;
        }
        .conclusion {
            font-size: 10pt;
            color: #475569;
            margin: 5mm 0 1mm;
        }
        .course-title {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 17pt;
            font-weight: bold;
            color: #0f172a;
            margin: 2mm 15mm 0;
            line-height: 1.25;
        }
        .meta-line {
            font-size: 9pt;
            color: #64748b;
            margin: 4mm 0 0;
        }
        .meta-line strong { color: #1e1b4b; font-weight: bold; }

        /* ─── Selo circular ────────────────────────────────────────────── */
        /* Alinhado verticalmente com o header p/ não colidir com o corpo
           que começa em top:52mm (seal termina em 22+26=48mm → 4mm folga). */
        .seal {
            position: absolute;
            top: 22mm;
            right: 22mm;
            width: 26mm;
            height: 26mm;
            border: 0.8pt solid #4338ca;
            border-radius: 13mm;
            background: #ffffff;
            text-align: center;
        }
        .seal-ring {
            position: absolute;
            top: 1.5mm; left: 1.5mm;
            width: 23mm; height: 23mm;
            border: 0.4pt solid #c7d2fe;
            border-radius: 11.5mm;
        }
        .seal-mono {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 20pt;
            font-weight: bold;
            color: #4338ca;
            margin: 4mm 0 0;
            line-height: 1;
        }
        .seal-hr {
            width: 8mm; height: 0.4pt;
            background: #4338ca;
            margin: 1.5mm auto;
        }
        .seal-txt1 {
            font-size: 5.5pt;
            font-weight: bold;
            color: #1e1b4b;
            letter-spacing: 1pt;
            text-transform: uppercase;
        }
        .seal-txt2 {
            font-size: 6.5pt;
            font-weight: bold;
            color: #4338ca;
            letter-spacing: 1.5pt;
            margin-top: 1mm;
        }

        /* ─── Assinaturas ──────────────────────────────────────────────── */
        .signatures {
            position: absolute;
            bottom: 30mm;
            left: 30mm;
            right: 22mm;
        }
        .signatures table { width: 100%; border-collapse: collapse; }
        .sig-cell {
            width: 50%;
            text-align: center;
            padding: 0 15mm;
        }
        .sig-line {
            width: 80%;
            margin: 0 auto 2mm;
            border-top: 0.6pt solid #1e293b;
        }
        .sig-name {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 10pt;
            font-weight: bold;
            color: #1e1b4b;
        }
        .sig-role {
            font-size: 7.5pt;
            color: #64748b;
            margin-top: 1mm;
            text-transform: uppercase;
            letter-spacing: 1.5pt;
        }

        /* ─── Rodapé de validação ─────────────────────────────────────── */
        .footer {
            position: absolute;
            bottom: 15mm;
            left: 30mm;
            right: 22mm;
            padding-top: 3mm;
            border-top: 0.4pt solid #e2e8f0;
        }
        .footer table { width: 100%; border-collapse: collapse; }
        .footer .f-left {
            text-align: left;
            font-size: 7pt;
            color: #64748b;
            letter-spacing: 0.5pt;
            text-transform: uppercase;
            font-weight: bold;
        }
        .footer .f-left .code {
            display: block;
            margin-top: 1mm;
            font-family: 'Courier', monospace;
            font-weight: bold;
            color: #4338ca;
            font-size: 9pt;
            letter-spacing: 1.5pt;
            text-transform: none;
        }
        .footer .f-right {
            text-align: right;
            font-size: 7pt;
            color: #64748b;
            letter-spacing: 0.5pt;
            vertical-align: bottom;
        }
        .footer .f-right .url {
            display: block;
            color: #4338ca;
            font-weight: bold;
            font-size: 8.5pt;
            margin-top: 1mm;
        }
    </style>
</head>
<body>

<div class="sheet">

    <div class="frame">
        <div class="frame-inner"></div>
    </div>

    <div class="side-bar"></div>
    <div class="side-bar-hair"></div>

    <div class="corner tl"></div>
    <div class="corner tr"></div>
    <div class="corner bl"></div>
    <div class="corner br"></div>

    {{-- Cabeçalho --}}
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

    {{-- Selo --}}
    <div class="seal">
        <div class="seal-ring">
            <div class="seal-mono">V</div>
            <div class="seal-hr"></div>
            <div class="seal-txt1">Vivensi Academy</div>
            <div class="seal-txt2">{{ $issued->format('Y') }}</div>
        </div>
    </div>

    {{-- Rule --}}
    <div class="rule">
        <span class="line"></span>
        <span class="diamond"></span>
        <span class="line"></span>
    </div>

    {{-- Corpo --}}
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

    {{-- Assinaturas --}}
    <div class="signatures">
        <table>
            <tr>
                <td class="sig-cell">
                    <div class="sig-line"></div>
                    <div class="sig-name">{{ $teacherName }}</div>
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

    {{-- Rodapé --}}
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

</body>
</html>
