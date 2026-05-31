@php
    $originColors = [
        'manual'        => ['bg' => '#f1f5f9', 'text' => '#475569', 'label' => 'Manual'],
        'demo_agendada' => ['bg' => '#dbeafe', 'text' => '#1d4ed8', 'label' => 'Demo'],
        'indicacao'     => ['bg' => '#dcfce7', 'text' => '#15803d', 'label' => 'Indicação'],
    ];
    $oc = $originColors[$lead->origin] ?? $originColors['manual'];
@endphp

<div class="lead-card-prem"
     id="lead-{{ $lead->id }}"
     draggable="true"
     ondragstart="drag(event)"
     onclick="openLead({{ $lead->id }})"
     data-stage="{{ $lead->stage_id }}">

    <div class="lc-top">
        <span class="lc-badge" style="background:{{ $oc['bg'] }};color:{{ $oc['text'] }};">
            {{ $oc['label'] }}
        </span>
        @if($lead->plan)
        <span class="lc-badge" style="background:#f3e8ff;color:#7e22ce;">
            {{ $lead->plan->name }}
        </span>
        @endif
    </div>

    <div class="lc-name">{{ $lead->name }}</div>

    @if($lead->company)
    <div class="lc-company">
        <i class="fas fa-building" style="font-size:.65rem;opacity:.5;"></i>
        {{ $lead->company }}
    </div>
    @endif

    <div class="lc-meta">
        @if($lead->estimated_value)
        <span class="lc-value">
            <i class="fas fa-dollar-sign"></i>
            R$ {{ number_format($lead->estimated_value, 0, ',', '.') }}
        </span>
        @endif
        @if($lead->responsible)
        <span class="lc-resp" title="{{ $lead->responsible->name }}">
            <i class="fas fa-user-circle"></i>
            {{ explode(' ', $lead->responsible->name)[0] }}
        </span>
        @endif
    </div>

    @if($lead->next_step)
    <div class="lc-next">
        <i class="fas fa-arrow-right" style="font-size:.6rem;color:#94a3b8;"></i>
        {{ Str::limit($lead->next_step, 40) }}
        @if($lead->next_step_date)
        <span class="lc-date {{ $lead->next_step_date->isPast() ? 'lc-date-late' : '' }}">
            {{ $lead->next_step_date->format('d/m') }}
        </span>
        @endif
    </div>
    @endif
</div>
