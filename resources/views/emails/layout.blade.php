<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Vivensi')</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f8fafc;
            padding-bottom: 60px;
        }
        .main {
            background-color: #ffffff;
            margin: 0 auto;
            width: 100%;
            max-width: 600px;
            border-spacing: 0;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.05);
        }
        .header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 40px;
            text-align: center;
        }
        .content {
            padding: 45px;
            line-height: 1.6;
        }
        .footer {
            padding: 40px;
            text-align: center;
            color: #64748b;
            font-size: 13px;
        }
        h1 {
            color: #0f172a;
            font-size: 24px;
            font-weight: 800;
            margin-top: 0;
            letter-spacing: -0.5px;
        }
        p {
            margin-bottom: 24px;
        }
    </style>
</head>
<body>
    <center class="wrapper">
        <table class="main" width="100%">
            <tr>
                <td class="header">
                    <img loading="lazy" src="{{ config('app.url') }}/novalogo.png" alt="Vivensi" style="width: 150px; height: auto;">
                </td>
            </tr>
            <tr>
                <td class="content">
                    @yield('content')
                </td>
            </tr>
            <tr>
                <td class="footer">
                    <p style="margin-bottom: 8px;">&copy; {{ date('Y') }} Vivensi. Todos os direitos reservados.</p>
                    <p style="margin-bottom: 0;">Impulsionando o Terceiro Setor com Inteligência e Transparência.</p>
                </td>
            </tr>
        </table>
    </center>
</body>
</html>
