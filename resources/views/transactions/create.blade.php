@extends('layouts.app')

@section('content')
<style>
    .type-toggle-premium {
        display: flex;
        background: #f1f5f9;
        padding: 8px;
        border-radius: 20px;
        margin-bottom: 35px;
        border: 2px solid #f1f5f9;
        position: relative;
    }
    .type-option {
        flex: 1;
        text-align: center;
        padding: 14px;
        border-radius: 14px;
        font-weight: 800;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        color: #94a3b8;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        z-index: 1;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .type-option.active-expense {
        background: white;
        color: #ef4444;
        box-shadow: 0 10px 20px rgba(239, 68, 68, 0.1);
    }
    .type-option.active-income {
        background: white;
        color: #10b981;
        box-shadow: 0 10px 20px rgba(16, 185, 129, 0.1);
    }
    .input-premium-icon {
        position: absolute; 
        left: 20px; 
        top: 18px; 
        color: #cbd5e1;
        font-size: 1.1rem;
        transition: color 0.3s;
    }
    .form-control-vivensi:focus + .input-premium-icon {
        color: var(--primary-color);
    }
</style>

<div class="header-page" style="margin-bottom: 40px; position: relative;">
    <div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(239, 68, 68, 0.05) 100%); position: absolute; top: -30px; left: -30px; right: -30px; bottom: 0; z-index: -1;"></div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: #10b981; width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: #10b981; font-weight: 800; text-transform: uppercase; margin: 0; letter-spacing: 2px; font-size: 0.7rem;">Tesouraria & Fluxo</h6>
            </div>
            <h2 style="margin: 0; color: #1e293b; font-weight: 900; font-size: 2.5rem; letter-spacing: -1.5px;">Registrar Movimentação</h2>
            <p style="color: #64748b; margin: 8px 0 0 0; font-size: 1.1rem; font-weight: 500;">Mantenha seu balanço em dia com precisão absoluta.</p>
        </div>
        <a href="{{ url('/transactions') }}" class="btn-premium" style="background: white; color: #1e293b; border: 1px solid #e2e8f0; text-decoration: none; font-weight: 700;">
            <i class="fas fa-list-ul me-2 text-primary"></i> Ver Extrato
        </a>
    </div>
</div>

