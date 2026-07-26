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

        $user = $request->user();
        if (!$user || !$user->tenant_id) {
            return response()->json([
                'error'      => 'Sessão inválida ou usuário sem tenant vinculado.',
                'error_code' => 'invalid_session',
            ], 401);
        }

        $tenantId = (int) $user->tenant_id;

        // Guarda global: qualquer exception nao prevista vira 503 amigavel com
        // contexto no log, evitando 500 HTML pro usuario. Cobre falhas de DB
        // (ex.: tabela ausente), timeout DeepSeek, JSON malformado, etc.
        try {
            $limit = $quota->limitForTenant($tenantId);

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
        } catch (\Throwable $e) {
            Log::error('EmailAiGeneration: exception nao prevista', [
                'tenant_id' => $tenantId,
                'user_id'   => $user->id,
                'class'     => get_class($e),
                'message'   => $e->getMessage(),
                'file'      => $e->getFile() . ':' . $e->getLine(),
                'trace'     => collect($e->getTrace())->take(6)->map(fn($t) => ($t['file'] ?? '?') . ':' . ($t['line'] ?? '?'))->toArray(),
            ]);
            return response()->json([
                'error'      => 'Ocorreu uma falha inesperada ao gerar o template. Nossa equipe foi notificada.',
                'error_code' => 'internal_error',
            ], 503);
        }
    }

    public function quota(Request $request, EmailAiTemplateQuotaService $quota): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->tenant_id) {
            return response()->json(['error' => 'Sessão inválida.'], 401);
        }
        $tenantId = (int) $user->tenant_id;

        try {
            return response()->json([
                'used'      => $quota->currentUsage($tenantId),
                'remaining' => $quota->remaining($tenantId),
                'limit'     => $quota->limitForTenant($tenantId),
            ]);
        } catch (\Throwable $e) {
            Log::error('EmailAiGeneration.quota: exception', [
                'tenant_id' => $tenantId,
                'class'     => get_class($e),
                'message'   => $e->getMessage(),
                'file'      => $e->getFile() . ':' . $e->getLine(),
            ]);
            return response()->json(['error' => 'Não foi possível ler a cota agora.'], 503);
        }
    }
}
