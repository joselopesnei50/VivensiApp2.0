@extends('layouts.app')

@section('content')
@php
    $basePath = rtrim(request()->getBaseUrl(), '/');
    $banks = [
        ['name' => 'Banco do Brasil', 'icon' => 'fas fa-landmark',  'color' => '#f59e0b'],
        ['name' => 'Itaú',            'icon' => 'fas fa-university', 'color' => '#ef4444'],
        ['name' => 'Bradesco',        'icon' => 'fas fa-university', 'color' => '#dc2626'],
        ['name' => 'Santander',       'icon' => 'fas fa-university', 'color' => '#b91c1c'],
        ['name' => 'Nubank',          'icon' => 'fas fa-credit-card','color' => '#7c3aed'],
        ['name' => 'Inter',           'icon' => 'fas fa-piggy-bank', 'color' => '#ea580c'],
    ];
@endphp

{{-- Header --}}
<div class="d-flex align-items-start gap-3 mb-4 flex-wrap">
    <div class="flex-1">
        <div style="font-size:.68rem;color:#94a3b8;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;margin-bottom:4px;">
            Gestão / Conciliação Bancária
        </div>
        <h2 class="fw-800 mb-0" style="font-size:1.6rem;color:#0f172a;line-height:1.2;">Conciliação Bancária</h2>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">Importe extratos OFX e sincronize com o financeiro da organização.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4">{{ session('error') }}</div>
@endif

