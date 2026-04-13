@php
/*
 * Partial compartilhada entre builder e render.
 * Recebe: $c (array), $type (string), $banner (model, opcional)
 */
$font   = $banner->settings['font_family'] ?? 'Inter';
$align  = $c['alignment'] ?? 'center';
$flexAl = $align === 'left' ? 'flex-start' : ($align === 'right' ? 'flex-end' : 'center');
$btnR   = ($c['button_radius'] ?? 8) . 'px';
$hasBtn = !empty($c['button_text']);
@endphp

@if($type === 'hero_text')
<div style="
    background:{{ $c['bg_color'] ?? '#4f46e5' }};
    width:100%;height:100%;
    display:flex;flex-direction:column;
    align-items:{{ $flexAl }};justify-content:center;
    padding:60px 48px;text-align:{{ $align }};
    box-sizing:border-box;
">
    <h1 style="font-size:{{ $c['title_size'] ?? 52 }}px;color:{{ $c['title_color'] ?? '#fff' }};font-family:'{{ $font }}',sans-serif;font-weight:800;line-height:1.08;margin:0 0 18px;letter-spacing:-1px;">{{ $c['title'] ?? 'Título' }}</h1>
    <p style="font-size:{{ $c['subtitle_size'] ?? 22 }}px;color:{{ $c['subtitle_color'] ?? '#e0e7ff' }};font-family:'{{ $font }}',sans-serif;line-height:1.5;margin:0 0 36px;max-width:640px;">{{ $c['subtitle'] ?? '' }}</p>
    @if($hasBtn)
    <a href="{{ $c['button_url'] ?? '#' }}" style="display:inline-block;padding:14px 40px;background:{{ $c['button_bg'] ?? '#fff' }};color:{{ $c['button_color'] ?? '#4f46e5' }};font-family:'{{ $font }}',sans-serif;font-size:18px;font-weight:700;border-radius:{{ $btnR }};text-decoration:none;">{{ $c['button_text'] }}</a>
    @endif
</div>

@elseif($type === 'image_overlay')
@php $op = ($c['overlay_opacity'] ?? 55) / 100; @endphp
<div style="position:relative;width:100%;height:100%;overflow:hidden;">
    <img src="{{ $c['image_url'] ?? '' }}" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;">
    <div style="position:absolute;inset:0;background:{{ $c['overlay_color'] ?? '#000' }};opacity:{{ $op }};"></div>
    <div style="position:relative;z-index:1;height:100%;display:flex;flex-direction:column;align-items:{{ $flexAl }};justify-content:center;padding:60px 48px;text-align:{{ $align }};box-sizing:border-box;">
        <h1 style="font-size:{{ $c['title_size'] ?? 56 }}px;color:{{ $c['title_color'] ?? '#fff' }};font-family:'{{ $font }}',sans-serif;font-weight:800;margin:0 0 18px;line-height:1.08;letter-spacing:-1px;">{{ $c['title'] ?? 'Título' }}</h1>
        <p style="font-size:{{ $c['subtitle_size'] ?? 22 }}px;color:{{ $c['subtitle_color'] ?? '#f1f5f9' }};font-family:'{{ $font }}',sans-serif;line-height:1.5;margin:0 0 36px;max-width:640px;">{{ $c['subtitle'] ?? '' }}</p>
        @if($hasBtn)
        <a href="{{ $c['button_url'] ?? '#' }}" style="display:inline-block;padding:14px 40px;background:{{ $c['button_bg'] ?? '#fff' }};color:{{ $c['button_color'] ?? '#1e293b' }};font-family:'{{ $font }}',sans-serif;font-size:18px;font-weight:700;border-radius:{{ $btnR }};text-decoration:none;">{{ $c['button_text'] }}</a>
        @endif
    </div>
</div>

@elseif($type === 'gradient_text')
@php $angle = $c['gradient_angle'] ?? 135; @endphp
<div style="
    background:linear-gradient({{ $angle }}deg, {{ $c['gradient_from'] ?? '#7C3AED' }}, {{ $c['gradient_to'] ?? '#3B82F6' }});
    width:100%;height:100%;
    display:flex;flex-direction:column;
    align-items:{{ $flexAl }};justify-content:center;
    padding:60px 48px;text-align:{{ $align }};
    box-sizing:border-box;
