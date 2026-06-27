@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 24px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:14px;">
        <div>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                <span style="background: var(--primary-color); width: 12px; height: 3px; border-radius: 2px;"></span>
                <h6 style="color: var(--primary-color); font-weight:800; text-transform:uppercase; margin:0; letter-spacing:2px; font-size:0.7rem;">WhatsApp / Formulários</h6>
            </div>
            <h2 style="margin:0; color:#1e293b; font-weight:900; font-size:2.4rem; letter-spacing:-1px;">Formulários conversacionais</h2>
            <p style="color:#64748b; margin-top:8px;">Crie questionários que o bot envia pergunta por pergunta na conversa. Respostas com <code>field_key</code> = phone/email/name/city/tags viram Lead automático.</p>
        </div>
        <a href="{{ route('whatsapp.forms.create') }}" class="btn-premium" style="padding:13px 24px;">
            <i class="fas fa-plus me-2"></i> Novo formulário
        </a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="vivensi-card" style="padding:0; overflow:hidden;">
    @if($forms->isEmpty())
        <div style="padding:60px 30px; text-align:center;">
            <div style="font-size:3rem; color:#cbd5e1; margin-bottom:10px;"><i class="fas fa-clipboard-list"></i></div>
            <h4 style="color:#334155; font-weight:800; margin-bottom:6px;">Nenhum formulário ainda</h4>
            <p style="color:#64748b;">Crie o primeiro pra começar a coletar dados dos contatos via WhatsApp.</p>
        </div>
    @else
        <table class="table" style="margin:0;">
            <thead style="background:#f8fafc;">
                <tr>
                    <th style="padding:14px 18px; font-size:.75rem; text-transform:uppercase; letter-spacing:1px; color:#64748b;">Nome</th>
                    <th style="padding:14px 18px; font-size:.75rem; text-transform:uppercase; letter-spacing:1px; color:#64748b;">Perguntas</th>
                    <th style="padding:14px 18px; font-size:.75rem; text-transform:uppercase; letter-spacing:1px; color:#64748b;">Status</th>
                    <th style="padding:14px 18px; font-size:.75rem; text-transform:uppercase; letter-spacing:1px; color:#64748b;">Criado</th>
                    <th style="padding:14px 18px; font-size:.75rem; text-transform:uppercase; letter-spacing:1px; color:#64748b; text-align:right;">Ações</th>
                </tr>
            </thead>
            <tbody>
            @foreach($forms as $f)
                <tr style="border-top:1px solid #f1f5f9;">
                    <td style="padding:14px 18px;">
                        <div style="font-weight:800; color:#1e293b;">{{ $f->name }}</div>
                        @if($f->description)
                            <div style="font-size:.78rem; color:#64748b; margin-top:2px;">{{ \Illuminate\Support\Str::limit($f->description, 90) }}</div>
                        @endif
                    </td>
                    <td style="padding:14px 18px; color:#64748b;">
                        <span style="background:#eef2ff; color:#4338ca; font-size:.75rem; font-weight:700; padding:3px 10px; border-radius:99px;">{{ $f->questions_count }}</span>
                    </td>
                    <td style="padding:14px 18px;">
                        @if($f->is_active)
                            <span style="background:#ecfdf5; color:#047857; font-size:.72rem; font-weight:700; padding:4px 10px; border-radius:99px;">Ativo</span>
                        @else
                            <span style="background:#fef2f2; color:#b91c1c; font-size:.72rem; font-weight:700; padding:4px 10px; border-radius:99px;">Inativo</span>
                        @endif
                    </td>
                    <td style="padding:14px 18px; color:#94a3b8; font-size:.85rem;">{{ optional($f->created_at)->format('d/m/Y') }}</td>
                    <td style="padding:14px 18px; text-align:right; white-space:nowrap;">
                        <a href="{{ route('whatsapp.forms.edit', $f->id) }}" class="btn btn-sm btn-outline-primary" style="font-size:.75rem;">
                            <i class="fas fa-edit me-1"></i> Editar
                        </a>
                        <form action="{{ route('whatsapp.forms.duplicate', $f->id) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;" title="Duplicar">
                                <i class="fas fa-copy"></i>
                            </button>
                        </form>
                        <form action="{{ route('whatsapp.forms.destroy', $f->id) }}" method="POST" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:.75rem;" onclick="return confirm('Remover o formulário {{ $f->name }}?')" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div style="padding:14px;">{{ $forms->links() }}</div>
    @endif
</div>
@endsection
