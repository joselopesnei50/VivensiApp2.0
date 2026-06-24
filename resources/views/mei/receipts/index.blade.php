@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
        <div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: var(--primary-color); font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Recibos / MEI</h6>
            </div>
            <h2 style="margin:0; color:#1e293b; font-weight:900; font-size:2.4rem; letter-spacing:-1px;">Recibos para clientes</h2>
            <p style="color:#64748b; margin-top:8px; font-size:1rem;">Cada recibo gera um link público assinado que você envia ao cliente. Ele pode validar o recibo a qualquer momento pelo código de autenticação.</p>
        </div>
        <a href="{{ route('personal.receipts.create') }}" class="btn-premium">
            <i class="fas fa-plus me-2"></i> Novo Recibo
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="vivensi-card" style="padding:0; overflow:hidden;">
    @if($receipts->isEmpty())
        <div style="padding:60px 30px; text-align:center;">
            <div style="font-size:3rem; color:#cbd5e1; margin-bottom:10px;"><i class="fas fa-file-invoice-dollar"></i></div>
            <h4 style="color:#334155; font-weight:800; margin-bottom:6px;">Nenhum recibo emitido ainda</h4>
            <p style="color:#64748b;">Clique em <strong>Novo Recibo</strong> pra emitir o primeiro.</p>
        </div>
    @else
        <table class="table" style="margin:0;">
            <thead style="background:#f8fafc;">
                <tr>
                    <th style="padding:14px 18px; font-size:.75rem; text-transform:uppercase; letter-spacing:1px; color:#64748b;">Data</th>
                    <th style="padding:14px 18px; font-size:.75rem; text-transform:uppercase; letter-spacing:1px; color:#64748b;">Descrição</th>
                    <th style="padding:14px 18px; font-size:.75rem; text-transform:uppercase; letter-spacing:1px; color:#64748b;">Cliente</th>
                    <th style="padding:14px 18px; font-size:.75rem; text-transform:uppercase; letter-spacing:1px; color:#64748b;">Valor</th>
                    <th style="padding:14px 18px; font-size:.75rem; text-transform:uppercase; letter-spacing:1px; color:#64748b;">Status link</th>
                    <th style="padding:14px 18px; font-size:.75rem; text-transform:uppercase; letter-spacing:1px; color:#64748b; text-align:right;">Ações</th>
                </tr>
            </thead>
            <tbody>
            @foreach($receipts as $t)
                @php
                    $linkAtivo = $t->public_receipt_token && (!$t->public_receipt_expires_at || $t->public_receipt_expires_at->isFuture());
                    $url = $t->public_receipt_token ? url('/r/' . $t->public_receipt_token) : null;
                @endphp
                <tr style="border-top:1px solid #f1f5f9;">
                    <td style="padding:14px 18px; color:#475569;">{{ optional($t->date)->format('d/m/Y') }}</td>
                    <td style="padding:14px 18px; color:#1e293b; font-weight:600;">{{ $t->description }}</td>
                    <td style="padding:14px 18px; color:#64748b;">{{ optional($t->client)->name ?? '—' }}</td>
                    <td style="padding:14px 18px; color:#10b981; font-weight:700;">R$ {{ number_format((float) $t->amount, 2, ',', '.') }}</td>
                    <td style="padding:14px 18px;">
                        @if($linkAtivo)
                            <span style="background:#ecfdf5; color:#047857; font-size:.72rem; font-weight:700; padding:4px 10px; border-radius:99px;">Ativo</span>
                        @else
                            <span style="background:#fef2f2; color:#b91c1c; font-size:.72rem; font-weight:700; padding:4px 10px; border-radius:99px;">Revogado</span>
                        @endif
                    </td>
                    <td style="padding:14px 18px; text-align:right; white-space:nowrap;">
                        @if($linkAtivo && $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary" style="font-size:.75rem;">
                                <i class="fas fa-external-link-alt me-1"></i> Abrir
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;" onclick="navigator.clipboard.writeText('{{ $url }}'); this.innerText='Copiado ✓';">
                                <i class="fas fa-copy me-1"></i> Copiar link
                            </button>
                        @endif
                        <form action="{{ route('personal.receipts.regenerate_link', $t->id) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-warning" style="font-size:.75rem;" onclick="return confirm('Gerar um link novo? O atual deixa de funcionar.')">
                                <i class="fas fa-rotate me-1"></i> Regenerar
                            </button>
                        </form>
                        @if($linkAtivo)
                        <form action="{{ route('personal.receipts.revoke_link', $t->id) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:.75rem;" onclick="return confirm('Revogar este link? O cliente perde o acesso imediatamente.')">
                                <i class="fas fa-ban me-1"></i> Revogar
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div style="padding:14px;">{{ $receipts->links() }}</div>
    @endif
</div>
@endsection
