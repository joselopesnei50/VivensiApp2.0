<?php

/*
|--------------------------------------------------------------------------
| Envio próprio de campanhas (super admin)
|--------------------------------------------------------------------------
| Campanhas com delivery_driver='own' não passam pelo marketing da Brevo:
| o Vivensi enfileira, envia um a um pelo mailer abaixo (SES por padrão),
| e mede aberturas, cliques, bounces e reclamações por conta própria.
| A Brevo continua sendo o mailer transacional (MAIL_MAILER=brevo).
*/

return [

    // Motor pré-selecionado no formulário de nova campanha: brevo | own.
    // Deixe 'brevo' até o SES sair do sandbox e as chaves estarem no .env.
    'default_driver' => env('CAMPAIGN_DEFAULT_DRIVER', 'brevo'),

    // Mailer de config/mail.php usado nas campanhas: campaign_ses | campaign_smtp | log | array
    'mailer' => env('CAMPAIGN_MAILER', 'campaign_ses'),

    // Remetente padrão. Use um subdomínio dedicado à prospecção
    // (ex.: contato.vivensi.app.br) para não afetar o transacional.
    'from' => [
        'address' => env('CAMPAIGN_FROM_ADDRESS', env('MAIL_FROM_ADDRESS')),
        'name'    => env('CAMPAIGN_FROM_NAME', env('MAIL_FROM_NAME', 'Vivensi')),
    ],

    // Domínios permitidos como remetente (verificados no SES). Vazio = qualquer um.
    'allowed_sender_domains' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CAMPAIGN_ALLOWED_SENDER_DOMAINS', ''))
    ))),

    // Ritmo padrão. Domínio novo? Comece baixo (ex.: 20/min) e aumente por semana.
    'rate_per_minute' => (int) env('CAMPAIGN_RATE_PER_MINUTE', 60),

    // Teto por segundo dentro de um lote (limite do SES: 14/s em produção, 1/s no sandbox).
    'max_per_second' => (int) env('CAMPAIGN_MAX_PER_SECOND', 10),

    // Teto de envios em 24h somando todas as campanhas próprias (aquecimento). 0 = sem teto.
    'daily_limit' => (int) env('CAMPAIGN_DAILY_LIMIT', 0),

    // Freio automático: pausa a campanha se, após `min_sample` envios,
    // bounce ou reclamação passarem desses percentuais.
    'safety' => [
        'min_sample'          => (int) env('CAMPAIGN_SAFETY_MIN_SAMPLE', 100),
        'max_bounce_rate'     => (float) env('CAMPAIGN_SAFETY_MAX_BOUNCE', 5.0),
        'max_complaint_rate'  => (float) env('CAMPAIGN_SAFETY_MAX_COMPLAINT', 0.3),
    ],

    'tracking' => [
        'opens'  => (bool) env('CAMPAIGN_TRACK_OPENS', true),
        'clicks' => (bool) env('CAMPAIGN_TRACK_CLICKS', true),
    ],

    // Webhook SNS do SES: ARN do tópico aceito (vazio = qualquer tópico com assinatura válida)
    'ses_sns_topic_arn' => env('CAMPAIGN_SES_SNS_TOPIC_ARN'),
];