">
    <h1 style="font-size:{{ $c['title_size'] ?? 54 }}px;color:{{ $c['title_color'] ?? '#fff' }};font-family:'{{ $font }}',sans-serif;font-weight:800;line-height:1.08;margin:0 0 18px;letter-spacing:-1px;">{{ $c['title'] ?? 'Título' }}</h1>
    <p style="font-size:{{ $c['subtitle_size'] ?? 22 }}px;color:{{ $c['subtitle_color'] ?? '#e0e7ff' }};font-family:'{{ $font }}',sans-serif;line-height:1.5;margin:0 0 36px;max-width:640px;">{{ $c['subtitle'] ?? '' }}</p>
    @if($hasBtn)
    <a href="{{ $c['button_url'] ?? '#' }}" style="display:inline-block;padding:14px 40px;background:{{ $c['button_bg'] ?? 'rgba(255,255,255,0.15)' }};color:{{ $c['button_color'] ?? '#fff' }};font-family:'{{ $font }}',sans-serif;font-size:18px;font-weight:700;border-radius:{{ $btnR }};text-decoration:none;border:2px solid rgba(255,255,255,.3);">{{ $c['button_text'] }}</a>
    @endif
</div>

@elseif($type === 'split_banner')
@php $imgLeft = ($c['image_side'] ?? 'left') === 'left'; @endphp
<div style="display:flex;flex-direction:{{ $imgLeft ? 'row' : 'row-reverse' }};width:100%;height:100%;">
    <div style="flex:1;overflow:hidden;position:relative;">
        <img src="{{ $c['image_url'] ?? '' }}" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;">
    </div>
    <div style="flex:1;background:{{ $c['bg_color'] ?? '#fff' }};padding:48px 44px;display:flex;flex-direction:column;justify-content:center;box-sizing:border-box;">
        @if(!empty($c['tag']))
        <span style="display:inline-block;background:{{ $c['tag_bg'] ?? '#4f46e5' }};color:{{ $c['tag_color'] ?? '#fff' }};font-size:12px;font-weight:700;padding:4px 16px;border-radius:99px;letter-spacing:1.5px;margin-bottom:20px;width:fit-content;font-family:'{{ $font }}',sans-serif;">{{ $c['tag'] }}</span>
        @endif
        <h1 style="font-size:{{ $c['title_size'] ?? 38 }}px;color:{{ $c['title_color'] ?? '#111827' }};font-family:'{{ $font }}',sans-serif;font-weight:800;margin:0 0 16px;line-height:1.15;letter-spacing:-.5px;">{{ $c['title'] ?? 'Título' }}</h1>
        <p style="font-size:{{ $c['subtitle_size'] ?? 17 }}px;color:{{ $c['subtitle_color'] ?? '#6b7280' }};font-family:'{{ $font }}',sans-serif;line-height:1.65;margin:0 0 32px;">{{ $c['subtitle'] ?? '' }}</p>
        @if($hasBtn)
        <a href="{{ $c['button_url'] ?? '#' }}" style="display:inline-block;padding:13px 34px;background:{{ $c['button_bg'] ?? '#4f46e5' }};color:{{ $c['button_color'] ?? '#fff' }};font-family:'{{ $font }}',sans-serif;font-size:16px;font-weight:700;border-radius:{{ $btnR }};text-decoration:none;width:fit-content;">{{ $c['button_text'] }}</a>
        @endif
    </div>
</div>

@elseif($type === 'announcement')
<div style="background:{{ $c['bg_color'] ?? '#fffbeb' }};width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:56px 48px;text-align:center;box-sizing:border-box;">
    <div style="margin-bottom:18px;">
        <i class="{{ $c['icon'] ?? 'fas fa-bullhorn' }}" style="font-size:48px;color:{{ $c['icon_color'] ?? '#f59e0b' }};"></i>
    </div>
    @if(!empty($c['tag']))
    <span style="display:inline-block;background:{{ $c['tag_bg'] ?? '#f59e0b' }};color:{{ $c['tag_color'] ?? '#fff' }};font-size:11px;font-weight:700;padding:3px 16px;border-radius:99px;letter-spacing:2px;margin-bottom:18px;font-family:'{{ $font }}',sans-serif;">{{ $c['tag'] }}</span>
    @endif
    <h1 style="font-size:{{ $c['title_size'] ?? 36 }}px;color:{{ $c['title_color'] ?? '#111827' }};font-family:'{{ $font }}',sans-serif;font-weight:800;margin:0 0 16px;letter-spacing:-.5px;">{{ $c['title'] ?? 'Título' }}</h1>
    <p style="font-size:{{ $c['message_size'] ?? 18 }}px;color:{{ $c['message_color'] ?? '#6b7280' }};font-family:'{{ $font }}',sans-serif;line-height:1.65;margin:0 0 32px;max-width:600px;">{{ $c['message'] ?? '' }}</p>
    @if($hasBtn)
    <a href="{{ $c['button_url'] ?? '#' }}" style="display:inline-block;padding:14px 40px;background:{{ $c['button_bg'] ?? '#f59e0b' }};color:{{ $c['button_color'] ?? '#fff' }};font-family:'{{ $font }}',sans-serif;font-size:18px;font-weight:700;border-radius:{{ $btnR }};text-decoration:none;">{{ $c['button_text'] }}</a>
    @endif
</div>

