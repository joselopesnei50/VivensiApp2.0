<?php

namespace App\Services;

use App\Models\SocialAccount;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaSocialAuthService
{
    private string $appId;
    private string $appSecret;
    private string $redirectUri;
    private string $graphVersion = 'v20.0';

    public function __construct()
    {
        $this->appId      = SystemSetting::getValue('meta_social_app_id', '');
        $this->appSecret  = SystemSetting::getValue('meta_social_app_secret', '');
        $this->redirectUri = route('social.facebook.callback');
    }

    public function isConfigured(): bool
    {
        return !empty($this->appId) && !empty($this->appSecret);
    }

    /** URL para redirecionar o cliente ao OAuth da Meta */
    public function getAuthUrl(): string
    {
        $scopes = implode(',', [
            'public_profile',
            'pages_show_list',
            'pages_read_engagement',
            'pages_manage_posts',
        ]);

        return "https://www.facebook.com/dialog/oauth?" . http_build_query([
            'client_id'     => $this->appId,
            'redirect_uri'  => $this->redirectUri,
            'scope'         => $scopes,
            'response_type' => 'code',
            'state'         => csrf_token(),
        ]);
    }

    /** Troca o code pelo user access token */
    public function exchangeCodeForToken(string $code): ?string
    {
        $response = Http::get("https://graph.facebook.com/{$this->graphVersion}/oauth/access_token", [
            'client_id'     => $this->appId,
            'client_secret' => $this->appSecret,
            'redirect_uri'  => $this->redirectUri,
            'code'          => $code,
        ]);

        if (!$response->successful()) {
            Log::error('Meta OAuth token exchange failed', ['body' => $response->body()]);
            return null;
        }

        return $response->json('access_token');
    }

    /** Troca short-lived token por long-lived (60 dias) */
    public function getLongLivedToken(string $shortToken): ?string
    {
        $response = Http::get("https://graph.facebook.com/{$this->graphVersion}/oauth/access_token", [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => $this->appId,
            'client_secret'     => $this->appSecret,
            'fb_exchange_token' => $shortToken,
        ]);

        return $response->successful() ? $response->json('access_token') : null;
    }

    /** Busca todas as páginas do usuário e seus tokens de página */
    public function getUserPages(string $userToken): array
    {
        $response = Http::get("https://graph.facebook.com/{$this->graphVersion}/me/accounts", [
            'access_token' => $userToken,
            'fields'       => 'id,name,picture,access_token,instagram_business_account',
        ]);

        if (!$response->successful()) {
            Log::error('Meta get pages failed', ['body' => $response->body()]);
            return [];
        }

        return $response->json('data', []);
    }

    /** Busca dados do Instagram Business vinculado à página */
    public function getInstagramAccount(string $pageId, string $pageToken): ?array
    {
        $response = Http::get("https://graph.facebook.com/{$this->graphVersion}/{$pageId}", [
            'fields'       => 'instagram_business_account',
            'access_token' => $pageToken,
        ]);

        if (!$response->successful()) return null;

        $igId = $response->json('instagram_business_account.id');
        if (!$igId) return null;

        // Busca username do Instagram
        $igResponse = Http::get("https://graph.facebook.com/{$this->graphVersion}/{$igId}", [
            'fields'       => 'id,username,profile_picture_url',
            'access_token' => $pageToken,
        ]);

        return $igResponse->successful() ? $igResponse->json() : null;
    }

    /** Salva ou atualiza as contas conectadas para o tenant */
    public function saveAccounts(array $pages, int $tenantId): int
    {
        $saved = 0;
        foreach ($pages as $page) {
            $pageToken = $page['access_token'] ?? null;
            if (!$pageToken) continue;

            // Busca Instagram vinculado
            $ig = $this->getInstagramAccount($page['id'], $pageToken);

            SocialAccount::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenantId, 'page_id' => $page['id']],
                [
                    'platform'              => 'facebook',
                    'page_name'             => $page['name'],
                    'page_picture'          => $page['picture']['data']['url'] ?? null,
                    'access_token'          => $pageToken,
                    'token_expires_at'      => now()->addDays(60),
                    'instagram_business_id' => $ig['id'] ?? null,
                    'instagram_username'    => $ig['username'] ?? null,
                    'is_active'             => true,
                ]
            );
            $saved++;
        }
        return $saved;
    }
}
