<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show($slug)
    {
        // 1. Try to find in Database (CMS CMS content)
        $page = \App\Models\Page::where('slug', $slug)->first();

        if ($page) {
            return view('pages.show', compact('page'));
        }

        // 2. Whitelist allowed pages for legacy fallback
        $allowedPages = ['termos', 'privacidade', 'sobre'];

        if (!in_array($slug, $allowedPages)) {
            abort(404);
        }

        // 3. Map slug to legacy view name
        $viewMap = [
            'termos' => 'legal.terms',
            'privacidade' => 'legal.privacy',
            'sobre' => 'pages.about',
        ];

        if (isset($viewMap[$slug]) && view()->exists($viewMap[$slug])) {
            return view($viewMap[$slug]);
        }

        abort(404);
    }
}
