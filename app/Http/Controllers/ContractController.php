<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Notification;
use App\Models\User;
use App\Services\BrevoService;
use App\Services\ZApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContractController extends Controller
{
    private function publicSignTtlDays(): ?int
    {
        $ttl = config('contracts.public_sign_ttl_days', 30);
        return $ttl === null ? null : (int) $ttl;
    }

    private function computeDocumentHash(Contract $contract): string
    {
        // Canonical payload to detect tampering. Keep stable and deterministic.
        $payload = json_encode([
            'tenant_id' => (int) $contract->tenant_id,
            'title' => (string) $contract->title,
            'content' => (string) $contract->content,
            'signer_name' => (string) ($contract->signer_name ?? ''),
            'signer_email' => (string) ($contract->signer_email ?? ''),
            'signer_address' => (string) ($contract->signer_address ?? ''),
            'signer_phone' => (string) ($contract->signer_phone ?? ''),
            'signer_cpf' => (string) ($contract->signer_cpf ?? ''),
            'signer_rg' => (string) ($contract->signer_rg ?? ''),
            'token' => (string) $contract->token,
            'created_at' => $contract->created_at ? $contract->created_at->toISOString() : null,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash_hmac('sha256', (string) $payload, (string) config('app.key'));
    }

    private function normalizeWhatsappPhone(?string $input): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $input);
        if (!$digits) {
            return null;
        }

        // Common BR inputs: 11 digits (DDD + number). Prefix country code 55.
        if (strlen($digits) === 11) {
            return '55' . $digits;
        }

        // If already includes country code (e.g., 55XXXXXXXXXXX), keep.
        if (strlen($digits) >= 12 && strlen($digits) <= 14) {
            return $digits;
        }

        // Anything else: best-effort return digits (Z-API may reject).
        return $digits;
    }

    private function buildSimpleEmailHtml(string $title, string $contentHtml, ?string $buttonText = null, ?string $buttonUrl = null): string
    {
        $button = '';
        if ($buttonText && $buttonUrl) {
            $button = "<p style=\"margin:24px 0 0 0;\"><a href=\"{$buttonUrl}\" style=\"background:#4f46e5;color:#fff;padding:12px 18px;border-radius:10px;text-decoration:none;font-weight:700;display:inline-block;\">{$buttonText}</a></p>";
        }

        return "<!doctype html><html lang=\"pt-br\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"><title>{$title}</title></head><body style=\"margin:0;padding:0;background:#f8fafc;font-family:Inter,Segoe UI,Roboto,Arial,sans-serif;color:#0f172a;\"><div style=\"max-width:640px;margin:0 auto;padding:28px;\"><div style=\"background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 10px 25px rgba(15,23,42,.06);\"><div style=\"padding:22px 22px;background:linear-gradient(135deg,#4f46e5,#3730a3);color:#fff;\"><div style=\"font-weight:800;font-size:18px;\">Vivensi</div></div><div style=\"padding:22px;\"><h2 style=\"margin:0 0 12px 0;font-size:18px;\">{$title}</h2><div style=\"color:#334155;font-size:14px;line-height:1.6;\">{$contentHtml}</div>{$button}</div><div style=\"padding:16px 22px;background:#f1f5f9;color:#64748b;font-size:12px;border-top:1px solid #e2e8f0;\">Mensagem automática. Não responda.</div></div></div></body></html>";
    }

    private function notifyExternalOnSigned(Contract $contract, string $publicLink, ?string $code): void
    {
        // Email notifications
        if (config('contracts.notify_email_on_signed', false)) {
            try {
                $brevo = app(BrevoService::class);

                // 1) Signer confirmation (if we have an email)
                if (!empty($contract->signer_email)) {
                    $subject = "Confirmação de assinatura: {$contract->title}";
                    $body = "<p>Olá, <strong>{$contract->signer_name}</strong>.</p>"
                        . "<p>Sua assinatura foi registrada com sucesso no documento <strong>“{$contract->title}”</strong>.</p>"
                        . ($code ? "<p><strong>Código de autenticidade:</strong> {$code}</p>" : "")
                        . "<p>Você pode visualizar/guardar uma cópia no link abaixo.</p>";
                    $html = $this->buildSimpleEmailHtml('Contrato assinado com sucesso', $body, 'Abrir contrato', $publicLink);
                    $brevo->sendEmail($contract->signer_email, $contract->signer_name, $subject, $html, $contract->tenant_id);
                }

                // 2) Internal team (active users with manager/ngo role)
                $teamUsers = User::query()
                    ->where('tenant_id', $contract->tenant_id)
                    ->where('status', 'active')
                    ->whereIn('role', ['ngo', 'manager'])
                    ->whereNotNull('email')
                    ->get(['name', 'email']);

                foreach ($teamUsers as $u) {
                    $subject = "Contrato assinado: {$contract->title}";
                    $body = "<p>Olá, <strong>{$u->name}</strong>.</p>"
                        . "<p>O contrato <strong>“{$contract->title}”</strong> foi assinado por <strong>{$contract->signer_name}</strong>.</p>"
                        . ($code ? "<p><strong>Código de autenticidade:</strong> {$code}</p>" : "")
                        . "<p>Clique abaixo para visualizar o documento assinado.</p>";
                    $html = $this->buildSimpleEmailHtml('Contrato assinado', $body, 'Abrir contrato', $publicLink);
                    $brevo->sendEmail($u->email, $u->name, $subject, $html, $contract->tenant_id);
                }
            } catch (\Throwable $e) {
                Log::warning('Contract signed email notification failed: ' . $e->getMessage());
            }
        }

        // WhatsApp notifications (Signer)
        if (config('contracts.notify_whatsapp_on_signed', false)) {
            try {
                $to = $this->normalizeWhatsappPhone($contract->signer_phone);
                if ($to) {
                    $msg = "✅ Assinatura confirmada!\n\n"
                        . "Documento: {$contract->title}\n"
                        . "Signatário: {$contract->signer_name}\n"
                        . ($code ? "Código: {$code}\n" : "")
                        . "Link: {$publicLink}\n\n"
                        . "Vivensi";

                    $zapi = new ZApiService($contract->tenant_id);
                    $zapi->sendMessage($to, $msg);
                }
            } catch (\Throwable $e) {
                Log::warning('Contract signed WhatsApp notification failed: ' . $e->getMessage());
            }
        }
    }

    public function index()
    {
        $contracts = Contract::where('tenant_id', auth()->user()->tenant_id)
                             ->orderBy('created_at', 'desc')
                             ->get();
        return view('ngo.contracts.index', compact('contracts'));
    }

    public function create()
    {
        return view('ngo.contracts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'signer_name' => 'required|string|max:255',
            'signer_email' => 'nullable|email|max:255',
            'signer_address' => 'nullable|string|max:255',
            'signer_phone' => 'nullable|string|max:32',
            'signer_cpf' => 'nullable|string|max:32',
            'signer_rg' => 'nullable|string|max:32',
            'content' => 'required',
        ]);

        $contract = new Contract();
        $contract->tenant_id = auth()->user()->tenant_id;
        $contract->title = $validated['title'];
        $contract->signer_name = $validated['signer_name'];
        $contract->signer_email = $validated['signer_email'] ?? null;
        $contract->signer_address = $validated['signer_address'] ?? null;
        $contract->signer_phone = $validated['signer_phone'] ?? null;
        $contract->signer_cpf = $validated['signer_cpf'] ?? null;
        $contract->signer_rg = $validated['signer_rg'] ?? null;
        $contract->content = $validated['content'];
        $contract->status = 'sent';
        $contract->token = Str::random(64);
        $ttlDays = $this->publicSignTtlDays();
        $contract->public_sign_expires_at = $ttlDays === null ? null : now()->addDays($ttlDays);
        $contract->save();

        // Store a deterministic hash for authenticity checks.
        if (!$contract->document_hash) {
            $contract->document_hash = $this->computeDocumentHash($contract);
            $contract->saveQuietly();
        }

        return redirect('/ngo/contracts')->with('success', 'Contrato gerado com sucesso!');
    }

    public function showPublic($token)
    {
        // Public route: ignore tenant scopes (even if someone is logged in).
        $contract = Contract::withoutGlobalScopes()->where('token', $token)->firstOrFail();

        if (!$contract->document_hash) {
            $contract->document_hash = $this->computeDocumentHash($contract);
            $contract->saveQuietly();
        }

        if (!$contract->public_viewed_at) {
            $contract->public_viewed_at = now();
            $contract->saveQuietly();
        }

        if ($contract->public_sign_expires_at && $contract->public_sign_expires_at->isPast() && $contract->status !== 'signed') {
            return response()->view('public.contract_expired', compact('contract'), 410);
        }

        $tenant = DB::table('tenants')->where('id', $contract->tenant_id)->first();

        return view('public.contract_sign', compact('contract', 'tenant'));
    }

    public function sign(Request $request, $token)
    {
        // Public route: ignore tenant scopes (even if someone is logged in).
        $contract = Contract::withoutGlobalScopes()->where('token', $token)->firstOrFail();

        if ($contract->status === 'signed') {
            return back()->with('success', 'Este contrato já foi assinado.');
        }

        if ($contract->public_sign_expires_at && $contract->public_sign_expires_at->isPast()) {
            return response()->view('public.contract_expired', compact('contract'), 410);
        }
        
        $request->validate([
            'signature' => 'required|string|max:1000000',
        ]);

        $raw = (string) $request->input('signature');
        if (!preg_match('~^data:image/png;base64,([A-Za-z0-9+/=]+)$~', $raw, $m)) {
            return back()->withErrors(['signature' => 'Assinatura inválida. Tente assinar novamente.']);
        }

        $b64 = $m[1];
        // Basic anti-empty signature guard (client can bypass, but avoids most accidental submits).
        if (strlen($b64) < 2000) {
            return back()->withErrors(['signature' => 'Assinatura muito curta. Assine novamente antes de confirmar.']);
        }

        $bytes = base64_decode($b64, true);
        if ($bytes === false) {
            return back()->withErrors(['signature' => 'Assinatura inválida. Tente assinar novamente.']);
        }

        // Keep DB + page lightweight.
        if (strlen($bytes) > 300_000) {
            return back()->withErrors(['signature' => 'Assinatura muito grande. Assine novamente com traços menores.']);
        }

        if (!$contract->document_hash) {
            $contract->document_hash = $this->computeDocumentHash($contract);
        }

        $contract->signature_image = 'data:image/png;base64,' . base64_encode($bytes);
        $contract->status = 'signed';
        $contract->signer_ip = $request->ip() ?? 'Desconhecido';
        $contract->signer_user_agent = substr((string) $request->userAgent(), 0, 255) ?: null;
        $contract->signature_hash = hash_hmac('sha256', $bytes . '|' . $contract->document_hash, (string) config('app.key'));
        $contract->signed_at = now();
        $contract->save();

        // Notify tenant users (panel bell).
        try {
            $recipients = User::query()
                ->where('tenant_id', $contract->tenant_id)
                ->where('status', 'active')
                ->where('role', '!=', 'super_admin')
                ->pluck('id');

            $publicLink = route('public.contract', $contract->token);
            $code = $contract->document_hash ? strtoupper(substr($contract->document_hash, 0, 16)) : null;

            foreach ($recipients as $userId) {
                Notification::create([
                    'user_id' => $userId,
                    'title' => 'Contrato assinado',
                    'message' => trim(
                        "“{$contract->title}” foi assinado por {$contract->signer_name}."
                        . ($code ? " Código: {$code}." : '')
                    ),
                    'type' => 'success',
                    'link' => $publicLink,
                ]);
            }
        } catch (\Throwable $e) {
            // Keep public signing flow stable even if notifications fail.
        }

        // External channels (email / WhatsApp), optional & fail-safe.
        try {
            $publicLink = route('public.contract', $contract->token);
            $code = $contract->document_hash ? strtoupper(substr($contract->document_hash, 0, 16)) : null;
            $this->notifyExternalOnSigned($contract, $publicLink, $code);
        } catch (\Throwable $e) {
            // Keep stable.
        }
        
        return back()->with('success', 'Contrato assinado com sucesso!');
    }

    public function regenerateLink($id)
    {
        $contract = Contract::where('tenant_id', auth()->user()->tenant_id)->where('id', $id)->firstOrFail();

        if ($contract->status === 'signed') {
            return back()->with('error', 'Contrato já assinado. Não é possível renovar o link.');
        }

        $contract->token = Str::random(64);
        $ttlDays = $this->publicSignTtlDays();
        $contract->public_sign_expires_at = $ttlDays === null ? null : now()->addDays($ttlDays);
        $contract->public_viewed_at = null;
        $contract->document_hash = $this->computeDocumentHash($contract);
        $contract->save();

        return back()->with('success', 'Link público do contrato renovado. O link anterior foi revogado.');
    }

    public function revokeLink($id)
    {
        $contract = Contract::where('tenant_id', auth()->user()->tenant_id)->where('id', $id)->firstOrFail();

        if ($contract->status === 'signed') {
            return back()->with('error', 'Contrato já assinado. Não é possível revogar o link.');
        }

        if (!$contract->token) {
            $contract->token = Str::random(64);
        }
        $contract->public_sign_expires_at = now()->subSecond();
        $contract->save();

        return back()->with('success', 'Link público do contrato revogado com sucesso.');
    }
}
