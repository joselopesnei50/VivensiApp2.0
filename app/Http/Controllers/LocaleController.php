<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    private const SUPPORTED = ['pt_BR', 'en', 'es'];

    public function set(string $code)
    {
        if (!in_array($code, self::SUPPORTED, true)) {
            return back();
        }

        session(['locale' => $code]);

        if (auth()->check() && in_array('locale', auth()->user()->getFillable())) {
            auth()->user()->update(['locale' => $code]);
        }

        return back();
    }
}
