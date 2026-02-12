@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h6 style="color: #6366f1; font-weight: 700; text-transform: uppercase; margin: 0 0 5px 0;">Marketing Intelligence</h6>
        <h2 style="margin: 0; color: #111827;">Gerador de Estratégias (IA)</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Crie campanhas completas em segundos com Inteligência Artificial.</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="vivensi-card p-5">
            <form action="{{ route('marketing.generate') }}" method="POST">
                @csrf
                <div class="mb-4 text-center">
                    <img src="{{ asset('img/bruce-ai.png') }}" alt="Bruce AI" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid #e0e7ff;">
                    <h3 class="mt-3" style="font-weight: 700;">Bruce AI da Vivensi</h3>
                    <p class="text-muted">Descreva seu objetivo e eu crio toda a estratégia para você.</p>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Objetivo da Campanha</label>
                    <textarea name="goal" class="form-control form-control-lg bg-light border-0" rows="3" placeholder="Ex: Arrecadar fundos para reformar a quadra da escola..." required></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Público-Alvo</label>
                    <input type="text" name="audience" class="form-control form-control-lg bg-light border-0" placeholder="Ex: Empresários locais, pais de alunos..." required>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg py-3 fw-bold shaodw-lg">
                        <i class="fas fa-magic me-2"></i> Gerar Estratégia com IA
                    </button>
                    <small class="text-center text-muted mt-2">Isso pode levar alguns segundos. A IA está pensando...</small>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
