<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Cookie Banner</title>
</head>
<body style="font-family: Arial, sans-serif; padding: 20px;">
    
    <h1>Teste do Banner de Cookies</h1>
    <p>Se o banner estiver funcionando, você deve ver uma barra no rodapé da página.</p>
    
    <h2>Debug Info:</h2>
    <ul>
        <li>Cookie existe? <strong><?php echo isset($_COOKIE['vivensi_cookie_consent']) ? 'SIM' : 'NÃO'; ?></strong></li>
        <li>Valor do cookie: <strong><?php echo $_COOKIE['vivensi_cookie_consent'] ?? 'N/A'; ?></strong></li>
    </ul>

    <!-- Include do banner -->
    <?php
    // Simular Laravel request()->cookie()
    $hasCookie = isset($_COOKIE['vivensi_cookie_consent']);
    ?>
    
    <?php if (!$hasCookie): ?>
    <div id="cookieBanner" style="position: fixed; bottom: 0; left: 0; width: 100%; background: white; border-top: 1px solid #e5e7eb; box-shadow: 0 -4px 20px rgba(0,0,0,0.1); z-index: 9999; padding: 1.5rem; animation: slideUp 0.4s ease-out;">
        
        <div style="max-width: 1280px; margin: 0 auto; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
            
            <div style="flex: 1; min-width: 300px; font-size: 0.875rem; color: #4b5563;">
                <p style="margin: 0;">
                    <span style="font-weight: 700; color: #111827;">🍪 Controle de Privacidade do Vivensi.</span> 
                    Utilizamos cookies para personalizar sua experiência e garantir a segurança do sistema. 
                    Ao continuar navegando, você concorda com nossos 
                    <a href="/pagina/termos" style="color: #2563eb; text-decoration: none; font-weight: 500;">Termos de Uso</a> e 
                    <a href="/pagina/privacidade" style="color: #2563eb; text-decoration: none; font-weight: 500;">Política de Privacidade</a>.
                </p>
            </div>

            <div style="display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0;">
                <button onclick="dismissCookieBanner()" 
                        style="font-size: 0.75rem; font-weight: 500; color: #6b7280; background: transparent; border: none; padding: 0.5rem 0.75rem; cursor: pointer; border-radius: 0.5rem;">
                    Dispensar
                </button>

                <button onclick="acceptCookies()" 
                        style="background: #2563eb; color: white; font-size: 0.875rem; font-weight: 600; padding: 0.625rem 1.5rem; border: none; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); cursor: pointer;">
                    Aceitar Cookies
                </button>
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

    function acceptCookies() {
        // Criar cookie
        document.cookie = "vivensi_cookie_consent=accepted; max-age=31536000; path=/";
        dismissCookieBanner();
        setTimeout(() => {
            location.reload();
        }, 500);
    }
    </script>
    <?php else: ?>
    <div style="background: #10b981; color: white; padding: 1rem; border-radius: 0.5rem; margin-top: 1rem;">
        ✅ Cookie já foi aceito! O banner não deve aparecer.
    </div>
    <?php endif; ?>

</body>
</html>
