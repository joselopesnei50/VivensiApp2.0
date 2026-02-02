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
                <td style="padding: 15px 25px; text-align: center;">
                    <div style="display: flex; justify-content: center; gap: 10px;">
                        <a href="{{ route('public.receipt', $donation->id) }}" target="_blank" class="btn-outline" style="padding: 6px 12px; font-size: 0.8rem; text-decoration: none; border-radius: 8px;">
                            <i class="fas fa-eye"></i> Visualizar
                        </a>
                        <button onclick="shareReceipt('{{ $donation->description }}', '{{ route('public.receipt', $donation->id) }}')" class="btn-premium" style="padding: 6px 12px; font-size: 0.8rem; min-width: auto; background: #25D366; border: none;">
                            <i class="fab fa-whatsapp"></i> Enviar
                        </button>
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
</script>
@endsection