<div class="row g-4 justify-content-center">

    {{-- Upload card --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">

                <div class="d-flex align-items-center gap-2 mb-4">
                    <span style="width:34px;height:34px;background:rgba(2,132,199,.1);border-radius:10px;display:inline-flex;align-items:center;justify-content:center;">
                        <i class="fas fa-file-import" style="color:#0284c7;font-size:.85rem;"></i>
                    </span>
                    <h6 class="fw-bold mb-0" style="font-size:.95rem;color:#0f172a;">Importar Extrato OFX</h6>
                </div>

                <form action="{{ $basePath . '/ngo/reconciliation/upload' }}" method="POST"
                      enctype="multipart/form-data" id="ofxForm">
                    @csrf

                    {{-- Drop zone --}}
                    <label for="ofx_file" class="drop-zone" id="dropZone">
                        <div class="drop-zone-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="drop-zone-text">
                            <span class="fw-bold" style="color:#0f172a;font-size:.95rem;">
                                Arraste o arquivo aqui
                            </span>
                            <span style="font-size:.82rem;color:#94a3b8;display:block;margin-top:4px;">
                                ou clique para selecionar
                            </span>
                        </div>
                        <div id="fileNameDisplay" class="drop-zone-filename d-none">
                            <i class="fas fa-file-check me-2" style="color:#10b981;"></i>
                            <span id="fileNameText"></span>
                        </div>
                        <input type="file" id="ofx_file" name="ofx_file"
                               accept=".ofx" required class="d-none">
                        <div class="drop-zone-hint">Formato aceito: <strong>.ofx</strong></div>
                    </label>

                    <button type="submit" id="submitBtn"
                            class="btn btn-primary w-100 fw-bold rounded-3 py-3 mt-3 d-flex align-items-center justify-content-center gap-2"
                            style="font-size:.95rem;">
                        <i class="fas fa-sync-alt"></i> Processar Arquivo
                    </button>
                </form>

            </div>
        </div>
    </div>

    {{-- Info sidebar --}}
    <div class="col-lg-5">

        {{-- Steps --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span style="width:34px;height:34px;background:rgba(99,102,241,.1);border-radius:10px;display:inline-flex;align-items:center;justify-content:center;">
                        <i class="fas fa-list-check" style="color:#6366f1;font-size:.85rem;"></i>
                    </span>
                    <h6 class="fw-bold mb-0" style="font-size:.9rem;color:#0f172a;">Como funciona</h6>
                </div>

                @foreach([
                    ['1', '#6366f1', 'Exporte o extrato', 'No seu internet banking, exporte o período desejado no formato OFX.'],
                    ['2', '#0284c7', 'Importe aqui',      'Selecione ou arraste o arquivo .ofx no campo ao lado.'],
                    ['3', '#10b981', 'Revise e confirme', 'O sistema exibe os lançamentos para você confirmar ou ignorar.'],
                ] as [$n, $color, $title, $desc])
                <div class="d-flex gap-3 {{ !$loop->last ? 'mb-3' : '' }}">
                    <div style="width:28px;height:28px;border-radius:50%;background:{{ $color }};color:#fff;font-size:.72rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
                        {{ $n }}
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:.85rem;color:#0f172a;">{{ $title }}</div>
                        <div style="font-size:.78rem;color:#64748b;margin-top:2px;">{{ $desc }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Supported banks --}}
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span style="width:34px;height:34px;background:rgba(16,185,129,.1);border-radius:10px;display:inline-flex;align-items:center;justify-content:center;">
                        <i class="fas fa-building-columns" style="color:#10b981;font-size:.85rem;"></i>
                    </span>
                    <h6 class="fw-bold mb-0" style="font-size:.9rem;color:#0f172a;">Bancos suportados</h6>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    @foreach($banks as $bank)
                    <span class="bank-pill">
                        <i class="{{ $bank['icon'] }}" style="color:{{ $bank['color'] }};font-size:.7rem;"></i>
                        {{ $bank['name'] }}
                    </span>
                    @endforeach
                </div>

                <p class="text-muted mb-0 mt-3" style="font-size:.75rem;">
                    Qualquer banco que exporte no padrão OFX também é compatível.
                </p>
            </div>
        </div>

    </div>
</div>

@push('styles')
<style>
.fw-800 { font-weight: 800; }
.flex-1 { flex: 1; }

/* Drop zone */
.drop-zone {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 40px 24px;
    border: 2px dashed #cbd5e1;
    border-radius: 16px;
    background: #f8fafc;
    cursor: pointer;
    transition: border-color .2s, background .2s;
    text-align: center;
    min-height: 200px;
}
.drop-zone:hover,
.drop-zone.drag-over {
    border-color: #6366f1;
    background: #eef2ff;
}
.drop-zone.has-file {
    border-color: #10b981;
    background: #f0fdf4;
}

.drop-zone-icon {
    width: 56px; height: 56px;
    border-radius: 16px;
    background: rgba(2,132,199,.1);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem;
    color: #0284c7;
    transition: background .2s;
}
.drop-zone:hover .drop-zone-icon,
.drop-zone.drag-over .drop-zone-icon { background: rgba(99,102,241,.15); color: #6366f1; }
.drop-zone.has-file .drop-zone-icon  { background: rgba(16,185,129,.15); color: #10b981; }

.drop-zone-text { line-height: 1.4; }

.drop-zone-filename {
    font-size: .83rem;
    font-weight: 600;
    color: #059669;
    display: flex; align-items: center;
}

.drop-zone-hint {
    font-size: .72rem;
    color: #94a3b8;
}

/* Bank pill */
.bank-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px;
    font-size: .72rem; font-weight: 600;
    background: #f1f5f9; color: #475569;
    white-space: nowrap;
}
</style>
@endpush

<script>
document.addEventListener('DOMContentLoaded', function () {
    const zone   = document.getElementById('dropZone');
    const input  = document.getElementById('ofx_file');
    const nameEl = document.getElementById('fileNameDisplay');
    const nameText = document.getElementById('fileNameText');

    function showFile(name) {
        nameText.textContent = name;
        nameEl.classList.remove('d-none');
        zone.classList.add('has-file');
        zone.querySelector('.drop-zone-text').classList.add('d-none');
        zone.querySelector('.drop-zone-hint').classList.add('d-none');
    }

    input.addEventListener('change', function () {
        if (this.files[0]) showFile(this.files[0].name);
    });

    zone.addEventListener('dragover', function (e) {
        e.preventDefault();
        zone.classList.add('drag-over');
    });
    zone.addEventListener('dragleave', function () {
        zone.classList.remove('drag-over');
    });
    zone.addEventListener('drop', function (e) {
        e.preventDefault();
        zone.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        if (file && file.name.endsWith('.ofx')) {
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            showFile(file.name);
        }
    });
});
</script>
@endsection
