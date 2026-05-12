@extends('layouts.app', ['title' => 'Criação de Conteúdo com IA'])

@section('content')
<div class="container-fluid p-4" style="background: #0f172a; min-height: 100vh; color: white;">
    
    <!-- HEADER -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h1 style="font-weight: 800; letter-spacing: -1px; margin: 0;">Social <span style="color: #6366f1;">AI Hub</span></h1>
            <p style="color: rgba(255,255,255,0.5); margin-top: 5px;">Crie posts estratégicos com IA e envie direto para suas redes ou WhatsApp.</p>
        </div>
        <div class="col-md-4 text-end">
            <div style="background: rgba(255,255,255,0.05); padding: 15px 25px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); display: inline-block;">
                <span style="display: block; font-size: 0.75rem; color: rgba(255,255,255,0.5); text-transform: uppercase; font-weight: 700;">Uso Mensal de Imagens</span>
                <span style="font-size: 1.5rem; font-weight: 800;">{{ $quotaUsed }} <small style="font-size: 0.9rem; color: rgba(255,255,255,0.3);">/ 60</small></span>
                <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.1); border-radius: 10px; margin-top: 10px;">
                    <div style="width: {{ ($quotaUsed / 60) * 100 }}%; height: 100%; background: linear-gradient(90deg, #6366f1, #a855f7); border-radius: 10px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- FORMULÁRIO DE GERAÇÃO -->
    <div class="row mb-5">
        <div class="col-12">
            <div style="background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; padding: 40px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <h3 style="font-weight: 700; margin-bottom: 15px;">Sobre o que vamos postar hoje?</h3>
                        <div class="input-group" style="background: rgba(0,0,0,0.3); border-radius: 16px; padding: 8px; border: 1px solid rgba(255,255,255,0.1);">
                            <input type="text" id="postTheme" class="form-control border-0 bg-transparent text-white shadow-none" placeholder="Ex: Dicas de planejamento estratégico para ONGs em 2026..." style="padding: 15px;">
                            <button onclick="generatePost()" id="btnGenerate" class="btn btn-primary px-4" style="border-radius: 12px; font-weight: 700; background: #6366f1;">
                                <i class="fas fa-magic"></i> Gerar Post Completo
                            </button>
                        </div>
                        <p style="font-size: 0.8rem; color: rgba(255,255,255,0.4); margin-top: 15px;">
                            <i class="fas fa-info-circle"></i> Nossa IA criará uma legenda persuasiva e uma imagem fotorrealista exclusiva.
                        </p>
                    </div>
                    <div class="col-md-5 d-none d-md-block text-center">
                        <div style="position: relative; display: inline-block;">
                             <div style="position: absolute; top: -20px; right: -20px; width: 60px; height: 60px; background: rgba(99, 102, 241, 0.2); border-radius: 50%; filter: blur(20px);"></div>
                             <i class="fas fa-robot" style="font-size: 5rem; color: rgba(255,255,255,0.1);"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- LISTA DE POSTS GERADOS -->
    <h4 style="font-weight: 700; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-history" style="color: #6366f1;"></i> Seus Rascunhos de IA
    </h4>

    <div class="row" id="postsGrid">
        @forelse($posts as $post)
        <div class="col-md-6 col-xl-4 mb-4">
            <div class="post-card" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: 20px; overflow: hidden; transition: 0.3s; height: 100%; display: flex; flex-direction: column;">
                
                <div style="position: relative; aspect-ratio: 1/1; background: #000;">
                    @if($post->image_path)
                        <img src="{{ Storage::disk('public')->url($post->image_path) }}" style="width: 100%; height: 100%; object-fit: cover;">
                    @elseif($post->status === 'failed')
                         <div style="height: 100%; display: flex; align-items: center; justify-content: center; background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 20px; text-align: center;">
                            <div>
                                <i class="fas fa-exclamation-triangle mb-2" style="font-size: 2rem;"></i><br>
                                <small>Erro na geração da imagem</small>
                            </div>
                         </div>
                    @else
                        <div style="height: 100%; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.05);">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                    @endif
                    <div style="position: absolute; top: 15px; left: 15px; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); padding: 5px 12px; border-radius: 8px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                        {{ $post->status }}
                    </div>
                </div>

                <div style="padding: 20px; flex-grow: 1; display: flex; flex-direction: column;">
                    <h5 style="font-weight: 700; font-size: 1rem; margin-bottom: 12px; color: rgba(255,255,255,0.9);">{{ $post->title_theme }}</h5>
                    <p style="font-size: 0.85rem; color: rgba(255,255,255,0.5); line-height: 1.6; flex-grow: 1;">
                        {{ Str::limit($post->body_text, 150) }}
                    </p>
                    
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button class="btn btn-sm btn-dark" style="flex: 1; border-radius: 8px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                            <i class="fas fa-copy"></i> Copiar
                        </button>
                        <button class="btn btn-sm btn-primary" style="flex: 1; border-radius: 8px; background: #6366f1;">
                            <i class="fas fa-calendar"></i> Agendar
                        </button>
                    </div>
                    <button class="btn btn-sm btn-success w-100 mt-2" style="border-radius: 8px; background: #10b981; border: none;">
                        <i class="fab fa-whatsapp"></i> Enviar via WhatsApp
                    </button>

                    <form action="{{ route('social-ai.destroy', $post->id) }}" method="POST" class="mt-2" onsubmit="return confirm('Tem certeza que deseja excluir este rascunho?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100" style="border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3); font-size: 0.75rem;">
                            <i class="fas fa-trash-alt"></i> Excluir Rascunho
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center py-5">
            <p style="color: rgba(255,255,255,0.3);">Nenhum post gerado ainda. Digite um tema acima para começar!</p>
        </div>
        @endforelse
    </div>

    <div class="d-flex justify-content-center mt-4">
        {{ $posts->links() }}
    </div>
</div>

<script>
    async function generatePost() {
        const theme = document.getElementById('postTheme').value.trim();
        if (!theme) {
            alert('Por favor, digite um tema para o post.');
            return;
        }

        const btn = document.getElementById('btnGenerate');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Iniciando Magia...';

        try {
            const response = await fetch("{{ route('social-ai.generate') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ theme: theme })
            });

            const data = await response.json();

            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message || 'Erro ao processar requisição.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-magic"></i> Gerar Post Completo';
            }
        } catch (error) {
            console.error(error);
            alert('Falha na comunicação com o servidor.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-magic"></i> Gerar Post Completo';
        }
    }

    // Polling opcional para atualizar status "em tempo real" (simplificado aqui como reload)
    @if($posts->where('status', 'processing')->isNotEmpty())
        setTimeout(() => window.location.reload(), 10000);
    @endif
</script>

<style>
    .post-card:hover {
        transform: translateY(-5px);
        border-color: rgba(99, 102, 241, 0.4) !important;
        background: rgba(255,255,255,0.05) !important;
    }
    .pagination .page-link {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        color: white;
    }
    .pagination .active .page-link {
        background: #6366f1;
        border-color: #6366f1;
    }
</style>
@endsection
