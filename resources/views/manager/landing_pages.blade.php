@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Páginas de Captura (Landing Pages)</h2>
        <p style="color: #64748b; margin: 5px 0 0 0;">Crie páginas profissionais para seus projetos e campanhas.</p>
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
            @php $isPublished = (($page->status ?? 'draft') === 'published'); @endphp
            <div style="display:flex; align-items:center; justify-content: space-between; gap: 10px;">
                <h4 style="margin: 0 0 5px 0;">{{ $page->title }}</h4>
                @if($isPublished)
                    <span style="font-size: 0.7rem; background: rgba(16,185,129,.12); padding: 4px 10px; border-radius: 999px; color: #059669; font-weight: 900; letter-spacing:.06em; text-transform: uppercase;">Publicado</span>
                @else
                    <span style="font-size: 0.7rem; background: rgba(100,116,139,.12); padding: 4px 10px; border-radius: 999px; color: #475569; font-weight: 900; letter-spacing:.06em; text-transform: uppercase;">Rascunho</span>
                @endif
            </div>
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

        <div style="margin-top: 10px; display:flex; gap: 10px; flex-wrap: wrap;">
            <form action="{{ url('/ngo/landing-pages/' . $page->id . ($isPublished ? '/unpublish' : '/publish')) }}" method="POST" style="flex:1;" onsubmit="return confirm('{{ $isPublished ? 'Despublicar esta Landing Page?' : 'Publicar esta Landing Page?' }}')">
                @csrf
                <button type="submit" class="btn-premium" style="width:100%; justify-content:center; font-size: 0.8rem; background: {{ $isPublished ? '#f1f5f9' : '#10b981' }}; color: {{ $isPublished ? '#0f172a' : '#ffffff' }}; border: {{ $isPublished ? '1px solid #e2e8f0' : 'none' }};">
                    <i class="fas {{ $isPublished ? 'fa-eye-slash' : 'fa-bullhorn' }}"></i> {{ $isPublished ? 'Despublicar' : 'Publicar' }}
                </button>
            </form>

            <form action="{{ url('/ngo/landing-pages/' . $page->id . '/duplicate') }}" method="POST" style="flex:1;" onsubmit="return confirm('Duplicar esta Landing Page (com os mesmos blocos)?')">
                @csrf
                <button type="submit" class="btn-premium" style="width:100%; justify-content:center; font-size: 0.8rem; background: #ffffff; color: #4f46e5; border: 1px solid #e2e8f0;">
                    <i class="fas fa-clone"></i> Duplicar
                </button>
            </form>

            <form action="{{ url('/ngo/landing-pages/' . $page->id) }}" method="POST" style="flex:1;" onsubmit="return confirm('Excluir esta Landing Page? Isso remove também os blocos e leads capturados.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-premium" style="width:100%; justify-content:center; font-size: 0.8rem; background: #ffffff; color: #ef4444; border: 1px solid rgba(239,68,68,.25);">
                    <i class="fas fa-trash"></i> Excluir
                </button>
            </form>
        </div>
    </div>
    @endforeach
</div>

@if($pages->isEmpty())
    <x-empty-state 
        icon="fa-laptop-code" 
        title="Nenhuma Landing Page" 
        description="Você ainda não criou nenhuma página de captura. Comece agora para converter visitantes em leads para seus projetos." 
        action_label="Criar Minha Primeira Página" 
        action_url="javascript:document.getElementById('newPageModal').style.display='flex'" 
    />
@endif

