@extends('layouts.public')

@section('title', 'Blog & Insights - Vivensi')

@section('content')
<section style="padding: 80px 5%; background: var(--bg-light);">
    <div style="text-align: center; margin-bottom: 60px;">
        <span class="section-badge">Blog & Insights</span>
        <h1 style="font-size: 3.5rem; color: var(--secondary); margin-top: 15px; font-weight: 800; letter-spacing: -1.5px;">Fique por dentro das novidades</h1>
        <p style="color: var(--text-light); max-width: 600px; margin: 20px auto; font-size: 1.2rem;">Dicas de gestão, finanças e tecnologia para potencializar sua missão.</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px; max-width: 1200px; margin: 0 auto;">
        @foreach($posts as $post)
        <div class="blog-card" style="background: white; border-radius: 20px; overflow: hidden; border: 1px solid #e2e8f0; transition: all 0.3s ease; display: flex; flex-direction: column;">
            <div style="height: 240px; background: #e2e8f0; position: relative; overflow: hidden;">
                <img src="{{ $post->image ?: 'https://images.unsplash.com/photo-1499750310107-5fef28a66643' }}" alt="{{ $post->title }}" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;">
            </div>
            <div style="padding: 30px; flex-grow: 1; display: flex; flex-direction: column;">
                <div style="font-size: 0.8rem; font-weight: 700; color: var(--primary); margin-bottom: 15px; text-transform: uppercase;">
                    {{ $post->published_at ? \Carbon\Carbon::parse($post->published_at)->format('d M, Y') : 'Recente' }}
                </div>
                <h3 style="font-size: 1.5rem; font-weight: 800; color: #1e293b; margin-bottom: 15px; line-height: 1.3;">{{ $post->title }}</h3>
                <p style="color: #64748b; font-size: 1rem; line-height: 1.6; margin-bottom: 25px;">
                    {{ Str::limit(strip_tags($post->content), 120) }}
                </p>
                <div style="margin-top: auto;">
                    <a href="{{ route('public.blog.show', $post->slug) }}" style="color: var(--primary); font-weight: 800; text-decoration: none; font-size: 0.9rem; display: flex; align-items: center;">
                        LER ARTIGO COMPLETO <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div style="margin-top: 60px; display: flex; justify-content: center;">
        {{ $posts->links() }}
    </div>
</section>

<style>
    .blog-card:hover { transform: translateY(-10px); box-shadow: 0 30px 60px rgba(0,0,0,0.1); border-color: var(--primary); }
    .blog-card:hover img { transform: scale(1.05); }
    
    /* Pagination Styling */
    .pagination { display: flex; gap: 10px; list-style: none; padding: 0; }
    .page-item .page-link { 
        padding: 10px 18px; 
        border: 1px solid #e2e8f0; 
        border-radius: 12px; 
        color: var(--text-main); 
        text-decoration: none; 
        font-weight: 700;
        transition: all 0.2s;
    }
    .page-item.active .page-link { background: var(--primary); color: white; border-color: var(--primary); }
    .page-item .page-link:hover:not(.active) { background: #f1f5f9; }
</style>
@endsection
