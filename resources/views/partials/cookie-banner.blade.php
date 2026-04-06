@unless(request()->cookie('vivensi_cookie_consent'))
<div id="cookieBanner" style="position: fixed; bottom: 0; left: 0; width: 100%; background: white; border-top: 1px solid #e5e7eb; box-shadow: 0 -4px 20px rgba(0,0,0,0.1); z-index: 9999; padding: 1.5rem; animation: slideUp 0.4s ease-out;">
    
    <div style="max-width: 1280px; margin: 0 auto; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
        
        <div style="flex: 1; min-width: 300px; font-size: 0.875rem; color: #4b5563;">
            <p style="margin: 0;">
                <span style="font-weight: 700; color: #111827;">🍪 Controle de Privacidade do Vivensi.</span> 
                Utilizamos cookies para personalizar sua experiência e garantir a segurança do sistema. 
                Ao continuar navegando, você concorda com nossos 
                <a href="{{ route('public.page', 'termos') }}" style="color: #2563eb; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Termos de Uso</a> e 
                <a href="{{ route('public.page', 'privacidade') }}" style="color: #2563eb; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Política de Privacidade</a>.
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0;">
            <button onclick="dismissCookieBanner()" 
                    style="font-size: 0.75rem; font-weight: 500; color: #6b7280; background: transparent; border: none; padding: 0.5rem 0.75rem; cursor: pointer; border-radius: 0.5rem; transition: all 0.2s;"
                    onmouseover="this.style.color='#374151'; this.style.background='#f3f4f6'"
                    onmouseout="this.style.color='#6b7280'; this.style.background='transparent'">
                Dispensar
            </button>

            <form action="{{ route('cookie.accept') }}" method="POST" id="cookieAcceptForm" style="margin: 0;">
                @csrf
                <button type="submit" 
                        style="background: #2563eb; color: white; font-size: 0.875rem; font-weight: 600; padding: 0.625rem 1.5rem; border: none; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); cursor: pointer; transition: all 0.2s;"
                        onmouseover="this.style.background='#1d4ed8'; this.style.transform='scale(1.05)'"
                        onmouseout="this.style.background='#2563eb'; this.style.transform='scale(1)'">
                    Aceitar Cookies
                </button>
            </form>
        </div>
    </div>
</div>

<style>
@keyframes slideUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.cookie-banner-fade-out {
    animation: fadeOut 0.3s ease-out forwards;
}

@keyframes fadeOut {
    to {
        opacity: 0;
        transform: translateY(20px);
    }
}

@media (max-width: 768px) {
    #cookieBanner > div {
        flex-direction: column;
        text-align: center;
    }
    #cookieBanner > div > div:first-child {
        min-width: 100%;
    }
}
</style>

<script>
function dismissCookieBanner() {
    const banner = document.getElementById('cookieBanner');
    if (banner) {
        banner.classList.add('cookie-banner-fade-out');
        setTimeout(() => {
            banner.style.display = 'none';
        }, 300);
    }
}
</script>
@endunless
