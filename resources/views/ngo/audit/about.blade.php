@extends('layouts.app')

@section('content')

{{-- ── HEADER ── --}}
<div style="display:flex; align-items:center; gap:14px; margin-bottom:28px; flex-wrap:wrap;">
    <a href="{{ url('/ngo/audit') }}" style="display:inline-flex; align-items:center; gap:6px; color:#64748b; font-size:.8rem; font-weight:700; text-decoration:none; padding:7px 14px; border:1px solid #e2e8f0; border-radius:10px; background:#fff;">
        <i class="fas fa-arrow-left" style="font-size:.7rem;"></i> Voltar para Auditoria
    </a>
</div>

{{-- ── HERO ── --}}
<div style="background:linear-gradient(135deg, #0A0A0B 0%, #141420 60%, #0f0f1e 100%); border-radius:28px; padding:52px 56px 48px; margin-bottom:28px; position:relative; overflow:hidden; border:1px solid rgba(255,122,26,.15);">
    <div style="position:absolute; right:-40px; top:-60px; width:340px; height:340px; background:rgba(255,122,26,.07); border-radius:50%; filter:blur(70px); pointer-events:none;"></div>
    <div style="position:absolute; left:40%; bottom:-80px; width:280px; height:280px; background:rgba(79,70,229,.06); border-radius:50%; filter:blur(60px); pointer-events:none;"></div>

    <div style="display:flex; align-items:center; gap:48px; position:relative; z-index:1; flex-wrap:wrap;">
        <div style="flex:1; min-width:280px;">
            <div style="display:inline-flex; align-items:center; gap:8px; background:rgba(255,122,26,.12); border:1px solid rgba(255,122,26,.3); border-radius:20px; padding:5px 14px; margin-bottom:18px;">
                <i class="fas fa-shield-halved" style="color:#FF7A1A; font-size:.75rem;"></i>
                <span style="color:#FF7A1A; font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:1.2px;">Central de Auditoria</span>
            </div>
            <h1 style="color:#fff; font-size:2rem; font-weight:900; margin:0 0 14px; letter-spacing:-.5px; line-height:1.15;">Como funciona a<br><span style="color:#FF7A1A;">rastreabilidade</span> do sistema?</h1>
            <p style="color:rgba(255,255,255,.6); font-size:.92rem; line-height:1.65; margin:0; max-width:520px;">
                A Central de Auditoria registra automaticamente todas as ações sensíveis realizadas no sistema — quem fez, o quê, quando e de onde. Isso garante transparência, conformidade com a LGPD e suporte às exigências do CEBAS e MROSC.
            </p>
        </div>
        <div style="flex-shrink:0; display:flex; flex-direction:column; align-items:center; gap:14px;">
            <img src="{{ asset('img/bruce/bruceia-icone-fundo-claro.svg') }}" alt="Bruce IA"
                 style="width:120px; height:120px; border-radius:28px; box-shadow:0 12px 40px rgba(255,122,26,.4), 0 4px 12px rgba(0,0,0,.5);">
            <span style="color:rgba(255,255,255,.4); font-size:.7rem; font-weight:600;">Explicado por Bruce IA</span>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:28px;">

    {{-- O que é registrado --}}
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:30px;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
            <div style="width:44px; height:44px; background:linear-gradient(135deg,#4f46e5,#4338ca); border-radius:13px; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 6px 16px rgba(79,70,229,.25);">
                <i class="fas fa-list-check" style="color:#fff; font-size:1rem;"></i>
            </div>
            <div>
                <div style="font-size:.92rem; font-weight:800; color:#0f172a; letter-spacing:-.2px;">O que é registrado?</div>
                <div style="font-size:.72rem; color:#94a3b8; margin-top:1px;">Eventos capturados automaticamente</div>
            </div>
        </div>
        <div style="display:flex; flex-direction:column; gap:10px;">
            @foreach([
                ['fa-plus-circle','#16a34a','#dcfce7','created','Criação','Novo beneficiário, transação, projeto ou usuário adicionado'],
                ['fa-pen-to-square','#d97706','#fef9c3','updated','Atualização','Dados alterados em qualquer módulo principal'],
                ['fa-trash','#dc2626','#fef2f2','deleted','Exclusão','Registro removido do sistema'],
                ['fa-right-to-bracket','#0284c7','#eff6ff','login','Acesso','Entrada de usuário no sistema'],
                ['fa-download','#7c3aed','#f5f3ff','download','Exportação','Download de CSV, PDF ou relatório'],
            ] as [$ico, $color, $bg, $ev, $label, $desc])
            <div style="display:flex; align-items:flex-start; gap:12px; padding:12px 14px; background:{{ $bg }}; border-radius:12px;">
                <i class="fas {{ $ico }}" style="color:{{ $color }}; font-size:.85rem; margin-top:2px; flex-shrink:0;"></i>
                <div>
                    <div style="font-size:.8rem; font-weight:700; color:#0f172a;">{{ $label }} <code style="font-size:.7rem; background:rgba(0,0,0,.06); padding:1px 6px; border-radius:5px;">{{ $ev }}</code></div>
                    <div style="font-size:.72rem; color:#475569; margin-top:2px;">{{ $desc }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Módulos cobertos --}}
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:30px;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
            <div style="width:44px; height:44px; background:linear-gradient(135deg,#FF7A1A,#ea580c); border-radius:13px; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 6px 16px rgba(255,122,26,.25);">
                <i class="fas fa-cubes" style="color:#fff; font-size:1rem;"></i>
            </div>
            <div>
                <div style="font-size:.92rem; font-weight:800; color:#0f172a; letter-spacing:-.2px;">Módulos cobertos</div>
                <div style="font-size:.72rem; color:#94a3b8; margin-top:1px;">Onde a rastreabilidade está ativa</div>
            </div>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
            @foreach([
                ['fa-people-roof','Beneficiários'],
                ['fa-money-bill-wave','Finanças'],
                ['fa-diagram-project','Projetos'],
                ['fa-list-check','Tarefas'],
                ['fa-users','Usuários'],
                ['fa-box-archive','Patrimônio'],
                ['fa-boxes-stacked','Estoque'],
                ['fa-file-contract','Editais'],
                ['fa-clipboard-list','Atendimentos'],
                ['fa-folder-open','Documentos'],
                ['fa-handshake','Contratos'],
                ['fa-shield-check','Conformidade'],
            ] as [$ico, $label])
            <div style="display:flex; align-items:center; gap:9px; padding:9px 12px; border:1px solid #f1f5f9; border-radius:10px; background:#fafafa;">
                <i class="fas {{ $ico }}" style="color:#FF7A1A; font-size:.78rem; flex-shrink:0;"></i>
                <span style="font-size:.78rem; font-weight:600; color:#374151;">{{ $label }}</span>
            </div>
            @endforeach
        </div>
    </div>

