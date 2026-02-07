<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show($slug)
    {
        // Whitelist allowed pages to prevent LFI
        $allowedPages = ['termos', 'privacidade', 'sobre'];

        if (!in_array($slug, $allowedPages)) {
            abort(404);
        }

        // Map slug to view name
        $viewMap = [
            'termos' => 'legal.terms',
            'privacidade' => 'legal.privacy',
            'sobre' => 'pages.about',
        ];

        return view($viewMap[$slug]);
    }
}
