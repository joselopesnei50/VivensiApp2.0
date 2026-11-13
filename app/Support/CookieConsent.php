<?php

namespace App\Support;

/**
 * Cookies não essenciais (Google Analytics/Tag Manager e Meta Pixel) só carregam
 * depois que a pessoa clica em "Aceitar todos" no aviso de cookies (LGPD, art. 7º, I).
 */
class CookieConsent
{
    public const COOKIE = 'vivensi_cookie_consent';

    public static function allowsTracking(): bool
    {
        return request()->cookie(self::COOKIE) === 'accepted';
    }
}
