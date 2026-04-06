@props(['icon', 'title', 'description', 'action_label' => null, 'action_url' => null])
<div style="text-align: center; padding: 60px 20px; background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.1); border-radius: 16px; margin: 20px 0;">
    <div style="width: 80px; height: 80px; background: rgba(99,102,241,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
        <i class="fas {{ $icon }}" style="font-size: 2.5rem; color: #818cf8; opacity: 0.8;"></i>
    </div>
    <h5 style="color: white; font-weight: 800; font-size: 1.2rem; margin-bottom: 8px;">{{ $title }}</h5>
    <p style="color: rgba(255,255,255,0.4); font-size: 0.9rem; max-width: 400px; margin: 0 auto 24px;">{{ $description }}</p>
    
    @if($action_label && $action_url)
        <a href="{{ $action_url }}" 
           style="background: linear-gradient(135deg, #4f46e5, #818cf8); color: white; padding: 12px 24px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: 0 4px 15px rgba(79,70,229,0.3);"
           onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(79,70,229,0.4)'"
           onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 15px rgba(79,70,229,0.3)'">
            <i class="fas fa-plus"></i> {{ $action_label }}
        </a>
    @endif
</div>
