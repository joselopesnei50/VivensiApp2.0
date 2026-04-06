@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h6 style="color: #ef4444; font-weight: 700; text-transform: uppercase; margin: 0 0 5px 0; letter-spacing: 1px;">Infraestrutura</h6>
        <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">Saúde do Servidor (AWS VPS)</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Monitoramento de carga, memória e recursos do sistema.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Load Average -->
    <div class="col-md-3">
        <div class="vivensi-card text-center" style="padding: 30px;">
            <div style="font-size: 2.5rem; color: #6366f1; margin-bottom: 10px;">
                <i class="fas fa-microchip"></i>
            </div>
            <h4 style="font-size: 0.9rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Load Average</h4>
            <div style="font-size: 1.5rem; font-weight: 800; color: #1e293b;">
                {{ $load }}
            </div>
            <p style="font-size: 0.75rem; color: #94a3b8; margin-top: 10px;">Carga média de processamento</p>
        </div>
    </div>

    <!-- Memory -->
    <div class="col-md-3">
        <div class="vivensi-card text-center" style="padding: 30px;">
            <div style="font-size: 2.5rem; color: #10b981; margin-bottom: 10px;">
                <i class="fas fa-memory"></i>
            </div>
            <h4 style="font-size: 0.9rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Memória RAM</h4>
            <div style="font-size: 1.5rem; font-weight: 800; color: #1e293b;">
                {{ $memory }}
            </div>
            <p style="font-size: 0.75rem; color: #94a3b8; margin-top: 10px;">Uso de memória física</p>
        </div>
    </div>

    <!-- Disk -->
    <div class="col-md-3">
        <div class="vivensi-card text-center" style="padding: 30px;">
            <div style="font-size: 2.5rem; color: #f59e0b; margin-bottom: 10px;">
                <i class="fas fa-hdd"></i>
            </div>
            <h4 style="font-size: 0.9rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Espaço em Disco</h4>
            <div style="font-size: 1.5rem; font-weight: 800; color: #1e293b;">
                {{ $disk }}
            </div>
            <p style="font-size: 0.75rem; color: #94a3b8; margin-top: 10px;">SSD / NVMe Disponível</p>
        </div>
    </div>

    <!-- Uptime -->
    <div class="col-md-3">
        <div class="vivensi-card text-center" style="padding: 30px;">
            <div style="font-size: 2.5rem; color: #ef4444; margin-bottom: 10px;">
                <i class="fas fa-clock"></i>
            </div>
            <h4 style="font-size: 0.9rem; color: #64748b; font-weight: 700; text-transform: uppercase;">Uptime</h4>
            <div style="font-size: 1.5rem; font-weight: 800; color: #1e293b;">
                {{ str_replace('up ', '', $uptime) }}
            </div>
            <p style="font-size: 0.75rem; color: #94a3b8; margin-top: 10px;">Tempo online sem interrupção</p>
        </div>
    </div>
</div>

<div class="vivensi-card mt-4" style="padding: 30px;">
    <h4 style="margin: 0 0 20px 0; font-size: 1.1rem; color: #334155; font-weight: 700;">Ambiente de Execução</h4>
    <div class="row">
        <div class="col-md-4">
            <div class="p-3 bg-light rounded-4 mb-3">
                <span class="text-muted small">Versão do PHP</span><br>
                <strong>{{ PHP_VERSION }}</strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 bg-light rounded-4 mb-3">
                <span class="text-muted small">Sistema Operacional</span><br>
                <strong>{{ PHP_OS_FAMILY }} ({{ PHP_OS }})</strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 bg-light rounded-4 mb-3">
                <span class="text-muted small">Web Server</span><br>
                <strong>{{ $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' }}</strong>
            </div>
        </div>
    </div>
    <div class="alert alert-warning border-0 rounded-4 mt-3" style="font-size: 0.85rem;">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Nota: Algumas métricas (como Load e Uptime) são otimizadas para sistemas Linux (VPS AWS). Em ambiente local Windows, os dados podem ser limitados.
    </div>
</div>
@endsection
