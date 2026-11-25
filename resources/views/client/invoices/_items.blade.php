{{-- Linhas da fatura (plano + adicionais), quando há mais de uma. --}}
@if(is_array($inv->items) && count($inv->items) > 1)
    <ul style="list-style:none;margin:6px 0 0;padding:0;font-size:.78rem;color:#64748b;line-height:1.55;">
        @foreach($inv->items as $line)
            <li>
                {{ $line['name'] ?? 'Item' }}@if(($line['quantity'] ?? 1) > 1) × {{ (int) $line['quantity'] }}@endif
                — R$ {{ number_format(((int) ($line['total_cents'] ?? 0)) / 100, 2, ',', '.') }}
            </li>
        @endforeach
    </ul>
@endif
