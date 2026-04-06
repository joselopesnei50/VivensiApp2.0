@extends('layouts.app')

@section('content')
<style>
    .donor-edit-card {
        background: white;
        border-radius: 24px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.03);
        border: 1px solid #f1f5f9;
        max-width: 600px;
        margin: 0 auto;
        overflow: hidden;
    }
    .donor-edit-header {
        padding: 30px;
        background: #f8fafc;
        border-bottom: 1px solid #f1f5f9;
        text-align: center;
    }
    .donor-edit-body {
        padding: 40px;
    }
    .form-label-premium {
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: 8px;
        letter-spacing: 0.05em;
    }
    .form-input-premium {
        width: 100%;
        padding: 12px 16px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        font-size: 1rem;
        margin-bottom: 20px;
    }
</style>

<div class="container py-4">
    <div class="d-flex align-items-center gap-3 mb-4 justify-content-center">
        <a href="{{ url('/ngo/donors') }}" class="btn btn-light rounded-circle shadow-sm border" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h3 class="fw-bold text-dark m-0">Editar Doador</h3>
    </div>

    <div class="donor-edit-card">
        <div class="donor-edit-header">
            <div class="avatar-ngo mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                {{ substr($donor->name, 0, 1) }}
            </div>
            <h5 class="fw-bold text-dark m-0">{{ $donor->name }}</h5>
            <p class="text-muted small m-0">Atualize as informações do parceiro.</p>
        </div>
        
        <div class="donor-edit-body">
            @if ($errors->any())
                <div class="alert alert-danger border-0 rounded-4">
                    <ul class="m-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ url('/ngo/donors/'.$donor->id) }}" method="POST">
                @csrf
                @method('PUT')

                <label class="form-label-premium">Nome / Razão Social</label>
                <input type="text" name="name" class="form-input-premium" value="{{ $donor->name }}" required>

                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label-premium">Tipo</label>
                        <select name="type" class="form-input-premium" required>
                            <option value="individual" {{ $donor->type == 'individual' ? 'selected' : '' }}>Pessoa Física</option>
                            <option value="company" {{ $donor->type == 'company' ? 'selected' : '' }}>Empresa (PJ)</option>
                            <option value="government" {{ $donor->type == 'government' ? 'selected' : '' }}>Governo</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-premium">CPF / CNPJ</label>
                        <input type="text" name="document" class="form-input-premium" value="{{ $donor->document }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label-premium">E-mail</label>
                        <input type="email" name="email" class="form-input-premium" value="{{ $donor->email }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-premium">Telefone / WhatsApp</label>
                        <input type="text" name="phone" class="form-input-premium" value="{{ $donor->phone }}">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn-premium w-100 py-3 border-0">
                        <i class="fas fa-save me-2"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
