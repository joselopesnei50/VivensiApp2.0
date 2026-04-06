<!-- Floating WhatsApp Button -->
<style>
    .whatsapp-float {
        position: fixed;
        width: 60px;
        height: 60px;
        bottom: 40px;
        right: 40px;
        background-color: #25d366;
        color: #FFF;
        border-radius: 50px;
        text-align: center;
        font-size: 30px;
        box-shadow: 0 4px 15px rgba(37, 211, 102, 0.4);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .whatsapp-float:hover {
        width: 180px;
        background-color: #20ba5a;
    }

    .whatsapp-text {
        display: none;
        font-size: 16px;
        font-weight: 700;
        margin-left: 10px;
        white-space: nowrap;
        color: #fff;
    }

    .whatsapp-float:hover .whatsapp-text {
        display: inline;
    }

    @keyframes pulse-whatsapp {
        0% { box-shadow: 0 0 0 0px rgba(37, 211, 102, 0.4); }
        70% { box-shadow: 0 0 0 15px rgba(37, 211, 102, 0); }
        100% { box-shadow: 0 0 0 0px rgba(37, 211, 102, 0); }
    }

    .whatsapp-float {
        animation: pulse-whatsapp 2s infinite;
    }

    @media (max-width: 768px) {
        .whatsapp-float {
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            font-size: 25px;
        }
        .whatsapp-float:hover {
            width: 50px;
        }
        .whatsapp-float:hover .whatsapp-text {
            display: none;
        }
    }
</style>

<a href="https://wa.me/55{{ \App\Models\SystemSetting::getValue('support_whatsapp', '16997618695') }}?text=Olá! Gostaria de saber mais sobre o Vivensi SaaS." class="whatsapp-float" target="_blank">
    <i class="fab fa-whatsapp"></i>
    <span class="whatsapp-text">Fale Conosco</span>
</a>
