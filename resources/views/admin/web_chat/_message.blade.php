@php
    $who = match ($m->sender) {
        'visitor' => 'Visitante',
        'bot'     => 'Bruno',
        'agent'   => $m->user?->name ?? 'Equipe',
        default   => null,
    };
@endphp
<li class="wc-msg wc-{{ $m->sender }}" data-id="{{ $m->id }}">
    @if($m->sender === 'system')
        <div class="wc-system">{{ $m->body }} <span>{{ $m->created_at?->format('d/m H:i') }}</span></div>
    @else
        <div class="wc-bubble">{!! nl2br(e($m->body)) !!}</div>
        <div class="wc-meta">{{ $who }} · {{ $m->created_at?->format('d/m H:i') }}@if(($m->meta['kind'] ?? null) === 'fallback') · <span style="color:#b45309;">resposta automática de erro</span>@endif</div>
    @endif
</li>
