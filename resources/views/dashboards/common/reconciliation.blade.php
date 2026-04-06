@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h6 style="color: #6366f1; font-weight: 700; text-transform: uppercase; margin: 0 0 5px 0;">Conciliação</h6>
        <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">Sincronizar Banco</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Importe seu extrato OFX para organizar suas finanças automaticamente.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="vivensi-card text-center" style="padding: 50px;">
            <i class="fas fa-file-invoice-dollar" style="font-size: 4rem; color: #6366f1; opacity: 0.2; margin-bottom: 25px;"></i>
            <h4>Importar Arquivo OFX</h4>
            <p class="text-muted mb-4">Selecione o arquivo exportado do seu internet banking.</p>
            
            <form action="{{ url('/personal/reconciliation/upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="input-group mb-3">
                    <input type="file" name="ofx_file" class="form-control" accept=".ofx">
                    <button class="btn btn-primary" type="submit">Processar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
