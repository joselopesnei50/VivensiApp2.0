@extends('layouts.app')

@section('content')
@php
    $days = $grant->deadline ? now()->diffInDays($grant->deadline, false) : null;
    $statusLabels = [
        'open' => ['label' => 'Ativo', 'class' => 'bg-success-subtle text-success'],
        'reporting' => ['label' => 'Prestação de Contas', 'class' => 'bg-warning-subtle text-warning'],
        'closed' => ['label' => 'Encerrado', 'class' => 'bg-light text-muted'],
    ];
    $curr = $statusLabels[$grant->status ?? 'open'] ?? $statusLabels['open'];
    $isExpired = $grant->deadline && $grant->deadline->isPast() && ($grant->status ?? 'open') !== 'closed';
@endphp

<div class="vivensi-card" style="max-width: 900px; margin: 0 auto; padding: 40px;">
    <div style="display:flex; justify-content: space-between; align-items:flex-start; gap: 20px; margin-bottom: 22px;">
        <div>
            <h3 style="margin: 0; font-size: 1.6rem; color: #1e293b; font-weight: 900;">{{ $grant->title }}</h3>
            <div class="text-muted small" style="margin-top: 6px;">ID: #{{ $grant->id }}</div>
        </div>
        <div style="display:flex; gap: 10px; align-items:center;">
            <a href="{{ url('/ngo/grants') }}" class="btn btn-outline-secondary" style="border-radius: 12px;">Voltar</a>
            <form action="{{ route('ngo.grants.destroy', $grant->id) }}" method="POST" onsubmit="return confirm('Excluir este convênio/edital? Esta ação não pode ser desfeita.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" style="border-radius: 12px;">
                    <i class="fas fa-trash-alt me-1"></i> Excluir
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        </div>
    @endif
    @if($isExpired)
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4">
            <strong><i class="fas fa-triangle-exclamation me-2"></i>Prazo vencido.</strong>
            Este convênio/edital está vencido e ainda não está marcado como <strong>Encerrado</strong>. Revise o status e a prestação de contas.
        </div>
    @endif

    <div class="row g-4">
        <div class="col-md-6">
            <div class="p-4" style="border: 1px solid #f1f5f9; border-radius: 18px; background: #fff;">
                <div class="text-muted small" style="font-weight: 800; text-transform: uppercase; letter-spacing: .06em;">Concedente</div>
                <div style="margin-top: 6px; font-weight: 800; color:#0f172a;">{{ $grant->agency ?: '-' }}</div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="p-4" style="border: 1px solid #f1f5f9; border-radius: 18px; background: #fff;">
                <div class="text-muted small" style="font-weight: 800; text-transform: uppercase; letter-spacing: .06em;">Valor global</div>
                <div style="margin-top: 6px; font-weight: 900; font-size: 1.4rem; color:#4f46e5;">
                    R$ {{ number_format($grant->value, 2, ',', '.') }}
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="p-4" style="border: 1px solid #f1f5f9; border-radius: 18px; background: #fff;">
                <div class="text-muted small" style="font-weight: 800; text-transform: uppercase; letter-spacing: .06em;">Processo / Contrato</div>
                <div style="margin-top: 6px; font-weight: 800; color:#0f172a;">{{ $grant->contract_number ?: '-' }}</div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="p-4" style="border: 1px solid #f1f5f9; border-radius: 18px; background: #fff;">
                <div class="text-muted small" style="font-weight: 800; text-transform: uppercase; letter-spacing: .06em;">Início da vigência</div>
                <div style="margin-top: 6px; font-weight: 800; color:#0f172a;">{{ $grant->start_date ? $grant->start_date->format('d/m/Y') : '-' }}</div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="p-4" style="border: 1px solid #f1f5f9; border-radius: 18px; background: #fff;">
                <div class="text-muted small" style="font-weight: 800; text-transform: uppercase; letter-spacing: .06em;">Prazo / Deadline</div>
                <div style="margin-top: 6px; font-weight: 800; color:#0f172a;">
                    {{ $grant->deadline ? $grant->deadline->format('d/m/Y') : '-' }}
                </div>
                @if($days !== null)
                    <div class="small" style="margin-top: 6px; color: {{ $days < 0 ? '#b91c1c' : ($days < 30 ? '#b91c1c' : '#64748b') }};">
                        <i class="far fa-clock me-1"></i>
                        @if($grant->deadline && $days < 0)
                            Vencido há {{ abs((int) $days) }} dias
                        @else
                            {{ (int) $days }} dias
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="col-md-6">
            <div class="p-4" style="border: 1px solid #f1f5f9; border-radius: 18px; background: #fff;">
                <div class="text-muted small" style="font-weight: 800; text-transform: uppercase; letter-spacing: .06em;">Status</div>
                <div style="margin-top: 10px;">
                    <span class="badge rounded-pill px-3 py-2 {{ $curr['class'] }}" style="font-weight:800;">{{ $curr['label'] }}</span>
                </div>
                <form action="{{ route('ngo.grants.status', $grant->id) }}" method="POST" style="margin-top: 12px; display:flex; gap:10px; align-items:center;">
                    @csrf
                    <select name="status" class="form-select form-select-sm" style="max-width: 220px; border-radius: 12px;">
                        <option value="open" {{ ($grant->status ?? 'open') === 'open' ? 'selected' : '' }}>Ativo</option>
                        <option value="reporting" {{ ($grant->status ?? 'open') === 'reporting' ? 'selected' : '' }}>Prestação de Contas</option>
                        <option value="closed" {{ ($grant->status ?? 'open') === 'closed' ? 'selected' : '' }}>Encerrado</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm" style="border-radius: 12px; background:#4f46e5; border:none;">
                        Atualizar
                    </button>
                </form>
            </div>
        </div>

        <div class="col-12">
            <div class="p-4" style="border: 1px solid #f1f5f9; border-radius: 18px; background: #fff;">
                <div class="text-muted small" style="font-weight: 800; text-transform: uppercase; letter-spacing: .06em;">Observações / Requisitos</div>
                <div style="margin-top: 10px; color:#0f172a; white-space: pre-wrap;">{{ $grant->notes ?: '-' }}</div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <div id="documents" style="display:flex; justify-content: space-between; align-items:flex-end; gap: 16px; margin-bottom: 12px;">
            <div>
                <h4 style="margin:0; font-weight: 900; color:#0f172a;">Documentos</h4>
                <div class="text-muted small">Anexe o edital, plano de trabalho, comprovantes e anexos para centralizar a prestação de contas.</div>
            </div>
        </div>

        <div class="p-4" style="border: 1px solid #f1f5f9; border-radius: 18px; background: #fff;">
            <form action="{{ route('ngo.grants.documents.upload', $grant->id) }}" method="POST" enctype="multipart/form-data" style="display:grid; grid-template-columns: 1.3fr .8fr 1.2fr; gap: 12px; align-items:end;">
                @csrf
                <div>
                    <label class="form-label small text-muted" style="font-weight:800;">Título</label>
                    <input type="text" name="title" class="form-control" placeholder="Ex: Edital completo" required style="border-radius: 12px;">
                </div>
                <div>
                    <label class="form-label small text-muted" style="font-weight:800;">Tipo</label>
                    <select name="type" class="form-select" style="border-radius: 12px;">
                        <option value="edital">Edital</option>
                        <option value="plano_trabalho">Plano de trabalho</option>
                        <option value="comprovante">Comprovante</option>
                        <option value="anexo">Anexo</option>
                        <option value="outros" selected>Outros</option>
                    </select>
                </div>
                <div>
                    <label class="form-label small text-muted" style="font-weight:800;">Arquivo</label>
                    <input type="file" name="file" class="form-control" required style="border-radius: 12px;">
                </div>
                <div style="grid-column: 1 / -1; display:flex; justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary" style="border-radius: 14px; background:#4f46e5; border:none; font-weight:800;">
                        <i class="fas fa-paperclip me-1"></i> Anexar
                    </button>
                </div>
            </form>

            <div style="margin-top: 18px;">
                @if(($grant->documents?->count() ?? 0) === 0)
                    <div class="text-muted small" style="padding: 14px; background:#f8fafc; border-radius: 14px;">
                        Nenhum documento anexado ainda.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr class="small text-muted" style="text-transform: uppercase; letter-spacing: .06em;">
                                    <th>Título</th>
                                    <th>Tipo</th>
                                    <th>Arquivo</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($grant->documents as $doc)
                                    <tr>
                                        <td class="fw-bold text-dark">{{ $doc->title }}</td>
                                        <td><span class="badge bg-light text-dark border rounded-pill">{{ $doc->type }}</span></td>
                                        <td class="text-muted small">{{ $doc->original_name ?: basename($doc->file_path) }}</td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-secondary" style="border-radius: 12px;"
                                               href="{{ route('ngo.grants.documents.download', ['id' => $grant->id, 'docId' => $doc->id]) }}">
                                                <i class="fas fa-download me-1"></i> Baixar
                                            </a>
                                            <form action="{{ route('ngo.grants.documents.delete', ['id' => $grant->id, 'docId' => $doc->id]) }}"
                                                  method="POST" style="display:inline;" onsubmit="return confirm('Remover este documento?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius: 12px;">
                                                    <i class="fas fa-trash-alt me-1"></i> Remover
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

