@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 22px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Certificados de Voluntariado</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Histórico, filtros e exportação para prestação de contas.</p>
    </div>
    <div style="display:flex; gap:10px; flex-wrap: wrap;">
        <a class="btn-premium" style="background:#111827;" href="{{ url('/ngo/hr') }}">
            <i class="fas fa-arrow-left"></i> Voltar ao RH
        </a>
        <a class="btn-premium" style="background:#4f46e5;" href="{{ url('/ngo/hr/certificates/export') . '?' . http_build_query(request()->query()) }}">
            <i class="fas fa-file-csv"></i> Exportar CSV
        </a>
        <button class="btn-premium" style="background:#f1f5f9; color:#0f172a;" onclick="window.print()">
            <i class="fas fa-print"></i> Imprimir
        </button>
    </div>
</div>

<div class="vivensi-card" style="margin-bottom: 14px;">
    <form method="GET" action="{{ url('/ngo/hr/certificates') }}" style="display:flex; flex-wrap: wrap; gap: 10px; align-items: end;">
        <div class="form-group" style="min-width: 240px; margin:0;">
            <label>Busca</label>
            <input class="form-control-vivensi" type="text" name="q" value="{{ $q ?? '' }}" placeholder="Voluntário, e-mail ou atividade">
        </div>
        <div class="form-group" style="min-width: 240px; margin:0;">
            <label>Voluntário</label>
            <select class="form-control-vivensi" name="volunteer_id">
                <option value="">Todos</option>
                @foreach($volunteers as $v)
                    <option value="{{ (int) $v->id }}" @if(!empty($volunteerId) && (int) $volunteerId === (int) $v->id) selected @endif>
                        {{ $v->name ?? ('Voluntário #'.$v->id) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label>De</label>
            <input class="form-control-vivensi" type="date" name="from" value="{{ $from ?? '' }}">
        </div>
        <div class="form-group" style="margin:0;">
            <label>Até</label>
            <input class="form-control-vivensi" type="date" name="to" value="{{ $to ?? '' }}">
        </div>
        <div style="display:flex; gap:10px;">
            <button class="btn-premium" type="submit">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            <a class="btn-premium" style="background:#f1f5f9; color:#0f172a;" href="{{ url('/ngo/hr/certificates') }}">
                Limpar
            </a>
        </div>
    </form>
</div>

<div class="vivensi-card" style="padding:0; overflow:hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <tr>
                <th style="padding: 12px; text-align:left; font-size:.8rem; color:#64748b; text-transform: uppercase;">Data</th>
                <th style="padding: 12px; text-align:left; font-size:.8rem; color:#64748b; text-transform: uppercase;">Voluntário</th>
                <th style="padding: 12px; text-align:left; font-size:.8rem; color:#64748b; text-transform: uppercase;">Atividade</th>
                <th style="padding: 12px; text-align:center; font-size:.8rem; color:#64748b; text-transform: uppercase;">Horas</th>
                <th style="padding: 12px; text-align:right; font-size:.8rem; color:#64748b; text-transform: uppercase;">Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach($certs as $c)
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 12px; color:#334155;">
                        {{ optional($c->issued_at)->format('d/m/Y') ?? '—' }}
                        <div style="color:#94a3b8; font-size:.8rem;">#{{ (int) $c->id }}</div>
                    </td>
                    <td style="padding: 12px;">
                        <strong style="display:block; color:#0f172a;">{{ $c->volunteer_name ?? '—' }}</strong>
                        <span style="color:#64748b; font-size:.85rem;">{{ $c->volunteer_email ?? '' }}</span>
                    </td>
                    <td style="padding: 12px; color:#334155;">
                        {{ $c->activity_description }}
                    </td>
                    <td style="padding: 12px; text-align:center; font-weight:800; color:#0f172a;">
                        {{ number_format((int) ($c->hours ?? 0)) }}
                    </td>
                    <td style="padding: 12px; text-align:right;">
                        <div style="display:flex; gap: 10px; justify-content: flex-end; align-items:center; flex-wrap: wrap;">
                            <a class="btn-premium" style="font-size:.85rem; background:#111827;" href="{{ url('/ngo/hr/certificates/'.$c->id.'/download') }}">
                                <i class="fas fa-download"></i> Baixar PDF
                            </a>
                            @php
                                $phone = preg_replace('/\\D+/', '', (string) ($c->volunteer_phone ?? ''));
                                $link = $validateLinks[(int) $c->id] ?? '';
                                $msg = $link ? ("Olá! Segue o link para validar seu Certificado de Voluntariado: " . $link) : '';
                            @endphp
                            @if(!empty($phone) && !empty($link))
                                <a class="btn-premium" target="_blank" rel="noopener" style="font-size:.85rem; background:#dcfce7; color:#166534;" href="https://wa.me/{{ $phone }}?text={{ urlencode($msg) }}">
                                    <i class="fab fa-whatsapp"></i> Enviar link
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            @if($certs->count() === 0)
                <tr>
                    <td colspan="5" style="padding: 26px; text-align:center; color:#94a3b8;">
                        Nenhum certificado encontrado para os filtros selecionados.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

<div style="margin-top: 14px;">
    {{ $certs->links() }}
</div>
@endsection

