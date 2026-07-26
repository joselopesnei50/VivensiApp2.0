<?php

namespace App\Http\Controllers;

use App\Services\EmailAiTemplateQuotaService;
use App\Services\EmailTemplateAiGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailAiGenerationController extends Controller
{
    public function generate(
        Request $request,
        EmailAiTemplateQuotaService $quota,
        EmailTemplateAiGeneratorService $generator
    ): JsonResponse {
        $validated = $request->validate([
            'brief'       => ['required', 'string', 'min:15', 'max:2000'],
            'tone'        => ['nullable', 'string', 'max:80'],
            'audience'    => ['nullable', 'string', 'max:150'],
            'cta'         => ['nullable', 'string', 'max:120'],
            'sender_name' => ['nullable', 'string', 'max:100'],
            'brand_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $user     = $request->user();
        $tenantId = (int) $user->tenant_id;
        $limit    = $quota->limitForTenant($tenantId);

        if (!$quota->hasQuota($tenantId)) {
            return response()->json([
                'error'      => "Cota mensal de {$limit} gerações de template esgotada. O contador reinicia no dia 1 do próximo mês.",
                'error_code' => 'monthly_quota_exceeded',
                'remaining'  => 0,
                'limit'      => $limit,
            ], 429);
        }

        $result = $generator->generate($validated, $tenantId);

        if (isset($result['error'])) {
            Log::info('EmailAiGeneration: erro upstream', [
                'tenant_id'  => $tenantId,
                'user_id'    => $user->id,
                'error_code' => $result['error_code'] ?? 'unknown',
            ]);
            return response()->json([
                'error'      => $result['error'],
                'error_code' => $result['error_code'] ?? 'ai_error',
                'remaining'  => $quota->remaining($tenantId),
                'limit'      => $limit,
            ], 503);
        }

        // So consome cota mensal se a geracao foi bem-sucedida.
        // (A cota diaria de IA em AiCallQuotaService ja foi debitada pelo DeepSeekService.)
        $quota->consume($tenantId, $user->id);

        Log::info('EmailAiGeneration: template gerado', [
            'tenant_id' => $tenantId,
            'user_id'   => $user->id,
            'used'      => $quota->currentUsage($tenantId),
            'limit'     => $limit,
        ]);

        return response()->json([
            'subject'   => $result['subject'],
            'preheader' => $result['preheader'],
            'html'      => $result['html'],
            'remaining' => $quota->remaining($tenantId),
            'limit'     => $limit,
        ]);
    }

    public function quota(Request $request, EmailAiTemplateQuotaService $quota): JsonResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        return response()->json([
            'used'      => $quota->currentUsage($tenantId),
            'remaining' => $quota->remaining($tenantId),
            'limit'     => $quota->limitForTenant($tenantId),
        ]);
    }
}
