<?php

/*
| Chat do site com o Bruno (atendimento comercial do Vivensi).
| Liga/desliga pelo super admin (SystemSetting web_chat_enabled).
*/
return [
    // Limites anti-abuso
    'max_conversations_per_ip_day' => (int) env('WEB_CHAT_MAX_CONV_IP_DAY', 15),
    'max_visitor_messages'         => (int) env('WEB_CHAT_MAX_VISITOR_MSGS', 60),
    'max_ai_replies'               => (int) env('WEB_CHAT_MAX_AI_REPLIES', 40),
    'max_ai_replies_day'           => (int) env('WEB_CHAT_MAX_AI_REPLIES_DAY', 1500),
    'max_message_length'           => 1000,

    // Dias à frente que o chat mostra para agendar
    'booking_days_ahead'           => 10,

    // Validade do link de retomada enviado por e-mail
    'resume_link_days'             => 14,

    'greeting' => "Oi! Eu sou o Bruno, do Vivensi 👋\nPosso te mostrar como o Vivensi ajuda a sua organização, tirar dúvidas sobre os planos ou já marcar uma conversa de 20 minutos com a nossa equipe. Como posso ajudar?",
];
