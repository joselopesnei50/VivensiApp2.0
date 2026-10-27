<?php

namespace App\Http\Controllers;

use App\Models\DestineIrPartner;
use App\Models\DestineIrSetting;
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
