@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 32px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 style="margin: 0; font-weight: 950; font-size: 2rem; letter-spacing: -1px;">API Tokens</h2>
            <p style="color: #64748b; margin: 6px 0 0; font-size: 0.95rem;">Gerencie chaves de acesso para a API Pública v1 do Vivensi.</p>
        </div>
        <a href="https://docs.vivensi.app.br/api" target="_blank" style="font-size: 0.8rem; color: var(--ds-brand); font-weight: 700; text-decoration: none;">
            <i class="fas fa-book me-1"></i> Documentação da API
        </a>
    </div>
</div>

@if(session('new_token'))
<div style="background: #0f172a; border: 2px solid #10b981; border-radius: 20px; padding: 28px; margin-bottom: 28px;">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
        <i class="fas fa-key" style="color: #10b981;"></i>
        <strong style="color: white; font-size: 1rem;">Token criado — copie agora!</strong>
    </div>
    <p style="color: rgba(255,255,255,0.5); font-size: 0.8rem; margin-bottom: 12px;">Este token não será exibido novamente após sair desta página.</p>
    <div style="display: flex; align-items: center; gap: 10px;">
        <code id="newTokenValue" style="flex:1; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 12px 16px; color: #34d399; font-size: 0.85rem; word-break: break-all; font-family: monospace;">{{ session('new_token') }}</code>
        <button onclick="navigator.clipboard.writeText(document.getElementById('newTokenValue').textContent); this.innerHTML='<i class=\'fas fa-check\'></i>'; setTimeout(()=>this.innerHTML='<i class=\'fas fa-copy\'></i>',2000);" style="background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); color: #34d399; border-radius: 10px; padding: 12px 16px; cursor: pointer; white-space: nowrap;">
            <i class="fas fa-copy"></i>
        </button>
    </div>
</div>
@endif

