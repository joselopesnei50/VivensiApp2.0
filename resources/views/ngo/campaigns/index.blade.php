@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Campanhas de Arrecadação</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Crie Landing Pages para captar recursos.</p>
    </div>
    <a href="{{ url('/ngo/campaigns/create') }}" class="btn-premium">
        <i class="fas fa-plus"></i> Nova Campanha
    </a>
</div>

@if(session('success'))
    <div style="background: #dcfce7; color: #16a34a; padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #bbf7d0;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

<div class="metrics-grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
    @foreach($campaigns as $campaign)
    <div class="vivensi-card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
        <div style="padding: 20px; flex: 1;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span style="background: #dbeafe; color: #1e40af; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">{{ strtoupper($campaign->status) }}</span>
                <span style="font-size: 0.8rem; color: #64748b;">{{ \Carbon\Carbon::parse($campaign->created_at)->format('d/m/Y') }}</span>
            </div>
            <h3 style="margin: 0 0 10px 0; font-size: 1.1rem; color: #1e293b;">{{ $campaign->title }}</h3>
            <p style="color: #64748b; font-size: 0.9rem; line-height: 1.5; margin-bottom: 20px;">
                {{ Str::limit($campaign->description, 100) }}
            </p>
            
            <div style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; margin-bottom: 5px;">
                    <span style="color: #64748b;">Arrecadado: R$ {{ number_format($campaign->current_amount, 2, ',', '.') }}</span>
                    <span style="color: #1e293b;">Meta: R$ {{ number_format($campaign->target_amount, 2, ',', '.') }}</span>
                </div>
                <div style="background: #f1f5f9; height: 8px; border-radius: 4px; overflow: hidden;">
                    @php $perc = $campaign->target_amount > 0 ? ($campaign->current_amount / $campaign->target_amount) * 100 : 0; @endphp
                    <div style="background: #4f46e5; width: {{ $perc }}%; height: 100%;"></div>
                </div>
            </div>
        </div>
        <div style="background: #f8fafc; padding: 15px 20px; border-top: 1px solid #e2e8f0; display: flex; gap: 10px;">
            <a href="{{ url('/c/'.$campaign->slug) }}" target="_blank" class="btn-outline" style="flex: 1; text-align: center; justify-content: center; font-size: 0.8rem;">
                <i class="fas fa-external-link-alt"></i> Ver Página
            </a>
            <a href="#" class="btn-outline" style="flex: 1; text-align: center; justify-content: center; font-size: 0.8rem;">
                <i class="fas fa-edit"></i> Editar
            </a>
        </div>
    </div>
    @endforeach
</div>
@endsection
