@extends('layouts.app')

@section('content')
@php
    $basePath = rtrim(request()->getBaseUrl(), '/');
    $publicBase = request()->getSchemeAndHttpHost() . $basePath;
@endphp
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; gap: 20px;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Contratos Digitais</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Gerencie e colete assinaturas eletrônicas.</p>
    </div>
    <a href="{{ $basePath . '/ngo/contracts/create' }}" class="btn-premium">
        <i class="fas fa-plus"></i> Novo Contrato
    </a>
</div>

@php
    $signedCount = $contracts->where('status', 'signed')->count();
    $expiredCount = $contracts->filter(function($c) {
        return $c->public_sign_expires_at && $c->public_sign_expires_at->isPast() && $c->status !== 'signed';
    })->count();
    $pendingCount = max(0, $contracts->count() - $signedCount - $expiredCount);
@endphp

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="vivensi-card" style="padding: 18px 20px; border-radius: 18px;">
            <div style="font-size:.75rem; color:#94a3b8; font-weight:900; text-transform:uppercase; letter-spacing:.08em;">Assinados</div>
            <div style="font-size: 1.8rem; font-weight: 900; color:#16a34a;">{{ $signedCount }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="vivensi-card" style="padding: 18px 20px; border-radius: 18px;">
            <div style="font-size:.75rem; color:#94a3b8; font-weight:900; text-transform:uppercase; letter-spacing:.08em;">Aguardando</div>
            <div style="font-size: 1.8rem; font-weight: 900; color:#c2410c;">{{ $pendingCount }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="vivensi-card" style="padding: 18px 20px; border-radius: 18px;">
            <div style="font-size:.75rem; color:#94a3b8; font-weight:900; text-transform:uppercase; letter-spacing:.08em;">Expirados</div>
            <div style="font-size: 1.8rem; font-weight: 900; color:#b91c1c;">{{ $expiredCount }}</div>
        </div>
    </div>
</div>

<div class="vivensi-card mb-3" style="padding: 16px 18px; border-radius: 18px;">
    <div style="display:flex; gap: 12px; align-items:center; justify-content: space-between; flex-wrap: wrap;">
        <div style="display:flex; gap: 10px; align-items:center; flex: 1; min-width: 260px;">
            <div style="position: relative; flex: 1;">
                <i class="fas fa-search" style="position:absolute; left: 14px; top: 12px; color:#94a3b8;"></i>
                <input id="contractsSearch" type="text" placeholder="Buscar por título ou signatário..."
                       style="width: 100%; padding: 10px 12px 10px 38px; border-radius: 12px; border:1px solid #e2e8f0; background:#fff; font-weight: 700; color:#0f172a;">
            </div>
            <button type="button" onclick="clearContractsFilters()" class="btn-outline" style="padding: 8px 12px; border-radius: 12px; font-weight: 900;">
                Limpar
            </button>
        </div>

        <div style="display:flex; gap: 8px; flex-wrap: wrap; align-items:center;">
            <button type="button" class="btn-outline" data-filter="all" onclick="setContractsFilter(this)" style="padding: 8px 12px; border-radius: 999px; font-weight: 900;">Todos</button>
            <button type="button" class="btn-outline" data-filter="pending" onclick="setContractsFilter(this)" style="padding: 8px 12px; border-radius: 999px; font-weight: 900;">Aguardando</button>
            <button type="button" class="btn-outline" data-filter="signed" onclick="setContractsFilter(this)" style="padding: 8px 12px; border-radius: 999px; font-weight: 900;">Assinados</button>
            <button type="button" class="btn-outline" data-filter="expired" onclick="setContractsFilter(this)" style="padding: 8px 12px; border-radius: 999px; font-weight: 900;">Expirados</button>
        </div>
    </div>

    <div id="contractsFilterHint" style="margin-top: 10px; color:#94a3b8; font-weight:800; font-size:.85rem;"></div>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <tr>
                <th style="padding: 15px 25px; text-align: left; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Título</th>
                <th style="padding: 15px 25px; text-align: left; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Signatário</th>
                <th style="padding: 15px 25px; text-align: center; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Status</th>
                <th style="padding: 15px 25px; text-align: center; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Expira</th>
                <th style="padding: 15px 25px; text-align: center; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Ações</th>
            </tr>
        </thead>
        <tbody id="contractsTbody">
            @foreach($contracts as $c)
            @php
                $isExpired = $c->public_sign_expires_at && $c->public_sign_expires_at->isPast() && $c->status !== 'signed';
                // Do not rely on APP_URL; support subfolder installs.
                $publicUrl = $publicBase . '/sign/' . $c->token;
                $rowStatus = $c->status === 'signed' ? 'signed' : ($isExpired ? 'expired' : 'pending');
                $searchText = mb_strtolower(trim(($c->title ?? '') . ' ' . ($c->signer_name ?? '')));
            @endphp
            <tr style="border-bottom: 1px solid #f1f5f9;" data-status="{{ $rowStatus }}" data-search="{{ e($searchText) }}">
                <td style="padding: 15px 25px; font-weight: 600; color: #334155;">{{ $c->title }}</td>
                <td style="padding: 15px 25px; color: #64748b;">{{ $c->signer_name }}</td>
                <td style="padding: 15px 25px; text-align: center;">
                    @if($c->status == 'signed')
                        <span style="background: #dcfce7; color: #16a34a; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">ASSINADO</span>
                    @elseif($isExpired)
                        <span style="background: #fee2e2; color: #b91c1c; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">EXPIRADO</span>
                    @else
                        <span style="background: #fff7ed; color: #c2410c; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">AGUARDANDO</span>
                    @endif
                </td>
                <td style="padding: 15px 25px; text-align: center; color: #64748b; font-size: 0.9rem;">
                    @if($c->public_sign_expires_at)
                        {{ $c->public_sign_expires_at->format('d/m/Y') }}
                    @else
                        <span style="color:#94a3b8;">Sem expiração</span>
                    @endif
                </td>
                <td style="padding: 15px 25px; text-align: center;">
                    <div style="display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                        <button type="button" onclick="copyLink('{{ $publicUrl }}')" class="btn-outline" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 8px; cursor: pointer;">
                            <i class="fas fa-copy"></i> Copiar
                        </button>

                        <button type="button" onclick="shareContract('{{ $c->signer_name }}', '{{ $publicUrl }}')" class="btn-outline" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 8px; cursor: pointer;">
                            <i class="fas fa-whatsapp"></i> WhatsApp
                        </button>

                        @if($c->status !== 'signed')
                            <form action="{{ $basePath . '/ngo/contracts/' . $c->id . '/regenerate-link' }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn-outline" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 8px; cursor: pointer;">
                                    <i class="fas fa-rotate"></i> Renovar
                                </button>
                            </form>

                            <form action="{{ $basePath . '/ngo/contracts/' . $c->id . '/revoke-link' }}" method="POST" style="display:inline;" onsubmit="return confirm('Revogar o link público deste contrato?');">
                                @csrf
                                <button type="submit" class="btn-outline" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 8px; cursor: pointer; border-color:#fecaca; color:#b91c1c;">
                                    <i class="fas fa-ban"></i> Revogar
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
    let __contractsFilter = 'all';

    function setContractsFilter(btn) {
        const f = btn && btn.dataset ? (btn.dataset.filter || 'all') : 'all';
        __contractsFilter = f;

        document.querySelectorAll('[data-filter]').forEach(b => {
            const active = (b.dataset.filter === __contractsFilter);
            b.style.background = active ? '#0f172a' : '';
            b.style.color = active ? '#fff' : '';
            b.style.borderColor = active ? '#0f172a' : '';
        });

        applyContractsFilters();
    }

    function clearContractsFilters() {
        const inp = document.getElementById('contractsSearch');
        if (inp) inp.value = '';
        __contractsFilter = 'all';
        document.querySelectorAll('[data-filter]').forEach(b => {
            const active = (b.dataset.filter === 'all');
            b.style.background = active ? '#0f172a' : '';
            b.style.color = active ? '#fff' : '';
            b.style.borderColor = active ? '#0f172a' : '';
        });
        applyContractsFilters();
    }

    function applyContractsFilters() {
        const inp = document.getElementById('contractsSearch');
        const q = (inp && inp.value ? inp.value : '').toString().trim().toLowerCase();

        const rows = document.querySelectorAll('#contractsTbody tr[data-status]');
        let shown = 0;
        rows.forEach(r => {
            const st = (r.dataset.status || 'pending');
            const s = (r.dataset.search || '');
            const statusOk = (__contractsFilter === 'all') ? true : (st === __contractsFilter);
            const searchOk = (!q) ? true : (s.indexOf(q) !== -1);
            const ok = statusOk && searchOk;
            r.style.display = ok ? '' : 'none';
            if (ok) shown++;
        });

        const hint = document.getElementById('contractsFilterHint');
        if (hint) {
            const labelMap = { all: 'Todos', pending: 'Aguardando', signed: 'Assinados', expired: 'Expirados' };
            const lf = labelMap[__contractsFilter] || __contractsFilter;
            hint.innerText = 'Mostrando ' + shown + ' contrato(s) • Filtro: ' + lf + (q ? (' • Busca: "' + q + '"') : '');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const inp = document.getElementById('contractsSearch');
        if (inp) inp.addEventListener('input', applyContractsFilters);
        // set default active button (Todos)
        const defaultBtn = document.querySelector('[data-filter="all"]');
        if (defaultBtn) setContractsFilter(defaultBtn);
    });

    async function copyLink(url) {
        try {
            await navigator.clipboard.writeText(url);
            alert('Link copiado!');
        } catch (e) {
            prompt('Copie o link:', url);
        }
    }

    function shareContract(name, url) {
        let text = `Olá ${name}, por favor assine o contrato digital no link: ${url}`;
        let waLink = `https://wa.me/?text=${encodeURIComponent(text)}`;
        window.open(waLink, '_blank');
    }
</script>
@endsection
