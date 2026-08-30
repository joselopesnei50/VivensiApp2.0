<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    public function __construct()
    {
        // Auditoria 2026-08-29 P2 (media): fecha bypass de role em Campanhas.
        // Rota publica /c/{slug} (show) NAO exige auth.
        $this->middleware('can:manage-campaigns')->except(['show']);
    }

    public function index()
    {
        $campaigns = Campaign::where('tenant_id', auth()->user()->tenant_id)
                             ->orderBy('created_at', 'desc')
                             ->get();
        return view('ngo.campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        return view('ngo.campaigns.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'target_amount' => 'required|numeric|min:0',
            'video_url' => 'nullable|url'
        ]);

        $campaign = new Campaign($validated);
        $campaign->tenant_id = auth()->user()->tenant_id;
        $campaign->slug = Str::slug($request->title) . '-' . substr(md5(time()), 0, 5);
        $campaign->status = 'active';
        $campaign->save();

        return redirect('/ngo/campaigns')->with('success', 'Campanha criada! Link público: ' . url('/c/' . $campaign->slug));
    }

    public function show($slug)
    {
        // Usar withoutGlobalScopes pois é rota pública (sem Auth).
        // SEGURANÇA: filtrar apenas campanhas ativas para não expor rascunhos.
        $campaign = Campaign::withoutGlobalScopes()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        return view('public.campaign', compact('campaign'));
    }
}
