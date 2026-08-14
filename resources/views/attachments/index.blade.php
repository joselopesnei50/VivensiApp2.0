@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Anexos · {{ $label }}</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">
            @if(isset($owner->name))
                {{ $owner->name }}
            @elseif(isset($owner->description))
                {{ Str::limit($owner->description, 80) }}
            @else
                Registro #{{ $owner->id }}
            @endif
        </p>
    </div>
    <div>
        <a href="{{ url($backUrl) }}" class="btn-premium" style="background:#64748b;">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom: 20px;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" style="margin-bottom: 20px;">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" style="margin-bottom: 20px;">
        <ul style="margin: 0;">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="vivensi-card" style="margin-bottom: 25px;">
    <h3 style="margin: 0 0 15px 0; color: #2c3e50;"><i class="fas fa-upload" style="color: #4f46e5;"></i> Enviar novo anexo</h3>
    <form action="{{ route('attachments.store', ['morphType' => $morphType, 'morphId' => $morphId]) }}"
          method="POST" enctype="multipart/form-data">
        @csrf
        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            @if(!empty($tiposDocumento))
                <select name="tipo_documento" required
                        class="form-control-vivensi" style="min-width: 220px;">
                    <option value="">Tipo de documento…</option>
                    @foreach($tiposDocumento as $slug => $rotulo)
                        <option value="{{ $slug }}">{{ $rotulo }}</option>
                    @endforeach
                </select>
            @endif
            <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required
                   class="form-control-vivensi" style="flex: 1; min-width: 260px;">
            <button type="submit" class="btn-premium">
                <i class="fas fa-paper-plane"></i> Enviar
            </button>
        </div>
        <p style="color: #64748b; font-size: 0.85rem; margin: 8px 0 0 0;">
            Tipos aceitos: PDF, JPG, PNG · Tamanho máximo: {{ $maxSizeMb }} MB
        </p>
    </form>
</div>

<div class="vivensi-card">
    <h3 style="margin: 0 0 15px 0; color: #2c3e50;">
        <i class="fas fa-paperclip" style="color: #4f46e5;"></i>
        Anexos ({{ $attachments->count() }})
    </h3>

    @if($attachments->isEmpty())
        <p style="color: #94a3b8; padding: 20px; text-align: center; margin: 0;">Nenhum anexo ainda.</p>
    @else
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc;">
                    <th style="padding: 12px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Arquivo</th>
                    @if(!empty($tiposDocumento))
                        <th style="padding: 12px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Documento</th>
                    @endif
                    <th style="padding: 12px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">MIME</th>
                    <th style="padding: 12px; text-align: right; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Tamanho</th>
                    <th style="padding: 12px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Enviado em</th>
                    <th style="padding: 12px; text-align: center; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attachments as $att)
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 12px; color: #334155;">
                        <i class="fas fa-file{{ Str::startsWith($att->mime_type, 'image/') ? '-image' : '-pdf' }}" style="color: #4f46e5; margin-right: 6px;"></i>
                        {{ $att->original_name }}
                    </td>
                    @if(!empty($tiposDocumento))
                        <td style="padding: 12px; color: #334155; font-size: 0.85rem;">
                            {{ $tiposDocumento[$att->tipo_documento] ?? ($att->tipo_documento ?: '—') }}
                        </td>
                    @endif
                    <td style="padding: 12px; color: #64748b; font-size: 0.85rem;">{{ $att->mime_type }}</td>
                    <td style="padding: 12px; text-align: right; color: #64748b; font-size: 0.85rem;">
                        {{ number_format($att->size_bytes / 1024, 1, ',', '.') }} KB
                    </td>
                    <td style="padding: 12px; color: #64748b; font-size: 0.85rem;">
                        {{ $att->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td style="padding: 12px; text-align: center; white-space: nowrap;">
                        <a href="{{ route('attachments.download', $att->id) }}" target="_blank"
                           style="background:none; border:none; color:#0284c7; padding: 0 6px;" title="Baixar">
                            <i class="fas fa-download"></i>
                        </a>
                        @if($canDelete)
                            <form action="{{ route('attachments.destroy', $att->id) }}" method="POST"
                                  onsubmit="return confirm('Remover este anexo?');" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer; padding:0 6px;" title="Remover">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
