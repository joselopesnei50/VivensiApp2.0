
<!-- ─── BLOG SECTION ─── -->
<section class="blog-section" id="blog" style="padding: 100px 6%; background: #090909; border-top: 1px solid rgba(255,255,255,0.06);">
    <div class="blog-inner" style="max-width: 1240px; margin: 0 auto;">
        <div class="center aos">
            <div class="section-tag st-teal"><i class="fas fa-newspaper"></i> Blog Vivensi</div>
            <h2 class="section-title">Últimas do nosso portal</h2>
            <p class="section-sub center">Conteúdo especializado em gestão, tecnologia e impacto para o terceiro setor.</p>
        </div>

        <div class="blog-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-top: 50px;">
            @foreach($posts as $post)
            <div class="blog-card aos" style="background: #111; border: 1px solid rgba(255,255,255,0.07); border-radius: 20px; overflow: hidden; transition: all 0.3s ease; display: flex; flex-direction: column;">
                <div class="blog-img" style="height: 200px; overflow: hidden; position: relative;">
                    @if($post->image)
                        <img loading="lazy" src="{{ $post->image }}" alt="{{ $post->title }}" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;">
                    @else
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #1e293b, #0f172a); display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.1);">
                            <i class="fas fa-image fa-3x"></i>
                        </div>
                    @endif
                    <div class="blog-date" style="position: absolute; bottom: 15px; left: 15px; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); color: #fff; font-size: 0.7rem; font-weight: 700; padding: 5px 12px; border-radius: 100px;">
                        {{ $post->published_at ? $post->published_at->format('d M, Y') : $post->created_at->format('d M, Y') }}
                    </div>
                </div>
                <div class="blog-content" style="padding: 25px; flex: 1; display: flex; flex-direction: column;">
                    <h3 style="font-size: 1.2rem; font-weight: 800; color: #fff; line-height: 1.3; margin-bottom: 12px; height: 3.1rem; overflow: hidden;">
                        {{ $post->title }}
                    </h3>
                    <p style="font-size: 0.85rem; color: rgba(255,255,255,0.45); line-height: 1.6; margin-bottom: 20px; flex: 1;">
                        {{ Str::limit($post->excerpt ?? strip_tags($post->content), 120) }}
                    </p>
                    <a href="{{ route('public.blog.show', $post->slug) }}" class="blog-link" style="color: #6B8BFF; font-weight: 700; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: gap 0.2s;">
                        Ler artigo completo <i class="fas fa-arrow-right" style="font-size: 0.75rem;"></i>
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        <div class="center aos" style="margin-top: 50px;">
            <a href="{{ route('public.blog.index') }}" class="btn-hero-outline" style="font-size: 0.85rem; padding: 12px 30px;">
                Ver todos os artigos
            </a>
        </div>
    </div>
</section>

<style>
.blog-card:hover {
    transform: translateY(-8px);
    border-color: rgba(107, 139, 255, 0.3);
    box-shadow: 0 15px 40px rgba(0,0,0,0.5);
}
.blog-card:hover img {
    transform: scale(1.05);
}
.blog-link:hover {
    gap: 12px !important;
}
</style>
