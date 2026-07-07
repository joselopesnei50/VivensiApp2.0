<?php

namespace App\Http\Controllers;

use App\Models\FacebookDataDeletionRequest;
use App\Models\SocialAccount;
use App\Models\SystemSetting;
use App\Services\MetaSocialAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        Gate::authorize('disconnect', $account);

        $account->update(['is_active' => false, 'access_token' => '']);

        return back()->with('success', "Conta \"{$account->page_name}\" desconectada.");
    }

    /** Remove permanentemente */
    public function destroy(SocialAccount $account)
    {
        Gate::authorize('delete', $account);
        $account->delete();
        return back()->with('success', 'Conta removida.');
    }

    /**
     * Deauthorize Callback — chamado pela Meta quando o usuário remove o app
     * pelas configurações do Facebook. Requer resposta 200 (o corpo é ignorado).
     * Não conseguimos mapear o Facebook user_id para nossas SocialAccounts (que
     * usam page_id), então apenas registramos o evento para auditoria.
     */
    public function handleDeauthorize(Request $request): Response
    {
        $data = $this->auth->parseSignedRequest($request->input('signed_request'));

        if ($data === null) {
            Log::warning('Facebook deauthorize webhook: signed_request inválido', [
                'ip' => $request->ip(),
            ]);
            return response('', 200);
        }

        Log::info('Facebook deauthorize webhook recebido', [
            'facebook_user_id' => $data['user_id'] ?? null,
            'issued_at'        => $data['issued_at'] ?? null,
        ]);

        return response('', 200);
    }

    /**
     * Data Deletion Callback — chamado pela Meta quando o usuário pede exclusão
     * dos dados pelas configurações do Facebook. Precisa retornar JSON com uma URL
     * pública onde o usuário possa acompanhar o status, e um confirmation_code.
     * Persistimos a solicitação em facebook_data_deletion_requests para auditoria
     * e processamento manual pelo DPO dentro do prazo LGPD.
     */
    public function handleDataDeletion(Request $request): JsonResponse
    {
        $data = $this->auth->parseSignedRequest($request->input('signed_request'));
        $code = Str::random(40);

        if ($data === null) {
            Log::warning('Facebook data deletion webhook: signed_request inválido', [
                'ip' => $request->ip(),
            ]);
            FacebookDataDeletionRequest::create([
                'facebook_user_id' => null,
                'confirmation_code' => $code,
                'status'           => 'rejected',
                'notes'            => 'signed_request inválido; recebido de ' . $request->ip(),
            ]);
        } else {
            FacebookDataDeletionRequest::create([
                'facebook_user_id' => $data['user_id'] ?? null,
                'confirmation_code' => $code,
                'status'           => 'received',
                'notes'            => 'Solicitação via Facebook em ' . now()->toIso8601String(),
            ]);
        }

        return response()->json([
            'url'               => route('lgpd.public-status', ['code' => $code]),
            'confirmation_code' => $code,
        ]);
    }
}
