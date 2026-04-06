@extends('layouts.app')

@section('content')
<div class="header-page mb-4">
    <a href="{{ route('admin.plans.index') }}" class="text-muted text-decoration-none small fw-bold">
        <i class="fas fa-arrow-left me-1"></i> Voltar para Planos
    </a>
    <h2 class="mt-2 fw-bold" style="color: #2c3e50;">Editar Plano: {{ $plan->name }}</h2>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="vivensi-card">
            <form action="{{ route('admin.plans.update', $plan->id) }}" method="POST" id="planForm">
                @csrf
                @method('PUT')
                
                <div class="row g-4">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Nome do Plano</label>
                        <input type="text" name="name" class="form-control-vivensi" required value="{{ old('name', $plan->name) }}" placeholder="Ex: Premium ONG Pro">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Público Alvo</label>
                        <select name="target_audience" class="form-select border-0 bg-light rounded-3 py-3" required>
                            <option value="ngo" {{ old('target_audience', $plan->target_audience) == 'ngo' ? 'selected' : '' }}>Terceiro Setor (ONG)</option>
                            <option value="manager" {{ old('target_audience', $plan->target_audience) == 'manager' ? 'selected' : '' }}>Gestor de Empresas</option>
                            <option value="common" {{ old('target_audience', $plan->target_audience) == 'common' ? 'selected' : '' }}>Pessoa Comum</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Valor Mensal (R$)</label>
                        <div class="input-group">
                            <span class="input-group-text border-0 bg-light">R$</span>
                            <input type="number" step="0.01" name="price" class="form-control-vivensi" required value="{{ old('price', $plan->price) }}" placeholder="0,00">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Valor Anual (R$)</label>
                        <div class="input-group">
                            <span class="input-group-text border-0 bg-light">R$</span>
                            <input type="number" step="0.01" name="price_yearly" class="form-control-vivensi" value="{{ old('price_yearly', $plan->price_yearly) }}" placeholder="0,00">
                        </div>
                    </div>

                    <div class="col-md-6">
                         <label class="form-label fw-bold">ID do Plano Anual (PagSeguro)</label>
                         <input type="text" name="pagseguro_plan_id_yearly" class="form-control-vivensi" value="{{ old('pagseguro_plan_id_yearly', $plan->pagseguro_plan_id_yearly) }}" placeholder="Ex: PLAN_12345">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Intervalo de Cobrança</label>
                        <select name="interval" class="form-select border-0 bg-light rounded-3 py-3" required>
                            <option value="monthly" {{ old('interval', $plan->interval) == 'monthly' ? 'selected' : '' }}>Mensal</option>
                            <option value="yearly" {{ old('interval', $plan->interval) == 'yearly' ? 'selected' : '' }}>Anual</option>
                        </select>
                    </div>

                    <div class="col-md-6 d-flex align-items-center">
                        <div class="form-check form-switch mt-4">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }} id="isActive">
                            <label class="form-check-label fw-bold ms-2" for="isActive">Plano Ativo para Vendas</label>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold d-flex justify-content-between">
                            Recursos / Benefícios
                            <button type="button" class="btn btn-sm btn-outline-primary border-0" onclick="addFeature()">
                                <i class="fas fa-plus me-1"></i> Adicionar Item
                            </button>
                        </label>
                        <div id="featuresList">
                            @if($plan->features)
                                @foreach($plan->features as $feature)
                                    <div class="input-group mb-2">
                                        <input type="text" name="features[]" class="form-control-vivensi" value="{{ $feature }}" placeholder="Ex: IA Ilimitada">
                                        <button type="button" class="btn btn-light" onclick="this.parentElement.remove()">
                                            <i class="fas fa-times text-danger"></i>
                                        </button>
                                    </div>
                                @endforeach
                            @else
                                <div class="input-group mb-2">
                                    <input type="text" name="features[]" class="form-control-vivensi" placeholder="Ex: IA Ilimitada">
                                    <button type="button" class="btn btn-light" onclick="this.parentElement.remove()">
                                        <i class="fas fa-times text-danger"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-5 pt-3 border-top text-end">
                    <button type="submit" class="btn btn-dark px-5 py-3 rounded-pill fw-bold">
                        <i class="fas fa-save me-2"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="alert alert-info border-0 rounded-4 p-4 shadow-sm">
            <h5 class="fw-bold"><i class="fas fa-info-circle me-2"></i> Alteração de Planos</h5>
            <p class="small mb-3">
                Edite as informações que aparecem nas landing pages. Alterar o preço aqui impacta apenas novas assinaturas.
            </p>
            <p class="small mb-0">
                Se este plano já estiver vinculado no <strong>Asaas</strong>, lembre-se de sincronizar as alterações lá também se necessário.
            </p>
        </div>
    </div>
</div>

<script>
    function addFeature() {
        const div = document.createElement('div');
        div.className = 'input-group mb-2';
        div.innerHTML = `
            <input type="text" name="features[]" class="form-control-vivensi" placeholder="Novo benefício">
            <button type="button" class="btn btn-light" onclick="this.parentElement.remove()">
                <i class="fas fa-times text-danger"></i>
            </button>
        `;
        document.getElementById('featuresList').appendChild(div);
    }
</script>

<style>
    .form-control-vivensi {
        background: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px;
        padding: 12px 16px;
        width: 100%;
        transition: all 0.2s;
    }
    .form-control-vivensi:focus {
        border-color: #6366f1 !important;
        background: white !important;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1) !important;
        outline: none;
    }
</style>
@endsection
