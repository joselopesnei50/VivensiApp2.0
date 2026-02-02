@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; gap: 20px;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Contratos Digitais</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Gerencie e colete assinaturas eletrônicas.</p>
    </div>
    <a href="{{ url('/ngo/contracts/create') }}" class="btn-premium">
        <i class="fas fa-plus"></i> Novo Contrato
    </a>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <tr>
                <th style="padding: 15px 25px; text-align: left; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Título</th>
                <th style="padding: 15px 25px; text-align: left; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Signatário</th>
                <th style="padding: 15px 25px; text-align: center; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Status</th>
                <th style="padding: 15px 25px; text-align: center; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach($contracts as $c)
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 15px 25px; font-weight: 600; color: #334155;">{{ $c->title }}</td>
                <td style="padding: 15px 25px; color: #64748b;">{{ $c->signer_name }}</td>
                <td style="padding: 15px 25px; text-align: center;">
                    @if($c->status == 'signed')
                        <span style="background: #dcfce7; color: #16a34a; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">ASSINADO</span>
                    @else
                        <span style="background: #fff7ed; color: #c2410c; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">AGUARDANDO</span>
                    @endif
                </td>
                <td style="padding: 15px 25px; text-align: center;">
                    <div style="display: flex; justify-content: center; gap: 10px;">
                       <button onclick="shareContract('{{ $c->signer_name }}', '{{ route('public.contract', $c->token) }}')" class="btn-outline" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 8px; cursor: pointer;">
                            <i class="fas fa-link"></i> Link
                        </button>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
    function shareContract(name, url) {
        let text = `Olá ${name}, por favor assine o contrato digital no link: ${url}`;
        let waLink = `https://wa.me/?text=${encodeURIComponent(text)}`;
        window.open(waLink, '_blank');
    }
</script>
@endsection
