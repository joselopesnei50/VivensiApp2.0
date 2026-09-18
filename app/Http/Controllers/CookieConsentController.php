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
     * Dispensa o banner sem consentimento explicito. Grava cookie curto (30d)
     * pra nao mostrar o banner de novo a cada page load — LGPD permite o user
     * "adiar" a decisao. Depois de 30d o banner volta.
     */
    public function dismiss(Request $request)
    {
        $cookie = cookie('vivensi_cookie_consent', 'dismissed', 60 * 24 * 30);

        return redirect()->back()->cookie($cookie);
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
