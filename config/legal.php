<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Legal / LGPD Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the company details used in Terms of Use and Privacy Policy.
    | Changing these values here will update all legal pages automatically.
    |
    */

    // A Vivensi é um produto da NC5 HUB DIGITAL LTDA (mesmos dados do contrato de adesão).
    'company_name' => 'NC5 HUB DIGITAL LTDA',
    'product_name' => 'Vivensi',
    'cnpj' => '67.848.807/0001-50',
    'city' => 'Araraquara/SP',
    'forum' => 'Araraquara/SP',
    'email_support' => env('LEGAL_EMAIL_SUPPORT', 'contato@vivensi.app.br'),
    'email_dpo' => env('LEGAL_EMAIL_DPO', 'dpo@vivensi.app.br'),
    'dpo_name' => env('LEGAL_DPO_NAME', ''),
    'refund_days' => 7,
    'retention_days_after_cancel' => 90,
    'last_update' => '13/11/2026',
    
    // Links externos (opcional)
    'link_dispute_resolution' => 'https://www.consumidor.gov.br/',
];
