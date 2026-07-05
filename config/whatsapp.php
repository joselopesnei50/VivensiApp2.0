<?php

return [
    // Data retention policy for WhatsApp messages/notes (LGPD).
    // Use 0 or null to disable cleanup scheduling (handled in Kernel).
    'retention_days' => (int) env('WHATSAPP_RETENTION_DAYS', 365),

    // Sandbox mode for localhost/dev: do not call external providers.
    'sandbox_enabled' => (bool) env('WHATSAPP_SANDBOX_ENABLED', false),

    // Evolution API v2 connection settings
    'evolution_api_url'      => env('EVOLUTION_API_URL', 'https://evo.vivensi.app.br'),
    'evolution_global_key'   => env('EVOLUTION_GLOBAL_KEY'),
    // Segredo HMAC opcional para validar assinatura dos webhooks da Evolution API.
    // Se definido, rejeita payloads sem header x-webhook-hmac válido.
    'evolution_webhook_secret' => env('EVOLUTION_WEBHOOK_SECRET'),

    // Chave dedicada para o blind index dos tokens de instância WhatsApp
    // (campo instance_token_bidx em whatsapp_instances). Antes da Tarefa 1.5
    // o hash usava config('app.key') diretamente — rotacionar APP_KEY quebrava
    // a busca de instâncias nos webhooks. Esta chave separada permite rotação
    // independente. Se vazia, o helper whatsapp_bidx_key() faz fallback para
    // APP_KEY (mantém compatibilidade em dev e em prod ainda não migrada).
    'bidx_key' => env('WHATSAPP_BIDX_KEY'),

    // ── Política antiban (Tarefa 3.2 da auditoria) ─────────────────────────
    // Valores que antes eram constantes no AntiBanManager. Configurável por
    // env (defaults idênticos ao comportamento anterior) e overridável por
    // instância via whatsapp_instances.settings['warming_profile'] e
    // settings['max_per_hour']. Ver docs/WHATSAPP_TUNING.md.
    'antiban' => [
        // Limite máximo por hora (rate limiter horário). Default 55 = comportamento
        // pré-3.2. Override por instância via settings.max_per_hour.
        'max_per_hour' => (int) env('WHATSAPP_MAX_PER_HOUR', 55),

        // Horas de restrição quando isBanSignal detecta sinal de ban (markAsRestricted).
        // Aplicado globalmente — não há override por instância por design (decisão
        // de saúde de plataforma, não de produto).
        'default_ban_restriction_hours' => (int) env('WHATSAPP_BAN_RESTRICTION_HOURS', 24),

        // Perfis de warming disponíveis. Cada perfil mapeia dia => limite diário.
        // Após dia 14 o warming é desativado e usa instance.daily_limit normal.
        //
        // Tenant escolhe via $instance->settings['warming_profile'] = 'default' | 'conservative'.
        // Default = comportamento pré-3.2 (20→370 em 14 dias, ~30% crescimento/dia).
        // Conservative = recomendado para chip novo ou cliente cauteloso (15→150 em 14 dias).
        'warming_profiles' => [
            'default' => [
                1  => 20,  2  => 30,  3  => 40,  4  => 55,  5  => 70,
                6  => 90,  7  => 115, 8  => 140, 9  => 170, 10 => 205,
                11 => 245, 12 => 290, 13 => 340, 14 => 370,
            ],
            'conservative' => [
                1  => 15,  2  => 20,  3  => 25,  4  => 35,  5  => 45,
                6  => 55,  7  => 65,  8  => 80,  9  => 95,  10 => 110,
                11 => 125, 12 => 135, 13 => 145, 14 => 150,
            ],
            // Fase 4 (Anti-Ban 2026): perfil "ultra-seguro" — recomendado
            // para o cenário Meta pós-jan/2026 e para clientes com histórico
            // de ban. Escalada MAIS suave (10 → 370) em 21 dias em vez de 14.
            'ultra_safe' => [
                1  => 10,  2  => 15,  3  => 20,  4  => 28,  5  => 36,
                6  => 46,  7  => 58,  8  => 72,  9  => 88,  10 => 106,
                11 => 126, 12 => 148, 13 => 172, 14 => 198, 15 => 226,
                16 => 256, 17 => 288, 18 => 322, 19 => 358, 20 => 370,
                21 => 370,
            ],
        ],
    ],

    // Bot de Gestão Interna (Vivensi Command Bot)
    // Nome da instância na Evolution API dedicada ao bot de comandos
    'bot_instance_name' => env('WHATSAPP_BOT_INSTANCE', 'vivensi-bot'),
    'bot_phone'         => env('WHATSAPP_BOT_PHONE', '5516997618695'),

    // ── Double Opt-in (P0.2 — Fase 5.2 do roadmap) ─────────────────────────
    // Mensagem disparada quando um Lead opt-in é capturado em formulário
    // público ou WhatsApp. O webhook inbound confirma/recusa pela resposta
    // do contato, comparando contra as keywords abaixo (case-insensitive,
    // sem acentos). TTL define a janela de validade do token.
    //
    // Placeholders disponíveis no template: :nome e :tenant.
    'double_opt_in' => [
        'message_template' => env(
            'WHATSAPP_DOUBLE_OPT_IN_MESSAGE',
            "Olá :nome! 👋\n\nRecebemos seu cadastro em *:tenant*.\n\nPara confirmar e receber nossas comunicações, responda *SIM*.\n\nSe não foi você ou prefere não receber, responda *NÃO* — ficamos por aqui sem te incomodar."
        ),
        'ttl_hours'        => (int) env('WHATSAPP_DOUBLE_OPT_IN_TTL_HOURS', 72),
        'confirm_keywords' => ['sim', 'quero', 'confirmo', 'confirmar', 'aceito', 'concordo', 'yes', 'ok', '1'],
        'opt_out_keywords' => ['nao', 'cancelar', 'sair', 'stop', 'remover', 'descadastrar', 'no', '2'],
    ],

    // Termo de Responsabilidade Anti-Ban (Fase 2 — item 4.2 do roadmap).
    // Aceite obrigatório do gestor do tenant ANTES de criar instância Evolution.
    // É um registro jurídico de responsabilidade pelo número — NÃO inclui
    // técnicas de evasão de banimento.
    // Subir 'current_version' aqui invalida aceites anteriores e força reaceite.
    'anti_ban_terms' => [
        'current_version' => 'v2026-07',
        'versions' => [
            '1.0' => [
                'effective_at' => '2026-06-17',
                'title'        => 'Termo de Responsabilidade pelo Uso do WhatsApp',
                'text' => <<<'TERM'
Ao prosseguir, declaro ciência de que:

1. O WhatsApp (Evolution API / Baileys) é uma integração não-oficial. O número conectado pode ser bloqueado ou banido pela Meta sem aviso, mesmo em uso lícito. O Vivensi NÃO se responsabiliza por banimentos, perdas operacionais ou de reputação decorrentes do uso desse canal.

2. Sou o único responsável jurídico pelo número conectado, pelas mensagens enviadas a partir dele e pelo cumprimento das normas aplicáveis: LGPD (Lei 13.709/18), Marco Civil da Internet (Lei 12.965/14) e — se for o caso — Lei Eleitoral (Lei 9.504/97) e normativas do TSE.

3. Comprometo-me a obter opt-in explícito de cada contato antes de enviar qualquer mensagem em massa, a respeitar pedidos de descadastro (STOP) e a não enviar conteúdo enganoso, ofensivo, ilegal ou que infrinja direitos de terceiros.

4. Em hipótese alguma usarei o canal para fraude, golpe (incluindo falsa promessa de benefício em troca de voto), desinformação eleitoral ou tratamento ilegal de dados pessoais sensíveis.

5. Caso a Meta restrinja o número, comprometo-me a interromper imediatamente o uso, sem responsabilizar o Vivensi pelas consequências.

Ao clicar em "Aceito", confirmo que li, entendi e concordo com este termo na íntegra.
TERM,
            ],

            // v2026-07 — atualização de julho/2026.
            // Justificativa: em janeiro/2026 a Meta intensificou o bloqueio de APIs
            // não-oficiais. O termo antigo (1.0) não cobria esse cenário. Aceites
            // em 1.0 continuam válidos (aceite histórico) — só criações NOVAS
            // exigem a versão vigente (current_version).
            'v2026-07' => [
                'effective_at' => '2026-07-04',
                'title'        => 'Termo de Responsabilidade — Uso de API WhatsApp via Evolution API (v2026-07)',
                'text' => <<<'TERM'
Ao prosseguir com a criação de uma instância WhatsApp, você declara ter lido, compreendido e concordado com os seguintes termos:

1. Natureza da ferramenta
A Evolution API é uma solução de código aberto desenvolvida pela comunidade que emula o protocolo do WhatsApp Web. Ela NÃO é autorizada, homologada ou reconhecida pela Meta Platforms, Inc. (empresa controladora do WhatsApp).

2. Cenário regulatório de 2026
Em janeiro de 2026, a Meta intensificou os mecanismos automáticos de detecção e bloqueio de ferramentas não-oficiais. O risco de suspensão ou banimento de números que utilizam APIs não-oficiais aumentou significativamente em relação a anos anteriores. Este risco é real, mensurável e permanente.

3. Consequências do banimento
O banimento imposto pela Meta é vinculado ao número de telefone, não à conta ou à ferramenta utilizada. Isso significa que:
— Um número banido não pode ser recuperado pela simples troca de ferramenta;
— Números banidos pela Meta geralmente não são aceitos para cadastro na API Oficial do WhatsApp mesmo após a migração;
— A perda do número implica perda do histórico de conversas e da base de contatos associada a ele.

4. Proteções técnicas implementadas pelo Vivensi
O Vivensi implementa as seguintes camadas de proteção técnica para reduzir (mas não eliminar) o risco de banimento:
— Warming progressivo com escalada gradual de volume;
— Limite horário de mensagens por instância;
— Janela de horário seguro de envio;
— Simulação de comportamento humano (digitação, pausas);
— Bloqueio automático de URLs encurtadas;
— Detecção automática de opt-out e parada imediata de envio;
— Detecção de sinal de ban e restrição automática da instância;
— Fingerprint de conteúdo: limite diário de envios do mesmo texto por instância, para reduzir o padrão de spam detectado pelo ML da Meta.

5. O que NÃO é coberto pelas proteções
As proteções acima NÃO eliminam o risco de banimento. Situações que aumentam significativamente o risco incluem, sem se limitar a:
— Envio de mensagens idênticas para grande número de destinatários sem variação de conteúdo (mesmo dentro dos limites configurados);
— Disparo para listas sem opt-in explícito e documentado;
— Conteúdo classificado pela Meta como spam (promoções agressivas, links encurtados, conteúdo enganoso);
— Volume elevado de denúncias pelos destinatários;
— Uso simultâneo do mesmo número em múltiplas ferramentas.

6. Responsabilidade
— O Vivensi fornece a infraestrutura e as proteções técnicas descritas acima.
— A responsabilidade pelo conteúdo enviado, pelo respeito ao opt-in dos destinatários e pela conformidade com as políticas de uso do WhatsApp é EXCLUSIVAMENTE do usuário/organização.
— O Vivensi não se responsabiliza por banimentos de números, perda de histórico ou danos operacionais decorrentes do uso da Evolution API.
— O Vivensi não oferece garantia de ausência de banimento nem compromisso de recuperação de números banidos.

7. Recomendação do Vivensi
Para operações de maior escala, alto volume de disparos ou tolerância zero a risco de interrupção, o Vivensi recomenda a migração para a API Oficial do WhatsApp (Cloud API da Meta). Consulte os planos disponíveis.

Ao clicar em "Li e aceito os termos", você confirma que leu este documento, compreende os riscos descritos e assume responsabilidade pelo uso da ferramenta.
TERM,
            ],
        ],
    ],
];


