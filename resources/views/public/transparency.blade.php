<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Transparência - Vivensi</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; color: #334155; margin: 0; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .header { text-align: center; margin-bottom: 40px; }
        .header h1 { font-size: 2rem; color: #1e293b; margin: 0; }
        .card { background: white; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 30px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; border-bottom: 2px solid #e2e8f0; color: #64748b; font-size: 0.85rem; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; }
        .badge { background: #dcfce7; color: #16a34a; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .amount { font-weight: 700; color: #334155; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <i class="fas fa-landmark" style="font-size: 3rem; color: #4f46e5; margin-bottom: 15px;"></i>
            <h1>Portal da Transparência</h1>
            <p>Prestação de contas pública realizada via Vivensi Platform.</p>
        </div>

        <div class="card">
            <h3><i class="fas fa-list"></i> Últimas Despesas Realizadas</h3>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Categoria</th>
                        <th style="text-align: right;">Valor</th>
                        <th style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $t)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($t->date)->format('d/m/Y') }}</td>
                        <td>{{ $t->description }}</td>
                        <td>{{ $t->category_id ? 'Operacional' : 'Geral' }}</td>
                        <td style="text-align: right;" class="amount">R$ {{ number_format($t->amount, 2, ',', '.') }}</td>
                        <td style="text-align: center;"><span class="badge">AUDITADO</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div style="text-align: center; font-size: 0.8rem; color: #94a3b8; margin-top: 40px;">
            Dados atualizados em tempo real. Powered by <strong>Vivensi</strong>.
        </div>
    </div>
</body>
</html>
