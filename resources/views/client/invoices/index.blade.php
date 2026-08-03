@extends('layouts.app')
@section('title', 'Minhas Faturas')

@push('styles')
<style>
    .inv-page { max-width: 1100px; margin: 32px auto; padding: 0 20px; }
    .inv-hero { margin-bottom: 24px; }
    .inv-hero h1 { font-size: 1.7rem; font-weight: 800; color: #0f172a; margin: 0 0 6px; }
    .inv-hero p { color: #64748b; margin: 0; font-size: .95rem; }

    .inv-courtesy {
        background: linear-gradient(135deg, #065f46 0%, #047857 100%);
        border-radius: 20px; padding: 40px 44px; color: #fff;
        display: flex; align-items: center; gap: 22px; margin-bottom: 28px;
    }
    .inv-courtesy i { font-size: 3rem; opacity: .85; }
    .inv-courtesy h2 { font-size: 1.5rem; font-weight: 800; margin: 0 0 6px; letter-spacing: -.3px; }
    .inv-courtesy p { margin: 0; font-size: .95rem; opacity: .9; }

    .inv-section-title { font-size: 1rem; font-weight: 800; color: #0f172a; margin: 32px 0 14px; padding-left: 12px; border-left: 3px solid #4f46e5; }
    .inv-section-title:first-of-type { margin-top: 0; }

    .inv-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 12px rgba(15,23,42,.04); }
    .inv-table { width: 100%; border-collapse: collapse; }
    .inv-table th { background: #f8fafc; color: #64748b; font-weight: 700; font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; padding: 12px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
    .inv-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: .88rem; vertical-align: middle; }
    .inv-table tr:last-child td { border-bottom: 0; }

    .inv-amount { font-weight: 800; color: #0f172a; font-size: .95rem; white-space: nowrap; }
    .inv-status { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; }
    .inv-status.open     { background: #dbeafe; color: #1e40af; }
    .inv-status.overdue  { background: #fee2e2; color: #991b1b; }
    .inv-status.paid     { background: #dcfce7; color: #166534; }

    .inv-cta { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 8px; font-size: .78rem; font-weight: 700; text-decoration: none; white-space: nowrap; }
    .inv-cta.primary { background: #4f46e5; color: #fff; }
    .inv-cta.primary:hover { background: #4338ca; color: #fff; text-decoration: none; }
    .inv-cta.ghost { background: #f1f5f9; color: #334155; }
    .inv-cta.ghost:hover { background: #e2e8f0; color: #0f172a; text-decoration: none; }

    .inv-empty { background: #fff; border: 2px dashed #e2e8f0; border-radius: 14px; padding: 40px 24px; text-align: center; }
    .inv-empty i { font-size: 2.6rem; color: #cbd5e1; margin-bottom: 12px; }
    .inv-empty p { color: #64748b; margin: 0; font-size: .9rem; }

    .inv-pix-card {
        background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
        padding: 16px 18px; margin-top: 12px; font-size: .82rem;
    }
    .inv-pix-card strong { color: #0f172a; }
    .inv-pix-code {
        background: #fff; border: 1px dashed #cbd5e1; border-radius: 8px;
        padding: 10px 14px; margin-top: 8px; font-family: ui-monospace, "SF Mono", Menlo, monospace;
        font-size: .82rem; color: #0f172a; display: flex; justify-content: space-between; align-items: center; gap: 8px;
    }
    .inv-pix-copy {
        background: #10b981; color: #fff; border: 0; padding: 6px 12px; border-radius: 6px;
        font-size: .72rem; font-weight: 700; cursor: pointer;
    }
    .inv-pix-copy:hover { background: #059669; }

    /* Modal QR PIX */
    .pix-modal { display:none; position:fixed; inset:0; background:rgba(15,23,42,.6); z-index:9999; align-items:center; justify-content:center; padding:20px; }
    .pix-modal.open { display:flex; }
    .pix-modal-box { background:#fff; border-radius:20px; padding:32px; max-width:440px; width:100%; text-align:center; box-shadow:0 25px 60px rgba(0,0,0,.15); }
    .pix-modal-box h3 { color:#0f172a; font-weight:800; margin:0 0 6px; font-size:1.15rem; }
    .pix-modal-box p.sub { color:#64748b; font-size:.85rem; margin:0 0 20px; }
    .pix-modal-box img.qr { width:280px; height:280px; margin:0 auto 16px; display:block; border:1px solid #e2e8f0; border-radius:12px; }
    .pix-modal-code { background:#f1f5f9; border:1px dashed #cbd5e1; border-radius:10px; padding:12px; font-family:ui-monospace,monospace; font-size:.72rem; color:#0f172a; word-break:break-all; text-align:left; max-height:100px; overflow-y:auto; }
    .pix-modal-actions { display:flex; gap:10px; margin-top:16px; }
    .pix-modal-actions button { flex:1; padding:12px; border-radius:10px; border:0; font-weight:800; font-size:.85rem; cursor:pointer; }
    .pix-btn-copy { background:#10b981; color:#fff; }
    .pix-btn-copy:hover { background:#059669; }
    .pix-btn-close { background:#f1f5f9; color:#334155; }
    .pix-btn-close:hover { background:#e2e8f0; }
    .pix-modal-info { margin-top:14px; padding:10px 12px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; color:#1e40af; font-size:.78rem; }
</style>
@endpush

@section('content')
<div class="inv-page">

    <div class="inv-hero">
        <h1><i class="fas fa-file-invoice-dollar" style="color:#4f46e5;"></i> Minhas Faturas</h1>
        <p>Acompanhe suas cobranças de assinatura Vivensi — pagas, em aberto e vencidas.</p>
    </div>

    @if($isCourtesy)
        {{-- ── Banner Cortesia ── --}}
        <div class="inv-courtesy">
            <i class="fas fa-award"></i>
            <div>
                <h2>Plano Cortesia</h2>
                <p>Seu plano <strong>{{ $plan?->name ?? 'atual' }}</strong> é uma cortesia Vivensi — sem cobrança recorrente. Continue usando a plataforma sem preocupação com faturas.</p>
            </div>
        </div>
    @else
        {{-- ── Em Aberto ── --}}
        <h3 class="inv-section-title">Faturas em aberto</h3>
        @if($unpaid->isEmpty())
            <div class="inv-empty">
                <i class="fas fa-circle-check"></i>
                <p>Nenhuma fatura em aberto no momento.</p>
            </div>
        @else
            <div class="inv-table-wrap">
                <table class="inv-table">
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th>Vencimento</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th style="text-align:right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unpaid as $inv)
                            <tr>
                                <td>{{ $inv->description }}</td>
                                <td>{{ $inv->due_date->format('d/m/Y') }}</td>
                                <td class="inv-amount">{{ $inv->formatted_amount }}</td>
                                <td>
                                    <span class="inv-status {{ $inv->status }}">
                                        {{ $inv->statusLabel() }}
                                    </span>
                                </td>
                                <td style="text-align:right; white-space:nowrap;">
                                    @if($inv->abacatepay_pix_qr_base64 || $inv->abacatepay_pix_url)
                                        {{-- QR code embutido do AbacatePay --}}
                                        <button type="button" class="inv-cta primary" onclick="openPixModal({{ $inv->id }})">
                                            <i class="fas fa-qrcode"></i> Pagar com PIX
                                        </button>
                                    @elseif($inv->abacatepay_billing_url)
                                        {{-- Fallback pra link hospedado --}}
                                        <a href="{{ $inv->abacatepay_billing_url }}" target="_blank" rel="noopener" class="inv-cta primary">
                                            <i class="fas fa-external-link-alt"></i> Pagar
                                        </a>
                                    @endif
                                    @if($pixKey)
                                        <a href="#pix-info" class="inv-cta ghost" onclick="document.getElementById('pix-info').scrollIntoView({behavior:'smooth'}); return false;">
                                            <i class="fas fa-key"></i> PIX manual
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Bloco PIX Vivensi (fallback se AbacatePay indisponível) --}}
            @if($pixKey)
                <div class="inv-pix-card" id="pix-info">
                    <strong>Preferir pagar via PIX?</strong> Use os dados abaixo — depois envie o comprovante pelo WhatsApp <a href="https://wa.me/5516997618695" target="_blank" rel="noopener" style="color:#128C7E; font-weight:600;">(16) 99761-8695</a> pra confirmarmos.
                    <div style="margin-top:8px;"><small>Beneficiário: <strong>{{ $pixHolderName }}</strong> · Tipo: <strong>{{ $pixKeyType }}</strong></small></div>
                    <div class="inv-pix-code">
                        <span id="pix-key-value">{{ $pixKey }}</span>
                        <button type="button" class="inv-pix-copy" onclick="navigator.clipboard.writeText(document.getElementById('pix-key-value').innerText.trim()); this.innerText='Copiado ✓'; setTimeout(()=>this.innerText='Copiar',2500);">Copiar</button>
                    </div>
                </div>
            @endif
        @endif

        {{-- ── Pagas ── --}}
        <h3 class="inv-section-title">Faturas pagas</h3>
        @if($paid->isEmpty())
            <div class="inv-empty">
                <i class="fas fa-receipt"></i>
                <p>Nenhuma fatura paga registrada ainda.</p>
            </div>
        @else
            <div class="inv-table-wrap">
                <table class="inv-table">
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th>Pagamento em</th>
                            <th>Valor</th>
                            <th>Método</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paid as $inv)
                            <tr>
                                <td>{{ $inv->description }}</td>
                                <td>{{ $inv->paid_at?->timezone('America/Sao_Paulo')->format('d/m/Y') ?? '—' }}</td>
                                <td class="inv-amount">{{ $inv->formatted_amount }}</td>
                                <td style="color:#64748b; font-size:.82rem;">{{ $inv->paidViaLabel() ?? '—' }}</td>
                                <td>
                                    <span class="inv-status paid">{{ $inv->statusLabel() }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ── Modais QR PIX (1 por fatura com QR gerado) ── --}}
        @foreach($unpaid as $inv)
            @if($inv->abacatepay_pix_qr_base64 || $inv->abacatepay_pix_url)
                <div class="pix-modal" id="pixModal-{{ $inv->id }}" onclick="if(event.target===this) closePixModal({{ $inv->id }})">
                    <div class="pix-modal-box">
                        <h3><i class="fas fa-qrcode" style="color:#4f46e5;"></i> Pagar {{ $inv->formatted_amount }}</h3>
                        <p class="sub">{{ $inv->description }} · vence em {{ $inv->due_date->format('d/m/Y') }}</p>

                        @if($inv->abacatepay_pix_qr_base64)
                            <img class="qr" src="data:image/png;base64,{{ $inv->abacatepay_pix_qr_base64 }}" alt="QR Code PIX">
                        @endif

                        @if($inv->abacatepay_pix_url)
                            <div class="pix-modal-code" id="pix-code-{{ $inv->id }}">{{ $inv->abacatepay_pix_url }}</div>
                        @endif

                        <div class="pix-modal-actions">
                            @if($inv->abacatepay_pix_url)
                                <button type="button" class="pix-btn-copy" onclick="copyPixCode({{ $inv->id }}, this)">
                                    <i class="fas fa-copy"></i> Copiar código
                                </button>
                            @endif
                            <button type="button" class="pix-btn-close" onclick="closePixModal({{ $inv->id }})">Fechar</button>
                        </div>

                        <div class="pix-modal-info">
                            <i class="fas fa-info-circle"></i> Abra o app do seu banco, escaneie o QR ou cole o código. O pagamento é confirmado automaticamente em segundos.
                        </div>
                    </div>
                </div>
            @endif
        @endforeach

        <script>
            function openPixModal(id) { document.getElementById('pixModal-'+id).classList.add('open'); }
            function closePixModal(id) { document.getElementById('pixModal-'+id).classList.remove('open'); }
            function copyPixCode(id, btn) {
                const el = document.getElementById('pix-code-'+id);
                if (!el) return;
                navigator.clipboard.writeText(el.innerText.trim()).then(() => {
                    btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
                    setTimeout(() => btn.innerHTML = '<i class="fas fa-copy"></i> Copiar código', 2500);
                });
            }
        </script>
    @endif

</div>
@endsection
