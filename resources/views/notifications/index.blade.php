@extends('layouts.app')

@section('content')
<div class="header-page" style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; gap: 15px; flex-wrap:wrap;">
    <div>
        <h2 style="margin: 0; color: #2c3e50;">Notificações</h2>
        <p style="color:#64748b; margin:6px 0 0 0;">
            Você tem <strong>{{ $unreadCount }}</strong> não lida(s).
        </p>
    </div>
    <form action="{{ route('notifications.read_all') }}" method="POST">
        @csrf
        <button type="submit" class="btn-outline" style="padding: 8px 12px; border-radius: 10px;">
            <i class="fas fa-check-double"></i> Marcar todas como lidas
        </button>
    </form>
</div>

<div class="vivensi-card" style="padding: 0; overflow: hidden;">
    <div style="max-height: 70vh; overflow:auto;">
        @forelse($notifications as $n)
            <div style="padding: 14px 16px; border-bottom: 1px solid #f1f5f9; background: {{ $n->read_at ? 'transparent' : '#f0f4ff' }};">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap: 10px;">
                    <div style="flex: 1;">
                        <div style="font-weight: 700; color:#1e293b; margin-bottom: 4px;">{{ $n->title }}</div>
                        <div style="color:#64748b; font-size:0.9rem; line-height:1.4;">{{ $n->message }}</div>
                        <div style="color:#94a3b8; font-size:0.75rem; margin-top: 6px;">
                            {{ $n->created_at?->format('d/m/Y H:i') }}
                            @if($n->read_at) • Lida @endif
                        </div>
                    </div>

                    <div style="display:flex; gap: 8px; flex-wrap:wrap; justify-content:flex-end;">
                        @if(!$n->read_at)
                            <form action="{{ route('notifications.read', $n->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn-outline" style="padding: 6px 10px; border-radius: 10px; font-size:0.85rem;">
                                    Marcar como lida
                                </button>
                            </form>
                        @endif

                        @if($n->link)
                            <a href="{{ $n->link }}" class="btn-outline" style="padding: 6px 10px; border-radius: 10px; font-size:0.85rem; text-decoration:none;">
                                Abrir
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div style="padding: 24px; text-align:center; color:#94a3b8;">
                Nenhuma notificação.
            </div>
        @endforelse
    </div>
</div>

<div style="margin-top: 15px;">
    {{ $notifications->links() }}
</div>
@endsection

