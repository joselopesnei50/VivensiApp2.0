<?php

namespace App\Services;

use App\Models\MarketingPlan;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarketingAIService
{
    public function generate(MarketingPlan $plan): bool
    {
        $prompt = $this->buildPrompt($plan);

        $result = $this->tryDeepSeek($prompt);

        if (!$result) {
            $plan->update(['status' => 'failed']);
            return false;
        }

        $plan->update([
            'mindmap_data' => ['markdown' => $result['markdown']],
            'ai_provider'  => $result['provider'],
            'status'       => 'done',
        ]);

        return true;
    }

    private function buildPrompt(MarketingPlan $plan): string
    {
        $scope       = $plan->scope === 'online_offline' ? 'online e presencial (offline)' : 'apenas online';
        $hasWA       = $plan->has_whatsapp_groups;
        $waLine      = $hasWA ? 'SIM — possui grupos de WhatsApp ativos com a comunidade' : 'Não possui grupos ativos ainda';
        $competitors = $plan->competitor_links ? "\n- Referências/concorrentes: {$plan->competitor_links}" : '';
        $budget      = $plan->budget_range     ? "\n- Orçamento disponível: {$plan->budget_range}" : "\n- Orçamento: não informado (priorizar ações orgânicas)";
        $extra       = $plan->extra_info       ? "\n- Contexto adicional: {$plan->extra_info}" : '';

        $tones = [
            'professional'  => 'Profissional e Confiante — linguagem direta, autoridade e credibilidade',
            'friendly'      => 'Amigável e Próximo — conversa informal, empatia e proximidade com a comunidade',
            'inspirational' => 'Inspirador e Motivador — storytelling emocional, propósito e transformação',
            'urgent'        => 'Urgente e Persuasivo — gatilhos de escassez, prazo e impacto imediato',
        ];
        $tone = $tones[$plan->tone] ?? 'Profissional e Confiante';

        $waSectionExtra = $hasWA ? "
      - 📋 Gestão dos Grupos Existentes
        - Mensagem de boas-vindas fixada (pinned): apresente a causa, as regras e o próximo passo
        - Frequência ideal: 3x por semana (seg, qua, sex) — nunca mais de 1x por dia
        - Formato das mensagens: gancho de 1 linha + corpo curto + 1 CTA claro
        - Enquetes semanais para medir engajamento (ex: 'Você conhece alguém que precisa desse serviço?')
        - Stories de bastidor: mostre o trabalho real — gera empatia e confiança
        - Anti-spam: nunca encaminhe mensagens genéricas — produza conteúdo exclusivo para o grupo
      - 🚀 Crescimento dos Grupos
        - Link de convite fixado no Instagram bio, Landing Page e assinatura de e-mail
        - Campanha 'Traga um amigo': benefício ou reconhecimento para quem indicar
        - QR Code nos materiais físicos (panfletos, banners, crachás)
        - Parceiros institucionais divulgando o link nos canais deles
      - 💬 Scripts Prontos para WhatsApp
        - Boas-vindas: 'Olá, [Nome]! Seja bem-vindo(a) ao grupo [Nome]. Aqui você vai encontrar [benefício 1], [benefício 2] e muito mais. Qualquer dúvida, é só chamar! 🙌'
        - Lembrete de evento: '⏰ Atenção! [Evento] acontece em [data] às [hora]. Confirme sua presença respondendo SIM aqui. Vagas limitadas!'
        - Agradecimento pós-ação: 'Obrigado a todos que participaram! Juntos fizemos [resultado concreto]. Compartilhe com quem também precisa saber disso. 💙'
      - 📊 Métricas dos Grupos
        - Taxa de resposta nas enquetes (meta: >30%)
        - Crescimento semanal de membros (meta: +5%)
        - Saídas por semana (alerta se >3% saírem)" : "
      - 🌱 Criar os Primeiros Grupos
        - Grupo piloto com 20-30 contatos mais próximos (doadores, voluntários, beneficiários)
        - Nome claro e atrativo: '[Organização] — Novidades e Oportunidades'
        - Foto de capa profissional com logo e slogan
        - Mensagem de boas-vindas fixada desde o primeiro dia
      - 📲 Construção da Base de Contatos
        - Formulário de cadastro na Landing Page com campo de WhatsApp
        - Peça autorização explícita: 'Posso te adicionar ao nosso grupo de novidades?'
        - QR Code nos eventos presenciais para entrada imediata no grupo";

        return <<<PROMPT
Você é um estrategista sênior de marketing digital com 15 anos de experiência em ONGs, terceiro setor e gestão de projetos sociais. Você conhece profundamente copywriting, growth hacking, WhatsApp marketing, redes sociais e captação de recursos.

Sua tarefa: criar um **Plano Estratégico de Marketing COMPLETO, DETALHADO e PREMIUM** para o briefing abaixo. O resultado deve ser tão rico e útil que o usuário sinta que contratou uma consultoria de alto nível.

═══════════════════════════════════════
BRIEFING DO CLIENTE
═══════════════════════════════════════
- Objetivo: {$plan->objective}
- Público-alvo: {$plan->target_audience}
- Abrangência: {$scope}
- Tom de voz: {$tone}
- Grupos de WhatsApp: {$waLine}{$competitors}{$budget}{$extra}

MÓDULOS DISPONÍVEIS NO SISTEMA VIVENSI:
→ Disparo em massa WhatsApp: /whatsapp/broadcast
→ Configurar Bot de atendimento: /whatsapp/settings
→ Landing Page: /manager/landing_pages ou /ngo/landing_pages
→ Criar Rifa Online: /raffles
→ Criar Post para redes sociais: /social/posts/create
→ Prospectar Parceiros: /prospecting

═══════════════════════════════════════
ESTRUTURA OBRIGATÓRIA DO MAPA MENTAL
═══════════════════════════════════════

# [Título criativo e impactante da campanha — máx. 8 palavras]

## 🎯 Posicionamento Estratégico
   - 💡 Proposta de Valor Única
     - [Frase de posicionamento de 1 linha — o que torna esta causa/projeto único]
     - Diferencial: [por que apoiar ESTA organização e não outra]
   - 🏆 Vantagens Competitivas
     - [Vantagem 1 com exemplo concreto]
     - [Vantagem 2 com exemplo concreto]
   - 👥 Personas do Público
     - Persona 1: [nome fictício, idade, motivação, canal preferido]
     - Persona 2: [nome fictício, idade, motivação, canal preferido]

## 📱 WhatsApp — Canal Principal
{$waSectionExtra}
   - 📢 Disparo em Massa (Broadcast)
     - Segmentação: [como dividir a lista — doadores ativos, leads frios, voluntários]
     - Melhor horário: terça e quinta-feira entre 9h-11h ou 19h-21h
     - Sequência de 3 mensagens: Interesse → Prova Social → CTA direto
     - Copy Mensagem 1 — Interesse: '[Gancho impactante de 1 linha relacionado ao objetivo]. [Contexto em 2 linhas]. Saiba mais 👇'
     - Copy Mensagem 2 — Prova Social: 'Já [resultado concreto alcançado]. [Nome] diz: "[depoimento curto]". Você também pode fazer parte disso 💙'
     - Copy Mensagem 3 — CTA: '⏰ Últimas horas! [Benefício urgente]. Clique aqui e [ação]: [link]'
     - [Usar Disparo em Massa](/whatsapp/broadcast)
   - 🤖 Bot de Atendimento 24h
     - Resposta automática para novos contatos: apresentação + FAQ
     - Palavras-chave: 'como ajudar', 'quero saber mais', 'informações'
     - Triagem: separa doadores, voluntários e beneficiários automaticamente
     - [Configurar Bot](/whatsapp/settings)

## 📸 Conteúdo para Redes Sociais
   - 🎬 Instagram — Estratégia Completa
     - Reels (maior alcance orgânico)
       - Vídeo 1: 'Bastidor em 30 segundos' — mostre o dia a dia real da equipe
       - Vídeo 2: 'Transformação em números' — antes e depois com dados reais
       - Vídeo 3: 'Depoimento em 60s' — beneficiário conta a própria história
       - Vídeo 4: 'Tutorial rápido' — como participar/apoiar em 3 passos
     - Carrossel (maior engajamento no feed)
       - 'X motivos para apoiar [causa]' — slide por motivo com dado real
       - 'Como seu apoio vira impacto' — jornada do recurso até o beneficiário
       - 'Mitos e verdades sobre [tema da causa]' — educa e gera compartilhamento
     - Stories (conexão diária)
       - Poll semanal: 'Você sabia que [dado surpreendente]?' Sim / Não
       - Quiz: 'Teste seus conhecimentos sobre [tema]'
       - Contagem regressiva para eventos e campanhas
     - Hashtags estratégicas: [3 amplas] + [3 de nicho] + [1 própria da marca]
     - [Criar Post](/social/posts/create)
   - 💼 LinkedIn (para captação de parceiros corporativos)
     - Artigo mensal: case de impacto com dados e fotos
     - Post semanal: 'Por dentro da [organização]' — transparência gera confiança
     - Abordagem direta: mensagem personalizada para empresas do setor
     - [Prospectar Parceiros](/prospecting)
   - 📅 Calendário Editorial — Mês 1
     - Semana 1 — Awareness: apresente a causa, o problema e a solução
     - Semana 2 — Prova Social: depoimentos, números, histórias de impacto
     - Semana 3 — Engajamento: interação, desafios, enquetes, co-criação
     - Semana 4 — Conversão: CTA direto, urgência, oferta de participação

## 🌐 Captação de Leads e Novos Apoiadores
   - 🚀 Landing Page de Alta Conversão
     - Headline: [Crie uma headline impactante de 6-10 palavras baseada no objetivo]
     - Subheadline: [Complemento em 1 frase que reforça o benefício]
     - Elementos obrigatórios: foto real + número de impactados + formulário simples (nome + WhatsApp)
     - Prova social: logos de parceiros, depoimentos, selos de transparência
     - CTA acima do fold: botão verde com texto de ação '[Verbo] agora grátis'
     - [Criar Landing Page](/manager/landing_pages)
   - 🎟️ Campanhas de Captação Criativa
     - Rifa Solidária: prêmio atrativo + causa clara + divulgação em grupos WA
     - Desafio social: '#[HashtagDaOrganização] — marque 3 amigos que precisam saber disso'
     - Campanha de indicação: quem trouxer um apoiador ganha [benefício simbólico]
     - [Criar Rifa Online](/raffles)
   - 🤝 Parcerias Estratégicas
     - Empresas locais: patrocínio em troca de visibilidade (logo em materiais)
     - Influenciadores da causa: micro-influenciadores (5k-50k) têm mais engajamento
     - Outras ONGs: parceria de conteúdo e divulgação cruzada
     - Igrejas, associações, sindicatos: canais de distribuição com audiência qualificada
     - [Prospectar Parceiros](/prospecting)

## ✍️ Copywriting — Textos Prontos para Usar
   - 📣 Headline Principal da Campanha
     - Opção A (emocional): [Crie uma headline emocional de 8 palavras baseada no objetivo]
     - Opção B (racional): [Crie uma headline racional com dado numérico]
     - Opção C (urgência): [Crie uma headline com gatilho de urgência]
   - 📧 Copy para E-mail Marketing
     - Assunto: '[Dado surpreendente] — e você pode mudar isso'
     - Abertura: história de 3 linhas de um beneficiário real (ou fictício mas verossímil)
     - Meio: apresente o problema, a solução e os resultados já alcançados
     - CTA: botão + texto alternativo em link para quem não carrega imagens
   - 💬 Bio para Instagram
     - '[O que faz] | [Para quem] | [Resultado] | 👇 [Link da LP]'
   - 🔖 Tagline da Campanha
     - [Frase curta e memorável — máx. 6 palavras — que sintetize o propósito]

## 📊 Métricas e Indicadores de Sucesso
   - 🎯 KPIs Essenciais (primeiros 30 dias)
     - Alcance: [meta realista de pessoas alcançadas]
     - Leads gerados: [meta de novos contatos na LP]
     - Taxa de abertura WhatsApp: meta >40% (média do setor: 28%)
     - Engajamento Instagram: meta >5% (média do setor: 1,2%)
     - Conversões (doações/inscrições/voluntários): [meta baseada no orçamento]
   - 📈 Ferramentas de Monitoramento
     - WhatsApp Business: relatório nativo de mensagens enviadas/lidas
     - Instagram Insights: alcance, impressões, engajamento por post
     - Google Analytics (na LP): origem do tráfego e taxa de conversão
     - Planilha semanal: registre resultados toda sexta para ajustar na segunda
   - 🔄 Otimização Contínua
     - Teste A/B de headlines: troque a cada 2 semanas e compare abertura
     - Horário de publicação: teste manhã vs. noite por 2 semanas cada
     - Formato de conteúdo: compare engajamento de Reels vs. Carrossel vs. Foto

## ⚡ Plano de Ação — Próximas 72 Horas
   - ✅ Hoje (Dia 1)
     - Configure o Bot de atendimento com FAQ básico → /whatsapp/settings
     - Crie a Landing Page com headline e formulário → /manager/landing_pages
     - Publique 1 post de apresentação da campanha no Instagram
   - ✅ Amanhã (Dia 2)
     - Envie o primeiro disparo em massa para sua lista → /whatsapp/broadcast
     - Fixe a mensagem de boas-vindas nos grupos de WhatsApp
     - Entre em contato com 3 parceiros potenciais → /prospecting
   - ✅ Depois de Amanhã (Dia 3)
     - Grave 1 Reels de bastidor mostrando a equipe em ação
     - Publique o carrossel '5 motivos para apoiar [causa]'
     - Analise as métricas das primeiras ações e ajuste o que não funcionou

═══════════════════════════════════════
REGRAS ABSOLUTAS DE SAÍDA
═══════════════════════════════════════
1. Retorne APENAS o Markdown do mapa mental — sem introdução, sem explicação, sem bloco de código
2. Siga EXATAMENTE a estrutura acima, adaptando o conteúdo ao briefing fornecido
3. Substitua TODOS os placeholders entre colchetes [ ] por conteúdo real e específico para este briefing
4. Use emojis em todos os títulos de segundo e terceiro nível
5. Cada nó folha deve ter conteúdo concreto e acionável — nunca genérico
6. Copies devem estar prontas para copiar e usar — não deixe templates em branco
7. Links do Vivensi devem aparecer como subnós: [Nome do Módulo](/caminho)
8. Profundidade mínima: 4 níveis em pelo menos 3 ramificações principais
9. Volume mínimo: o mapa deve ter no mínimo 80 nós no total

Gere agora o mapa mental completo:
PROMPT;
    }

    private function tryDeepSeek(string $prompt): ?array
    {
        try {
            // Marketing AI usa PRO — gera plano estrategico de marketing com
            // Markmap (80+ nos com personas, KPIs, copywriting). Output longo e
            // estruturado se beneficia do raciocinio mais profundo.
            $ds  = new DeepSeekService();
            $res = $ds->chat([
                ['role' => 'system', 'content' => 'Você é um estrategista de marketing. Retorne APENAS Markdown estruturado para Markmap.js, sem blocos de código, sem explicações.'],
                ['role' => 'user',   'content' => $prompt],
            ], 'deepseek-v4-pro');
            $text = $res['choices'][0]['message']['content'] ?? null;
            if ($text) return ['markdown' => $this->cleanMarkdown(trim($text)), 'provider' => 'deepseek'];
        } catch (\Exception $e) {
            Log::error("MarketingAI DeepSeek: " . $e->getMessage());
        }
        return null;
    }

    // Remove blocos de código markdown que a IA às vezes insere indevidamente
    private function cleanMarkdown(string $text): string
    {
        $text = preg_replace('/^```(?:markdown)?\s*/i', '', $text);
        $text = preg_replace('/\s*```\s*$/', '', $text);
        return trim($text);
    }
}