<div class="row g-4">
    {{-- Criar novo token --}}
    <div class="col-lg-5">
        <div style="background: #0f172a; border-radius: 24px; padding: 32px; border: 1px solid rgba(255,255,255,0.06);">
            <h4 style="color: white; font-weight: 900; margin-bottom: 24px; font-size: 1.1rem;">Novo Token</h4>
            <form method="POST" action="{{ route('settings.api-tokens.store') }}">
                @csrf
                <div style="margin-bottom: 16px;">
                    <label for="token_name" style="color: rgba(255,255,255,0.6); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">Nome do Token</label>
                    <input type="text" name="name" id="token_name" required placeholder="Ex: Integração Zapier" maxlength="100"
                        style="width:100%; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:12px 16px; color:white; font-size:0.9rem; outline:none;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="color: rgba(255,255,255,0.6); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 12px;">Permissões</label>
                    @foreach([
                        'transactions:read'  => ['Transações: Leitura',  'fa-eye',    'var(--ds-brand)'],
                        'transactions:write' => ['Transações: Escrita',  'fa-pen',    '#10b981'],
                        'projects:read'      => ['Projetos: Leitura',    'fa-folder', '#f59e0b'],
                        'tasks:read'         => ['Tarefas: Leitura',     'fa-list',   '#818cf8'],
                        'tasks:write'        => ['Tarefas: Escrita',     'fa-plus',   '#34d399'],
                    ] as $ability => [$label, $icon, $color])
                    <label style="display:flex; align-items:center; gap:10px; padding:10px 0; cursor:pointer; border-bottom:1px solid rgba(255,255,255,0.04);">
                        <input type="checkbox" name="abilities[]" value="{{ $ability }}" style="accent-color: {{ $color }}; width:16px; height:16px; cursor:pointer;">
                        <i class="fas {{ $icon }}" style="color:{{ $color }}; width:16px; text-align:center;"></i>
                        <span style="color:rgba(255,255,255,0.8); font-size:0.85rem; font-weight:600;">{{ $label }}</span>
                    </label>
                    @endforeach
                    <p style="font-size:0.7rem; color:rgba(255,255,255,0.3); margin-top:8px;">Sem seleção = acesso total (*)</p>
                </div>

                <button type="submit" style="width:100%; background:linear-gradient(135deg,var(--ds-brand),#4f46e5); color:white; border:none; border-radius:14px; padding:14px; font-weight:900; font-size:0.9rem; cursor:pointer; box-shadow:0 8px 24px rgba(99,102,241,0.25);">
                    <i class="fas fa-key me-2"></i> Gerar Token
                </button>
            </form>
        </div>

        {{-- API Base URL --}}
        <div style="background: #0f172a; border-radius: 20px; padding: 24px; border: 1px solid rgba(255,255,255,0.06); margin-top: 16px;">
            <h5 style="color: white; font-weight: 800; font-size: 0.9rem; margin-bottom: 16px;"><i class="fas fa-code me-2" style="color:var(--ds-brand);"></i>Base URL</h5>
            <code style="background:rgba(99,102,241,0.1); border:1px solid rgba(99,102,241,0.2); border-radius:8px; padding:10px 14px; color:#a5b4fc; font-size:0.8rem; display:block; font-family:monospace;">
                {{ config('app.url') }}/api/v1
            </code>
            <div style="margin-top: 12px;">
                <p style="color:rgba(255,255,255,0.4); font-size:0.75rem; margin-bottom:6px; font-weight:700;">Autenticação:</p>
                <code style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:10px 14px; color:#94a3b8; font-size:0.75rem; display:block; font-family:monospace;">
                    Authorization: Bearer &lt;token&gt;
                </code>
            </div>
            <div style="margin-top: 12px; padding: 10px 14px; background:rgba(245,158,11,0.08); border-radius:8px; border:1px solid rgba(245,158,11,0.2);">
                <p style="color:#fbbf24; font-size:0.7rem; font-weight:700; margin:0;"><i class="fas fa-gauge-high me-1"></i> Rate Limit: 60 req/min por token</p>
            </div>
        </div>
    </div>

    {{-- Tokens existentes --}}
    <div class="col-lg-7">
        <div style="background: #0f172a; border-radius: 24px; padding: 32px; border: 1px solid rgba(255,255,255,0.06);">
            <h4 style="color: white; font-weight: 900; margin-bottom: 24px; font-size: 1.1rem;">
                Tokens Ativos
                <span style="font-size:0.75rem; background:rgba(99,102,241,0.2); color:#818cf8; padding:3px 10px; border-radius:99px; margin-left:8px; font-weight:800;">{{ $tokens->count() }}</span>
            </h4>

            @forelse($tokens as $token)
            <div style="padding: 18px 20px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; margin-bottom: 10px; display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 800; color: white; font-size: 0.9rem; margin-bottom: 4px;">{{ $token->name }}</div>
                    <div style="font-size: 0.7rem; color: rgba(255,255,255,0.35); font-weight: 600;">
                        Criado {{ $token->created_at->diffForHumans() }}
                        @if($token->last_used_at)
                            · Usado {{ $token->last_used_at->diffForHumans() }}
                        @else
                            · <span style="color:#f59e0b;">Nunca usado</span>
                        @endif
                    </div>
                    <div style="margin-top: 6px; display: flex; flex-wrap: wrap; gap: 4px;">
                        @foreach($token->abilities as $ability)
                            <span style="font-size:0.6rem; background:rgba(99,102,241,0.15); color:#a5b4fc; padding:2px 8px; border-radius:99px; font-weight:800; text-transform:uppercase;">{{ $ability }}</span>
                        @endforeach
                    </div>
                </div>
                <form method="POST" action="{{ route('settings.api-tokens.destroy', $token->id) }}" onsubmit="return confirm('Revogar este token? Integrações que o usam deixarão de funcionar.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.25); color:#f87171; border-radius:10px; padding:8px 14px; cursor:pointer; font-size:0.75rem; font-weight:800; white-space:nowrap;">
                        <i class="fas fa-trash-alt me-1"></i> Revogar
                    </button>
                </form>
            </div>
            @empty
            <div style="text-align:center; padding:40px 20px; color:rgba(255,255,255,0.3);">
                <i class="fas fa-key" style="font-size:2rem; margin-bottom:12px; display:block;"></i>
                <p style="font-weight:700; font-size:0.9rem;">Nenhum token criado ainda.</p>
                <p style="font-size:0.8rem;">Crie um token para começar a integrar o Vivensi com outros sistemas.</p>
            </div>
            @endforelse
        </div>

        {{-- Endpoints disponíveis --}}
        <div style="background: #0f172a; border-radius: 20px; padding: 24px; border: 1px solid rgba(255,255,255,0.06); margin-top: 16px;">
            <h5 style="color: white; font-weight: 800; font-size: 0.9rem; margin-bottom: 16px;"><i class="fas fa-route me-2" style="color:#10b981;"></i>Endpoints Disponíveis</h5>
            @foreach([
                ['GET',  '/me',                   'Dados do usuário autenticado'],
                ['GET',  '/transactions',          'Listar transações (filtros: type, status, from, to)'],
                ['POST', '/transactions',          'Criar transação'],
                ['GET',  '/transactions/{id}',     'Buscar transação por ID'],
                ['GET',  '/projects',              'Listar projetos (filtro: status)'],
                ['GET',  '/projects/{id}',         'Buscar projeto por ID'],
                ['GET',  '/projects/{id}/tasks',   'Listar tarefas de um projeto'],
                ['GET',  '/tasks',                 'Listar tarefas (filtros: status, priority, project_id)'],
                ['POST', '/tasks',                 'Criar tarefa'],
                ['GET',  '/tasks/{id}',            'Buscar tarefa por ID'],
            ] as [$method, $path, $desc])
            <div style="display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.03);">
                <span style="font-size:0.6rem; font-weight:900; padding:3px 8px; border-radius:6px; min-width:38px; text-align:center; {{ $method === 'GET' ? 'background:rgba(16,185,129,0.15);color:#34d399;' : 'background:rgba(99,102,241,0.15);color:#818cf8;' }}">{{ $method }}</span>
                <code style="font-size:0.75rem; color:#94a3b8; font-family:monospace;">/api/v1{{ $path }}</code>
                <span style="font-size:0.7rem; color:rgba(255,255,255,0.3); flex:1;">{{ $desc }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
