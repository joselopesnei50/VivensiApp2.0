<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show($slug, \Illuminate\Http\Request $request)
    {
        // Termos e Privacidade têm uma versão só (resources/views/legal), alinhada ao
        // Contrato de Adesão. Cópias antigas cadastradas no admin não valem mais.
        $legal = ['termos' => 'legal.terms', 'privacidade' => 'legal.privacy'];
        if (isset($legal[$slug])) {
            return redirect()->route($legal[$slug], [], 301);
        }

        // 1. Try to find in Database (CMS content)
        $page = \App\Models\Page::where('slug', $slug)->first();

        if ($page) {
            \App\Models\PageView::record($page, $request);
            return view('pages.show', compact('page'));
        }

        // 2. Whitelist allowed pages for legacy fallback
        $allowedPages = ['termos', 'privacidade', 'sobre'];

        if (!in_array($slug, $allowedPages)) {
            abort(404);
        }

        // 3. Map slug to legacy view (only where view actually exists)
        $viewMap = [
            'termos'      => 'legal.terms',
            'privacidade' => 'legal.privacy',
        ];

        if (isset($viewMap[$slug]) && view()->exists($viewMap[$slug])) {
            return view($viewMap[$slug]);
        }

        abort(404);
    }
}
