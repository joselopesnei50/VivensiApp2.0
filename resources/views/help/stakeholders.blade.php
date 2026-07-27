@extends('layouts.app')
@section('title', 'Guia — Cadastro de Stakeholders')

@section('content')
<div style="max-width:820px; margin:0 auto;">

    {{-- Cabecalho com Bruce --}}
    <div style="background:linear-gradient(135deg,#1e1b4b,#4f46e5); border-radius:24px; padding:36px 32px; color:white; display:flex; align-items:center; gap:20px; margin-bottom:28px; box-shadow:0 20px 40px rgba(79,70,229,0.2);">
        <div style="width:76px; height:76px; border-radius:50%; background:white; padding:6px; box-shadow:0 4px 12px rgba(0,0,0,0.15); flex-shrink:0;">
            <img src="{{ asset('img/bruce/bruceia-icone-fundo-claro.svg') }}" alt="Bruce IA"
                 style="width:100%; height:100%; object-fit:contain;">
        </div>
        <div style="min-width:0;">
            <div style="font-weight:900; font-size:1.6rem; letter-spacing:-0.5px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                Ola, aqui e o Bruce
                <span style="font-size:0.62rem; font-weight:800; background:rgba(16,185,129,0.25); color:#a7f3d0; padding:3px 10px; border-radius:10px; letter-spacing:0.5px; text-transform:uppercase;">Assistente</span>
            </div>
            <p style="margin:6px 0 0; opacity:0.9; font-size:0.95rem; line-height:1.5;">
                Vou te ajudar a entender como funciona o cadastro de pessoas no projeto e qual tipo escolher.
            </p>
        </div>
    </div>

    {{-- Fala do Bruce: intro --}}
    <div style="background:#f8fafc; border-left:4px solid #6366f1; padding:20px 24px; border-radius:12px; margin-bottom:32px;">
        <div style="font-weight:800; color:#4f46e5; font-size:0.85rem; margin-bottom:6px;">
            <i class="fas fa-comment-dots me-1"></i>Bruce
        </div>
        <p style="margin:0; color:#334155; font-size:0.95rem; line-height:1.7;">
            Quando voce clica em <strong>"Expandir Stakeholders"</strong> dentro do projeto, aparece um modal
            com duas abas: <strong>"Vincular Ativo"</strong> e <strong>"Novo Credenciamento"</strong>. Cada uma
            resolve uma coisa diferente. Vou explicar em ordem.
        </p>
    </div>

    {{-- Vincular Ativo --}}
    <div class="vivensi-card" style="padding:28px; border-radius:20px; margin-bottom:24px;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
            <div style="width:44px; height:44px; border-radius:12px; background:#eef2ff; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fas fa-user-plus" style="color:#6366f1; font-size:1.1rem;"></i>
            </div>
            <h4 style="margin:0; font-weight:900; color:#0f172a; font-size:1.15rem;">Aba 1 — Vincular Ativo</h4>
        </div>
        <p style="color:#475569; font-size:0.92rem; line-height:1.7; margin:0 0 14px;">
            Use quando a pessoa <strong>ja tem conta na organizacao</strong> — outro coordenador, um analista,
            um voluntario que ja atua em outros projetos. Voce so escolhe o nome dele na lista, define o
            <strong>protocolo de acesso</strong> (Viewer, Editor ou Admin) e pronto: ele passa a ver o
            projeto na tela dele tambem.
        </p>
        <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:12px 16px; color:#1e40af; font-size:0.85rem;">
            <i class="fas fa-lightbulb me-1"></i>
            <strong>Dica:</strong> essa aba nao cria conta nova. Se a pessoa ainda nao existe no sistema,
            use a aba <em>Novo Credenciamento</em>.
        </div>
    </div>

    {{-- Novo Credenciamento --}}
    <div class="vivensi-card" style="padding:28px; border-radius:20px; margin-bottom:24px;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
            <div style="width:44px; height:44px; border-radius:12px; background:#fef3c7; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fas fa-id-badge" style="color:#d97706; font-size:1.1rem;"></i>
            </div>
            <h4 style="margin:0; font-weight:900; color:#0f172a; font-size:1.15rem;">Aba 2 — Novo Credenciamento</h4>
        </div>
        <p style="color:#475569; font-size:0.92rem; line-height:1.7; margin:0 0 20px;">
            Use quando a pessoa <strong>ainda nao tem conta</strong> na sua organizacao. Aqui voce cria o
            login com nome + email. Um link para definir a propria senha e enviado por e-mail — voce
            nunca precisa saber a senha dele.
        </p>

        <div style="font-weight:800; color:#0f172a; font-size:0.95rem; margin:20px 0 10px;">
            E ai vem a decisao mais importante:
        </div>

        {{-- Colaborador --}}
        <div style="border:2px solid #e2e8f0; border-radius:14px; padding:18px 20px; margin-bottom:14px;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                <i class="fas fa-user-tie" style="color:#64748b; font-size:1.05rem;"></i>
                <div style="font-weight:800; color:#0f172a; font-size:0.95rem;">Colaborador (padrao)</div>
            </div>
            <p style="color:#475569; font-size:0.88rem; line-height:1.65; margin:0 0 8px;">
                Cria uma conta comum. A pessoa tem acesso ao <strong>painel completo da organizacao</strong>:
                dashboard, doadores, financeiro, tarefas, WhatsApp — dependendo do que a role dela permitir.
            </p>
            <div style="color:#64748b; font-size:0.82rem;">
                <i class="fas fa-check-circle me-1" style="color:#10b981;"></i>Use para pessoal interno da organizacao (analistas, coordenadores, voluntarios de longo prazo).
            </div>
        </div>

        {{-- Credenciado --}}
        <div style="border:2px solid #fcd34d; background:#fffbeb; border-radius:14px; padding:18px 20px;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                <i class="fas fa-lock" style="color:#d97706; font-size:1.05rem;"></i>
                <div style="font-weight:800; color:#78350f; font-size:0.95rem;">Credenciado (acesso exclusivo ao projeto)</div>
            </div>
            <p style="color:#78350f; font-size:0.88rem; line-height:1.65; margin:0 0 10px;">
                Marque o <strong>checkbox ambar "Acesso exclusivo a este projeto"</strong> no fim do formulario.
                A pessoa entra em um <strong>painel proprio, minimalista</strong>, e so ve o projeto onde foi
                cadastrada. <strong>Nao ve financeiro, doadores, transacoes, nem qualquer outra area da organizacao.</strong>
            </p>
            <div style="color:#78350f; font-size:0.82rem;">
                <i class="fas fa-check-circle me-1" style="color:#d97706;"></i>Use para oficineiros, professores, agentes que atuam <em>so na aplicacao do projeto</em>.
            </div>
        </div>
    </div>

    {{-- O que o Credenciado ve --}}
    <div class="vivensi-card" style="padding:28px; border-radius:20px; margin-bottom:24px;">
        <h4 style="margin:0 0 14px; font-weight:900; color:#0f172a; font-size:1.05rem;">
            <i class="fas fa-eye me-2" style="color:#6366f1;"></i>O que o Credenciado enxerga na tela dele
        </h4>
        <ul style="color:#475569; font-size:0.9rem; line-height:1.9; margin:0 0 16px; padding-left:22px;">
            <li>Info basica do projeto (nome, descricao, cronograma)</li>
            <li>Somente as <strong>tarefas atribuidas a ele</strong> (pode atualizar status)</li>
            <li>Aulas cadastradas no projeto (leitura)</li>
            <li>Perfil proprio (editar dados pessoais, senha, 2FA)</li>
        </ul>
        <div style="background:#fef2f2; border:1px solid #fca5a5; border-radius:10px; padding:12px 16px; color:#991b1b; font-size:0.85rem; margin-top:8px;">
            <strong><i class="fas fa-shield-halved me-1"></i>Ele NAO ve:</strong>
            dashboard geral, doadores, financeiro, transacoes, orcamento, DRE, relatorios,
            radar de editais, WhatsApp, e-mail marketing, tarefas de outros membros, membros da equipe interna.
        </div>
    </div>

    {{-- Protocolos de acesso --}}
    <div class="vivensi-card" style="padding:28px; border-radius:20px; margin-bottom:24px;">
        <h4 style="margin:0 0 14px; font-weight:900; color:#0f172a; font-size:1.05rem;">
            <i class="fas fa-shield-halved me-2" style="color:#6366f1;"></i>Niveis de acesso (Viewer / Editor / Admin)
        </h4>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px;">
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px;">
                <div style="font-weight:800; color:#0f172a; margin-bottom:4px;"><i class="fas fa-eye me-1" style="color:#64748b;"></i>Viewer</div>
                <div style="color:#64748b; font-size:0.82rem; line-height:1.6;">So consulta. Nao edita nada.</div>
            </div>
            <div style="border:2px solid #6366f1; background:#eef2ff; border-radius:12px; padding:14px 16px;">
                <div style="font-weight:800; color:#4338ca; margin-bottom:4px;"><i class="fas fa-pen me-1"></i>Editor <span style="font-size:0.65rem; background:#4338ca; color:white; padding:1px 6px; border-radius:6px; margin-left:4px;">Padrão</span></div>
                <div style="color:#4338ca; font-size:0.82rem; line-height:1.6;">Operacional: atualiza tarefas, adiciona registros do dia-a-dia.</div>
            </div>
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px;">
                <div style="font-weight:800; color:#0f172a; margin-bottom:4px;"><i class="fas fa-shield me-1" style="color:#64748b;"></i>Admin</div>
                <div style="color:#64748b; font-size:0.82rem; line-height:1.6;">Total dentro do projeto. Use com cuidado.</div>
            </div>
        </div>
    </div>

    {{-- LGPD --}}
    <div style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:20px; padding:24px; margin-bottom:24px;">
        <div style="display:flex; align-items:flex-start; gap:14px;">
            <i class="fas fa-scale-balanced" style="color:#0369a1; font-size:1.4rem; margin-top:2px; flex-shrink:0;"></i>
            <div>
                <div style="font-weight:800; color:#0369a1; font-size:0.95rem; margin-bottom:6px;">Boa pratica LGPD</div>
                <p style="color:#075985; font-size:0.85rem; line-height:1.7; margin:0;">
                    Sempre que a pessoa <strong>nao precisa</strong> ver dados sensiveis da organizacao (financeiro,
                    doadores, outros beneficiarios), cadastre como <strong>Credenciado</strong>. Menor superficie
                    de exposicao = menor risco de vazamento. E se um dia a parceria terminar, voce so precisa
                    remover o vinculo daquele projeto — nao ha risco dela ter guardado informacoes de outras areas.
                </p>
            </div>
        </div>
    </div>

    {{-- Fechamento Bruce --}}
    <div style="background:linear-gradient(135deg,#1e1b4b,#4f46e5); border-radius:20px; padding:24px 28px; color:white; display:flex; align-items:center; gap:16px; margin-bottom:32px;">
        <img src="{{ asset('img/bruce/bruceia-icone-fundo-claro.svg') }}" alt="Bruce"
             style="width:48px; height:48px; border-radius:50%; background:white; padding:4px; flex-shrink:0;">
        <div>
            <div style="font-weight:800; font-size:1rem; margin-bottom:4px;">Ficou alguma duvida?</div>
            <div style="opacity:0.9; font-size:0.87rem; line-height:1.5;">
                Chame o suporte pelo menu lateral. Estou por aqui pra ajudar sempre que precisar.
            </div>
        </div>
    </div>

    <div style="text-align:center;">
        <button type="button" onclick="window.close()" class="btn btn-outline-secondary rounded-4 fw-bold px-4">
            Fechar guia
        </button>
    </div>
</div>
@endsection
