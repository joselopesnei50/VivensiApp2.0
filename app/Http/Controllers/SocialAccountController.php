<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Models\SystemSetting;
use App\Services\MetaSocialAuthService;
use Illuminate\Http\Request;

class SocialAccountController extends Controller
{
    public function __construct(private MetaSocialAuthService $auth) {}

    /** Lista contas conectadas do tenant */
    public function index()
    {
        $accounts  = SocialAccount::orderByDesc('created_at')->get();
        $configured = $this->auth->isConfigured();
        return view('social.accounts', compact('accounts', 'configured'));
    }

    /** Redireciona para o OAuth da Meta */
    public function connect()
    {
        if (!$this->auth->isConfigured()) {
            return back()->with('error', 'As credenciais do Meta App ainda não foram configuradas pelo administrador.');
        }
        return redirect($this->auth->getAuthUrl());
    }

    /** Callback OAuth — Meta redireciona aqui após autorização */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('social.accounts')->with('error', 'Autorização cancelada ou negada pelo Facebook.');
        }

        $code  = $request->get('code');
        $tenantId = auth()->user()->tenant_id;

        // Troca code por token
        $shortToken = $this->auth->exchangeCodeForToken($code);
        if (!$shortToken) {
            return redirect()->route('social.accounts')->with('error', 'Falha ao obter token do Facebook. Tente novamente.');
        }

        // Troca por long-lived token (60 dias)
        $longToken = $this->auth->getLongLivedToken($shortToken) ?? $shortToken;

        // Busca páginas do usuário
        $pages = $this->auth->getUserPages($longToken);
        if (empty($pages)) {
            return redirect()->route('social.accounts')->with('error', 'Nenhuma página do Facebook encontrada. Certifique-se de ter páginas administradas.');
        }

        $saved = $this->auth->saveAccounts($pages, $tenantId);

        return redirect()->route('social.accounts')
            ->with('success', "{$saved} conta(s) conectada(s) com sucesso!");
    }

    /** Desconecta (desativa) uma conta */
    public function disconnect(SocialAccount $account)
    {
        // Garante pertencimento ao tenant
        abort_unless($account->tenant_id === auth()->user()->tenant_id, 403);

        $account->update(['is_active' => false, 'access_token' => '']);

        return back()->with('success', "Conta \"{$account->page_name}\" desconectada.");
    }

    /** Remove permanentemente */
    public function destroy(SocialAccount $account)
    {
        abort_unless($account->tenant_id === auth()->user()->tenant_id, 403);
        $account->delete();
        return back()->with('success', 'Conta removida.');
    }
}
