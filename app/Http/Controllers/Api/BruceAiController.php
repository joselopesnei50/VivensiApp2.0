<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BruceAiService;
use Illuminate\Http\Request;

class BruceAiController extends Controller
{
    public function __construct(private BruceAiService $bruce) {}

    public function chat(Request $request)
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $user     = auth()->user();
        $response = $this->bruce->chat(
            $request->input('message'),
            $user->tenant_id,
            $user->role
        );

        if (isset($response['error'])) {
            return response()->json(['error' => $response['error']], 503);
        }

        return response()->json($response);
    }

    public function clearHistory()
    {
        $this->bruce->clearHistory(auth()->user()->tenant_id);
        return response()->json(['success' => true]);
    }

    public function insight()
    {
        $user    = auth()->user();
        $insight = $this->bruce->dailyInsight($user->tenant_id, $user->role);
        return response()->json(['insight' => $insight]);
    }
}
