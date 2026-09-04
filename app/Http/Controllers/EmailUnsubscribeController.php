<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\EmailContact;
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

        return view('public.email.unsubscribe', [
            'tenant'   => $tenant,
            'email'    => $email,
            'token'    => $token,
            'affected' => $affected,
        ]);
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
