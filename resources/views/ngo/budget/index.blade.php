@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; gap: 20px;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Orçamento Anual - {{ $year }}</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Planejamento Financeiro vs Realizado.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <form action="{{ url('/ngo/budget') }}" method="GET" style="display: flex; gap: 5px;">
            <select name="year" class="form-control-vivensi" onchange="this.form.submit()" style="padding: 8px; border-radius: 8px;">
                @for($i = date('Y')-2; $i <= date('Y')+2; $i++)
                    <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>{{ $i }}</option>
                @endfor
            </select>
        </form>
        <button onclick="toggleModal()" class="btn-premium">
            <i class="fas fa-edit"></i> Ajustar Metas
        </button>
    </div>
</div>

<div class="grid-2" style="margin-bottom: 30px;">
    <div class="vivensi-card" style="border-left: 5px solid #16a34a;">
        <p style="text-transform: uppercase; font-size: 0.75rem; color: #64748b; font-weight: 700;">Previsto de Receitas (Ano)</p>
        <h3 style="margin: 10px 0; font-size: 1.8rem;">R$ {{ number_format($targets->where('type', 'income')->sum('amount'), 2, ',', '.') }}</h3>
        <p style="font-size: 0.85rem; color: #16a34a;">Realizado: R$ {{ number_format($realized->where('type', 'income')->sum('total'), 2, ',', '.') }}</p>
    </div>
    <div class="vivensi-card" style="border-left: 5px solid #ef4444;">
        <p style="text-transform: uppercase; font-size: 0.75rem; color: #64748b; font-weight: 700;">Teto de Gastos (Ano)</p>
        <h3 style="margin: 10px 0; font-size: 1.8rem;">R$ {{ number_format($targets->where('type', 'expense')->sum('amount'), 2, ',', '.') }}</h3>
        <p style="font-size: 0.85rem; color: #ef4444;">Gasto: R$ {{ number_format($realized->where('type', 'expense')->sum('total'), 2, ',', '.') }}</p>
    </div>
</div>

<div class="vivensi-card">
    <h3 style="font-size: 1.1rem; margin-bottom: 25px;">Distribuição por Categorias (Despesas)</h3>
    
    @foreach($expenseCategories as $category)
        @php
            $target = $targets->where('category_id', $category->id)->where('type', 'expense')->first();
            $real = $realized->where('category_id', $category->id)->where('type', 'expense')->first();
            $planned = $target ? $target->amount : 0;
            $spent = $real ? $real->total : 0;
            $percent = $planned > 0 ? min(($spent / $planned) * 100, 100) : 0;
            $color = $percent > 90 ? '#ef4444' : ($percent > 70 ? '#f59e0b' : '#3b82f6');
        @endphp
        
        @if($planned > 0 || $spent > 0)
        <div style="margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.9rem;">
                <span style="font-weight: 600; color: #1e293b;">{{ $category->name }}</span>
                <span style="color: #64748b;">
                    R$ {{ number_format($spent, 2, ',', '.') }} / <strong>R$ {{ number_format($planned, 2, ',', '.') }}</strong>
                </span>
            </div>
            <div style="width: 100%; height: 10px; background: #f1f5f9; border-radius: 10px; overflow: hidden;">
                <div style="width: {{ $percent }}%; height: 100%; background: {{ $color }}; border-radius: 10px; transition: width 0.5s;"></div>
            </div>
        </div>
        @endif
    @endforeach
</div>

<!-- Modal para Ajustar Metas -->
<div id="budgetModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div class="vivensi-card" style="width: 90%; max-width: 650px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;">Definir Metas Anuais - {{ $year }}</h3>
            <button onclick="toggleModal()" style="border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        
        <form action="{{ url('/ngo/budget') }}" method="POST">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <div>
                    <h4 style="font-size: 0.9rem; color: #16a34a; text-transform: uppercase; margin-bottom: 15px; border-bottom: 2px solid #dcfce7; padding-bottom: 5px;">Previsto de Receita</h4>
                    @foreach($incomeCategories as $category)
                        @php
                            $target = $targets->where('category_id', $category->id)->where('type', 'income')->first();
                        @endphp
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="display: block; font-size: 0.8rem; margin-bottom: 5px; color: #64748b;">{{ $category->name }}</label>
                            <input type="number" step="0.01" name="targets[{{ $category->id }}]" value="{{ $target ? $target->amount : '' }}" placeholder="0,00" class="form-control-vivensi" style="padding: 8px;">
                        </div>
                    @endforeach
                </div>

                <div>
                    <h4 style="font-size: 0.9rem; color: #ef4444; text-transform: uppercase; margin-bottom: 15px; border-bottom: 2px solid #fee2e2; padding-bottom: 5px;">Teto de Despesa</h4>
                    @foreach($expenseCategories as $category)
                        @php
                            $target = $targets->where('category_id', $category->id)->where('type', 'expense')->first();
                        @endphp
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="display: block; font-size: 0.8rem; margin-bottom: 5px; color: #64748b;">{{ $category->name }}</label>
                            <input type="number" step="0.01" name="targets[{{ $category->id }}]" value="{{ $target ? $target->amount : '' }}" placeholder="0,00" class="form-control-vivensi" style="padding: 8px;">
                        </div>
                    @endforeach
                </div>
            </div>
            
            <div style="margin-top: 30px; display: flex; gap: 10px;">
                <button type="submit" class="btn-premium" style="flex: 1; justify-content: center;">Salvar Orçamento Completo</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleModal() {
        const modal = document.getElementById('budgetModal');
        modal.style.display = modal.style.display === 'none' ? 'flex' : 'none';
    }
</script>
@endsection
