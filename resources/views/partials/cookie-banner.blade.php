@unless(request()->cookie('vivensi_cookie_consent'))
<div id="cookieBanner" class="fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 shadow-2xl z-50 p-4 md:p-6 cookie-banner-animate">
    
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
        
        <div class="text-sm text-gray-600 flex-1">
            <p>
                <span class="font-bold text-gray-900">🍪 Controle de Privacidade do Vivensi.</span> 
                Utilizamos cookies para personalizar sua experiência e garantir a segurança do sistema. 
                Ao continuar navegando, você concorda com nossos 
                <a href="{{ route('public.page', 'termos') }}" class="text-blue-600 hover:underline font-medium">Termos de Uso</a> e 
                <a href="{{ route('public.page', 'privacidade') }}" class="text-blue-600 hover:underline font-medium">Política de Privacidade</a>.
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <button onclick="dismissCookieBanner()" 
                    class="text-xs font-medium text-gray-500 hover:text-gray-700 transition px-3 py-2 rounded-lg hover:bg-gray-100">
                Dispensar
            </button>

            <form action="{{ route('cookie.accept') }}" method="POST" id="cookieAcceptForm">
                @csrf
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2.5 px-6 rounded-lg shadow-sm transition-all transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Aceitar Cookies
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.cookie-banner-animate {
    animation: slideUp 0.4s ease-out;
}

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
