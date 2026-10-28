<?php

namespace App\Http\Controllers;

use App\Models\DestineIrMeetingSignup;
use App\Models\DestineIrPartner;
use App\Models\DestineIrSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DestineIrPublicController extends Controller
{
    public function show(Request $request, ?string $slug = null)
    {
        $settings = DestineIrSetting::current();

        if (!$settings->is_published) {
            abort(404);
        }

        $partner = null;
        if ($slug) {
            $partner = DestineIrPartner::where('slug', $slug)->where('is_active', true)->first();
            if (!$partner) {
                abort(404);
            }
        }

        $this->trackVisit($request, $settings, $partner);

        $partners = $settings->show_partners_wall
            ? DestineIrPartner::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
            : collect();

        return response()
            ->view('destine-ir.show', compact('settings', 'partner', 'partners'))
            ->header('X-Robots-Tag', 'index, follow')
            ->header('Permissions-Policy', 'interest-cohort=()');
    }

    public function storeMeetingSignup(Request $request, ?string $slug = null): RedirectResponse
    {
        $settings = DestineIrSetting::current();

        if (!$settings->is_published || !$settings->show_meeting_cta) {
            abort(404);
        }

        // Honeypot: campo invisível. Bot preenche, humano nao. Silenciosamente aceita.
        if (trim((string) $request->input('website')) !== '') {
            return $this->signupRedirect($slug, $request);
        }

        $data = $request->validate([
            'name'         => 'required|string|max:160',
            'email'        => 'required|email|max:160',
            'phone'        => 'nullable|string|max:40',
            'lgpd_consent' => 'accepted',
        ], [
            'name.required'         => 'Diga seu nome.',
            'email.required'        => 'Preciso do seu e-mail pra avisar o dia.',
            'email.email'           => 'E-mail inválido.',
            'lgpd_consent.accepted' => 'Pra te enviar o dia preciso do seu consentimento.',
        ]);

        $partner = null;
        if ($slug) {
            $partner = DestineIrPartner::where('slug', $slug)->where('is_active', true)->first();
        }

        DestineIrMeetingSignup::create([
            'name'         => $data['name'],
            'email'        => strtolower(trim($data['email'])),
            'phone'        => $data['phone'] ?? null,
            'partner_id'   => $partner?->id,
            'lgpd_consent' => true,
            'ip_hash'      => hash('sha256', ($request->ip() ?? '') . '|' . config('app.key')),
        ]);

        return $this->signupRedirect($slug, $request)->with('meeting_signup_ok', true);
    }

    private function signupRedirect(?string $slug, Request $request): RedirectResponse
    {
        $route = $slug
            ? route('destine-ir.partner', ['slug' => $slug])
            : route('destine-ir.show');
        return redirect($route . '#reuniao');
    }

    private function trackVisit(Request $request, DestineIrSetting $settings, ?DestineIrPartner $partner): void
    {
        $sessionKey = 'destine_ir_visit_' . ($partner?->id ?? 'global');
        if ($request->session()->has($sessionKey)) {
            return;
        }
        $request->session()->put($sessionKey, now()->timestamp);

        $ipHash = hash('sha256', ($request->ip() ?? '') . '|' . config('app.key'));

        try {
            \DB::table('destine_ir_visits')->insert([
                'partner_id'  => $partner?->id,
                'ip_hash'     => $ipHash,
                'user_agent'  => substr((string) $request->userAgent(), 0, 500),
                'referer'     => substr((string) $request->header('referer'), 0, 500),
                'utm_source'  => substr((string) $request->query('utm_source'), 0, 120),
                'created_at'  => now(),
            ]);

            $settings->increment('total_visits');
            if ($partner) {
                $partner->forceFill(['last_visit_at' => now()])->save();
                $partner->increment('visits_count');
            }
        } catch (\Throwable $e) {
            // Silencioso: página não pode cair por causa de métrica.
            \Log::warning('destine-ir visit tracking falhou', ['msg' => $e->getMessage()]);
        }
    }
}