<div class="vivensi-card" style="max-width: 800px; margin: 0 auto; border: 1px solid #f1f5f9; background: white; padding: 45px; border-radius: 28px; box-shadow: 0 20px 50px rgba(0,0,0,0.02);">
    
    @if ($errors->any())
        <div style="background: #fef2f2; color: #dc2626; padding: 20px; border-radius: 16px; margin-bottom: 30px; border: 1px solid #fecaca; display: flex; gap: 15px; align-items: center;">
            <i class="fas fa-exclamation-triangle"></i>
            <ul style="margin: 0; padding-left: 0; list-style: none; font-weight: 600; font-size: 0.9rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ url('/transactions') }}" method="POST" id="transactionForm" enctype="multipart/form-data">
        @csrf
        
        <!-- Toggle de Tipo -->
        <div class="type-toggle-premium">
            <div class="type-option active-expense" id="toggle-expense" onclick="setType('expense')">
                <i class="fas fa-circle-arrow-down"></i> Saída de Caixa
            </div>
            <div class="type-option" id="toggle-income" onclick="setType('income')">
                <i class="fas fa-circle-arrow-up"></i> Entrada de Fundo
            </div>
            <input type="hidden" name="type" id="type-input" value="expense">
        </div>

        <div style="margin-bottom: 35px;">
            <div class="row g-4">
                <div class="col-md-7">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Valor Principal</label>
                        <div style="position: relative;">
                             <span style="position: absolute; left: 20px; top: 18px; color: #64748b; font-weight: 900; font-size: 1.1rem; z-index: 2;">R$</span>
                             <input type="text" name="amount" id="amount" placeholder="0,00" required 
                                    style="width: 100%; padding: 18px 20px 18px 60px; border: 2px solid #f1f5f9; border-radius: 18px; background: #f8fafc; font-weight: 900; font-size: 1.5rem; color: #1e293b; transition: all 0.3s;"
                                    value="{{ old('amount') }}" onfocus="this.style.borderColor='var(--primary-color)'; this.style.background='white';">
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Data Efetiva</label>
                        <div style="position: relative;">
                            <input type="date" name="date" required 
                                   style="width: 100%; padding: 18px 20px; border: 2px solid #f1f5f9; border-radius: 18px; background: #f8fafc; font-weight: 700; color: #1e293b; font-size: 1.1rem;" 
                                   value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 35px;">
            <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Descrição ou Destino</label>
            <div style="position: relative;">
                <input type="text" name="description" placeholder="Ex: Aquisição de Insumos Hospitalares" required 
                       style="width: 100%; padding: 18px 20px 18px 55px; border: 2px solid #f1f5f9; border-radius: 18px; background: #f8fafc; font-weight: 600; font-size: 1rem; color: #1e293b;" 
                       value="{{ old('description') }}">
                <i class="fas fa-signature input-premium-icon"></i>
            </div>
        </div>

        @if(in_array(auth()->user()->role, ['manager', 'ngo', 'super_admin']))
        <div style="margin-bottom: 45px;">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Vincular Categoria</label>
                        <div style="position: relative;">
                            <select name="category_id" style="width: 100%; padding: 18px 20px 18px 55px; border: 2px solid #f1f5f9; border-radius: 18px; background: #f8fafc; font-weight: 700; color: #1e293b; appearance: none; cursor: pointer;">
                                <option value="">Classificar movimento...</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <i class="fas fa-tags input-premium-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Centro de Custo (Projeto)</label>
                        <div style="position: relative;">
                            <select name="project_id" style="width: 100%; padding: 18px 20px 18px 55px; border: 2px solid #f1f5f9; border-radius: 18px; background: #f8fafc; font-weight: 700; color: #1e293b; appearance: none; cursor: pointer;">
                                <option value="">Sem vínculo direto</option>
                                @foreach($projects as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                            <i class="fas fa-project-diagram input-premium-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="form-group" style="margin-bottom: 45px;">
            <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 700; font-size: 0.9rem;">Comprovante ou Documento Relacionado</label>
            <div style="position: relative;">
                <input type="file" name="attachment" class="form-control" style="padding: 15px; border-radius: 18px; border: 2px dashed #e2e8f0; background: #f8fafc; font-weight: 600;">
                <p style="font-size: 0.75rem; color: #94a3b8; margin-top: 8px; font-weight: 600;">PDF, JPG, PNG ou ZIP (Máx 5MB)</p>
            </div>
        </div>

        <div style="display: flex; gap: 20px; align-items: center; padding-top: 20px; border-top: 1px solid #f1f5f9;">
            <button type="submit" class="btn-premium btn-premium-shine" style="flex: 2; border: none; padding: 20px; font-size: 1.1rem; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 12px;">
                 Confirmar Lançamento <i class="fas fa-check-circle"></i>
            </button>
            <a href="{{ url('/transactions') }}" style="flex: 1; text-align: center; color: #94a3b8; font-weight: 800; font-size: 0.9rem; text-decoration: none; text-transform: uppercase;">Cancelar</a>
        </div>
        <p style="text-align: center; color: #94a3b8; font-size: 0.75rem; font-weight: 700; margin-top: 25px; text-transform: uppercase; letter-spacing: 1px;">
            <i class="fas fa-shield-halved me-1"></i> Auditoria em tempo real ativada
        </p>
    </form>
</div>

<script>
    function setType(type) {
        const toggleExpense = document.getElementById('toggle-expense');
        const toggleIncome = document.getElementById('toggle-income');
        const typeInput = document.getElementById('type-input');
        
        typeInput.value = type;
        
        if (type === 'expense') {
            toggleExpense.classList.add('active-expense');
            toggleIncome.classList.remove('active-income');
        } else {
            toggleExpense.classList.remove('active-expense');
            toggleIncome.classList.add('active-income');
        }
    }

    document.getElementById('amount').addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        value = (value / 100).toFixed(2) + '';
        value = value.replace(".", ",");
        value = value.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
        value = value.replace(/(\d)(\d{3}),/g, "$1.$2,");
        e.target.value = value;
    });
</script>
@endsection
