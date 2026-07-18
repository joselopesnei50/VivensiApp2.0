<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Etapas em atraso</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f8fafc; margin: 0; padding: 24px; color: #0f172a;">
    <div style="max-width: 640px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.05);">
        <div style="background: linear-gradient(135deg, #f59e0b 0%, #dc2626 100%); color: white; padding: 24px 28px;">
            <div style="font-size: .72rem; text-transform: uppercase; letter-spacing: 1.5px; opacity: .85;">Vivensi · Alerta</div>
            <h1 style="margin: 6px 0 0; font-size: 1.5rem; font-weight: 900;">Etapas em atraso</h1>
        </div>

        <div style="padding: 24px 28px;">
            <p>Olá, {{ $manager->name }}.</p>
            <p>Temos <strong>{{ $stages->count() }} etapa(s)</strong> com prazo vencido e status ainda pendente ou em andamento. Um resumo por projeto abaixo:</p>

            @foreach($stages->groupBy('project.name') as $projectName => $projectStages)
                <div style="margin: 16px 0; padding: 14px 16px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px;">
                    <div style="font-weight: 900; color: #78350f; margin-bottom: 8px;">
                        📁 {{ $projectName ?? 'Sem projeto' }}
                    </div>
                    <ul style="margin: 0; padding-left: 18px; color: #78350f;">
                        @foreach($projectStages as $s)
                            <li style="margin-bottom: 4px;">
                                <strong>{{ $s->title }}</strong>
                                @if($s->end_date)
                                    — prazo era {{ $s->end_date->format('d/m/Y') }} ({{ (int) now()->startOfDay()->diffInDays($s->end_date) }} dia(s) atrás)
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <p style="margin-top: 20px; color: #64748b; font-size: .9rem;">
                Recomendação: entre em cada etapa, atualize o status para <em>Concluída</em> ou ajuste o prazo,
                e informe a equipe responsável se algum ajuste for necessário.
            </p>

            <p style="margin-top: 16px; font-size: .8rem; color: #94a3b8;">
                Este é um email automático diário. Se as etapas forem concluídas ou os prazos ajustados,
                elas param de aparecer neste alerta.
            </p>
        </div>
    </div>
</body>
</html>