<!-- Banner de Upsell Premium -->
<div style="margin-top: 48px; position: relative; border-radius: 28px; overflow: hidden; background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%); border: 1px solid rgba(99,102,241,0.2); box-shadow: 0 25px 60px rgba(0,0,0,0.25);">

    <!-- Glow decorativo -->
    <div style="position: absolute; top: -60px; left: -60px; width: 220px; height: 220px; background: rgba(99,102,241,0.15); border-radius: 50%; filter: blur(60px); pointer-events: none;"></div>
    <div style="position: absolute; bottom: -60px; right: -40px; width: 200px; height: 200px; background: rgba(124,58,237,0.12); border-radius: 50%; filter: blur(60px); pointer-events: none;"></div>

    <!-- Grid decorativo -->
    <div style="position: absolute; inset: 0; background-image: linear-gradient(rgba(99,102,241,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(99,102,241,0.05) 1px, transparent 1px); background-size: 40px 40px; pointer-events: none;"></div>

    <div style="position: relative; z-index: 1; padding: 44px 48px; display: flex; align-items: center; justify-content: space-between; gap: 40px; flex-wrap: wrap;">

        <!-- Conteúdo esquerdo -->
        <div style="flex: 1; min-width: 280px;">
            <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.3); border-radius: 20px; padding: 5px 14px; margin-bottom: 18px;">
                <span style="width: 6px; height: 6px; background: #818cf8; border-radius: 50%; display: inline-block; box-shadow: 0 0 8px #818cf8; animation: pulse-dot 2s infinite;"></span>
                <span style="font-size: 0.65rem; font-weight: 800; color: #a5b4fc; text-transform: uppercase; letter-spacing: 1.5px;">Vivensi Creative Studio</span>
            </div>

            <h3 style="margin: 0 0 12px; font-size: 1.9rem; font-weight: 950; color: white; letter-spacing: -1px; line-height: 1.1;">
                Quer um layout
                <span style="background: linear-gradient(90deg, #818cf8, #a78bfa); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">exclusivo</span>
                para seu negócio?
            </h3>
            <p style="margin: 0; color: rgba(255,255,255,0.55); font-size: 1rem; line-height: 1.6; max-width: 520px;">
                Nossa equipe especializada cria landing pages, identidades visuais e experiências digitais sob medida. Projetos entregues com qualidade global.
            </p>

            <!-- Trust badges -->
            <div style="display: flex; gap: 20px; margin-top: 22px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-check-circle" style="color: #34d399; font-size: 0.85rem;"></i>
                    <span style="font-size: 0.8rem; color: rgba(255,255,255,0.5); font-weight: 600;">Entrega em 7 dias</span>
                </div>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-check-circle" style="color: #34d399; font-size: 0.85rem;"></i>
                    <span style="font-size: 0.8rem; color: rgba(255,255,255,0.5); font-weight: 600;">Suporte dedicado</span>
                </div>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-check-circle" style="color: #34d399; font-size: 0.85rem;"></i>
                    <span style="font-size: 0.8rem; color: rgba(255,255,255,0.5); font-weight: 600;">100% personalizado</span>
                </div>
            </div>
        </div>

        <!-- CTA direita -->
        <div style="display: flex; flex-direction: column; align-items: center; gap: 14px;">
            <a href="https://wa.me/5516997618695?text=Ol%C3%A1!%20Vim%20pelo%20Vivensi%20App%20e%20gostaria%20de%20um%20layout%20exclusivo."
               target="_blank"
               style="display: inline-flex; align-items: center; gap: 12px; background: #25d366; color: white; text-decoration: none; padding: 16px 30px; border-radius: 18px; font-weight: 800; font-size: 1rem; box-shadow: 0 8px 30px rgba(37,211,102,0.35); transition: all 0.25s; white-space: nowrap;"
               onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 14px 40px rgba(37,211,102,0.5)';"
               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 30px rgba(37,211,102,0.35)';">
               <i class="fab fa-whatsapp" style="font-size: 1.4rem;"></i>
               Falar com um Especialista
            </a>
            <div style="text-align: center;">
                <div style="font-size: 0.75rem; color: rgba(255,255,255,0.35); font-weight: 600; margin-bottom: 2px;">Atendimento via WhatsApp</div>
                <div style="font-size: 0.9rem; color: rgba(255,255,255,0.65); font-weight: 700; letter-spacing: 0.5px;">(16) 99761-8695</div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes pulse-dot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.4); }
}
</style>

<!-- Modal -->
<div id="newPageModal" class="custom-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div class="vivensi-card" style="width: 90%; max-width: 400px;">
        <h3>Nova Campanha</h3>
        <form action="{{ url('/ngo/landing-pages') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Título da Página (Ex: Campanha de Vendas)</label>
                <input type="text" name="title" class="form-control-vivensi" required placeholder="Digite o nome...">
            </div>
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="button" onclick="document.getElementById('newPageModal').style.display='none'" style="flex: 1; border: none; background: #f1f5f9; cursor: pointer; border-radius: 8px;">Cancelar</button>
                <button type="submit" class="btn-premium" style="flex: 1; justify-content: center;">Continuar</button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Inline styles for quick replication */
    .grid-3 { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
    .btn-premium { display: inline-flex; align-items: center; gap: 8px; text-decoration: none; cursor: pointer; border: none; }
</style>
@endsection
