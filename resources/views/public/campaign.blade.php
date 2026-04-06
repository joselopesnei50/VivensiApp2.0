<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apoie: {{ $campaign->title }} - Vivensi</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --success: #16a34a;
            --text-main: #1e293b;
            --text-light: #64748b;
            --bg-light: #f8fafc;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-light);
            margin: 0;
            padding: 0;
            color: var(--text-main);
            line-height: 1.6;
        }

        .navbar {
            background: white;
            padding: 20px 40px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand {
            font-weight: 800;
            font-size: 1.4rem;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand i { color: var(--primary); }

        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
        }

        @media (max-width: 768px) {
            .container { grid-template-columns: 1fr; }
        }

        .main-content {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px -5px rgba(0,0,0,0.05);
        }

        .sidebar {
            position: sticky;
            top: 40px;
            height: fit-content;
        }

        .donation-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px -5px rgba(0,0,0,0.1);
            border: 1px solid rgba(0,0,0,0.05);
        }

        h1 { font-size: 2.2rem; margin: 0 0 20px 0; line-height: 1.2; letter-spacing: -0.02em; }
        
        .progress-section { margin-bottom: 25px; }
        
        .progress-bar {
            height: 12px;
            background: #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--success);
            border-radius: 6px;
            transition: width 1s ease-in-out;
        }

        .stats-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 5px;
        }

        .amount-raised { font-size: 2rem; font-weight: 800; color: var(--success); }
        .amount-target { color: var(--text-light); font-size: 0.9rem; font-weight: 600; }

        .btn-donate {
            display: block;
            width: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            text-align: center;
            padding: 18px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            text-decoration: none;
            box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);
            transition: transform 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .btn-donate:hover { transform: translateY(-3px); }

        .image-placeholder {
            width: 100%;
            height: 300px;
            background: linear-gradient(45deg, #f1f5f9, #e2e8f0);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 3rem;
            margin-bottom: 30px;
        }

        .description { font-size: 1.1rem; color: #475569; }
        
        .verified-badge {
            background: #dbeafe;
            color: #1e40af;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="brand"><i class="fas fa-cube"></i> Vivensi Social</div>
        <div>
            <a href="#" style="color: var(--text-main); text-decoration: none; font-weight: 600;">Login</a>
        </div>
    </nav>

    <div class="container">
        <!-- Main Content -->
        <div class="main-content">
            <span class="verified-badge"><i class="fas fa-check-circle"></i> Campanha Verificada</span>
            <h1>{{ $campaign->title }}</h1>
            
            <div class="image-placeholder">
                @if($campaign->video_url)
                    <i class="fab fa-youtube" style="color: #ef4444;"></i>
                @else
                    <i class="fas fa-image"></i>
                @endif
            </div>

            <div class="description">
                {!! nl2br(e($campaign->description)) !!}
            </div>

            <div style="margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
                <h3 style="margin-top: 0;">Transparência do Projeto</h3>
                <div style="display: flex; gap: 20px;">
                    <div style="flex: 1; background: #f8fafc; padding: 15px; border-radius: 8px;">
                        <i class="fas fa-file-invoice-dollar" style="color: #64748b; font-size: 1.2rem; margin-bottom: 8px;"></i>
                        <div style="font-weight: 700; font-size: 0.9rem;">Prestação de Contas</div>
                        <div style="font-size: 0.8rem; color: #64748b;">Disponível mensalmente</div>
                    </div>
                     <div style="flex: 1; background: #f8fafc; padding: 15px; border-radius: 8px;">
                        <i class="fas fa-users" style="color: #64748b; font-size: 1.2rem; margin-bottom: 8px;"></i>
                        <div style="font-weight: 700; font-size: 0.9rem;">Impacto Social</div>
                        <div style="font-size: 0.8rem; color: #64748b;">Relatórios de visita</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar / Donation Box -->
        <div class="sidebar">
            <div class="donation-card">
                <div class="progress-section">
                    <div class="stats-row">
                        <span class="amount-raised">R$ {{ number_format($campaign->current_amount, 2, ',', '.') }}</span>
                    </div>
                     <div class="progress-bar">
                         @php $perc = $campaign->target_amount > 0 ? ($campaign->current_amount / $campaign->target_amount) * 100 : 0; @endphp
                        <div class="progress-fill" style="width: {{ $perc }}%;"></div>
                    </div>
                    <div style="text-align: right; font-size: 0.85rem; color: #64748b; font-weight: 600;">
                        de R$ {{ number_format($campaign->target_amount, 2, ',', '.') }} meta
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid #f1f5f9; margin: 25px 0;">

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 10px; color: #334155;">Valor da Doação</label>
                    <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                        <button style="flex: 1; padding: 10px; background: white; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; font-weight: 600;">R$ 50</button>
                        <button style="flex: 1; padding: 10px; background: white; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; font-weight: 600;">R$ 100</button>
                        <button style="flex: 1; padding: 10px; background: white; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; font-weight: 600;">R$ 200</button>
                    </div>
                    <input type="number" placeholder="Outro valor (R$)" style="width: 100%; box-sizing: border-box; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem;">
                </div>

                <button class="btn-donate">
                    <i class="fas fa-heart"></i> Fazer Doação
                </button>
                
                <div style="text-align: center; margin-top: 15px; font-size: 0.8rem; color: #94a3b8;">
                    <i class="fas fa-lock"></i> Pagamento Seguro via Vivensi
                </div>
            </div>
        </div>
    </div>
    <!-- Payment Modal -->
    <div id="paymentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; width: 100%; max-width: 400px; padding: 30px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); text-align: center;">
            <div id="step1">
                <h3 style="margin-top: 0; color: #1e293b;">Finalizar Doação</h3>
                <p style="color: #64748b; margin-bottom: 20px;">Você está doando para <strong>{{ $campaign->title }}</strong></p>
                
                <div style="background: #f8fafc; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 0.9rem; color: #64748b;">Valor total</div>
                    <div style="font-size: 2rem; font-weight: 800; color: #1e293b;" id="modalAmount">R$ 0,00</div>
                </div>

                <button onclick="processPayment()" class="btn-donate" style="width: 100%;">
                    <i class="fas fa-qrcode"></i> Gerar PIX
                </button>
                <button onclick="closeModal()" style="margin-top: 15px; background: none; border: none; color: #94a3b8; cursor: pointer;">Cancelar</button>
            </div>

            <div id="step2" style="display: none;">
                <div style="margin-bottom: 20px;">
                    <i class="fas fa-circle-notch fa-spin" style="font-size: 3rem; color: #4f46e5;"></i>
                </div>
                <p style="color: #64748b;">Gerando cobrança segura...</p>
            </div>

            <div id="step3" style="display: none;">
                <h3 style="color: #1e293b;">Escaneie o QR Code</h3>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=00020126580014BR.GOV.BCB.PIX0136123e4567-e89b-12d3-a456-426614174000520400005303986540510.005802BR5913Vivensi Social6008Brasilia62070503***63041D3D" style="width: 200px; margin-bottom: 20px; border: 1px solid #e2e8f0; padding: 10px; border-radius: 10px;">
                <p style="font-size: 0.9rem; color: #64748b;">Aguardando pagamento...</p>
                <div style="font-size: 0.8rem; background: #f0fdf4; color: #16a34a; padding: 5px 10px; border-radius: 20px; display: inline-block;">
                    <i class="fas fa-shield-alt"></i> Ambiente Seguro
                </div>
            </div>
            
            <div id="step4" style="display: none;">
                <div style="color: #16a34a; font-size: 4rem; margin-bottom: 10px;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 style="color: #1e293b; margin: 0;">Doação Confirmada!</h3>
                <p style="color: #64748b;">Obrigado por apoiar esta causa.</p>
                <button onclick="closeModal()" class="btn-donate" style="background: #1e293b;">Fechar</button>
            </div>
        </div>
    </div>

    <script>
        let selectedAmount = 0;

        // Select amount buttons
        document.querySelectorAll('.donation-card button').forEach(btn => {
            if(btn.innerText.includes('R$')) { // Check if it's one of the amount buttons
                 btn.addEventListener('click', (e) => {
                     // Reset others
                     document.querySelectorAll('.donation-card button').forEach(b => {
                         if(b.innerText.includes('R$')) b.style.borderColor = '#e2e8f0';
                         if(b.innerText.includes('R$')) b.style.background = 'white';
                         if(b.innerText.includes('R$')) b.style.color = '#334155';
                     });
                     // Select current
                     e.target.style.borderColor = '#4f46e5';
                     e.target.style.background = '#eef2ff';
                     e.target.style.color = '#4f46e5';
                     
                     selectedAmount = parseFloat(e.target.innerText.replace('R$', '').trim());
                     document.querySelector('input[type="number"]').value = '';
                 });
            }
        });

        // Custom amount input
        document.querySelector('input[type="number"]').addEventListener('input', (e) => {
            selectedAmount = parseFloat(e.target.value);
            // Reset buttons
            document.querySelectorAll('.donation-card button').forEach(b => {
                 if(b.innerText.includes('R$')) b.style.borderColor = '#e2e8f0';
                 if(b.innerText.includes('R$')) b.style.background = 'white';
            });
        });

        // Main Donate Button
        document.querySelector('.btn-donate').addEventListener('click', () => {
             if(selectedAmount <= 0 || isNaN(selectedAmount)) {
                 alert('Por favor, selecione ou digite um valor para doação.');
                 return;
             }
             document.getElementById('modalAmount').innerText = 'R$ ' + selectedAmount.toFixed(2).replace('.', ',');
             document.getElementById('step1').style.display = 'block';
             document.getElementById('step2').style.display = 'none';
             document.getElementById('step3').style.display = 'none';
             document.getElementById('step4').style.display = 'none';
             
             document.getElementById('paymentModal').style.display = 'flex';
        });

        function closeModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }

        function processPayment() {
            document.getElementById('step1').style.display = 'none';
            document.getElementById('step2').style.display = 'block';
            
            // Mock API call delay
            setTimeout(() => {
                document.getElementById('step2').style.display = 'none';
                document.getElementById('step3').style.display = 'block';
                
                // Mock Payment Confirmation delay
                setTimeout(() => {
                     document.getElementById('step3').style.display = 'none';
                     document.getElementById('step4').style.display = 'block';
                     
                     // Play success sound (optional, browser policy blocks usually)
                }, 5000); // 5 seconds to "Pay"
            }, 2000);
        }
    </script>
</body>
</html>
