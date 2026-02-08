@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h6 style="color: #6366f1; font-weight: 700; text-transform: uppercase; margin: 0 0 5px 0;">Vivensi Academy</h6>
        <h2 style="margin: 0; color: #111827;">Cursos Disponíveis</h2>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Gerencie os cursos e treinamentos da plataforma.</p>
    </div>
    <a href="{{ route('admin.academy.create') }}" class="btn-premium">
        <i class="fas fa-plus me-2"></i> Novo Curso
    </a>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    <table class="table" style="margin: 0;">
        <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <tr>
                <th style="padding: 15px 25px; font-weight: 700; color: #475569; font-size: 0.85rem; text-transform: uppercase;">Curso</th>
                <th style="padding: 15px 25px; font-weight: 700; color: #475569; font-size: 0.85rem; text-transform: uppercase;">Instrutor</th>
                <th style="padding: 15px 25px; font-weight: 700; color: #475569; font-size: 0.85rem; text-transform: uppercase;">Status</th>
                <th style="padding: 15px 25px; font-weight: 700; color: #475569; font-size: 0.85rem; text-transform: uppercase; text-align: right;">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($courses as $course)
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 20px 25px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        @if($course->thumbnail_url)
                            <img src="{{ $course->thumbnail_url }}" alt="" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover;">
                        @else
                            <div style="width: 50px; height: 50px; background: #e0e7ff; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #4338ca;">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                        @endif
                        <div>
                            <div style="font-weight: 700; color: #1e293b;">{{ $course->title }}</div>
                            <div style="font-size: 0.85rem; color: #64748b;">{{ $course->modules_count ?? 0 }} Módulos</div>
                        </div>
                    </div>
                </td>
                <td style="padding: 20px 25px; color: #64748b;">{{ $course->teacher_name ?? 'Vivensi Team' }}</td>
                <td style="padding: 20px 25px;">
                    @if($course->is_active)
                        <span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">ATIVO</span>
                    @else
                        <span style="background: #f1f5f9; color: #64748b; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">RASCUNHO</span>
                    @endif
                </td>
                <td style="padding: 20px 25px; text-align: right;">
                    <a href="{{ route('admin.academy.edit', $course->id) }}" class="btn btn-sm btn-light" style="margin-right: 5px;">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <form action="{{ route('admin.academy.destroy', $course->id) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Tem certeza?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-light" style="color: #ef4444;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="padding: 40px; text-align: center; color: #94a3b8;">
                    <i class="fas fa-graduation-cap" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                    Nenhum curso criado ainda.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    @if($courses->count() > 0)
    <div style="padding: 20px; border-top: 1px solid #f1f5f9;">
        {{ $courses->links() }}
    </div>
    @endif
</div>
@endsection
