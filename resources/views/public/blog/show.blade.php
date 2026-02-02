@extends('layouts.public')

@section('title', $post->title . ' - Blog Vivensi')

@section('content')
<article style="padding-bottom: 100px;">
    <!-- Article Header -->
    <header style="padding: 80px 5% 40px; text-align: center; background: white;">
        <div style="max-width: 800px; margin: 0 auto;">
            <div style="font-size: 0.9rem; font-weight: 700; color: var(--primary); margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px;">
                {{ $post->published_at ? \Carbon\Carbon::parse($post->published_at)->format('d \d\e F \d\e Y') : 'Publicado Recentemente' }}
            </div>
            <h1 style="font-size: 3.5rem; color: var(--secondary); font-weight: 900; line-height: 1.1; letter-spacing: -2px; margin-bottom: 30px;">
                {{ $post->title }}
            </h1>
            <div style="display: flex; align-items: center; justify-content: center; gap: 15px;">
                <img src="{{ asset('img/logovivensi.png') }}" alt="Vivensi Author" style="height: 30px; opacity: 0.8;">
                <span style="color: var(--text-light); font-weight: 600;">Equipe Vivensi</span>
            </div>
        </div>
    </header>

    <!-- Featured Image -->
    <div style="max-width: 1000px; margin: 0 auto 60px; padding: 0 5%;">
        <div style="width: 100%; height: 500px; border-radius: 30px; overflow: hidden; box-shadow: 0 40px 80px -20px rgba(0,0,0,0.15);">
            <img src="{{ $post->image ?: 'https://images.unsplash.com/photo-1499750310107-5fef28a66643' }}" alt="{{ $post->title }}" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
    </div>

    <!-- Article Content -->
    <div class="article-content" style="max-width: 800px; margin: 0 auto; padding: 0 5%; font-size: 1.25rem; line-height: 1.8; color: #334155;">
        {!! nl2br($post->content) !!}
    </div>

    <!-- Back to Blog -->
    <div style="max-width: 800px; margin: 60px auto 0; padding: 0 5%; text-align: center;">
        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 40px;">
        <h3 style="margin-bottom: 20px; font-weight: 800;">Gostou deste conteúdo?</h3>
        <p style="color: var(--text-light); margin-bottom: 30px;">Compartilhe conhecimento ou explore mais artigos em nosso blog.</p>
        <div style="display: flex; gap: 15px; justify-content: center;">
            <a href="{{ route('public.blog.index') }}" class="btn-outline">
                <i class="fas fa-arrow-left me-2"></i> Voltar ao Blog
            </a>
            <a href="{{ url('/#pricing') }}" class="btn-cta">Testar Vivensi Grátis</a>
        </div>
    </div>
</article>

<style>
    .article-content h2, .article-content h3 { color: var(--secondary); margin-top: 40px; margin-bottom: 20px; font-weight: 800; letter-spacing: -0.5px; }
    .article-content p { margin-bottom: 25px; }
    .article-content blockquote {
        border-left: 5px solid var(--primary);
        padding-left: 30px;
        margin: 40px 0;
        font-style: italic;
        color: var(--secondary);
        font-weight: 500;
    }
</style>
@endsection
