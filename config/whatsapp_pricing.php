<?php

/*
|--------------------------------------------------------------------------
| WhatsApp Cloud API Pricing (Meta)
|--------------------------------------------------------------------------
|
| Preços por conversa de 24h por PAIS e CATEGORIA em USD micros
| (1 USD = 1.000.000 micros). Meta envia o pricing no webhook via
| statuses[].pricing — usamos essa tabela apenas como FALLBACK quando
| o webhook não trouxer o campo `pricing`.
|
| Fonte: https://developers.facebook.com/docs/whatsapp/pricing
| Valores validos em jul/2026 (revisar trimestralmente).
|
| Categorias:
|  - marketing:       Promocional (mais caro)
|  - utility:         Transacional (confirmacoes, updates)
|  - authentication:  OTP, verificacao
|  - service:         User-initiated (gratis dentro da janela 24h)
|
*/

return [

    /*
     * Taxa de cambio USD -> BRL usada para calcular custo em reais no dashboard.
     * Pode ser sobrescrita por config('services.exchange.usd_brl') via .env
     * WHATSAPP_EXCHANGE_USD_BRL.
     */
    'usd_brl_rate' => env('WHATSAPP_EXCHANGE_USD_BRL', 5.50),

    /*
     * Markup padrao aplicado no repasse ao tenant. Ex: 50 = Meta cobra $0.10,
     * Vivensi cobra $0.15 do cliente (via débito em BRL do saldo pré-pago).
     * Usado por WhatsappCreditService::debitForConversation.
     */
    'default_markup_pct' => env('WHATSAPP_DEFAULT_MARKUP_PCT', 50),

    /*
     * Configuração do modo pré-pago (Fase 1 do modelo comercial A).
     */
    'prepaid' => [
        // Valor mínimo de recarga em BRL micros (1_000_000 = R$ 1,00).
        'min_topup_brl_micros' => (int) env('WHATSAPP_MIN_TOPUP_BRL', 50) * 1_000_000,

        // Threshold para alertar saldo baixo (% da última recarga).
        'low_balance_threshold_pct' => (int) env('WHATSAPP_LOW_BALANCE_PCT', 20),

        // Pacotes sugeridos pra recarga self-service (Fase 2). Em BRL.
        'suggested_topups' => [50, 100, 250, 500],
    ],

    /*
     * Preco por categoria em USD micros por pais.
     * Estrutura: pricing[country][category] = micros
     */
    'pricing' => [

        // Brasil
        'BR' => [
            'marketing'           => 62_500,   // $0.0625
            'utility'             =>  8_000,   // $0.0080
            'authentication'      => 31_500,   // $0.0315
            'service'             =>      0,   // gratis se user-initiated
            'referral_conversion' =>      0,   // gratis (novo em 2024)
        ],

        // Estados Unidos
        'US' => [
            'marketing'           => 25_000,   // $0.025
            'utility'             =>  8_500,   // $0.0085
            'authentication'      => 13_500,   // $0.0135
            'service'             =>      0,
            'referral_conversion' =>      0,
        ],

        // Padrao mundial (fallback)
        'default' => [
            'marketing'           => 50_000,   // $0.050
            'utility'             => 10_000,   // $0.010
            'authentication'      => 25_000,   // $0.025
            'service'             =>      0,
            'referral_conversion' =>      0,
        ],
    ],

];
