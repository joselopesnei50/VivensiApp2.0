@extends('layouts.app')

@section('content')
<style>
    .crm-card {
        background: white;
        border-radius: 20px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        overflow: hidden;
    }
    .crm-header {
        padding: 24px;
        background: #f8fafc;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .donor-table {
        width: 100%;
        border-collapse: collapse;
    }
    .donor-table th {
        padding: 16px 24px;
        text-align: left;
        font-size: 0.75rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .donor-table td {
        padding: 20px 24px;
        border-bottom: 1px solid #f8fafc;
        vertical-align: middle;
    }
    .donor-table tr:hover { background-color: #fcfdfe; }
    
    .avatar-ngo {
        width: 45px;
        height: 45px;
        border-radius: 14px;
        background: #eef2ff;
        color: #6366f1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.1rem;
    }
    
    .badge-donor-pf { background: #dcfce7; color: #166534; }
    .badge-donor-pj { background: #dbeafe; color: #1e40af; }
    .badge-donor-gov { background: #fef9c3; color: #854d0e; }
    
    .action-circle {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        color: #64748b;
        transition: all 0.2s;
    }
    .action-circle:hover {
        background: #e2e8f0;
        color: #1e293b;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 pb-2">
    <div>
        <h2 class="fw-bold text-dark m-0">Base de Doadores</h2>
        <p class="text-muted mt-1">Gestão inteligente de parceiros e investidores sociais.</p>
    </div>
    <a href="{{ url('/ngo/donors/create') }}" class="btn-premium">
        <i class="fas fa-plus me-2"></i> Novo Doador
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
    </div>
@endif

<div class="crm-card">
    <div class="crm-header" style="flex-wrap:wrap; gap:12px;">
        <form method="GET" action="{{ url('/ngo/donors') }}" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; flex:1;">
            <div style="position: relative; min-width:220px; flex:1; max-width:320px;">
                <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform:translateY(-50%); color: #94a3b8; pointer-events:none;"></i>
                <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Buscar por nome, e-mail, doc…"
                    style="width:100%; padding: 10px 15px 10px 40px; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 0.9rem; outline:none;"
                    autocomplete="off">
                @if(!empty($type))<input type="hidden" name="type" value="{{ $type }}">@endif
            </div>
            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                @php
                    $typeFilters = ['' => 'Todos', 'individual' => 'Pessoa Física', 'company' => 'Empresa (PJ)', 'government' => 'Governo'];
                    $typeColors  = ['' => '#0f172a', 'individual' => '#166534', 'company' => '#1e40af', 'government' => '#854d0e'];
                @endphp
                @foreach($typeFilters as $val => $label)
                    @php $isActive = ($type ?? '') === $val; @endphp
                    <a href="{{ url('/ngo/donors') }}?{{ http_build_query(array_filter(['q' => $q ?? '', 'type' => $val], fn($v) => $v !== '')) }}"
                       style="padding:6px 14px; border-radius:99px; font-size:.75rem; font-weight:700; text-decoration:none; white-space:nowrap;
                              border: 1.5px solid {{ $isActive ? 'transparent' : '#e2e8f0' }};
                              background: {{ $isActive ? $typeColors[$val] : '#fff' }};
                              color: {{ $isActive ? '#fff' : '#64748b' }};">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
            @if(!empty($q))
                <a href="{{ url('/ngo/donors') }}" style="font-size:.8rem; color:#94a3b8; white-space:nowrap; text-decoration:none;">
                    <i class="fas fa-times"></i> Limpar
                </a>
            @endif
        </form>
        <div class="text-muted small fw-bold" style="white-space:nowrap;">
            Mostrando {{ $donors->count() }} de {{ $donors->total() }} registros
        </div>
    </div>
    
    <table class="donor-table">
        <thead>
            <tr>
                <th>Doador / Parceiro</th>
                <th>Informação de Contato</th>
                <th class="text-center">Tipo</th>
                <th class="text-end">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($donors as $d)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-ngo">
                            {{ substr($d->name, 0, 1) }}
                        </div>
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 1.05rem;">{{ $d->name }}</div>
                            <div class="text-muted small">Doc: {{ $d->document ?? 'Não informado' }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    @if($d->email)
                        <div class="text-dark small fw-bold mb-1"><i class="far fa-envelope me-2 text-muted"></i> {{ $d->email }}</div>
                    @endif
                    @if($d->phone)
                        <div class="text-muted small"><i class="fas fa-phone-alt me-2 text-muted"></i> {{ $d->phone }}</div>
                    @endif
                </td>
                <td class="text-center">
                    @php
                        $types = [
                            'individual' => ['label' => 'Pessoa Física', 'class' => 'badge-donor-pf'],
                            'company' => ['label' => 'Empresa (PJ)', 'class' => 'badge-donor-pj'],
                            'government' => ['label' => 'Governo', 'class' => 'badge-donor-gov'],
                        ];
                        $curr = $types[$d->type] ?? ['label' => 'Outro', 'class' => 'bg-secondary'];
                    @endphp
                    <span class="badge {{ $curr['class'] }} px-3 py-2 rounded-pill fw-bold" style="font-size: 0.7rem;">
                        {{ $curr['label'] }}
                    </span>
                </td>
                <td class="text-end">
                    @php
                        $portalLink = url('/portal-doador/' . $d->portal_token);
                        $waMsg = urlencode("Olá " . explode(' ', $d->name)[0] . "! 💙\n\nCriamos um Portal VIP exclusivo para você acompanhar o impacto das suas doações e baixar seus informes de rendimentos.\n\nAcesse aqui: " . $portalLink);
                        $waUrl = "https://wa.me/" . preg_replace('/\D/', '', $d->phone ?? '') . "?text=" . $waMsg;
                    @endphp
                    <div class="d-flex justify-content-end gap-2 flex-wrap">

                        {{-- Copiar link do portal --}}
                        <button type="button"
                            class="action-circle"
                            style="background:#f0fdf4;color:#059669;border:none;cursor:pointer;"
                            title="Copiar link do Portal VIP"
                            onclick="copyPortalLink('{{ $portalLink }}', this)">
                            <i class="fas fa-link"></i>
                        </button>

                        {{-- Enviar por e-mail --}}
                        @if($d->email)
                            <form action="{{ route('ngo.donors.send-portal-email', $d->id) }}" method="POST" style="display:inline;"
                                  onsubmit="return confirm('Enviar Portal VIP para {{ $d->email }}?')">
                                @csrf
                                <button type="submit" class="action-circle border-0"
                                    style="background:#eff6ff;color:#3b82f6;cursor:pointer;"
                                    title="Enviar Portal VIP por e-mail para {{ $d->email }}">
                                    <i class="fas fa-envelope"></i>
                                </button>
                            </form>
                        @endif

                        {{-- Enviar via WhatsApp (com mensagem pré-pronta) --}}
                        @if($d->phone)
                            <a href="{{ $waUrl }}" target="_blank"
                               class="action-circle" style="background:#eef2ff;color:#6366f1;"
                               title="Enviar Portal VIP via WhatsApp">
                                <i class="fas fa-paper-plane"></i>
                            </a>
                            <a href="https://wa.me/{{ preg_replace('/\D/', '', $d->phone) }}" target="_blank"
                               class="action-circle text-success"
                               title="WhatsApp direto">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        @endif

                        {{-- Regenerar token do portal --}}
                        <form action="{{ route('ngo.donors.regenerate-token', $d->id) }}" method="POST" style="display:inline;"
                              onsubmit="return confirm('Regenerar o link do portal de {{ addslashes($d->name) }}? O link atual deixará de funcionar.')">
                            @csrf
                            <button type="submit" class="action-circle border-0"
                                style="background:#fff7ed;color:#c2410c;cursor:pointer;"
                                title="Regenerar link do Portal VIP">
                                <i class="fas fa-rotate-right"></i>
                            </button>
                        </form>

                        <a href="{{ url('/ngo/donors/'.$d->id.'/edit') }}" class="action-circle" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ url('/ngo/donors/'.$d->id) }}" method="POST" onsubmit="return confirm('Excluir este doador?')" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="action-circle border-0 text-danger" title="Excluir">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="padding: 0; border: none;">
                    <x-empty-state
                        icon="fa-hand-holding-heart"
                        title="Nenhum doador cadastrado"
                        description="Comece a construir sua rede de apoiadores. Cadastre o primeiro doador e acompanhe todo o histórico de contribuições."
                        action_label="Cadastrar Doador"
                        action_url="{{ url('/ngo/donors/create') }}"
                    />
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="p-4 border-top">
        {{ $donors->links() }}
    </div>
</div>

@push('scripts')
<script>
function copyPortalLink(url, btn) {
    navigator.clipboard.writeText(url).then(function() {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.style.background = '#dcfce7';
        btn.style.color = '#15803d';
        setTimeout(function() {
            btn.innerHTML = orig;
            btn.style.background = '#f0fdf4';
            btn.style.color = '#059669';
        }, 2000);
    }).catch(function() {
        prompt('Copie o link abaixo:', url);
    });
}
</script>
@endpush
@endsection
