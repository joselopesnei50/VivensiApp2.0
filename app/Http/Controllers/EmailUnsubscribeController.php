<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\EmailContact;
use App\Models\EmailSuppression;
use App\Models\NgoDonor;
use App\Models\Tenant;
use App\Services\EmailUnsubscribeTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Landing publica de descadastro de campanhas de e-mail (LGPD art. 18).
 * Um clique = descadastra o e-mail de TODAS as listas do tenant + zera
 * ngo_donors.email_marketing_opt_in. Sem auth. Token HMAC deterministico.
 */
class EmailUnsubscribeController extends Controller
{
    public function show(string $token, Request $request, EmailUnsubscribeTokenService $svc)
    {
        $parsed = $svc->parse($token);
        if (!$parsed) {
            return response()->view('public.email.unsubscribe-invalid', [], 400);
        }

        $tenant = Tenant::withoutGlobalScopes()->find($parsed['tenant_id']);
        if (!$tenant) {
            return response()->view('public.email.unsubscribe-invalid', [], 404);
        }

        $email    = $parsed['email'];
        $tenantId = $parsed['tenant_id'];

        $affected = $this->applyUnsubscribe($email, $tenantId, $request);

        // Link veio de campanha com envio próprio → conta o descadastro na métrica dela
        CampaignTrackingController::markRecipientUnsubscribed($request->query('r'), $email);

        return view('public.email.unsubscribe', [
            'tenant'   => $tenant,
            'email'    => $email,
            'token'    => $token,
            'affected' => $affected,
        ]);
    }

    /**
     * Descadastra o e-mail em todas as listas do tenant + opt-out de doador,
     * com auditoria. Usado pela landing (GET) e pelo 1-clique (POST, RFC 8058).
     *
     * @return array{email_contacts: int, donors: int}
     */
    public function applyUnsubscribe(string $email, int $tenantId, Request $request): array
    {
        $affected = DB::transaction(function () use ($email, $tenantId) {
            $emailContacts = EmailContact::where('tenant_id', $tenantId)
                ->where('email', $email)
                ->where('status', '!=', EmailContact::STATUS_UNSUBSCRIBED)
                ->update([
                    'status'          => EmailContact::STATUS_UNSUBSCRIBED,
                    'unsubscribed_at' => now(),
                ]);

            $donors = NgoDonor::where('tenant_id', $tenantId)
                ->where('email', $email)
                ->where('email_marketing_opt_in', true)
                ->update(['email_marketing_opt_in' => false]);

            // Bloqueio global das campanhas com envio próprio: cobre quem não
            // está em nenhuma lista salva (avulsos, prospecção) — sem isso,
            // descadastrar não teria efeito pra esses contatos.
            EmailSuppression::suppress($email, EmailSuppression::REASON_UNSUBSCRIBE, 'Descadastro pelo link do e-mail');

            return ['email_contacts' => $emailContacts, 'donors' => $donors];
        });

        try {
            AuditLog::create([
                'tenant_id'  => $tenantId,
                'event'      => 'email.unsubscribed',
                'new_values' => [
                    'email'                 => $email,
                    'email_contacts_marked' => $affected['email_contacts'],
                    'donors_optout'         => $affected['donors'],
                ],
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'url'        => $request->fullUrl(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('AuditLog email.unsubscribed falhou: ' . $e->getMessage());
        }

        Log::info('Email unsubscribe processado', [
            'tenant_id'      => $tenantId,
            'email_contacts' => $affected['email_contacts'],
            'donors_optout'  => $affected['donors'],
        ]);

        return $affected;
    }

    public function reactivate(string $token, Request $request, EmailUnsubscribeTokenService $svc)
    {
        $parsed = $svc->parse($token);
        if (!$parsed) {
            return response()->view('public.email.unsubscribe-invalid', [], 400);
        }

        $tenant = Tenant::withoutGlobalScopes()->find($parsed['tenant_id']);
        if (!$tenant) {
            return response()->view('public.email.unsubscribe-invalid', [], 404);
        }

        $email    = $parsed['email'];
        $tenantId = $parsed['tenant_id'];

        DB::transaction(function () use ($email, $tenantId) {
            EmailContact::where('tenant_id', $tenantId)
                ->where('email', $email)
                ->where('status', EmailContact::STATUS_UNSUBSCRIBED)
                ->update([
                    'status'          => EmailContact::STATUS_ACTIVE,
                    'unsubscribed_at' => null,
                ]);

            // Reativação só desfaz o bloqueio por descadastro — nunca bounce/spam
            EmailSuppression::where('email', $email)
                ->where('reason', EmailSuppression::REASON_UNSUBSCRIBE)
                ->delete();
        });

        try {
            AuditLog::create([
                'tenant_id'  => $tenantId,
                'event'      => 'email.resubscribed',
                'new_values' => ['email' => $email],
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'url'        => $request->fullUrl(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('AuditLog email.resubscribed falhou: ' . $e->getMessage());
        }

        return view('public.email.resubscribe', [
            'tenant' => $tenant,
            'email'  => $email,
        ]);
    }
}
