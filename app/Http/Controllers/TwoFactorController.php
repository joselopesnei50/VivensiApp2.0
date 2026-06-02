<?php

namespace App\Http\Controllers;

use App\Services\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TwoFactorController extends Controller
{
    public function __construct(private TotpService $totp) {}

    public function show()
    {
        $user = auth()->user();

        $hasUnconfirmedSecret = $user->two_factor_secret && is_null($user->two_factor_confirmed_at);

        return view('profile.two-factor', [
            'enabled'      => !is_null($user->two_factor_confirmed_at),
            'confirmed'    => !is_null($user->two_factor_confirmed_at),
            'setupSecret'  => $hasUnconfirmedSecret ? $user->two_factor_secret : null,
            'otpauthUri'   => $hasUnconfirmedSecret
                ? $this->totp->getOtpauthUri($user->email, $user->two_factor_secret)
                : null,
        ]);
    }

    public function enable(Request $request)
    {
        $user   = auth()->user();
        $secret = $this->totp->generateSecret();

        $user->update([
            'two_factor_secret'           => $secret,
            'two_factor_recovery_codes'   => $this->totp->generateRecoveryCodes(),
            'two_factor_confirmed_at'     => null,
        ]);

        return back()->with('success', 'Escaneie o QR Code com seu app autenticador.');
    }

    public function confirm(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $user = auth()->user();

        if (!$user->two_factor_secret) {
            return back()->with('error', 'Inicie o processo de configuração primeiro.');
        }

        if (!$this->totp->verify($user->two_factor_secret, $request->code)) {
            return back()->with('error', 'Código inválido. Tente novamente.');
        }

        $user->update(['two_factor_confirmed_at' => now()]);

        return back()->with('success', '2FA ativado com sucesso! Guarde seus códigos de recuperação.');
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => 'required']);

        $user = auth()->user();

        if (!Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Senha incorreta.');
        }

        $user->update([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ]);

        return back()->with('success', '2FA desativado com sucesso.');
    }
}
