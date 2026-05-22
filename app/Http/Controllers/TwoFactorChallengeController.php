<?php

namespace App\Http\Controllers;

use App\Services\TotpService;
use Illuminate\Http\Request;

class TwoFactorChallengeController extends Controller
{
    public function __construct(private TotpService $totp) {}

    public function show()
    {
        if (!auth()->user()?->hasTwoFactorEnabled()) {
            return redirect()->intended('/dashboard');
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $user = auth()->user();
        $code = preg_replace('/\s/', '', $request->code);

        // Try TOTP code
        if ($user->two_factor_secret && $this->totp->verify($user->two_factor_secret, $code)) {
            $request->session()->put('2fa_verified', true);
            return redirect()->to($request->session()->pull('2fa_redirect', '/dashboard'));
        }

        // Try recovery code
        $codes = $user->two_factor_recovery_codes ?? [];
        $match = array_search(strtoupper($code), array_map('strtoupper', $codes));

        if ($match !== false) {
            // Consume the recovery code
            unset($codes[$match]);
            $user->update(['two_factor_recovery_codes' => array_values($codes)]);
            $request->session()->put('2fa_verified', true);
            return redirect()->to($request->session()->pull('2fa_redirect', '/dashboard'))
                ->with('warning', 'Código de recuperação usado. Restam ' . count($codes) . ' códigos.');
        }

        return back()->with('error', 'Código inválido. Tente novamente.')->withInput();
    }
}
