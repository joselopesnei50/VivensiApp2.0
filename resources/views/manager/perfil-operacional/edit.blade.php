@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4" style="max-width: 920px;">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1" style="font-weight: 800;">
                <i class="fas fa-compass me-2" style="color: #6366f1;"></i>Perfil Operacional
            </h1>
            <p class="text-muted mb-0">
                Define o contexto do <strong>{{ $tenant->name }}</strong>: rótulos do painel, KPIs do Centro de Comando e instruções do assistente Bruce.
            </p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center">
            <i class="fas fa-check-circle me-2"></i>{{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Não foi possível salvar:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('manager.perfil_operacional.update') }}" class="card shadow-sm">
        @csrf

        <div class="card-body p-4">
            <div class="mb-4">
                <label for="categoria" class="form-label fw-bold">Categoria operacional</label>
                <select name="categoria" id="categoria" class="form-select form-select-lg" required>
                    @foreach ($categorias as $key => $label)
                        <option value="{{ $key }}" @selected(old('categoria', $profile?->categoria ?? 'outro') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted d-block mt-2">
                    Categorias <strong>Campanha Eleitoral</strong> e <strong>Mobilização Social</strong> ativam KPIs específicos
                    e regras LGPD/eleitorais no assistente Bruce.
                </small>
            </div>

            <div class="mb-4">
                <label for="instrucao" class="form-label fw-bold">Instrução adicional para o Bruce</label>
                <textarea
                    name="instrucao"
                    id="instrucao"
                    rows="6"
                    maxlength="2000"
                    class="form-control"
                    placeholder="Ex: foque em editais municipais de cultura; tom acolhedor; prioridade para apoiadores recorrentes."
                >{{ old('instrucao', $profile?->instrucao) }}</textarea>
                <div class="d-flex justify-content-between mt-2">
                    <small class="text-danger fw-semibold">
                        <i class="fas fa-shield-alt me-1"></i>
                        Não inclua nome de pessoa, CPF, telefone, e-mail ou opinião política nominal. Use linguagem genérica.
                    </small>
                    <small class="text-muted">Até 2.000 caracteres.</small>
                </div>
            </div>

            <details class="mb-4">
                <summary class="fw-bold" style="cursor: pointer;">Vocabulário customizado (opcional)</summary>
                <div class="row g-3 mt-2">
                    @php
                        $vocab = old('vocabulario', $profile?->vocabulario ?? []);
                        $rotulosEditaveis = ['lead' => 'Rótulo de lead', 'projeto' => 'Rótulo de projeto', 'equipe' => 'Rótulo de equipe'];
                    @endphp
                    @foreach ($rotulosEditaveis as $key => $label)
                        <div class="col-md-4">
                            <label for="vocab_{{ $key }}" class="form-label">{{ $label }}</label>
                            <input
                                type="text"
                                name="vocabulario[{{ $key }}]"
                                id="vocab_{{ $key }}"
                                value="{{ $vocab[$key] ?? '' }}"
                                maxlength="60"
                                class="form-control"
                                placeholder="padrão da categoria"
                            >
                        </div>
                    @endforeach
                </div>
            </details>
        </div>

        <div class="card-footer bg-white d-flex justify-content-end gap-2 p-3">
            <button type="submit" class="btn btn-primary px-4">
                <i class="fas fa-save me-1"></i> Salvar perfil
            </button>
        </div>
    </form>
</div>
@endsection
