@extends('layouts.app')
@section('title', 'Licoes do Bruno')

@section('content')
<div class="container" style="max-width:1100px; margin:24px auto;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <div>
            <h1 style="margin:0; color:#0f172a;">📚 Licoes do Bruno</h1>
            <p style="color:#64748b; margin:4px 0 0;">
                Conversas fechadas cadastradas manualmente. Bruno usa como few-shots dinamicos por match de tag.
            </p>
        </div>
        <a href="{{ route('admin.bruno.lessons.create') }}"
           style="background:#10b981; color:#fff; padding:10px 18px; border-radius:8px; text-decoration:none; font-weight:600;">
            + Nova licao
        </a>
    </div>

    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; padding:12px 16px; border-radius:8px; margin-bottom:16px;">
            {{ session('success') }}
        </div>
    @endif

    @if($lessons->isEmpty())
        <div style="background:#fff; padding:60px 20px; border-radius:10px; text-align:center; color:#94a3b8;">
            Nenhuma licao cadastrada ainda. Comece cadastrando a primeira conversa que fechou.
        </div>
    @else
        <div style="background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.05);">
            <table style="width:100%; border-collapse:collapse;">
                <thead style="background:#f8fafc; text-align:left;">
                    <tr style="font-size:.8rem; text-transform:uppercase; color:#64748b;">
                        <th style="padding:12px 16px;">Titulo</th>
                        <th style="padding:12px 16px;">Tags</th>
                        <th style="padding:12px 16px;">Tenant</th>
                        <th style="padding:12px 16px;">Status</th>
                        <th style="padding:12px 16px; width:130px;">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lessons as $l)
                        <tr style="border-top:1px solid #f1f5f9;">
                            <td style="padding:12px 16px;">
                                <div style="font-weight:600; color:#0f172a;">{{ $l->title }}</div>
                                <div style="font-size:.78rem; color:#94a3b8;">
                                    {{ \Illuminate\Support\Str::limit($l->situation, 80) }}
                                </div>
                            </td>
                            <td style="padding:12px 16px;">
                                @foreach((array)($l->tags ?? []) as $t)
                                    <span style="background:#eff6ff; color:#1d4ed8; padding:3px 8px; border-radius:12px; font-size:.72rem; font-weight:600; margin-right:4px;">{{ $t }}</span>
                                @endforeach
                            </td>
                            <td style="padding:12px 16px; font-size:.85rem; color:#64748b;">
                                {{ $l->tenant_id ? "#{$l->tenant_id}" : 'Global' }}
                            </td>
                            <td style="padding:12px 16px;">
                                @if($l->active)
                                    <span style="background:#dcfce7; color:#166534; padding:3px 10px; border-radius:12px; font-size:.72rem; font-weight:600;">Ativa</span>
                                @else
                                    <span style="background:#f1f5f9; color:#64748b; padding:3px 10px; border-radius:12px; font-size:.72rem; font-weight:600;">Inativa</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px;">
                                <a href="{{ route('admin.bruno.lessons.edit', $l) }}" style="color:#6366f1; font-size:.85rem; text-decoration:none; margin-right:8px;">Editar</a>
                                <form method="POST" action="{{ route('admin.bruno.lessons.destroy', $l) }}"
                                      style="display:inline;"
                                      onsubmit="return confirm('Remover essa licao?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="color:#dc2626; font-size:.85rem; background:none; border:none; cursor:pointer; padding:0;">
                                        Remover
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;">
            {{ $lessons->links() }}
        </div>
    @endif
</div>
@endsection
