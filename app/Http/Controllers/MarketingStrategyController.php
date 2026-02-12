<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MarketingAIService;
use App\Services\UnsplashService;

class MarketingStrategyController extends Controller
{
    protected $aiService;
    protected $unsplashService;

    public function __construct(MarketingAIService $aiService, UnsplashService $unsplashService)
    {
        $this->aiService = $aiService;
        $this->unsplashService = $unsplashService;
    }

    public function index()
    {
        return view('admin.marketing.index');
    }

    public function generate(Request $request)
    {
        $request->validate([
            'goal' => 'required|string|max:500',
            'audience' => 'required|string|max:200',
        ]);

        // 1. Generate Strategy with AI
        $strategy = $this->aiService->generateStrategy($request->goal, $request->audience);

        if (!$strategy) {
            return back()->with('error', 'Não foi possível gerar a estratégia. Tente novamente.');
        }

        // 2. Fetch Images for Social Media Posts
        if (isset($strategy['social']) && is_array($strategy['social'])) {
            foreach ($strategy['social'] as &$post) {
                if (isset($post['image_keyword'])) {
                    $images = $this->unsplashService->searchPhotos($post['image_keyword'], 3);
                    $post['images'] = $images;
                }
            }
        }

        return view('admin.marketing.strategy_result', compact('strategy'));
    }
}
