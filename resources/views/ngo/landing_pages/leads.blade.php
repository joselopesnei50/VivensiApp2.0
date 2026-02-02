@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Leads Capturados: {{ $page->title }}</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Contatos interessados através desta Landing Page.</p>
    </div>
    <a href="{{ url('/ngo/landing-pages') }}" class="btn-premium" style="background: #64748b;">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <tr>
                <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Data</th>
                <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Nome</th>
                <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">E-mail</th>
                <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">WhatsApp</th>
                <th style="padding: 15px; text-align: left; font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Dados Extras</th>
            </tr>
        </thead>
        <tbody>
            @foreach($leads as $lead)
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 15px; color: #64748b; font-size: 0.9rem;">
                    {{ \Carbon\Carbon::parse($lead->created_at)->format('d/m/Y H:i') }}
                </td>
                <td style="padding: 15px; font-weight: 600; color: #1e293b;">
                    {{ $lead->name ?? 'N/A' }}
                </td>
                <td style="padding: 15px; color: #4f46e5;">
                    {{ $lead->email }}
                </td>
                <td style="padding: 15px; color: #1e293b;">
                    {{ $lead->phone ?? 'N/A' }}
                </td>
                <td style="padding: 15px; font-size: 0.8rem; color: #64748b;">
                    {{ $lead->extra_data }}
                </td>
            </tr>
            @endforeach
            
            @if($leads->isEmpty())
                <tr>
                    <td colspan="5" style="padding: 40px; text-align: center; color: #94a3b8;">Nenhum lead capturado ainda.</td>
                </tr>
            @endif
        </tbody>
    </table>
    <div style="padding: 20px;">
        {{ $leads->links() }}
    </div>
</div>
@endsection
