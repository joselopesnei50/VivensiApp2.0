<?php

namespace App\Http\Controllers;

use App\Models\EmailCampaign;
use App\Services\EmailCampaignAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class EmailCampaignAiController extends Controller
{
    public function __construct(
        private EmailCampaignAiService $ai,
    ) {}

    /**
     * F1 — Analise pre-envio. POST /email-campaigns/ai/analyze
     *
     * Body: subject, html_content, preheader (opcional)
     * Return: score (0-100), level, risks, suggestions, ai_notes
     */
    public function analyze(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->tenant_id) {
            return response()->json(['error' => 'Sessão sem tenant.'], 401);
        }

        $data = $request->validate([
            'subject'      => ['required', 'string', 'max:200'],
            'html_content' => ['required', 'string', 'max:200000'],
            'preheader'    => ['nullable', 'string', 'max:200'],
        ]);

        try {
            $result = $this->ai->analyzeBeforeSend($data, (int) $user->tenant_id);
            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error('EmailCampaignAiController.analyze exception', [
                'tenant_id' => $user->tenant_id,
                'error'     => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Falha ao analisar. Tente novamente.'], 503);
        }
    }

    /**
     * F2 — Insight pos-envio. GET /email-campaigns/{campaign}/ai/insight
     *
     * Return: insight, next_action, benchmark
     */
    public function insight(EmailCampaign $campaign): JsonResponse
    {
        $user = auth()->user();

        // Isolamento tenant — evita IDOR.
        if (!$user || (int) $campaign->tenant_id !== (int) $user->tenant_id) {
            return response()->json(['error' => 'Campanha não encontrada.'], 404);
        }

        if ($campaign->status !== 'sent') {
            return response()->json([
                'error' => 'Só é possível analisar campanhas já enviadas.',
            ], 422);
        }

        try {
            $result = $this->ai->analyzePerformance($campaign);
            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error('EmailCampaignAiController.insight exception', [
                'campaign_id' => $campaign->id,
                'error'       => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Falha ao gerar insight. Tente novamente.'], 503);
        }
    }
}