</div>

<div style="display:grid; grid-template-columns:2fr 1fr; gap:20px; margin-bottom:28px;">

    {{-- Como usar os filtros --}}
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:30px;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:22px;">
            <div style="width:44px; height:44px; background:linear-gradient(135deg,#0891b2,#0e7490); border-radius:13px; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 6px 16px rgba(8,145,178,.25);">
                <i class="fas fa-filter" style="color:#fff; font-size:1rem;"></i>
            </div>
            <div>
                <div style="font-size:.92rem; font-weight:800; color:#0f172a; letter-spacing:-.2px;">Como usar os filtros</div>
                <div style="font-size:.72rem; color:#94a3b8; margin-top:1px;">Encontre exatamente o que precisa</div>
            </div>
        </div>
        <div style="display:flex; flex-direction:column; gap:14px;">
            @foreach([
                ['Busca por texto','fa-magnifying-glass','Digite parte do nome do módulo, tipo de evento, endereço IP ou URL da ação. Ex: "Transaction", "Beneficiary", "192.168".'],
                ['Filtrar por evento','fa-tag','Selecione "created", "updated", "deleted", "login", "download" para focar em um tipo específico de ação.'],
                ['Filtrar por usuário','fa-user','Selecione um colaborador para ver apenas as ações realizadas por ele — útil em investigações internas.'],
                ['Período (De / Até)','fa-calendar-range','Defina uma janela de datas para análises de período: auditorias mensais, prestação de contas trimestral, etc.'],
                ['Exportar CSV','fa-file-csv','O botão "Exportar CSV" aplica os filtros ativos e gera o arquivo — ideal para anexar em processos CEBAS e MROSC.'],
            ] as [$title, $ico, $desc])
            <div style="display:flex; gap:14px; align-items:flex-start;">
                <div style="width:34px; height:34px; background:#f0f9ff; border:1px solid #bfdbfe; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="fas {{ $ico }}" style="color:#0284c7; font-size:.78rem;"></i>
                </div>
                <div>
                    <div style="font-size:.83rem; font-weight:700; color:#0f172a; margin-bottom:3px;">{{ $title }}</div>
                    <div style="font-size:.75rem; color:#64748b; line-height:1.5;">{{ $desc }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Por que importa --}}
    <div style="display:flex; flex-direction:column; gap:16px;">
        <div style="background:linear-gradient(135deg,#f0fdf4,#dcfce7); border:1px solid #bbf7d0; border-radius:20px; padding:24px;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
                <i class="fas fa-scale-balanced" style="color:#16a34a; font-size:1.1rem;"></i>
                <span style="font-size:.85rem; font-weight:800; color:#14532d;">LGPD</span>
            </div>
            <p style="font-size:.78rem; color:#166534; line-height:1.55; margin:0;">Demonstra quem acessou dados pessoais e quando, cumprindo o art. 37 da LGPD sobre registro de operações de tratamento.</p>
        </div>
        <div style="background:linear-gradient(135deg,#eff6ff,#dbeafe); border:1px solid #bfdbfe; border-radius:20px; padding:24px;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
                <i class="fas fa-award" style="color:#1d4ed8; font-size:1.1rem;"></i>
                <span style="font-size:.85rem; font-weight:800; color:#1e3a8a;">CEBAS / MROSC</span>
            </div>
            <p style="font-size:.78rem; color:#1e40af; line-height:1.55; margin:0;">Comprova rastreabilidade dos recursos públicos, exigência nos processos de certificação e prestação de contas.</p>
        </div>
        <div style="background:linear-gradient(135deg,#fdf4ff,#fae8ff); border:1px solid #e9d5ff; border-radius:20px; padding:24px;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
                <i class="fas fa-building-shield" style="color:#7c3aed; font-size:1.1rem;"></i>
                <span style="font-size:.85rem; font-weight:800; color:#4c1d95;">Governança</span>
            </div>
            <p style="font-size:.78rem; color:#5b21b6; line-height:1.55; margin:0;">Identifica ações não autorizadas, erros operacionais e desvios de processo antes que gerem impacto.</p>
        </div>
    </div>

</div>

{{-- CTA final --}}
<div style="background:#fff; border:1px solid #e2e8f0; border-radius:20px; padding:32px 36px; display:flex; align-items:center; justify-content:space-between; gap:24px; flex-wrap:wrap;">
    <div>
        <div style="font-size:.95rem; font-weight:800; color:#0f172a; margin-bottom:6px;">Pronto para auditar?</div>
        <p style="font-size:.82rem; color:#64748b; margin:0;">Acesse os registros, aplique filtros e exporte os dados que precisa.</p>
    </div>
    <a href="{{ url('/ngo/audit') }}" style="display:inline-flex; align-items:center; gap:8px; background:linear-gradient(135deg,#4f46e5,#4338ca); color:#fff; font-size:.82rem; font-weight:800; padding:12px 26px; border-radius:12px; text-decoration:none; box-shadow:0 6px 20px rgba(79,70,229,.3);">
        <i class="fas fa-shield-halved"></i> Acessar Central de Auditoria
    </a>
</div>

@push('styles')
<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns:1fr 1fr"],
    div[style*="grid-template-columns:2fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
@endpush

@endsection
