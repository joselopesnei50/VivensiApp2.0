@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Conciliação Bancária</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Importe arquivos OFX e sincronize com seu financeiro.</p>
    </div>
</div>

<div class="vivensi-card" style="max-width: 600px; padding: 40px; text-align: center;">
    <form action="{{ url('/ngo/reconciliation/upload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div style="margin-bottom: 30px;">
            <div style="width: 80px; height: 80px; background: #dbf4ff; color: #0284c7; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 20px;">
                <i class="fas fa-university"></i>
            </div>
            <h3 style="margin-bottom: 10px;">Importar Extrato (OFX)</h3>
            <p style="color: #64748b;">Suportado por: BB, Itaú, Bradesco, Santander, Nubank, Inter.</p>
        </div>

        <input type="file" name="ofx_file" accept=".ofx" class="form-control-vivensi" style="padding: 10px; border: 2px dashed #cbd5e1; background: #f8fafc; cursor: pointer; margin-bottom: 20px;" required>

        <button type="submit" class="btn-premium" style="width: 100%; justify-content: center; font-size: 1.1rem; padding: 15px; background: #0284c7;">
            <i class="fas fa-sync" style="margin-right: 10px;"></i> Processar Arquivo
        </button>
    </form>
</div>
@endsection
