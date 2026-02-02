@extends('layouts.app')

@section('content')
<style>
    .chat-container {
        max-width: 900px;
        margin: 0 auto;
    }
    .ticket-header {
        background: white;
        padding: 30px;
        border-radius: 16px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.03);
        border: 1px solid #e2e8f0;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .message-bubble {
        padding: 24px;
        border-radius: 16px;
        margin-bottom: 24px;
        position: relative;
        max-width: 85%;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
    }
    .message-user {
        background: white;
        border: 1px solid #e2e8f0;
        margin-right: auto;
        border-bottom-left-radius: 4px;
    }
    .message-admin {
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        margin-left: auto;
        border-bottom-right-radius: 4px;
    }
    .message-meta {
        font-size: 0.85rem;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding-bottom: 12px;
        border-bottom: 1px dashed rgba(0,0,0,0.1);
    }
    .avatar-circle {
        width: 40px; height: 40px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-weight: bold;
        font-size: 1rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .badge-pill {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .status-open { background: #fee2e2; color: #991b1b; }
    .status-answered { background: #dcfce7; color: #166534; }
    .status-user-reply { background: #fef9c3; color: #854d0e; }
    .status-closed { background: #f1f5f9; color: #64748b; }

    .reply-box {
        background: white;
        border-radius: 16px;
        box-shadow: 0 -5px 20px rgba(0,0,0,0.03);
        border: 1px solid #e2e8f0;
        padding: 24px;
    }
</style>

<div class="chat-container pt-4">
    <div class="mb-4">
        <a href="{{ route('support.index') }}" class="btn btn-light rounded-pill px-4 fw-bold text-muted border">
            <i class="fas fa-arrow-left me-2"></i> Voltar para Central
        </a>
    </div>

    <div class="ticket-header">
        <div>
            <div class="d-flex align-items-center gap-3 mb-2">
                @if($ticket->status == 'open')
                    <span class="badge-pill status-open">Aberto</span>
                @elseif($ticket->status == 'answered_by_admin')
                    <span class="badge-pill status-answered">Respondido</span>
                @elseif($ticket->status == 'answered_by_user')
                    <span class="badge-pill status-user-reply">Aguardando</span>
                @else
                    <span class="badge-pill status-closed">Fechado</span>
                @endif
                <span class="text-muted small">Ticket #{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</span>
            </div>
            <h2 class="fw-bold text-dark mb-2">{{ $ticket->subject }}</h2>
            <div class="d-flex align-items-center gap-3 text-muted small">
                <span><i class="far fa-calendar-alt me-1"></i> Criado em {{ $ticket->created_at->format('d/m/Y \à\s H:i') }}</span>
                <span>•</span>
                <span>Category: {{ ucfirst($ticket->category) }}</span>
            </div>
        </div>
        <div class="text-end">
             @if($ticket->priority == 'high')
                <div class="d-flex align-items-center gap-2 text-danger fw-bold">
                    <i class="fas fa-exclamation-circle"></i> Alta Prioridade
                </div>
             @elseif($ticket->priority == 'medium')
                <div class="d-flex align-items-center gap-2 text-warning fw-bold">
                    <i class="fas fa-arrow-up"></i> Média Prioridade
                </div>
             @else
                <div class="d-flex align-items-center gap-2 text-info fw-bold">
                    <i class="fas fa-arrow-down"></i> Baixa Prioridade
                </div>
             @endif
        </div>
    </div>

    <div class="chat-history mb-5">
        @foreach($ticket->messages as $msg)
            <div class="d-flex {{ $msg->is_admin_reply ? 'justify-content-end' : 'justify-content-start' }}">
                <div class="message-bubble {{ $msg->is_admin_reply ? 'message-admin' : 'message-user' }}">
                    <div class="message-meta {{ $msg->is_admin_reply ? 'justify-content-end' : '' }}">
                        @if(!$msg->is_admin_reply)
                        <div class="avatar-circle bg-dark text-white">{{ substr($msg->user->name, 0, 1) }}</div>
                        <span class="fw-bold text-dark">{{ $msg->user->name }}</span>
                        @else
                        <span class="fw-bold text-primary">Equipe Vivensi</span>
                        <div class="avatar-circle bg-primary text-white"><i class="fas fa-headset"></i></div>
                        @endif
                        <span class="text-muted ms-2 small">{{ $msg->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="message-content" style="color: #334155; line-height: 1.7; font-size: 1rem;">
                        {!! nl2br(e($msg->message)) !!}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($ticket->status != 'closed')
    <div class="reply-box sticky-bottom mb-5">
        <form action="{{ route('support.reply', $ticket->id) }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-bold text-dark small text-uppercase">Adicionar Resposta</label>
                <textarea name="message" class="form-control bg-light border-0 p-3" rows="4" placeholder="Digite sua resposta aqui..." style="border-radius: 12px; resize: none;" required></textarea>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    <i class="fas fa-info-circle me-1"></i> Sua resposta notificará nossa equipe imediatamente.
                </div>
                <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-lg transform-hover">
                    <i class="fas fa-paper-plane me-2"></i> Enviar Resposta
                </button>
            </div>
        </form>
    </div>
    @else
    <div class="alert alert-secondary text-center rounded-4 py-4 border-0 bg-light">
        <div class="mb-2 text-muted"><i class="fas fa-lock fa-2x"></i></div>
        <h5 class="fw-bold text-muted">Este chamado foi encerrado</h5>
        <p class="text-muted mb-3">Não é possível enviar novas respostas.</p>
        <a href="{{ route('support.create') }}" class="btn btn-outline-dark rounded-pill fw-bold">Abrir Novo Chamado</a>
    </div>
    @endif
</div>
@endsection
