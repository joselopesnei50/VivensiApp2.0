<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    private const SUPPORTED = ['pt_BR', 'en', 'es'];
    private const DEFAULT   = 'pt_BR';

    public function handle(Request $request, Closure $next)
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);
        \Carbon\Carbon::setLocale($locale);
        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        if ($session = session('locale')) {
            return $this->validate($session);
        }
        if (auth()->check() && ($userLocale = auth()->user()->locale ?? null)) {
            return $this->validate($userLocale);
        }
        $browserLang = substr($request->header('Accept-Language', ''), 0, 2);
        return ['pt' => 'pt_BR', 'en' => 'en', 'es' => 'es'][$browserLang] ?? self::DEFAULT;
    }

    private function validate(string $locale): string
    {
        return in_array($locale, self::SUPPORTED, true) ? $locale : self::DEFAULT;
    }
}
