@extends('layouts.app')
@section('title', 'Novo Plano Estratégico')

@section('content')
<div class="container py-4" style="max-width:780px;">

    <div class="mb-4">
        <a href="{{ route('marketing.index') }}" class="text-muted small text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> Voltar
        </a>
        <h4 class="fw-bold mt-2 mb-1" style="color:#1e293b;">
            <i class="fas fa-brain me-2" style="color:#4f46e5;"></i> Novo Plano Estratégico
        </h4>
        <p class="text-muted small">Preencha o briefing abaixo. A IA vai gerar um mapa mental estratégico completo para sua campanha.</p>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4 p-md-5">
            <form action="{{ route('marketing.store') }}" method="POST">
                @csrf

                {{-- Objetivo --}}
                <div class="mb-4">
                    <label class="form-label fw-bold small text-uppercase" style="color:#64748b;letter-spacing:.05em;">
                        <i class="fas fa-bullseye me-1 text-danger"></i> Motivação e Finalidade da Campanha *
                    </label>
                    <textarea name="objective" rows="3" class="form-control rounded-3 @error('objective') is-invalid @enderror"
                        placeholder="Ex: Queremos arrecadar fundos para reforma da sede e ampliar o atendimento de famílias em vulnerabilidade social..."
                        required>{{ old('objective') }}</textarea>
                    @error('objective')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Público-alvo --}}
                <div class="mb-4">
                    <label class="form-label fw-bold small text-uppercase" style="color:#64748b;letter-spacing:.05em;">
                        <i class="fas fa-users me-1 text-primary"></i> Público-alvo e Perfil *
                    </label>
                    <textarea name="target_audience" rows="2" class="form-control rounded-3 @error('target_audience') is-invalid @enderror"
                        placeholder="Ex: Moradores da cidade entre 25-55 anos, empresários locais, doadores recorrentes que já apoiaram projetos similares..."
                        required>{{ old('target_audience') }}</textarea>
                    @error('target_audience')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Abrangência + Tom --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-uppercase" style="color:#64748b;letter-spacing:.05em;">
                            <i class="fas fa-globe me-1 text-success"></i> Abrangência *
                        </label>
                        <select name="scope" class="form-select rounded-3" required>
                            <option value="online" {{ old('scope') == 'online' ? 'selected' : '' }}>Apenas Online</option>
                            <option value="online_offline" {{ old('scope') == 'online_offline' ? 'selected' : '' }}>Online + Presencial</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-uppercase" style="color:#64748b;letter-spacing:.05em;">
                            <i class="fas fa-microphone me-1 text-warning"></i> Tom de Voz *
                        </label>
                        <select name="tone" class="form-select rounded-3" required>
                            <option value="professional" {{ old('tone','professional') == 'professional' ? 'selected' : '' }}>Profissional e Confiante</option>
                            <option value="friendly"     {{ old('tone') == 'friendly'     ? 'selected' : '' }}>Amigável e Próximo</option>
                            <option value="inspirational"{{ old('tone') == 'inspirational' ? 'selected' : '' }}>Inspirador e Motivador</option>
                            <option value="urgent"       {{ old('tone') == 'urgent'       ? 'selected' : '' }}>Urgente e Persuasivo</option>
                        </select>
                    </div>
                </div>

                {{-- Concorrentes --}}
                <div class="mb-4">
                    <label class="form-label fw-bold small text-uppercase" style="color:#64748b;letter-spacing:.05em;">
                        <i class="fas fa-link me-1" style="color:#8b5cf6;"></i> Links de Concorrentes / Referências
                        <span class="fw-normal text-muted text-lowercase">(2 a 3 links, separados por vírgula)</span>
                    </label>
                    <input type="text" name="competitor_links" class="form-control rounded-3"
                        placeholder="https://ong-exemplo.org, https://campanha-referencia.com.br"
                        value="{{ old('competitor_links') }}">
                </div>

                {{-- Orçamento --}}
                <div class="mb-4">
                    <label class="form-label fw-bold small text-uppercase" style="color:#64748b;letter-spacing:.05em;">
                        <i class="fas fa-wallet me-1 text-success"></i> Faixa de Orçamento Disponível
                    </label>
                    <select name="budget_range" class="form-select rounded-3">
                        <option value="">Não informado</option>
                        <option value="Sem orçamento (apenas orgânico)" {{ old('budget_range') == 'Sem orçamento (apenas orgânico)' ? 'selected':'' }}>Sem orçamento (apenas orgânico)</option>
                        <option value="Até R$ 500"    {{ old('budget_range') == 'Até R$ 500'    ? 'selected':'' }}>Até R$ 500</option>
                        <option value="R$ 500 - R$ 2.000" {{ old('budget_range') == 'R$ 500 - R$ 2.000' ? 'selected':'' }}>R$ 500 – R$ 2.000</option>
                        <option value="R$ 2.000 - R$ 10.000" {{ old('budget_range') == 'R$ 2.000 - R$ 10.000' ? 'selected':'' }}>R$ 2.000 – R$ 10.000</option>
                        <option value="Acima de R$ 10.000" {{ old('budget_range') == 'Acima de R$ 10.000' ? 'selected':'' }}>Acima de R$ 10.000</option>
                    </select>
                </div>

                {{-- WhatsApp Groups --}}
                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="has_whatsapp_groups" id="hasWAGroups" value="1"
                            {{ old('has_whatsapp_groups') ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="hasWAGroups">
                            <i class="fab fa-whatsapp text-success me-1"></i>
                            Já possui grupos de WhatsApp ativos com a comunidade
                        </label>
                    </div>
                </div>

                {{-- Info extra --}}
                <div class="mb-4">
                    <label class="form-label fw-bold small text-uppercase" style="color:#64748b;letter-spacing:.05em;">
                        <i class="fas fa-info-circle me-1 text-info"></i> Informações Adicionais Relevantes
                    </label>
                    <textarea name="extra_info" rows="2" class="form-control rounded-3"
                        placeholder="Ex: Temos 2.000 seguidores no Instagram, já realizamos 3 rifas online, nosso maior canal é o WhatsApp...">{{ old('extra_info') }}</textarea>
                </div>

                {{-- Aviso IA --}}
                <div class="alert border-0 rounded-3 mb-4 d-flex gap-3" style="background:#f5f3ff;">
                    <i class="fas fa-magic mt-1" style="color:#7c3aed;flex-shrink:0;"></i>
                    <div style="font-size:.85rem;color:#4c1d95;">
                        <strong>Como funciona:</strong> A IA vai analisar seu briefing e gerar um mapa mental estratégico completo com ações, copys prontas e links diretos para os módulos do Vivensi. O processamento leva entre 10 e 30 segundos.
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <a href="{{ route('marketing.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Cancelar</a>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold flex-fill">
                        <i class="fas fa-wand-magic-sparkles me-2"></i> Gerar Plano Estratégico com IA
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection
