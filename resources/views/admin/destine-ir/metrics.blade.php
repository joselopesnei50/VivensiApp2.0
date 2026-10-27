@extends('layouts.app')
@section('title', 'Destine seu IR — Métricas')

@push('styles')
<style>
    .m-hero { background: linear-gradient(135deg, #0b4f6c 0%, #145676 100%); color: #fff; border-radius: 18px; padding: 24px 30px; margin-bottom: 20px; }
    .m-hero h1 { margin: 0; font-weight: 800; font-size: 1.4rem; }
    .m-hero p { margin: 6px 0 0; opacity: .9; font-size: .95rem; }
    .m-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 22px; }
    .m-kpi { background: #fff; border-radius: 14px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
    .m-kpi__label { font-size: .78rem; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
    .m-kpi__value { font-size: 2rem; font-weight: 800; color: #0b4f6c; margin-top: 6px; }

    .m-table-wrap { background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
    .m-table { width: 100%; border-collapse: collapse; }
    .m-table th { background: #f1f5f9; text-align: left; padding: 12px 16px; font-size: .78rem; text-transform: uppercase; color: #475569; font-weight: 700; letter-spacing: .5px; }
    .m-table td { padding: 14px 16px; border-top: 1px solid #f1f5f9; font-size: .9rem; vertical-align: middle; }
    .m-table tr:hover td { background: #f8fafc; }
    .m-rank { font-weight: 800; color: #0b4f6c; width: 50px; }
    .m-logo { width: 36px; height: 36px; object-fit: cover; border-radius: 8px; }
    .m-logo-blank { width: 36px; height: 36px; background: #e5e7eb; border-radius: 8px; display: inline-block; }
    .m-count { font-weight: 700; color: #0b4f6c; }
    .m-link { font-size: .78rem; color: #0b4f6c; text-decoration: none; }
    .m-link:hover { text-decoration: underline; }
    .m-copy-btn { background: #0b4f6c; color: #fff; border: none; border-radius: 6px; padding: 4px 10px; font-size: .74rem; font-weight: 700; cursor: pointer; margin-left: 8px; }
    .m-copy-btn.ok { background: #10b981; }
    .m-badge-off { background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 999px; font-size: .72rem; font-weight: 700; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="m-hero">
        <h1><i class="fas fa-chart-pie"></i> Métricas por entidade parceira</h1>
        <p>Quem distribuiu melhor o link da ação. Útil pra devolver pros parceiros: "sua divulgação trouxe X pessoas".</p>
    </div>

    <div class="m-kpis">
        <div class="m-kpi">
            <div class="m-kpi__label">Visitas totais</div>
            <div class="m-kpi__value">{{ number_format($settings->total_visits, 0, ',', '.') }}</div>
        </div>
        <div class="m-kpi">
            <div class="m-kpi__label">Entidades cadastradas</div>
            <div class="m-kpi__value">{{ $partners->count() }}</div>
        </div>
        <div class="m-kpi">
            <div class="m-kpi__label">Entidades ativas</div>
            <div class="m-kpi__value">{{ $partners->where('is_active', true)->count() }}</div>
        </div>
        <div class="m-kpi">
            <div class="m-kpi__label">Entidades que trouxeram visita</div>
            <div class="m-kpi__value">{{ $partners->where('visits_count', '>', 0)->count() }}</div>
        </div>
    </div>

    <div class="m-table-wrap">
        <table class="m-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Entidade</th>
                    <th>Link exclusivo</th>
                    <th style="text-align:right;">Visitas</th>
                    <th>Última visita</th>
                </tr>
            </thead>
            <tbody>
                @forelse($partners as $i => $partner)
                    <tr>
                        <td class="m-rank">{{ $i + 1 }}º</td>
                        <td>
                            <div style="display:flex; align-items:center; gap:12px;">
                                @if($partner->logo_url)
                                    <img src="{{ $partner->logo_url }}" alt="logo" class="m-logo">
                                @else
                                    <span class="m-logo-blank"></span>
                                @endif
                                <div>
                                    <div style="font-weight:600;">{{ $partner->name }} @unless($partner->is_active)<span class="m-badge-off">inativa</span>@endunless</div>
                                    @if($partner->contact_name)
                                        <div style="font-size:.78rem; color:#6b7280;">{{ $partner->contact_name }} {{ $partner->contact_phone ? ' · ' . $partner->contact_phone : '' }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <code style="font-size:.78rem; background:#f1f5f9; padding:3px 8px; border-radius:4px;">{{ url('/destine-ir/' . $partner->slug) }}</code>
                            <button type="button" class="m-copy-btn" data-link="{{ url('/destine-ir/' . $partner->slug) }}" onclick="copyLink(this)">Copiar</button>
                        </td>
                        <td style="text-align:right;" class="m-count">{{ number_format($partner->visits_count, 0, ',', '.') }}</td>
                        <td style="font-size:.85rem; color:#6b7280;">{{ optional($partner->last_visit_at)->format('d/m/Y H:i') ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center; padding:40px; color:#9ca3af;">Nenhuma entidade cadastrada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    function copyLink(btn) {
        const link = btn.getAttribute('data-link');
        navigator.clipboard.writeText(link).then(() => {
            const old = btn.textContent;
            btn.textContent = 'Copiado!';
            btn.classList.add('ok');
            setTimeout(() => { btn.textContent = old; btn.classList.remove('ok'); }, 1600);
        });
    }
</script>
@endsection
