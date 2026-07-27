<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificado — {{ $certificate->course->title }}</title>
    <style>
        @page { margin: 0; size: A4 landscape; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            color: #1e1b4b;
        }

        /* ─── Wrappers e moldura ────────────────────────────────────────── */
        .page {
            width: 100%;
            height: 100vh;
            padding: 26px;
            background: #f8fafc;
        }
        .outer {
            width: 100%;
            height: 100%;
            border: 2px solid #c7d2fe;
            background: #ffffff;
            position: relative;
        }
        .inner {
            position: absolute;
            top: 14px; left: 14px; right: 14px; bottom: 14px;
            border: 1px solid #e0e7ff;
            padding: 34px 60px 38px 60px;
        }

        /* Barra roxa no topo (identidade Vivensi) */
        .top-bar {
            position: absolute;
            top: 14px; left: 14px; right: 14px;
            height: 8px;
            background: #4f46e5;
        }

        /* Watermark VIVENSI de fundo */
        .watermark {
            position: absolute;
            top: 40%; left: 0; right: 0;
            text-align: center;
            font-size: 150px;
            color: #eef2ff;
            font-weight: bold;
            letter-spacing: 14px;
            z-index: 0;
            font-family: 'Georgia', 'Times New Roman', serif;
        }

        /* ─── Header: logo + tag ─────────────────────────────────────────── */
        .header {
            position: relative;
            z-index: 2;
            width: 100%;
            margin-bottom: 8px;
        }
        .header table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: middle; }
        .logo-cell { width: 260px; }
        .logo-cell img { height: 46px; width: auto; }
        .logo-fallback {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 28px;
            font-weight: bold;
            color: #4f46e5;
            letter-spacing: 4px;
        }
        .tag-cell {
            text-align: right;
            font-size: 10px;
            color: #6366f1;
            letter-spacing: 3px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .divider {
            height: 2px;
            background: #4f46e5;
            width: 60px;
            margin: 18px auto 22px auto;
            position: relative;
            z-index: 2;
        }

        /* ─── Corpo do certificado ───────────────────────────────────────── */
        .body {
            position: relative;
            z-index: 2;
            text-align: center;
        }
        .kicker {
            font-size: 11px;
            color: #6366f1;
            letter-spacing: 4px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .title {
            font-size: 52px;
            font-family: 'Georgia', 'Times New Roman', serif;
            color: #1e1b4b;
            font-weight: bold;
            margin: 6px 0 2px;
            letter-spacing: 2px;
        }
        .subtitle {
            font-size: 13px;
            color: #64748b;
            letter-spacing: 5px;
            text-transform: uppercase;
            margin-bottom: 26px;
        }
        .intro {
            font-size: 14px;
            color: #475569;
            margin-bottom: 8px;
            font-style: italic;
        }
        .student-name {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 42px;
            font-weight: bold;
            color: #4338ca;
            margin: 4px 0 6px;
            line-height: 1.15;
        }
        .name-underline {
            width: 60%;
            margin: 0 auto 20px;
            border-bottom: 1.5px solid #c7d2fe;
        }
        .conclusion {
            font-size: 14px;
            color: #475569;
            margin-bottom: 6px;
        }
        .course-title {
            font-size: 24px;
            color: #1e1b4b;
            font-weight: bold;
            margin: 4px 20px 8px;
            font-family: 'Georgia', 'Times New Roman', serif;
            line-height: 1.25;
        }
        .meta-line {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }

        /* ─── Selo circular ──────────────────────────────────────────────── */
        .seal {
            position: absolute;
            right: 70px;
            top: 130px;
            width: 110px;
            height: 110px;
            border: 3px solid #4f46e5;
            border-radius: 55px;
            text-align: center;
            padding-top: 22px;
            color: #4f46e5;
            z-index: 3;
            background: rgba(255,255,255,0.85);
        }
        .seal-star { font-size: 22px; line-height: 1; }
        .seal-line1 { font-size: 8.5px; font-weight: bold; letter-spacing: 1.5px; margin-top: 6px; }
        .seal-line2 { font-size: 9.5px; font-weight: bold; letter-spacing: 2px; margin-top: 3px; }
        .seal-year { font-size: 10.5px; font-weight: bold; margin-top: 4px; }

        /* ─── Assinaturas ────────────────────────────────────────────────── */
        .signatures {
            position: absolute;
            bottom: 76px;
            left: 60px;
            right: 60px;
            z-index: 2;
        }
        .signatures table { width: 100%; border-collapse: collapse; }
        .sig-cell {
            width: 50%;
            text-align: center;
            padding: 0 30px;
        }
        .sig-line {
            width: 78%;
            margin: 0 auto 8px;
            border-top: 1.5px solid #1e293b;
        }
        .sig-name {
            font-size: 12.5px;
            font-weight: bold;
            color: #1e1b4b;
        }
        .sig-role {
            font-size: 10.5px;
            color: #64748b;
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        /* ─── Footer: validacao ──────────────────────────────────────────── */
        .footer {
            position: absolute;
            bottom: 28px;
            left: 60px;
            right: 60px;
            z-index: 2;
            padding-top: 12px;
            border-top: 1px solid #e0e7ff;
        }
        .footer table { width: 100%; border-collapse: collapse; }
        .footer .f-left { text-align: left; font-size: 9.5px; color: #64748b; }
        .footer .f-right { text-align: right; font-size: 9.5px; color: #64748b; }
        .footer .f-code {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: #4338ca;
            font-size: 10.5px;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>

<div class="page">
    <div class="outer">
        <div class="top-bar"></div>
        <div class="watermark">VIVENSI</div>

        <div class="inner">

            {{-- Header: logo Vivensi + tag Academy --}}
            <div class="header">
                <table>
                    <tr>
                        <td class="logo-cell">
                            {{-- DomPDF exige GD pra renderizar PNG. Em prod (Ubuntu com php-gd)
                                 usa a logo real; em ambiente sem GD (dev/CI raro) cai no
                                 fallback textual pra nao quebrar a geracao. --}}
                            @if(function_exists('imagecreatetruecolor') && file_exists(public_path('img/novalogo.png')))
                                <img src="{{ public_path('img/novalogo.png') }}" alt="Vivensi">
                            @else
                                <span class="logo-fallback">VIVENSI</span>
                            @endif
                        </td>
                        <td class="tag-cell">Vivensi Academy • Certificacao Oficial</td>
                    </tr>
                </table>
            </div>

            <div class="divider"></div>

            {{-- Selo circular --}}
            <div class="seal">
                <div class="seal-star">&#9733;</div>
                <div class="seal-line1">CERTIFICADO</div>
                <div class="seal-line2">OFICIAL</div>
                <div class="seal-year">{{ $certificate->issued_at->format('Y') }}</div>
            </div>

            {{-- Corpo --}}
            <div class="body">
                <div class="kicker">Certificamos que</div>
                <div class="title">Certificado</div>
                <div class="subtitle">de Conclusao</div>

                <div class="intro">Este documento reconhece que</div>
                <div class="student-name">{{ $certificate->user->name }}</div>
                <div class="name-underline"></div>

                <div class="conclusion">concluiu com aproveitamento o curso</div>
                <div class="course-title">{{ $certificate->course->title }}</div>

                <div class="meta-line">
                    oferecido pela Vivensi Academy
                    &middot; Emitido em {{ $certificate->issued_at->format('d/m/Y') }}
                </div>
            </div>

            {{-- Assinaturas --}}
            <div class="signatures">
                <table>
                    <tr>
                        <td class="sig-cell">
                            <div class="sig-line"></div>
                            <div class="sig-name">{{ $certificate->course->teacher_name ?? 'Diretoria Vivensi' }}</div>
                            <div class="sig-role">Instrutor Responsavel</div>
                        </td>
                        <td class="sig-cell">
                            <div class="sig-line"></div>
                            <div class="sig-name">Vivensi Academy</div>
                            <div class="sig-role">Certificacao Oficial</div>
                        </td>
                    </tr>
                </table>
            </div>

            {{-- Footer: validacao --}}
            <div class="footer">
                <table>
                    <tr>
                        <td class="f-left">
                            Codigo de validacao: <span class="f-code">{{ $certificate->code }}</span>
                        </td>
                        <td class="f-right">
                            Verifique em vivensi.app.br &middot; Documento gerado automaticamente
                        </td>
                    </tr>
                </table>
            </div>

        </div>
    </div>
</div>

</body>
</html>
