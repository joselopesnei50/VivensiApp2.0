@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; gap: 20px;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Recibos de Doação</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Histórico e emissão de comprovantes.</p>
    </div>
    <a href="{{ url('/ngo/receipts/create') }}" class="btn-premium">
        <i class="fas fa-plus"></i> Novo Recibo
    </a>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <tr>
                <th style="padding: 15px 25px; text-align: left; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Data</th>
                <th style="padding: 15px 25px; text-align: left; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Doador / Descrição</th>
                <th style="padding: 15px 25px; text-align: right; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Valor</th>
                <th style="padding: 15px 25px; text-align: center; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Expira</th>
                <th style="padding: 15px 25px; text-align: center; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach($donations as $donation)
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 15px 25px; color: #475569;">
                    {{ \Carbon\Carbon::parse($donation->date)->format('d/m/Y') }}
                </td>
                <td style="padding: 15px 25px; font-weight: 500; color: #1e293b;">
                    {{ $donation->description }}
                </td>
                <td style="padding: 15px 25px; text-align: right; font-weight: 700; color: #16a34a;">
                    R$ {{ number_format($donation->amount, 2, ',', '.') }}
                </td>
                <td style="padding: 15px 25px; text-align: center; color: #64748b;">
                    @if($donation->public_receipt_expires_at)
                        @php $expired = $donation->public_receipt_expires_at->isPast(); @endphp
                        <span style="font-weight: 800; font-size: 0.8rem; color: {{ $expired ? '#b91c1c' : '#0f766e' }};">
                            {{ $donation->public_receipt_expires_at->format('d/m/Y') }}
                        </span>
                        <div style="font-size: 0.7rem; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase; color: {{ $expired ? '#ef4444' : '#10b981' }}; margin-top: 4px;">
                            {{ $expired ? 'Expirado' : 'Ativo' }}
                        </div>
                    @else
                        <span style="font-weight: 800; font-size: 0.8rem; color: #64748b;">Sem expiração</span>
                    @endif
                </td>
                <td style="padding: 15px 25px; text-align: center;">
                    <div style="display: flex; justify-content: center; gap: 10px;">
                        <a href="{{ route('public.receipt', $donation->public_receipt_token) }}" target="_blank" class="btn-outline" style="padding: 6px 12px; font-size: 0.8rem; text-decoration: none; border-radius: 8px;">
                            <i class="fas fa-eye"></i> Visualizar
                        </a>
                        <button type="button" onclick="copyReceipt('{{ route('public.receipt', $donation->public_receipt_token) }}')" class="btn-outline" style="padding: 6px 12px; font-size: 0.8rem; text-decoration: none; border-radius: 8px; border: 1px solid #e2e8f0; background: white; color: #64748b;">
                            <i class="fas fa-copy"></i> Copiar
                        </button>
                        <button onclick="shareReceipt('{{ $donation->description }}', '{{ route('public.receipt', $donation->public_receipt_token) }}')" class="btn-premium" style="padding: 6px 12px; font-size: 0.8rem; min-width: auto; background: #25D366; border: none;">
                            <i class="fab fa-whatsapp"></i> Enviar
                        </button>
                        <form action="{{ route('ngo.receipts.regenerate_link', ['id' => $donation->id]) }}" method="POST" onsubmit="return confirm('Regenerar link público? O link antigo vai parar de funcionar.')" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn-outline" style="padding: 6px 12px; font-size: 0.8rem; text-decoration: none; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff7ed; color: #9a3412;">
                                <i class="fas fa-rotate"></i> Renovar
                            </button>
                        </form>
                        <form action="{{ route('ngo.receipts.revoke_link', ['id' => $donation->id]) }}" method="POST" onsubmit="return confirm('Revogar o link público agora? Ele vai expirar imediatamente.')" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn-outline" style="padding: 6px 12px; font-size: 0.8rem; text-decoration: none; border-radius: 8px; border: 1px solid #e2e8f0; background: #fef2f2; color: #b91c1c;">
                                <i class="fas fa-ban"></i> Revogar
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div style="padding: 20px;">
        {{ $donations->links() }}
    </div>
</div>

<script>
    function shareReceipt(name, url) {
        let text = `Olá ${name}, agradecemos imensamente sua doação! Segue o link do seu recibo: ${url}`;
        let waLink = `https://wa.me/?text=${encodeURIComponent(text)}`;
        window.open(waLink, '_blank');
    }

    async function copyReceipt(url) {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(url);
                alert('Link copiado!');
                return;
            }
        } catch (e) {}

        // Fallback for non-secure contexts (common in localhost setups)
        window.prompt('Copie o link do recibo:', url);
    }
</script>
@endsection
