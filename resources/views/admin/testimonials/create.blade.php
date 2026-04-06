@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div>
        <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">Adicionar Depoimento</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Insira um novo depoimento de um cliente satisfeito.</p>
    </div>
</div>

<div class="vivensi-card" style="padding: 30px;">
    <form action="{{ route('admin.testimonials.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-md-6 mb-4">
                <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px;">Nome do Autor</label>
                <input type="text" name="name" class="form-control" placeholder="Ex: Maria Helena" required>
            </div>
            <div class="col-md-6 mb-4">
                <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px;">Cargo / Instituição</label>
                <input type="text" name="role" class="form-control" placeholder="Ex: Diretora da ONG Abraço Solidário">
            </div>
            <div class="col-md-12 mb-4">
                <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px;">URL da Foto (Avatar)</label>
                <input type="url" name="photo" class="form-control" placeholder="https://i.pravatar.cc/100?u=maria">
                <small class="text-muted">Use links de imagens ou serviços de avatar como o Pravatar.</small>
            </div>
            <div class="col-md-12 mb-4">
                <label style="display: block; font-weight: 700; color: #334155; margin-bottom: 8px;">Depoimento</label>
                <textarea name="content" class="form-control" style="height: 150px;" placeholder="O que o cliente disse sobre o Vivensi..." required></textarea>
            </div>
        </div>

        <div style="display: flex; gap: 15px;">
            <button type="submit" class="btn-premium">Salvar Depoimento</button>
            <a href="{{ route('admin.testimonials.index') }}" class="btn btn-light" style="padding: 12px 25px; border-radius: 12px; font-weight: 700; border: 1px solid #e2e8f0;">Cancelar</a>
        </div>
    </form>
</div>
@endsection
