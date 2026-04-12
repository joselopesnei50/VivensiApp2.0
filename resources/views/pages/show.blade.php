@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-5">
                    <h1 class="mb-4 text-center fw-bold text-primary">{{ $page->title }}</h1>
                    <p class="text-muted text-center mb-5">Última atualização: {{ $page->updated_at->format('d/m/Y') }}</p>

                    <div class="legal-content">
                        {!! strip_tags($page->content, '<h1><h2><h3><h4><h5><h6><p><br><strong><b><em><i><u><ul><ol><li><a><blockquote><table><thead><tbody><tr><th><td><img><span><div>') !!}
                    </div>
                    
                    <div class="text-center mt-5">
                        <a href="{{ url('/') }}" class="btn btn-outline-primary rounded-pill px-4">Voltar para Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .legal-content h4 {
        color: #1e293b;
        margin-top: 30px;
        margin-bottom: 15px;
        font-weight: 700;
    }
    .legal-content p {
        color: #64748b;
        line-height: 1.7;
        margin-bottom: 15px;
    }
    .legal-content ul {
        color: #64748b;
        margin-bottom: 15px;
    }
</style>
@endsection
