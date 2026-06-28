<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BruceAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sandbox interno (super_admin) pra testar o Bot Vendedor "Bruno" sem
 * mexer em chats reais. Usa BruceAiService com role='sales_bot' e
 * tenant_id=0 (chave Redis isolada por usuário).
 */
class BrunoSandboxController extends Controller
{
    private const SANDBOX_TENANT_ID = 0;

    public function index(): View
    {
        return view('admin.bruno-sandbox');
    }

    public function chat(Request $request, BruceAiService $bruce): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $result = $bruce->chat(
            userMessage: $data['message'],
            tenantId: self::SANDBOX_TENANT_ID,
            role: 'sales_bot',
            userId: (int) auth()->id(),
        );

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'error'   => $result['error'],
            ], 422);
        }

        return response()->json([
            'success'   => true,
            'reply'     => $result['reply'],
            'tokens'    => $result['tokens'] ?? 0,
            'timestamp' => $result['timestamp'] ?? now()->toIso8601String(),
        ]);
    }

    public function clear(BruceAiService $bruce): JsonResponse
    {
        $bruce->clearHistory(self::SANDBOX_TENANT_ID, (int) auth()->id());

        return response()->json(['success' => true]);
    }
}
