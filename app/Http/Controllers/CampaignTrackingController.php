<?php

namespace App\Http\Controllers;

use App\Models\EmailCampaignRecipient;
use App\Services\CampaignMail\CampaignRenderer;
use App\Services\EmailUnsubscribeTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Rotas públicas de rastreamento das campanhas próprias.
 * Nunca quebram a experiência de quem lê o e-mail: token inválido ainda
 * devolve o pixel / redireciona para a home, sem vazar nada.
 */
class CampaignTrackingController extends Controller
{
    // GIF transparente 1x1
    private const PIXEL = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    public function open(string $token)
    {
        $token = preg_replace('~\.gif$~', '', $token);

        EmailCampaignRecipient::where('token', $token)->update([
            'open_count' => DB::raw('open_count + 1'),
            'opened_at'  => DB::raw('COALESCE(opened_at, ' . $this->nowSql() . ')'),
        ]);

        return response(base64_decode(self::PIXEL), 200, [
            'Content-Type'  => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
        ]);
    }

    public function click(string $token, Request $request)
    {
        $url = CampaignRenderer::decodeUrl((string) $request->query('u', ''));
        $sig = (string) $request->query('s', '');

        if (!$url || !preg_match('~^https?://~i', $url) || !hash_equals(CampaignRenderer::sign($token, $url), $sig)) {
            return redirect('/');
        }

        EmailCampaignRecipient::where('token', $token)->update([
            'click_count' => DB::raw('click_count + 1'),
            'clicked_at'  => DB::raw('COALESCE(clicked_at, ' . $this->nowSql() . ')'),
            // Clique implica abertura (imagens bloqueadas no cliente de e-mail)
            'opened_at'   => DB::raw('COALESCE(opened_at, ' . $this->nowSql() . ')'),
        ]);

        return redirect()->away($url);
    }

    /**
     * Descadastro em 1 clique (RFC 8058) — chamado pelo Gmail/Outlook via
     * POST a partir do header List-Unsubscribe. Sem CSRF, sem página.
     */
    public function oneClickUnsubscribe(string $token, Request $request, EmailUnsubscribeTokenService $svc)
    {
        $parsed = $svc->parse($token);
        if (!$parsed) {
            return response('', 400);
        }

        app(EmailUnsubscribeController::class)->applyUnsubscribe($parsed['email'], $parsed['tenant_id'], $request);
        self::markRecipientUnsubscribed($request->query('r'), $parsed['email']);

        return response('', 200);
    }

    /** Marca o destinatário (métrica de descadastro) quando o link veio de uma campanha própria. */
    public static function markRecipientUnsubscribed(?string $recipientToken, string $email): void
    {
        if (!$recipientToken) return;

        EmailCampaignRecipient::where('token', $recipientToken)
            ->where('email', mb_strtolower($email))
            ->whereNull('unsubscribed_at')
            ->update(['unsubscribed_at' => now()]);
    }

    private function nowSql(): string
    {
        return DB::getPdo()->quote(now()->toDateTimeString());
    }
}
