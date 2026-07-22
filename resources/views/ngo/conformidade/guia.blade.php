@extends('layouts.app')

@section('content')

<style>
.guia-step { border-radius:16px; border:1px solid #e2e8f0; background:#fff; padding:24px 28px; margin-bottom:16px; }
.guia-step-num { width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.95rem; flex-shrink:0; }
.guia-tip { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:12px 16px; font-size:.82rem; color:#166534; }
.guia-warn { background:#fffbeb; border:1px solid #fde68a; border-radius:12px; padding:12px 16px; font-size:.82rem; color:#92400e; }
.tipo-pill { display:inline-flex; align-items:center; gap:6px; font-size:.75rem; font-weight:700; padding:4px 12px; border-radius:20px; }
.tipo-a { background:#e0e7ff; color:#3730a3; }
.tipo-b { background:#fce7f3; color:#9d174d; }
.tipo-c { background:#d1fae5; color:#065f46; }
.res-badge-sm { font-size:.7rem; font-weight:700; padding:2px 10px; border-radius:20px; display:inline-block; }
</style>

{{-- Header --}}
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('ngo.conformidade.dashboard') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
    <div>
        <h3 class="fw-bold mb-0">Guia de Conformidade</h3>
        <small class="text-muted">Passo a passo para configurar e manter a conformidade da sua organização</small>
    </div>
</div>

{{-- Intro --}}
<div class="mb-4 p-4" style="background:linear-gradient(135deg,#1a1a2e,#0f3460);border-radius:20px;color:#fff">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h5 class="fw-bold mb-2">O que é o Motor de Conformidade Contínua?</h5>
            <p class="mb-0" style="opacity:.85;font-size:.9rem">
                O sistema monitora automaticamente o cumprimento dos requisitos legais do <strong>CEBAS</strong>,
                <strong>MROSC</strong> e <strong>SUAS</strong>. Ele calcula um índice de conformidade (0–100%)
                baseado nos dados reais da sua organização: atendimentos, documentos, declarações e projetos.
                Siga os passos abaixo para deixar tudo configurado corretamente.
            </p>
        </div>
        <div class="col-md-4 text-center mt-3 mt-md-0" style="font-size:3.5rem">📊</div>
    </div>
</div>

{{-- Passo 1 --}}
<div class="guia-step">
    <div class="d-flex align-items-start gap-3">
        <div class="guia-step-num" style="background:#dbeafe;color:#1d4ed8">1</div>
        <div class="flex-grow-1">
            <h5 class="fw-bold mb-1">Configure o perfil da organização</h5>
            <p class="text-muted mb-3" style="font-size:.85rem">
                Acesse <a href="{{ route('ngo.conformidade.configurar') }}" class="fw-semibold">Conformidade → Configurar</a>.
                Esses dados são usados pelo motor para calcular requisitos automáticos.
            </p>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0">
                        <div class="fw-semibold small mb-2"><i class="bi bi-search me-1 text-primary"></i>Busca automática por CNPJ</div>
                        <div class="text-muted" style="font-size:.8rem">Digite o CNPJ e clique em <strong>Buscar</strong>. O sistema preenche automaticamente o CNAE principal e a data de fundação.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0">
                        <div class="fw-semibold small mb-2"><i class="bi bi-card-list me-1 text-success"></i>Campos obrigatórios</div>
                        <ul class="text-muted mb-0" style="font-size:.8rem;padding-left:16px">
                            <li><strong>CNAS</strong> — número e validade da certidão</li>
                            <li><strong>CMAS</strong> — número e validade do registro</li>
                            <li><strong>CNEAS</strong> — código de inscrição</li>
                            <li><strong>Área de atuação CEBAS</strong> — Assistência Social, Saúde ou Educação</li>
                            <li><strong>Data de fundação</strong> — usada para calcular tempo de existência</li>
                            <li><strong>Receita bruta anual</strong> — referência para gratuidade</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="guia-tip mt-3">
                <i class="bi bi-lightbulb me-1"></i>
                <strong>Dica:</strong> mantenha as validades do CNAS e CMAS sempre atualizadas. O sistema alerta automaticamente quando um documento está próximo do vencimento.
            </div>
        </div>
    </div>
</div>

{{-- Passo 2 --}}
<div class="guia-step">
    <div class="d-flex align-items-start gap-3">
        <div class="guia-step-num" style="background:#fce7f3;color:#9d174d">2</div>
        <div class="flex-grow-1">
            <h5 class="fw-bold mb-1">Configure os ciclos de conformidade</h5>
            <p class="text-muted mb-3" style="font-size:.85rem">
                Acesse <a href="{{ route('ngo.conformidade.ciclos') }}" class="fw-semibold">Conformidade → Ciclos</a>.
                O ciclo define o <strong>período de avaliação</strong> de cada eixo regulatório.
            </p>
            <div class="row g-3">
                <div class="col-md-4 col-6">
                    <div class="p-3 rounded-3 text-center" style="background:#fdf4ff;border:1px solid #e9d5ff">
                        <div style="font-size:1.4rem">🛡️</div>
                        <div class="fw-semibold small mt-1">CEBAS</div>
                        <div class="text-muted" style="font-size:.75rem">Ciclo de 3 ou 5 anos conforme enquadramento</div>
                    </div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="p-3 rounded-3 text-center" style="background:#f0fdf4;border:1px solid #bbf7d0">
                        <div style="font-size:1.4rem">📄</div>
                        <div class="fw-semibold small mt-1">MROSC</div>
                        <div class="text-muted" style="font-size:.75rem">Por parceria (vigência do instrumento)</div>
                    </div>
                </div>
                <div class="col-md-4 col-6">
                    <div class="p-3 rounded-3 text-center" style="background:#eff6ff;border:1px solid #bfdbfe">
                        <div style="font-size:1.4rem">🏠</div>
                        <div class="fw-semibold small mt-1">SUAS</div>
                        <div class="text-muted" style="font-size:.75rem">Ciclo anual (jan–dez)</div>
                    </div>
                </div>
            </div>
            <div class="guia-warn mt-3">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <strong>Atenção:</strong> sem um ciclo ativo configurado para o eixo, os requisitos daquele eixo não serão avaliados e aparecerão como N/A no dashboard.
            </div>
        </div>
    </div>
</div>

{{-- Passo 3 --}}
<div class="guia-step">
    <div class="d-flex align-items-start gap-3">
        <div class="guia-step-num" style="background:#d1fae5;color:#065f46">3</div>
        <div class="flex-grow-1">
            <h5 class="fw-bold mb-1">Entenda os tipos de requisito</h5>
            <p class="text-muted mb-3" style="font-size:.85rem">
                Cada eixo possui requisitos de diferentes tipos. Clique em qualquer eixo no dashboard para vê-los.
            </p>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="p-3 h-100 rounded-3" style="background:#f5f3ff;border:1px solid #ddd6fe">
                        <div class="mb-2"><span class="tipo-pill tipo-a">Tipo A — Automático</span></div>
                        <div class="text-muted" style="font-size:.8rem">Calculado automaticamente pelo sistema com base nos dados existentes: atendimentos, transações financeiras, projetos e frequência. <strong>Nenhuma ação é necessária</strong> — mantenha os dados do sistema atualizados.</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 h-100 rounded-3" style="background:#fdf2f8;border:1px solid #fbcfe8">
                        <div class="mb-2"><span class="tipo-pill tipo-b">Tipo B — Documento</span></div>
                        <div class="text-muted" style="font-size:.8rem">Exige o <strong>upload de um documento</strong> válido (ex: CNAS, ata de reunião, balanço patrimonial). Use o botão <strong>"Enviar Documento"</strong> no requisito. Informe a validade para que o sistema monitore o vencimento.</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 h-100 rounded-3" style="background:#f0fdf4;border:1px solid #bbf7d0">
                        <div class="mb-2"><span class="tipo-pill tipo-c">Tipo C — Declaração</span></div>
                        <div class="text-muted" style="font-size:.8rem">Requer uma <strong>declaração formal</strong> de que o requisito está sendo cumprido. Use o botão <strong>"Declarar"</strong>. Descreva como a organização atende ao requisito (mínimo 20 caracteres).</div>
                    </div>
                </div>
            </div>
            <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0">
                <div class="fw-semibold small mb-2">Resultados possíveis por requisito:</div>
                <div class="d-flex gap-3 flex-wrap" style="font-size:.8rem">
                    <div><span class="res-badge-sm" style="background:#d1fae5;color:#065f46">Verde</span> — requisito atendido, dentro do limite</div>
                    <div><span class="res-badge-sm" style="background:#fef9c3;color:#713f12">Atenção</span> — abaixo do ideal mas ainda aceitável</div>
                    <div><span class="res-badge-sm" style="background:#fee2e2;color:#7f1d1d">Crítico</span> — não conformidade, ação necessária</div>
                    <div><span class="res-badge-sm" style="background:#f1f5f9;color:#64748b">N/A</span> — não avaliado (sem ciclo ativo)</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Passo 4 --}}
<div class="guia-step">
    <div class="d-flex align-items-start gap-3">
        <div class="guia-step-num" style="background:#fce7f3;color:#9d174d">4</div>
        <div class="flex-grow-1">
            <h5 class="fw-bold mb-1">Envie documentos obrigatórios <span class="tipo-pill tipo-b ms-2">Tipo B</span></h5>
            <p class="text-muted mb-3" style="font-size:.85rem">
                Para requisitos do Tipo B, clique em <strong>"Enviar Documento"</strong> no eixo correspondente.
            </p>
            <ol class="text-muted mb-3" style="font-size:.85rem;padding-left:20px">
                <li class="mb-2">Navegue até o eixo (ex: CEBAS Geral) e localize o requisito com status <span class="res-badge-sm" style="background:#fee2e2;color:#7f1d1d">Crítico</span> ou <span class="res-badge-sm" style="background:#fef9c3;color:#713f12">Atenção</span>.</li>
                <li class="mb-2">Clique em <strong>"Enviar Documento"</strong> para abrir o formulário de upload.</li>
                <li class="mb-2">Selecione o arquivo: <strong>PDF, JPG ou PNG</strong>, máximo <strong>10 MB</strong>.</li>
                <li class="mb-2">Informe a <strong>data de validade</strong> do documento (ex: vencimento do CNAS).</li>
                <li>Clique em <strong>Enviar</strong>. O índice será recalculado automaticamente.</li>
            </ol>
            <div class="guia-tip">
                <i class="bi bi-lightbulb me-1"></i>
                O sistema mantém o <strong>histórico de versões</strong> — ao enviar um documento novo, o anterior é automaticamente marcado como substituído. Você pode ver o histórico clicando em "Histórico" no requisito.
            </div>
        </div>
    </div>
</div>

{{-- Passo 5 --}}
<div class="guia-step">
    <div class="d-flex align-items-start gap-3">
        <div class="guia-step-num" style="background:#d1fae5;color:#065f46">5</div>
        <div class="flex-grow-1">
            <h5 class="fw-bold mb-1">Faça declarações formais <span class="tipo-pill tipo-c ms-2">Tipo C</span></h5>
            <p class="text-muted mb-3" style="font-size:.85rem">
                Para requisitos do Tipo C, clique em <strong>"Declarar"</strong> no eixo correspondente.
            </p>
            <ol class="text-muted mb-3" style="font-size:.85rem;padding-left:20px">
                <li class="mb-2">Localize o requisito Tipo C com botão <strong>"Declarar"</strong> (aparece quando o status não é Verde).</li>
                <li class="mb-2">No modal, leia a pergunta de declaração exibida pelo sistema.</li>
                <li class="mb-2">Descreva detalhadamente como a organização cumpre o requisito (<strong>mínimo 20 caracteres</strong>).</li>
                <li class="mb-2">Marque o <strong>checkbox de ciência</strong> para confirmar a responsabilidade.</li>
                <li>Clique em <strong>Confirmar Declaração</strong>. A declaração é registrada com data, hora e usuário.</li>
            </ol>
            <div class="guia-warn">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <strong>Responsabilidade legal:</strong> ao declarar, o responsável confirma a veracidade das informações sob sua responsabilidade. Todas as declarações ficam registradas no histórico do requisito.
            </div>
        </div>
    </div>
</div>

{{-- Passo 6 --}}
<div class="guia-step">
    <div class="d-flex align-items-start gap-3">
        <div class="guia-step-num" style="background:#fef3c7;color:#92400e">6</div>
        <div class="flex-grow-1">
            <h5 class="fw-bold mb-1">Crie Planos de Ação para não-conformidades</h5>
            <p class="text-muted mb-3" style="font-size:.85rem">
                Acesse <a href="{{ route('ngo.conformidade.planos.index') }}" class="fw-semibold">Conformidade → Planos de Ação</a>.
                Use os planos para documentar e acompanhar as ações corretivas de requisitos em Amarelo ou Vermelho.
            </p>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0">
                        <div class="fw-semibold small mb-2">Como criar um plano</div>
                        <ol class="text-muted mb-0" style="font-size:.8rem;padding-left:16px">
                            <li>Clique em <strong>Novo Plano</strong></li>
                            <li>Selecione o requisito em não-conformidade</li>
                            <li>Descreva as ações a serem tomadas</li>
                            <li>Defina o <strong>responsável</strong> e o <strong>prazo</strong></li>
                        </ol>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0">
                        <div class="fw-semibold small mb-2">Status do plano</div>
                        <div style="font-size:.8rem;color:#475569">
                            <div class="mb-1"><span class="res-badge-sm" style="background:#f1f5f9;color:#475569">Pendente</span> — aguardando início</div>
                            <div class="mb-1"><span class="res-badge-sm" style="background:#e0e7ff;color:#3730a3">Em Andamento</span> — ações em execução</div>
                            <div class="mb-1"><span class="res-badge-sm" style="background:#d1fae5;color:#065f46">Concluído</span> — não-conformidade resolvida</div>
                            <div><span class="res-badge-sm" style="background:#fee2e2;color:#7f1d1d">Cancelado</span> — plano não será executado</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="guia-tip">
                <i class="bi bi-lightbulb me-1"></i>
                Nos eixos, requisitos em <strong>Amarelo</strong> ou <strong>Vermelho</strong> exibem um botão <strong>"Plano"</strong> que leva direto para a criação de um plano de ação pré-vinculado ao requisito.
            </div>
        </div>
    </div>
</div>

{{-- Passo 7 --}}
<div class="guia-step">
    <div class="d-flex align-items-start gap-3">
        <div class="guia-step-num" style="background:#e0e7ff;color:#3730a3">7</div>
        <div class="flex-grow-1">
            <h5 class="fw-bold mb-1">Gere relatórios e acompanhe a evolução</h5>
            <p class="text-muted mb-3" style="font-size:.85rem">
                No <a href="{{ route('ngo.conformidade.dashboard') }}" class="fw-semibold">dashboard de conformidade</a>, você tem acesso a todas as ferramentas de exportação e histórico.
            </p>
            <div class="row g-3">
                <div class="col-md-6 col-lg-3">
                    <div class="p-3 rounded-3 h-100 text-center" style="background:#f0fdf4;border:1px solid #bbf7d0">
                        <div style="font-size:1.5rem">📄</div>
                        <div class="fw-semibold small mt-1">RMA</div>
                        <div class="text-muted" style="font-size:.75rem">Relatório Mensal de Atendimentos (PDF) para prestação ao SUAS</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="p-3 rounded-3 h-100 text-center" style="background:#eff6ff;border:1px solid #bfdbfe">
                        <div style="font-size:1.5rem">🛡️</div>
                        <div class="fw-semibold small mt-1">Dossiê CEBAS</div>
                        <div class="text-muted" style="font-size:.75rem">Consolidado para renovação do CEBAS com todos os índices</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="p-3 rounded-3 h-100 text-center" style="background:#fdf4ff;border:1px solid #e9d5ff">
                        <div style="font-size:1.5rem">📊</div>
                        <div class="fw-semibold small mt-1">Relatório MROSC</div>
                        <div class="text-muted" style="font-size:.75rem">Para prestação de contas de parcerias e convênios</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="p-3 rounded-3 h-100 text-center" style="background:#fffbeb;border:1px solid #fde68a">
                        <div style="font-size:1.5rem">📸</div>
                        <div class="fw-semibold small mt-1">Snapshot</div>
                        <div class="text-muted" style="font-size:.75rem">Registra o índice atual no histórico para acompanhar evolução no gráfico</div>
                    </div>
                </div>
            </div>
            <div class="guia-tip mt-3">
                <i class="bi bi-lightbulb me-1"></i>
                Tire um <strong>Snapshot</strong> mensalmente para acompanhar a evolução do índice no gráfico histórico do dashboard.
            </div>
        </div>
    </div>
</div>

{{-- CTA final --}}
<div class="text-center py-4 mt-2 mb-3" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-radius:20px">
    <div style="font-size:2.5rem">✅</div>
    <h5 class="fw-bold mt-2 mb-1">Pronto para começar?</h5>
    <p class="text-muted small mb-3">Siga os passos na ordem acima para obter o índice de conformidade mais preciso possível.</p>
    <div class="d-flex gap-2 justify-content-center flex-wrap">
        <a href="{{ route('ngo.conformidade.configurar') }}" class="btn btn-success btn-sm fw-semibold px-4">
            <i class="bi bi-gear me-1"></i> Configurar Perfil
        </a>
        <a href="{{ route('ngo.conformidade.dashboard') }}" class="btn btn-outline-secondary btn-sm fw-semibold px-4">
            <i class="bi bi-speedometer2 me-1"></i> Ver Dashboard
        </a>
    </div>
</div>

@endsection
