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
                    <th style="padding:14px 18px; font-size:.75rem; text-transform:uppercase; letter-spacing:1px; color:#64748b;">NFS-e</th>
                    <th style="padding:14px 18px; font-size:.75rem; text-transform:uppercase; letter-spacing:1px; color:#64748b;">Link</th>
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
                        @if($t->nfse_numero)
                            <span style="background:#eef2ff; color:#4338ca; font-size:.72rem; font-weight:700; padding:4px 10px; border-radius:99px;">
                                <i class="fas fa-file-circle-check me-1"></i> Nº {{ $t->nfse_numero }}
                            </span>
                        @else
                            <button type="button" class="btn btn-sm" style="background:#f1f5f9; color:#475569; border:1px dashed #cbd5e1; font-size:.72rem; font-weight:700; padding:4px 10px; border-radius:99px;"
                                onclick="document.getElementById('nfseModal{{ $t->id }}').style.display='flex';">
                                <i class="fas fa-paperclip me-1"></i> Anexar
                            </button>
                        @endif
                    </td>
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

                        @if($t->nfse_numero)
                            <a href="{{ route('personal.receipts.nfse.download', $t->id) }}" class="btn btn-sm btn-outline-info" style="font-size:.75rem;" title="Baixar PDF da NFS-e">
                                <i class="fas fa-download me-1"></i> NFS-e
                            </a>
                            <form action="{{ route('personal.receipts.nfse.detach', $t->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;" onclick="return confirm('Remover anexo da NFS-e?')">
                                    <i class="fas fa-times me-1"></i>
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

{{-- Modais de anexar NFS-e (1 por receita sem NFS-e) --}}
@foreach($receipts as $t)
    @if(!$t->nfse_numero)
    <div id="nfseModal{{ $t->id }}" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.55); z-index:9999; align-items:center; justify-content:center; padding:20px;">
        <div style="background:#fff; border-radius:20px; padding:32px; max-width:520px; width:100%; box-shadow:0 25px 50px rgba(0,0,0,.18);">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:18px;">
                <div>
                    <h4 style="margin:0 0 4px 0; font-weight:900; color:#1e293b;">Anexar NFS-e</h4>
                    <p style="margin:0; font-size:.85rem; color:#64748b;">Emita pelo portal <a href="https://www.nfse.gov.br" target="_blank" rel="noopener">nfse.gov.br</a> e cole os dados aqui.</p>
                </div>
                <button type="button" style="background:none; border:none; font-size:1.4rem; color:#94a3b8;" onclick="document.getElementById('nfseModal{{ $t->id }}').style.display='none';">×</button>
            </div>

            <form action="{{ route('personal.receipts.nfse.attach', $t->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-700" style="font-size:.78rem; color:#475569; text-transform:uppercase; letter-spacing:1px;">Número da NFS-e</label>
                    <input type="text" name="nfse_numero" required class="form-control form-control-lg rounded-3" placeholder="Ex: 00000123" maxlength="60">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-700" style="font-size:.78rem; color:#475569; text-transform:uppercase; letter-spacing:1px;">Data de emissão</label>
                    <input type="date" name="nfse_emitida_em" required value="{{ optional($t->date)->toDateString() }}" class="form-control form-control-lg rounded-3">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-700" style="font-size:.78rem; color:#475569; text-transform:uppercase; letter-spacing:1px;">PDF da nota <span style="color:#94a3b8; font-weight:500; text-transform:none; letter-spacing:0;">(opcional, até 5 MB)</span></label>
                    <input type="file" name="pdf" accept="application/pdf" class="form-control rounded-3">
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="submit" style="flex:1; background:linear-gradient(135deg,#10b981,#047857); color:#fff; font-weight:800; padding:12px; border:none; border-radius:12px; box-shadow:0 8px 18px rgba(16,185,129,.3);">
                        <i class="fas fa-paperclip me-2"></i> Salvar NFS-e
                    </button>
                    <button type="button" class="btn btn-outline-secondary rounded-3" style="padding:12px 20px;" onclick="document.getElementById('nfseModal{{ $t->id }}').style.display='none';">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endforeach
@endsection
