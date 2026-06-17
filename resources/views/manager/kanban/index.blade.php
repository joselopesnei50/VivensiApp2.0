@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1" style="font-weight: 800;">
                <i class="fas fa-columns me-2" style="color: {{ $board->color ?: '#6366f1' }};"></i>{{ $board->name }}
            </h1>
            <p class="text-muted mb-0">Kanban geral do tenant. Drag-and-drop chega na sub-etapa 3.B.2.</p>
        </div>
    </div>

    <div style="display:flex; gap:14px; overflow-x:auto; padding-bottom:14px;">
        @foreach($columns as $col)
            <div style="flex:0 0 280px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:14px;">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
                    <div style="width:10px; height:10px; border-radius:50%; background:{{ $col->color ?: '#cbd5e1' }};"></div>
                    <strong style="color:#1e293b; flex:1;">{{ $col->name }}</strong>
                    <span style="background:#e2e8f0; color:#475569; font-size:.7rem; font-weight:700; padding:2px 8px; border-radius:99px;">
                        {{ $col->cards->count() }}
                    </span>
                </div>

                @forelse($col->cards as $card)
                    <div style="background:white; border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px; margin-bottom:8px; box-shadow:0 1px 2px rgba(0,0,0,.03);">
                        <div style="font-weight:700; color:#1e293b; font-size:.9rem;">{{ $card->title }}</div>
                        @if($card->description)
                            <div style="color:#64748b; font-size:.78rem; margin-top:4px;">{{ \Illuminate\Support\Str::limit($card->description, 80) }}</div>
                        @endif
                        @if($card->whatsapp_chat_id)
                            <a href="{{ url('/whatsapp/chat?chat_id=' . $card->whatsapp_chat_id) }}" style="font-size:.7rem; color:#10b981; font-weight:700; text-decoration:none;">
                                <i class="fab fa-whatsapp"></i> Ver conversa
                            </a>
                        @endif
                    </div>
                @empty
                    <div style="color:#94a3b8; font-size:.78rem; text-align:center; padding:20px 0;">
                        Nenhum card.
                    </div>
                @endforelse
            </div>
        @endforeach
    </div>
</div>
@endsection
