<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMarketingPlan;
use App\Models\MarketingPlan;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketingStrategyController extends Controller
{
    public function index()
    {
        $plans = MarketingPlan::orderByDesc('created_at')->paginate(10);
        return view('marketing.index', compact('plans'));
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
            'status'       => $marketing->status,
            'mindmap_data' => $marketing->mindmap_data,
            'ai_provider'  => $marketing->ai_provider,
        ]);
    }

    public function destroy(MarketingPlan $marketing)
    {
        $marketing->delete();
        return redirect()->route('marketing.index')->with('success', 'Plano removido.');
    }
}
