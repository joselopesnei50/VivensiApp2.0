<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Informe de Rendimentos {{ $year }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            font-size: 13px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0 0 10px 0;
            font-size: 20px;
            text-transform: uppercase;
        }
        .info-box {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            background: #fafafa;
        }
        .info-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11px;
            color: #666;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f4f6f8;
            font-weight: bold;
            color: #2c3e50;
        }
        .total-row td {
            font-weight: bold;
            background-color: #eef2f5;
            color: #2c3e50;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 11px;
            color: #777;
        }
        .signature {
            margin-top: 60px;
            text-align: center;
        }
        .signature-line {
            width: 300px;
            border-top: 1px solid #333;
            margin: 0 auto 10px auto;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>RECIBO E INFORME DE RENDIMENTOS ANUAL - {{ $year }}</h1>
        <p style="margin:0;">Comprovante Legal de Doações Recebidas</p>
    </div>

    <!-- Dados da Instituição -->
    <div class="info-box">
        <div class="info-title">1. DADOS DA INSTITUIÇÃO BENEFICIÁRIA (Receptora)</div>
        <strong>Razão Social:</strong> {{ $tenant->name ?? 'INSTITUIÇÃO SOCIAL' }}<br>
        <strong>CNPJ:</strong> {{ $tenant->document ?? 'Não Cadastrado' }}<br>
    </div>

    <!-- Dados do Doador -->
    <div class="info-box">
        <div class="info-title">2. DADOS DO DOADOR (Pagador)</div>
        <strong>Nome/Razão Social:</strong> {{ $donor->name }}<br>
        <strong>CPF/CNPJ:</strong> {{ $donor->document ?? 'Não Informado' }}<br>
        <strong>E-mail:</strong> {{ $donor->email ?? 'Não Informado' }}<br>
    </div>

    <p>Declaramos para os devidos fins de comprovação junto à Receita Federal do Brasil que a pessoa física/jurídica acima qualificada efetuou as seguintes doações durante o ano-calendário de {{ $year }}:</p>

    <table>
        <thead>
            <tr>
                <th style="width: 20%;">Data</th>
                <th style="width: 50%;">Histórico / Descrição</th>
                <th style="width: 30%; text-align: right;">Valor (R$)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($donations as $d)
            <tr>
                <td>{{ $d->date->format('d/m/Y') }}</td>
                <td>{{ $d->description ?? 'Doação Voluntária' }}</td>
                <td style="text-align: right;">{{ number_format($d->amount, 2, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" style="text-align: right;">TOTAL DOADO EM {{ $year }}:</td>
                <td style="text-align: right;">R$ {{ number_format($total, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="signature">
        <div class="signature-line"></div>
        Representante Legal / Departamento Financeiro<br>
        <strong>{{ $tenant->name ?? 'Instituição' }}</strong><br>
        Emitido pelo Sistema VIVENSI em {{ current(explode(' ', $date)) }}
    </div>

    <div class="footer">
        Este documento foi gerado digitalmente e serve como comprovante de doação para fins de dedução do imposto de renda (quando aplicável segundo as leis vigentes relacionadas à instituição recebedora).
    </div>

</body>
</html>
