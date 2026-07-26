<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMarketingPlan;
use App\Models\MarketingPlan;
use App\Models\Project;
use App\Services\MarketingAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketingStrategyController extends Controller
{
    public function index()
    {
        $plans = MarketingPlan::orderByDesc('created_at')->paginate(10);
        return view('marketing.index', compact('plans'));
    }

    public function about()
    {
        return view('marketing.about');
    }

    public function create()
    {
        $projects = Project::where('tenant_id', Auth::user()->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name', 'status']);

        return view('marketing.create', compact('projects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'objective'           => 'required|string|max:1000',
            'target_audience'     => 'required|string|max:500',
            'scope'               => 'required|in:online,online_offline',
            'competitor_links'    => 'nullable|string|max:1000',
            'budget_range'        => 'nullable|string|max:100',
            'tone'                => 'required|in:professional,friendly,inspirational,urgent',
            'extra_info'          => 'nullable|string|max:1000',
            'project_id'          => 'nullable|integer|exists:projects,id',
        ]);

        $plan = MarketingPlan::create([
            'tenant_id'           => Auth::user()->tenant_id,
            'user_id'             => Auth::id(),
            'project_id'          => $validated['project_id'] ?? null,
            'title'               => mb_substr($validated['objective'], 0, 80),
            'objective'           => $validated['objective'],
            'target_audience'     => $validated['target_audience'],
            'scope'               => $validated['scope'],
            'competitor_links'    => $validated['competitor_links'] ?? null,
            'budget_range'        => $validated['budget_range'] ?? null,
            'tone'                => $validated['tone'],
            'has_whatsapp_groups' => $request->boolean('has_whatsapp_groups'),
            'extra_info'          => $validated['extra_info'] ?? null,
            'status'              => 'pending',
        ]);

        ProcessMarketingPlan::dispatch($plan->id)->onQueue('default');

        return redirect()->route('marketing.show', $plan->id)
            ->with('success', 'Plano criado! A IA está gerando seu mapa estratégico...');
    }

    public function show(MarketingPlan $marketing)
    {
        return view('marketing.show', compact('marketing'));
    }

    public function status(MarketingPlan $marketing)
    {
        return response()->json([
            'status'          => $marketing->status,
            'mindmap_data'    => $marketing->mindmap_data,
            'ai_provider'     => $marketing->ai_provider,
            'guide_status'    => $marketing->guide_status,
            'execution_guide' => $marketing->execution_guide,
        ]);
    }

    /**
     * Regenera o Guia do Bruce sob demanda (síncrono, ~30-60s).
     * Útil quando a 1ª tentativa falhou ou quando o plano foi editado.
     */
    public function regenerateGuide(MarketingPlan $marketing, MarketingAIService $ai)
    {
        if ($marketing->status !== 'done') {
            return response()->json([
                'success' => false,
                'message' => 'O plano ainda não foi gerado. Aguarde a IA finalizar antes de regenerar o guia.',
            ], 422);
        }

        $marketing->update(['guide_status' => 'pending']);

        $guide = $ai->generateExecutionGuide($marketing);

        if ($guide) {
            $marketing->update([
                'execution_guide'    => $guide,
                'guide_status'       => 'ready',
                'guide_generated_at' => now(),
            ]);
            return response()->json(['success' => true, 'guide' => $guide]);
        }

        $marketing->update([
            'guide_status'       => 'failed',
            'guide_generated_at' => now(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Não foi possível gerar o guia agora. Tente novamente em alguns instantes.',
        ], 500);
    }

    public function destroy(MarketingPlan $marketing)
    {
        $marketing->delete();
        return redirect()->route('marketing.index')->with('success', 'Plano removido.');
    }
}