@elseif($type === 'event_banner')
<div style="background:{{ $c['bg_color'] ?? '#0f172a' }};width:100%;height:100%;padding:56px 64px;box-sizing:border-box;position:relative;overflow:hidden;display:flex;flex-direction:column;justify-content:center;">
    {{-- Decorative orbs --}}
    <div style="position:absolute;top:-80px;right:-80px;width:360px;height:360px;border-radius:50%;background:{{ $c['accent_color'] ?? '#6366f1' }};opacity:.12;pointer-events:none;"></div>
    <div style="position:absolute;bottom:-40px;left:80px;width:200px;height:200px;border-radius:50%;background:{{ $c['accent_color'] ?? '#6366f1' }};opacity:.08;pointer-events:none;"></div>
    <div style="position:relative;z-index:1;">
        @if(!empty($c['logo_url']))
        <img src="{{ $c['logo_url'] }}" alt="" style="height:52px;margin-bottom:28px;display:block;">
        @endif
        @if(!empty($c['tag']))
        <span style="display:inline-block;background:{{ $c['accent_color'] ?? '#6366f1' }};color:#fff;font-size:11px;font-weight:700;padding:3px 18px;border-radius:99px;letter-spacing:2.5px;margin-bottom:22px;font-family:'{{ $font }}',sans-serif;">{{ $c['tag'] }}</span>
        @endif
        <h1 style="font-size:{{ $c['title_size'] ?? 46 }}px;color:{{ $c['title_color'] ?? '#fff' }};font-family:'{{ $font }}',sans-serif;font-weight:800;margin:0 0 28px;line-height:1.08;letter-spacing:-1px;">{{ $c['title'] ?? 'Nome do Evento' }}</h1>
        <div style="display:flex;gap:32px;flex-wrap:wrap;margin-bottom:36px;">
            @if(!empty($c['date']))
            <div>
                <span style="display:block;font-size:10px;font-weight:700;letter-spacing:2px;color:{{ $c['accent_color'] ?? '#6366f1' }};opacity:.7;margin-bottom:4px;font-family:'{{ $font }}',sans-serif;">DATA</span>
                <span style="font-size:18px;font-weight:600;color:{{ $c['date_color'] ?? '#a5b4fc' }};font-family:'{{ $font }}',sans-serif;">{{ $c['date'] }}</span>
            </div>
            @endif
            @if(!empty($c['time']))
            <div>
                <span style="display:block;font-size:10px;font-weight:700;letter-spacing:2px;color:{{ $c['accent_color'] ?? '#6366f1' }};opacity:.7;margin-bottom:4px;font-family:'{{ $font }}',sans-serif;">HORÁRIO</span>
                <span style="font-size:18px;font-weight:600;color:{{ $c['date_color'] ?? '#a5b4fc' }};font-family:'{{ $font }}',sans-serif;">{{ $c['time'] }}</span>
            </div>
            @endif
            @if(!empty($c['location']))
            <div>
                <span style="display:block;font-size:10px;font-weight:700;letter-spacing:2px;color:{{ $c['accent_color'] ?? '#6366f1' }};opacity:.7;margin-bottom:4px;font-family:'{{ $font }}',sans-serif;">LOCAL</span>
                <span style="font-size:18px;font-weight:600;color:{{ $c['date_color'] ?? '#a5b4fc' }};font-family:'{{ $font }}',sans-serif;">{{ $c['location'] }}</span>
            </div>
            @endif
        </div>
        @if($hasBtn)
        <a href="{{ $c['button_url'] ?? '#' }}" style="display:inline-block;padding:14px 40px;background:{{ $c['button_bg'] ?? '#6366f1' }};color:{{ $c['button_color'] ?? '#fff' }};font-family:'{{ $font }}',sans-serif;font-size:18px;font-weight:700;border-radius:{{ $btnR }};text-decoration:none;">{{ $c['button_text'] }}</a>
        @endif
    </div>
</div>

@elseif($type === 'simple_text')
<div style="background:{{ $c['bg_color'] ?? '#f8fafc' }};width:100%;height:100%;display:flex;align-items:center;justify-content:{{ $flexAl }};padding:48px;box-sizing:border-box;text-align:{{ $align }};">
    <span style="font-size:{{ $c['text_size'] ?? 80 }}px;color:{{ $c['text_color'] ?? '#111827' }};font-weight:{{ $c['text_weight'] ?? 800 }};font-family:'{{ $font }}',sans-serif;line-height:1.0;word-break:break-word;white-space:pre-line;">{{ $c['text'] ?? 'Sua mensagem' }}</span>
</div>

@else
<div style="padding:24px;background:#f1f5f9;text-align:center;color:#94a3b8;font-size:14px;height:100%;display:flex;align-items:center;justify-content:center;">
    Tipo desconhecido: {{ $type }}
</div>
@endif
