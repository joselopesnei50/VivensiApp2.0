<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $raffle->title }} | Vivensi Solidário</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: rgba(67, 97, 238, 0.1);
            --secondary: #3f37c9;
            --accent: #4cc9f0;
            --bg-body: #f8fafc;
            --text-main: #1e293b;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg-body: #f8fafc; /* Force light background for form usability as requested */
                --text-main: #1e293b;
                --card-bg: #ffffff;
                --border-color: #e2e8f0;
            }
            .ticket:not(.selected) { background: #ffffff !important; color: #1e293b !important; }
        }

        /* If user really wants dark, we keep it subtle but readable */
        [data-theme="dark"] {
            --bg-body: #0f172a;
            --text-main: #f1f5f9;
            --card-bg: #1e293b;
            --border-color: #334155;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: all 0.3s ease;
        }

        .raffle-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .raffle-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0,0,0,0.05);
        }

        .raffle-banner {
            width: 100%;
            height: 350px;
            object-fit: cover;
            border-bottom: 1px solid var(--border-color);
        }

        .raffle-header {
            padding: 40px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }

        .raffle-title {
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 15px;
        }

        .raffle-description {
            color: #64748b;
            max-width: 700px;
            margin: 0 auto;
        }

        .ticket-price-badge {
            background: var(--primary-light);
            color: var(--primary);
            padding: 12px 25px;
            border-radius: 100px;
            font-weight: 800;
            display: inline-block;
            margin-bottom: 20px;
        }

        .ticket-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(50px, 1fr));
            gap: 8px;
            padding: 20px;
            background: rgba(0,0,0,0.02);
            border-radius: 20px;
            max-height: 400px;
            overflow-y: auto;
        }

        .ticket {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.85rem;
            border: 1px solid var(--border-color);
            background: var(--card-bg);
        }

        .ticket:hover:not(.disabled) {
            transform: translateY(-3px);
            border-color: var(--primary);
            color: var(--primary);
        }

        .ticket.selected {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
        }

        .ticket.pending { background: #fef3c7; color: #d97706; cursor: not-allowed; opacity: 0.7; }
        .ticket.paid { background: #f1f5f9; color: #94a3b8; cursor: not-allowed; text-decoration: line-through; opacity: 0.5; }

        .btn-reserve {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            border-radius: 15px;
            padding: 18px;
            font-weight: 800;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-reserve:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(67, 97, 238, 0.4);
            color: white;
        }

        .btn-reserve:disabled { opacity: 0.5; cursor: not-allowed; }

        .form-vivensi {
            background: var(--bg-body);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 12px;
            color: var(--text-main);
        }

        .form-vivensi:focus {
            background: var(--card-bg);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
        }

        @media (max-width: 768px) {
            .raffle-banner { height: 200px; }
            .raffle-header { padding: 30px 20px; }
            .ticket-grid { grid-template-columns: repeat(5, 1fr); }
        }
    </style>
</head>
<body>

    <div class="raffle-container">
        <div class="raffle-card">
            @if($raffle->image_path)
                <img loading="lazy" src="{{ Storage::url($raffle->image_path) }}" class="raffle-banner" alt="{{ $raffle->title }}">
            @endif

            <div class="raffle-header">
                <div class="ticket-price-badge">
                    BILHETE: R$ {{ number_format($raffle->ticket_price, 2, ',', '.') }}
                </div>
                <h1 class="raffle-title">{{ $raffle->title }}</h1>
                <p class="raffle-description mb-4">{{ $raffle->description }}</p>

                @if($raffle->rules)
                <div class="mt-4 p-3 rounded-4 text-start mx-auto" style="max-width: 700px; background: rgba(0,0,0,0.03); border: 1px dashed var(--border-color);">
                    <h6 class="fw-800 small text-uppercase ls-1 mb-2"><i class="bi bi-info-circle me-1"></i>Regras e Termos</h6>
                    <p class="small mb-0 opacity-75" style="white-space: pre-line;">{{ $raffle->rules }}</p>
                </div>
                @endif
                
                <div class="d-flex justify-content-center gap-4 mt-4 text-muted small fw-bold">
                    <span><i class="bi bi-circle-fill text-primary me-2 opacity-25"></i>LIVRES</span>
                    <span><i class="bi bi-circle-fill text-warning me-2 opacity-50"></i>RESERVADOS</span>
                    <span><i class="bi bi-circle-fill text-secondary me-2 opacity-25"></i>VENDIDOS</span>
                </div>
            </div>

            <div class="p-4 p-md-5">
                @if(session('success'))
                    <div class="alert alert-success border-0 shadow-sm mb-4 rounded-4 p-3 d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger border-0 shadow-sm mb-4 rounded-4 p-3 d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm mb-4 rounded-4 p-3">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row g-5">
                    <div class="col-lg-7">
                        <h5 class="fw-800 mb-4">Selecione seus números</h5>
                        <div class="ticket-grid" id="ticketGrid">
                            @foreach($raffle->tickets->sortBy('number') as $ticket)
                                <div class="ticket {{ $ticket->status }}" 
                                     data-number="{{ $ticket->number }}"
                                     data-id="{{ $ticket->id }}"
                                     onclick="toggleTicket(this)">
                                    {{ str_pad($ticket->number, 2, '0', STR_PAD_LEFT) }}
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="p-4 rounded-4" style="background: var(--bg-body); border: 1px solid var(--border-color);">
                            <h5 class="fw-800 mb-4">Resumo da Reserva</h5>
                            
                            <form action="{{ route('public.raffle.reserve', $raffle->slug) }}" method="POST" id="reserveForm">
                                @csrf
                                <div id="selectedTicketsContainer"></div>
                                
                                <div class="mb-3">
                                    <label for="buyer_name" class="form-label small fw-800 text-muted text-uppercase">Seu Nome</label>
                                    <input type="text" name="buyer_name" class="form-control form-vivensi" placeholder="Nome completo" required id="buyer_name">
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label small fw-800 text-muted text-uppercase">WhatsApp</label>
                                    <input type="tel" name="buyer_phone" class="form-control form-vivensi" placeholder="(00) 00000-0000" id="phone" required>
                                </div>

                                <div class="mb-4">
                                    <label for="buyer_email" class="form-label small fw-800 text-muted text-uppercase">E-mail</label>
                                    <input type="email" name="buyer_email" class="form-control form-vivensi" placeholder="seu@email.com" required id="buyer_email">
                                </div>

                                <div id="selectionSummary" class="mb-4 d-none">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted small fw-bold">Números:</span>
                                        <span class="fw-800 text-primary" id="displayNumbers">--</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small fw-bold">Total a pagar:</span>
                                        <span class="fs-4 fw-800 text-dark" id="displayTotal" style="color: var(--text-main) !important;">R$ 0,00</span>
                                    </div>
                                </div>

                                @if(!$pixConfigured)
                                    <div class="alert alert-warning rounded-3 p-3 mb-3 text-start" style="font-size: 0.85rem;">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        <strong>Pagamento via PIX não configurado.</strong> Entre em contato com a organização para efetuar o pagamento.
                                    </div>
                                @endif
                                <button type="submit" class="btn-reserve" disabled id="btnSubmit">
                                    RESERVAR E PAGAR COM PIX
                                </button>

                                <div class="text-center mt-3 text-muted small">
                                    <i class="bi bi-shield-check me-1 shadow-sm"></i> Ambiente 100% Seguro
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-5 opacity-50 small">
            &copy; {{ date('Y') }} Vivensi Solidário - Plataforma de Arrecadação Digital
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/imask@7/dist/imask.min.js"></script>
    <script>
        // Máscara de telefone — guarda contra falha do CDN para não abortar
        // o script e quebrar a seleção de números.
        if (window.IMask) {
            IMask(document.getElementById('phone'), { mask: '(00) 00000-0000' });
        }

        let selectedNumbers = [];
        const ticketPrice = {{ $raffle->ticket_price }};

        function toggleTicket(element) {
            if (element.classList.contains('pending') || element.classList.contains('paid')) {
                return;
            }

            const number = element.dataset.number;

            if (selectedNumbers.includes(number)) {
                selectedNumbers = selectedNumbers.filter(n => n !== number);
                element.classList.remove('selected');
            } else {
                selectedNumbers.push(number);
                element.classList.add('selected');
            }

            updateSummary();
        }

        function updateSummary() {
            const container = document.getElementById('selectedTicketsContainer');
            container.innerHTML = '';
            
            selectedNumbers.forEach(num => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_numbers[]';
                input.value = num;
                container.appendChild(input);
            });

            if (selectedNumbers.length > 0) {
                document.getElementById('displayNumbers').innerText = selectedNumbers.map(n => '#' + n.padStart(2, '0')).join(', ');
                document.getElementById('displayTotal').innerText = 'R$ ' + (selectedNumbers.length * ticketPrice).toLocaleString('pt-BR', {minimumFractionDigits: 2});
                document.getElementById('selectionSummary').classList.remove('d-none');
                document.getElementById('btnSubmit').disabled = false;
            } else {
                document.getElementById('selectionSummary').classList.add('d-none');
                document.getElementById('btnSubmit').disabled = true;
            }
        }
    </script>
</body>
</html>
