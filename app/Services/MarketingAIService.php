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

        $result = $this->tryGemini($prompt) ?? $this->tryDeepSeek($prompt);

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
        $scope    = $plan->scope === 'online_offline' ? 'online e presencial (offline)' : 'apenas online';
        $whatsapp = $plan->has_whatsapp_groups ? 'Sim, possui grupos de WhatsApp ativos.' : 'Não possui grupos de WhatsApp.';
        $competitors = $plan->competitor_links ? "Concorrentes/referências: {$plan->competitor_links}" : '';
        $budget   = $plan->budget_range ? "Orçamento: {$plan->budget_range}" : '';
        $extra    = $plan->extra_info   ? "Informações adicionais: {$plan->extra_info}" : '';

        $tones = [
            'professional'  => 'profissional e confiante',
            'friendly'      => 'amigável e próximo',
            'inspirational' => 'inspirador e motivador',
            'urgent'        => 'urgente e persuasivo',
        ];
        $tone = $tones[$plan->tone] ?? 'profissional';

        return "Você é um estrategista de marketing digital especialista em ONGs e gestão de projetos sociais.

Com base no briefing abaixo, crie um Plano Estratégico de Marketing Completo em formato Markdown compatível com Markmap.js.

BRIEFING:
- Objetivo: {$plan->objective}
- Público-alvo: {$plan->target_audience}
- Abrangência: {$scope}
- Tom de voz: {$tone}
- Grupos de WhatsApp: {$whatsapp}
{$competitors}
{$budget}
{$extra}

MÓDULOS DO SISTEMA VIVENSI (use para direcionar ações):
- Disparo WhatsApp → /whatsapp/broadcast
- Bot WhatsApp → /whatsapp/settings
- Landing Page → /ngo/landing_pages
- Rifa Online → /raffles
- Criar Post → /social/posts/create
- Prospectar Parceiros → /prospecting

REGRAS DE SAÍDA:
1. Retorne APENAS Markdown para Markmap.js (sem explicações extras, sem blocos de código)
2. Nó raiz = título da campanha
3. Pelo menos 5 frentes: Captação, Engajamento, Conversão, Retenção, Mensuração
4. Cada ação deve ter: copy sugerida e módulo do Vivensi a usar
5. Use emojis nos títulos dos nós
6. Para ações no Vivensi: adicione subnó com formato: [Nome do Módulo](/link)

Gere o Markmap Markdown agora:";
    }

    private function tryGemini(string $prompt): ?array
    {
        $apiKey = SystemSetting::getValue('gemini_api_key');
        if (!$apiKey) return null;

        foreach (['models/gemini-2.5-flash', 'models/gemini-2.0-flash-001', 'models/gemini-2.0-flash-lite'] as $model) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/{$model}:generateContent?key={$apiKey}";
                $response = Http::timeout(45)->post($url, [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 4096],
                ]);

                if ($response->successful()) {
                    $text = $response->json('candidates.0.content.parts.0.text');
                    if ($text) return ['markdown' => trim($text), 'provider' => 'gemini'];
                }
            } catch (\Exception $e) {
                Log::warning("MarketingAI Gemini {$model}: " . $e->getMessage());
            }
        }
        return null;
    }

    private function tryDeepSeek(string $prompt): ?array
    {
        try {
            $ds  = new DeepSeekService();
            $res = $ds->chat([
                ['role' => 'system', 'content' => 'Retorne APENAS Markdown estruturado para Markmap.js.'],
                ['role' => 'user',   'content' => $prompt],
            ]);
            $text = $res['choices'][0]['message']['content'] ?? null;
            if ($text) return ['markdown' => trim($text), 'provider' => 'deepseek'];
        } catch (\Exception $e) {
            Log::error("MarketingAI DeepSeek: " . $e->getMessage());
        }
        return null;
    }
}
