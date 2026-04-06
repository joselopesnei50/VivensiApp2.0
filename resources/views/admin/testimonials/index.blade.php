@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin: 0; color: #111827; font-weight: 800; font-size: 2rem;">Gente que transforma vidas</h2>
            <p style="color: #6b7280; margin: 5px 0 0 0;">Gerencie os depoimentos que aparecem na página inicial.</p>
        </div>
        <a href="{{ route('admin.testimonials.create') }}" class="btn-premium">
            <i class="fas fa-plus me-2"></i> Novo Depoimento
        </a>
    </div>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    <table class="table" style="margin-bottom: 0;">
        <thead style="background: #f8fafc;">
            <tr>
                <th style="padding: 15px 25px; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Autor</th>
                <th style="padding: 15px 25px; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Cargo/Papel</th>
                <th style="padding: 15px 25px; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Depoimento</th>
                <th style="padding: 15px 25px; color: #64748b; font-size: 0.8rem; text-transform: uppercase; text-align: right;">Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach($testimonials as $testimonial)
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 20px 25px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        @if($testimonial->photo)
                            <img src="{{ $testimonial->photo }}" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover;">
                        @endif
                        <div style="font-weight: 700; color: #1e293b;">{{ $testimonial->name }}</div>
                    </div>
                </td>
                <td style="padding: 20px 25px; color: #64748b;">
                    {{ $testimonial->role }}
                </td>
                <td style="padding: 20px 25px; color: #64748b; font-size: 0.85rem; max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    {{ $testimonial->content }}
                </td>
                <td style="padding: 20px 25px; text-align: right;">
                    <a href="{{ route('admin.testimonials.edit', $testimonial->id) }}" class="btn btn-sm btn-light" style="border: 1px solid #e2e8f0;"><i class="fas fa-edit"></i></a>
                    <form action="{{ route('admin.testimonials.destroy', $testimonial->id) }}" method="POST" style="display: inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-light" style="border: 1px solid #e2e8f0; color: #ef4444;" onclick="return confirm('Tem certeza?')"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
