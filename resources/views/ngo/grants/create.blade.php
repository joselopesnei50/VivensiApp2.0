@extends('layouts.app')

@section('content')
<div class="vivensi-card" style="max-width: 800px; margin: 0 auto; padding: 40px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h3 style="margin: 0; font-size: 1.5rem; color: #1e293b;">Novo Convênio / Edital</h3>
        <p style="color: #64748b; margin: 5px 0 0 0;">Cadastre os dados oficiais do termo financiador.</p>
    </div>

    @if(!empty($analyzed_data))
        <div class="alert alert-info border-0 rounded-4" style="margin-bottom: 20px;">
            <strong>Bruce AI:</strong> Preenchi automaticamente os campos a partir do edital. Revise antes de salvar.
            @if(!empty($analyzed_data['_ai_notes']))
                <div class="small mt-2" style="white-space: pre-wrap; color:#334155;">{{ $analyzed_data['_ai_notes'] }}</div>
            @endif
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 rounded-4" style="margin-bottom: 20px;">
            <strong>Erro:</strong> {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-warning border-0 rounded-4" style="margin-bottom: 20px;">
            <strong>Atenção:</strong> {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ url('/ngo/grants') }}" method="POST">
        @csrf

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Título / Objeto do Convênio</label>
            <input type="text" name="title" class="form-control-vivensi" required value="{{ old('title', $analyzed_data['title'] ?? '') }}" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="Ex: Termo de Fomento 001/2026 - Cultura Viva">
        </div>

        <div class="grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Concedente (Orgão/Empresa)</label>
                <input type="text" name="grantor_name" class="form-control-vivensi" required value="{{ old('grantor_name', $analyzed_data['grantor_name'] ?? '') }}" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="Ex: Secretaria de Cultura">
            </div>
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Número do Processo/Contrato</label>
                <input type="text" name="contract_number" class="form-control-vivensi" value="{{ old('contract_number', $analyzed_data['contract_number'] ?? '') }}" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="Ex: 231.422.11/2026">
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Valor Global (R$)</label>
            <input type="text" name="total_amount" id="total_amount" inputmode="numeric" class="form-control-vivensi" required value="{{ old('total_amount', $analyzed_data['total_amount'] ?? '') }}" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 1.2rem; font-weight: 700; color: #4f46e5;" placeholder="Ex: 50.000,00">
            <div class="text-muted small" style="margin-top: 8px;">Digite apenas números; o sistema formata automaticamente.</div>
        </div>

        <div class="grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Início da Vigência</label>
                <input type="date" name="start_date" class="form-control-vivensi" value="{{ old('start_date', $analyzed_data['start_date'] ?? '') }}" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;">
            </div>
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Fim da Vigência</label>
                <input type="date" name="end_date" class="form-control-vivensi" required value="{{ old('end_date', $analyzed_data['end_date'] ?? '') }}" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;">
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 30px;">
            <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Observações / Requisitos</label>
            <textarea name="notes" class="form-control-vivensi" rows="5" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="Cole aqui requisitos, objeto, itens de prestação de contas, etc.">{{ old('notes', $analyzed_data['notes'] ?? '') }}</textarea>
            <div class="text-muted small" style="margin-top: 8px;">Dica: quando você importar com IA, colocamos aqui “Objeto” e “Requisitos” encontrados no edital.</div>
        </div>

        <div style="display: flex; gap: 15px;">
            <a href="{{ url('/ngo/grants') }}" class="btn btn-outline" style="flex: 1; display: flex; align-items: center; justify-content: center; text-decoration: none; color: #64748b; border: 1px solid #e2e8f0; border-radius: 10px;">Cancelar</a>
            <button type="submit" class="btn-premium" style="flex: 2; border: none; cursor: pointer; justify-content: center;">Registrar Convênio</button>
        </div>
    </form>
</div>

<script>
    (function () {
        const input = document.getElementById('total_amount');
        if (!input) return;

        const fmt = new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        function formatInitialIfPlainInteger() {
            const raw = (input.value ?? '').trim();
            if (!raw) return;

            // If already has separators, don't touch.
            if (/[.,]/.test(raw)) return;

            // If it's only digits, treat as whole reais (AI often returns 50000 meaning 50k).
            if (/^\d+$/.test(raw)) {
                const n = Number(raw);
                if (Number.isFinite(n)) {
                    input.value = fmt.format(n);
                }
            }
        }

        function maskCurrencyOnType() {
            const digits = (input.value ?? '').toString().replace(/\D/g, '');
            if (!digits) {
                input.value = '';
                return;
            }
            const cents = Number(digits);
            if (!Number.isFinite(cents)) return;
            input.value = fmt.format(cents / 100);
        }

        formatInitialIfPlainInteger();
        input.addEventListener('input', maskCurrencyOnType);
        input.addEventListener('blur', () => {
            if ((input.value ?? '').trim() === '') return;
            // Ensure final is normalized
            maskCurrencyOnType();
        });
    })();
</script>
@endsection
