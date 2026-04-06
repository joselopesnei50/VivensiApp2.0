<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal do Doador | {{ $donor->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
            background: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .header {
            width: 100%;
            padding: 40px 20px;
            text-align: center;
            background: linear-gradient(180deg, rgba(30,41,59,1) 0%, rgba(15,23,42,1) 100%);
            border-bottom: 1px solid #1e293b;
        }
        .header h1 {
            margin: 0;
            font-size: 2.2rem;
            color: #f8fafc;
            font-weight: 800;
        }
        .header p {
            color: #94a3b8;
            margin-top: 10px;
            font-size: 1.1rem;
        }
        .container {
            width: 100%;
            max-width: 800px;
            padding: 40px 20px;
            flex: 1;
        }
        .card {
            background: #1e293b;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            margin-bottom: 30px;
            border: 1px solid #334155;
        }
        .impact-number {
            font-size: 3.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #4f46e5 0%, #0ea5e9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 10px 0;
        }
        .btn-premium {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.4);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #334155;
        }
        th {
            color: #94a3b8;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        td {
            color: #e2e8f0;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Olá, {{ explode(' ', $donor->name)[0] }}! 💙</h1>
        <p>Este é o seu portal seguro e exclusivo de transparência.</p>
    </div>

    <div class="container">
        <!-- Impact Card -->
        <div class="card" style="text-align: center;">
            <p style="color: #94a3b8; text-transform: uppercase; letter-spacing: 2px; font-size: 0.8rem; font-weight: 700;">Geração de Impacto Histórico</p>
            <div class="impact-number">R$ {{ number_format($totalDonated, 2, ',', '.') }}</div>
            <p style="color: #cbd5e1; font-size: 1.1rem; line-height: 1.6; max-width: 600px; margin: 20px auto;">
                Graças à sua generosidade, estamos transformando vidas. Cada centavo doado se transforma em esperança, alimento e educação para quem mais precisa. O nosso muito obrigado!
            </p>
        </div>

        <!-- IR Section -->
        <div class="card" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div>
                <h3 style="margin: 0 0 5px 0; color: #f8fafc;"><i class="fas fa-file-invoice-dollar" style="color:#10b981;"></i> Imposto de Renda</h3>
                <p style="margin: 0; color: #94a3b8; font-size: 0.9rem;">Baixe seu Comprovante Oficial de Rendimentos Anual para declaração do IRPF/IRPJ.</p>
            </div>
            
            <form action="{{ url('/portal-doador/'.$donor->portal_token.'/ir-pdf') }}" method="GET" style="display: flex; gap: 10px; align-items: center;">
                <select name="year" style="padding: 12px; border-radius: 12px; background: #0f172a; color: #f8fafc; border: 1px solid #334155; outline: none;">
                    @foreach($years as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                    @if(count($years) === 0)
                        <option value="{{ date('Y') }}">{{ date('Y') }}</option>
                    @endif
                </select>
                <button type="submit" class="btn-premium">
                    <i class="fas fa-download"></i> Baixar PDF
                </button>
            </form>
        </div>

        <!-- History -->
        <div class="card">
            <h3 style="margin: 0 0 15px 0;">Histórico de Doações Recebidas</h3>
            @if(count($donations) > 0)
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrição / Origem</th>
                            <th style="text-align: right;">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($donations as $d)
                        <tr>
                            <td>{{ $d->date->format('d/m/Y') }}</td>
                            <td>{{ $d->description ?? 'Doação' }}</td>
                            <td style="text-align: right; font-weight: 700; color: #10b981;">R$ {{ number_format($d->amount, 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="text-align: center; padding: 30px; color: #64748b;">
                <i class="fas fa-box-open" style="font-size: 2rem; margin-bottom: 15px; opacity: 0.5;"></i>
                <p margin="0">Nenhuma doação registrada até o momento.</p>
            </div>
            @endif
        </div>
    </div>

</body>
</html>
