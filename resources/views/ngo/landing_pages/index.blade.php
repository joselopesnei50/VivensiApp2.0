@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Páginas de Captura (Landing Pages)</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Crie páginas profissionais para doações e campanhas.</p>
    </div>
    @if(count($pages) < 5)
        <button onclick="document.getElementById('newPageModal').style.display='flex'" class="btn-premium">
            <i class="fas fa-magic"></i> Criar Nova Página
        </button>
    @else
        <button onclick="alert('Limite de 05 páginas atingido. Entre em contato com o suporte para contratar novas páginas.')" class="btn-premium" style="background: #94a3b8; cursor: not-allowed;">
            <i class="fas fa-lock"></i> Limite Atingido
        </button>
    @endif
</div>

<div class="grid-3">
    @foreach($pages as $page)
    <div class="vivensi-card" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="height: 120px; background: #e2e8f0; border-radius: 8px; margin-bottom: 15px; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                <i class="fas fa-desktop fa-3x"></i>
            </div>
            <h4 style="margin: 0 0 5px 0;">{{ $page->title }}</h4>
            <span style="font-size: 0.8rem; color: #64748b;">vvs.io/{{ $page->slug }}</span>
        </div>
        
        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <a href="{{ url('/ngo/landing-pages/builder/' . $page->id) }}" class="btn-premium" style="flex: 1; font-size: 0.8rem; justify-content: center;">
                <i class="fas fa-edit"></i> Editar
            </a>
            <a href="{{ url('/ngo/landing-pages/' . $page->id . '/leads') }}" class="btn-premium" style="flex: 1; font-size: 0.8rem; justify-content: center; background: #f8fafc; color: #4f46e5; border: 1px solid #e2e8f0;">
                <i class="fas fa-users"></i> Leads
            </a>
            <a href="{{ url('/lp/' . $page->slug) }}" target="_blank" class="btn-premium" style="background: #f1f5f9; color: #475569; padding: 10px; border-radius: 8px;">
                <i class="fas fa-external-link-alt"></i>
            </a>
        </div>
    </div>
    @endforeach
</div>

@if($pages->isEmpty())
    <div style="text-align: center; padding: 50px; color: #94a3b8;">
        <p>Você ainda não tem nenhuma Landing Page. Comece criando uma!</p>
    </div>
@endif

<!-- Banner de Upsell -->
<div class="vivensi-card" style="margin-top: 40px; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: white; border: none; padding: 30px; display: flex; align-items: center; justify-content: space-between;">
    <div>
        <h3 style="margin: 0; color: white;">Deseja um layout exclusivo ou personalizado?</h3>
        <p style="margin: 10px 0 0; opacity: 0.9;">Entre em contato com o time de criação da Vivensi App para projetos sob medida.</p>
    </div>
    <a href="https://wa.me/5511999999999" target="_blank" class="btn-premium" style="background: white; color: #4f46e5; border: none;">
        <i class="fab fa-whatsapp"></i> Falar com um Especialista
    </a>
</div>

<!-- Modal -->
<div id="newPageModal" class="custom-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div class="vivensi-card" style="width: 90%; max-width: 400px;">
        <h3>Nova Campanha</h3>
        <form action="{{ url('/ngo/landing-pages') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Título da Página (Ex: Campanha de Natal)</label>
                <input type="text" name="title" class="form-control-vivensi" required placeholder="Digite o nome...">
            </div>
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="button" onclick="document.getElementById('newPageModal').style.display='none'" style="flex: 1; border: none; background: #f1f5f9; cursor: pointer; border-radius: 8px;">Cancelar</button>
                <button type="submit" class="btn-premium" style="flex: 1; justify-content: center;">Continuar</button>
            </div>
        </form>
    </div>
</div>
@endsection
