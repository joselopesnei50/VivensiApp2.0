<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class CookieConsentController extends Controller
{
    /**
     * Aceitar cookies (LGPD)
     */
    public function accept(Request $request)
    {
        // Cookie válido por 1 ano (525600 minutos)
        $cookie = cookie('vivensi_cookie_consent', 'accepted', 525600);
        
        return redirect()->back()->cookie($cookie)->with('success', 'Preferências de cookies salvas!');
    }
    
    /**
     * Revogar consentimento de cookies
     */
    public function revoke(Request $request)
    {
        $cookie = cookie()->forget('vivensi_cookie_consent');
        
        return redirect()->back()->cookie($cookie)->with('info', 'Consentimento de cookies removido.');
    }
}
