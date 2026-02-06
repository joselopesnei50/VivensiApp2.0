@extends('layouts.app')

@section('content')
<div class="vivensi-card" style="max-width: 760px; margin: 0 auto; padding: 30px;">
    <div style="text-align:center; margin-bottom: 18px;">
        <h2 style="margin:0; color:#0f172a;">Link de assinatura expirado</h2>
        <p style="margin:10px 0 0 0; color:#64748b;">
            Este contrato foi localizado, porém o link público de assinatura não está mais válido.
        </p>
    </div>

    <div style="background:#fff7ed; border:1px solid #fed7aa; padding: 14px 16px; border-radius: 10px; color:#9a3412; line-height:1.4;">
        <strong>O que fazer agora:</strong>
        <ul style="margin:10px 0 0 18px;">
            <li>Solicite um novo link à organização emissora do contrato.</li>
            <li>Se você já assinou, peça uma cópia do documento assinado.</li>
        </ul>
    </div>

    <div style="margin-top: 18px; border-top:1px solid #e2e8f0; padding-top: 16px; color:#64748b;">
        <p style="margin:0;"><strong>Contrato:</strong> {{ $contract->title }}</p>
        <p style="margin:6px 0 0 0;"><strong>Signatário:</strong> {{ $contract->signer_name }}</p>
        @if($contract->document_hash)
            <p style="margin:6px 0 0 0;">
                <strong>Código de autenticidade:</strong>
                <span style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;">
                    {{ strtoupper(substr($contract->document_hash, 0, 16)) }}
                </span>
            </p>
        @endif
    </div>
</div>
@endsection

