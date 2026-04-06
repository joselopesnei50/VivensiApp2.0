@extends('layouts.app')

@section('content')
<div class="vivensi-card" style="max-width: 800px; margin: 0 auto; padding: 40px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h3 style="margin: 0; font-size: 1.5rem; color: #1e293b;">Nova Campanha</h3>
        <p style="color: #64748b; margin: 5px 0 0 0;">Crie uma Landing Page focada em conversão.</p>
    </div>

    <form action="{{ url('/ngo/campaigns') }}" method="POST">
        @csrf

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Título da Campanha</label>
            <input type="text" name="title" class="form-control-vivensi" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="Ex: Reforma da Biblioteca Comunitária">
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Descrição / História</label>
            <textarea name="description" class="form-control-vivensi" required rows="5" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="Conte a história do projeto e por que ele precisa de apoio..."></textarea>
        </div>

        <div class="grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Meta de Captação (R$)</label>
                <input type="number" step="0.01" name="target_amount" class="form-control-vivensi" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="0.00">
            </div>
            <div class="form-group">
                <label class="form-label" style="display: block; margin-bottom: 8px; color: #64748b; font-weight: 500;">Link Vídeo (YouTube/Vimeo) - Opcional</label>
                <input type="url" name="video_url" class="form-control-vivensi" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;" placeholder="https://...">
            </div>
        </div>

        <div style="display: flex; gap: 15px;">
            <a href="{{ url('/ngo/campaigns') }}" class="btn btn-outline" style="flex: 1; display: flex; align-items: center; justify-content: center; text-decoration: none; color: #64748b; border: 1px solid #e2e8f0; border-radius: 10px;">Cancelar</a>
            <button type="submit" class="btn-premium" style="flex: 2; border: none; cursor: pointer; justify-content: center;">Lançar Campanha</button>
        </div>
    </form>
</div>
@endsection
