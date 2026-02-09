<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificado de Conclusão</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            text-align: center;
        }
        .container {
            width: 100%;
            height: 100%;
            padding: 40px;
            box-sizing: border-box;
            border: 20px solid #1e293b;
            position: relative;
        }
        .header {
            margin-top: 50px;
            color: #1e293b;
        }
        .title {
            font-size: 50px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 5px;
            color: #312e81;
        }
        .subtitle {
            font-size: 20px;
            margin-top: 10px;
            color: #64748b;
        }
        .content {
            margin-top: 60px;
        }
        .student-name {
            font-size: 36px;
            font-weight: bold;
            color: #1e293b;
            border-bottom: 2px solid #cbd5e1;
            display: inline-block;
            padding-bottom: 5px;
            margin: 20px 0;
            width: 70%;
        }
        .course-title {
            font-size: 28px;
            font-weight: bold;
            color: #4338ca;
            margin: 20px 0;
        }
        .footer {
            margin-top: 80px;
            display: table;
            width: 100%;
        }
        .signature {
            display: table-cell;
            width: 50%;
            text-align: center;
        }
        .line {
            width: 200px;
            border-top: 1px solid #000;
            margin: 0 auto 10px auto;
        }
        .date {
            margin-top: 40px;
            font-size: 14px;
            color: #64748b;
        }
        .validation {
            position: absolute;
            bottom: 20px;
            right: 20px;
            font-size: 10px;
            color: #94a3b8;
            text-align: right;
        }
        .logo {
            position: absolute;
            top: 40px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            opacity: 0.1;
        }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="header">
            <div class="title">CERTIFICADO</div>
            <div class="subtitle">DE CONCLUSÃO</div>
        </div>

        <div class="content">
            <p style="font-size: 18px; color: #475569;">Certificamos que</p>
            
            <div class="student-name">{{ $certificate->user->name }}</div>
            
            <p style="font-size: 18px; color: #475569;">Concluiu com êxito o curso</p>
            
            <div class="course-title">{{ $certificate->course->title }}</div>
            
            <p style="font-size: 16px; color: #475569;">
                Ofertado pela Vivensi Academy.
            </p>
        </div>

        <div class="footer">
            <div class="signature">
                <div class="line"></div>
                <strong>{{ $certificate->course->teacher_name ?? 'Diretoria Vivensi' }}</strong><br>
                Instrutor / Diretor
            </div>
            <div class="signature">
                <div class="line"></div>
                <strong>Vivensi Academy</strong><br>
                Certificação Oficial
            </div>
        </div>

        <div class="date">
            Data de Emissão: {{ $certificate->issued_at->format('d/m/Y') }}
        </div>

        <div class="validation">
            Código de Validação: <strong>{{ $certificate->code }}</strong><br>
            Verifique a autenticidade deste documento em vivensi.app.br/validate
        </div>
    </div>
</body>
</html>
