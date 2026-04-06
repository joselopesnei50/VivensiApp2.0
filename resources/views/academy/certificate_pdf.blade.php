<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificado de Conclusão</title>
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
        }
        .container {
            width: 100%;
            height: 100vh;
            padding: 50px;
            box-sizing: border-box;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        /* Decorative Border */
        .border-frame {
            position: absolute;
            top: 30px;
            left: 30px;
            right: 30px;
            bottom: 30px;
            border: 8px solid #312e81;
            border-radius: 20px;
            box-shadow: inset 0 0 0 4px #6366f1;
        }
        
        /* Decorative Corner Elements */
        .corner {
            position: absolute;
            width: 80px;
            height: 80px;
            border: 3px solid #818cf8;
        }
        .corner.top-left { top: 50px; left: 50px; border-right: none; border-bottom: none; }
        .corner.top-right { top: 50px; right: 50px; border-left: none; border-bottom: none; }
        .corner.bottom-left { bottom: 50px; left: 50px; border-right: none; border-top: none; }
        .corner.bottom-right { bottom: 50px; right: 50px; border-left: none; border-top: none; }
        
        .content-wrapper {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 700px;
        }
        
        .logo-area {
            margin-bottom: 20px;
        }
        
        .logo-text {
            font-size: 24px;
            font-weight: bold;
            color: #312e81;
            letter-spacing: 2px;
        }
        
        .title {
            font-size: 56px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 8px;
            color: #312e81;
            margin: 20px 0 10px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .subtitle {
            font-size: 18px;
            color: #64748b;
            letter-spacing: 3px;
            margin-bottom: 40px;
        }
        
        .certification-text {
            font-size: 16px;
            color: #475569;
            margin: 20px 0 15px 0;
        }
        
        .student-name {
            font-size: 38px;
            font-weight: bold;
            color: #1e293b;
            border-bottom: 3px solid #6366f1;
            display: inline-block;
            padding: 10px 40px;
            margin: 15px 0;
            min-width: 400px;
        }
        
        .completion-text {
            font-size: 16px;
            color: #475569;
            margin: 20px 0 10px 0;
        }
        
        .course-title {
            font-size: 32px;
            font-weight: bold;
            color: #4338ca;
            margin: 15px 0 30px 0;
            line-height: 1.3;
        }
        
        .academy-text {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 40px;
        }
        
        .footer-section {
            display: table;
            width: 100%;
            margin-top: 50px;
        }
        
        .signature-block {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        
        .signature-line {
            width: 220px;
            border-top: 2px solid #1e293b;
            margin: 0 auto 8px auto;
        }
        
        .signature-name {
            font-size: 14px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 3px;
        }
        
        .signature-role {
            font-size: 12px;
            color: #64748b;
        }
        
        .date-validation {
            position: absolute;
            bottom: 50px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 11px;
            color: #64748b;
        }
        
        .validation-code {
            font-weight: bold;
            color: #312e81;
            font-size: 12px;
        }
        
        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            color: rgba(99, 102, 241, 0.03);
            font-weight: bold;
            z-index: 1;
            letter-spacing: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Decorative Frame -->
        <div class="border-frame"></div>
        <div class="corner top-left"></div>
        <div class="corner top-right"></div>
        <div class="corner bottom-left"></div>
        <div class="corner bottom-right"></div>
        
        <!-- Watermark -->
        <div class="watermark">VIVENSI</div>
        
        <!-- Main Content -->
        <div class="content-wrapper">
            <div class="logo-area">
                <div class="logo-text">🎓 VIVENSI ACADEMY</div>
            </div>
            
            <div class="title">CERTIFICADO</div>
            <div class="subtitle">DE CONCLUSÃO</div>
            
            <div class="certification-text">Certificamos que</div>
            
            <div class="student-name">{{ $certificate->user->name }}</div>
            
            <div class="completion-text">concluiu com êxito o curso</div>
            
            <div class="course-title">{{ $certificate->course->title }}</div>
            
            <div class="academy-text">oferecido pela Vivensi Academy</div>
            
            <!-- Signatures -->
            <div class="footer-section">
                <div class="signature-block">
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $certificate->course->teacher_name ?? 'Diretoria Vivensi' }}</div>
                    <div class="signature-role">Instrutor Responsável</div>
                </div>
                <div class="signature-block">
                    <div class="signature-line"></div>
                    <div class="signature-name">Vivensi Academy</div>
                    <div class="signature-role">Certificação Oficial</div>
                </div>
            </div>
        </div>
        
        <!-- Date and Validation -->
        <div class="date-validation">
            Emitido em {{ $certificate->issued_at->format('d/m/Y') }} • 
            Código de Validação: <span class="validation-code">{{ $certificate->code }}</span><br>
            <small>Verifique a autenticidade em vivensi.app.br/validate</small>
        </div>
    </div>
</body>
</html>
