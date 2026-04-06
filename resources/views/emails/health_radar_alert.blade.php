<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1e293b; line-height: 1.6; margin: 0; padding: 0; background-color: #f8fafc; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.05); }
        .header { background: #0f172a; padding: 40px; text-align: center; color: #ffffff; }
        .body { padding: 40px; }
        .footer { background: #f1f5f9; padding: 20px; text-align: center; font-size: 0.8rem; color: #64748b; }
        .score-box { background: #fee2e2; border: 1px solid #fecaca; border-radius: 16px; padding: 20px; text-align: center; margin-bottom: 30px; }
        .score-value { font-size: 3rem; font-weight: 950; color: #e11d48; margin: 0; }
        .btn { display: inline-block; padding: 14px 28px; background: #6366f1; color: #ffffff; text-decoration: none; border-radius: 12px; font-weight: 800; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin: 0; letter-spacing: -1px; font-weight: 900;">Vivensi Command Center</h2>
        </div>
        <div class="body">
            <h3 style="margin-top: 0;">Alerta de Saúde de Impacto ⚠️</h3>
            <p>Olá,</p>
            <p>O monitoramento inteligente detectou uma queda significativa no <strong>Radar de Saúde</strong> do projeto <strong>{{ $project->name }}</strong>.</p>
            
            <div class="score-box">
                <p style="margin: 0; font-weight: 800; text-transform: uppercase; color: #991b1b;">Novo Score Geral</p>
                <p class="score-value">{{ $currentScore }}%</p>
                <p style="margin: 5px 0 0 0; font-weight: 700; color: #b91c1c;">Queda de {{ $drop }} pontos desde o último registro</p>
            </div>

            <p>Recomendamos revisar as métricas financeiras, o progresso das tarefas e a conformidade dos documentos para reestabelecer o status ideal do registro.</p>
            
            <a href="{{ url('/projects/' . $project->id) }}" class="btn">Acessar Painel de Comando</a>
        </div>
        <div class="footer">
            © {{ date('Y') }} Vivensi Platform - Monitoramento de Alta Performance
        </div>
    </div>
</body>
</html>
