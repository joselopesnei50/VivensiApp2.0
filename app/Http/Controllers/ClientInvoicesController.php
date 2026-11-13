<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\SystemSetting;
use Illuminate\View\View;

/**
 * Dashboard de faturas do cliente (tenant scope aplicado por BelongsToTenant).
 * Se plano do tenant é cortesia, mostra banner verde em vez das tabelas.
 */
class ClientInvoicesController extends Controller
{
    public function index(): View
    {
        $tenant = auth()->user()->tenant;
        $plan   = $tenant?->plan;
        $isCourtesy = (bool) ($plan?->is_courtesy ?? false);

        $unpaid = collect();
        $paid   = collect();

        if (!$isCourtesy) {
            // Confere na AbacatePay as faturas em aberto com cobrança gerada
            // (no máx. 1x a cada 15s por organização): pagou → libera na hora.
            if ($tenant && \Illuminate\Support\Facades\Cache::add("invoices.sync.{$tenant->id}", 1, 15)) {
                foreach (Invoice::withoutGlobalScopes()->where('tenant_id', $tenant->id)->unpaid()->whereNotNull('abacatepay_charge_id')->limit(3)->get() as $inv) {
                    try {
                        app(\App\Services\Billing\InvoiceService::class)->syncFromGateway($inv);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('Faturas: falha ao conferir na AbacatePay', ['invoice_id' => $inv->id, 'error' => $e->getMessage()]);
                    }
                }
            }

            // Tenant scope aplicado pelo trait BelongsToTenant em Invoice.
            $unpaid = Invoice::unpaid()
                ->orderBy('due_date')
                ->get();

            $paid = Invoice::paid()
                ->orderByDesc('paid_at')
                ->limit(24) // 2 anos de histórico já bastam pro cliente
                ->get();
        }

        // Chave PIX do Vivensi (para exibir se cliente escolher pagar via PIX manual).
        $pixKey        = SystemSetting::getValue('vivensi_pix_key', '');
        $pixKeyType    = SystemSetting::getValue('vivensi_pix_key_type', 'CNPJ');
        $pixHolderName = SystemSetting::getValue('vivensi_pix_holder_name', 'Vivensi');

        $cancellation = app(\App\Services\Billing\SubscriptionCancellation::class);

        return view('client.invoices.index', [
            'tenant'        => $tenant,
            'canCancel'     => $tenant && !$isCourtesy && $tenant->subscription_status === 'active' && !$tenant->cancel_requested_at && $this->isAccountAdmin(),
            'refundEligible'=> $tenant && !$isCourtesy && $cancellation->refundEligible($tenant),
            'paidUntil'     => ($tenant && !$isCourtesy) ? $cancellation->paidUntil($tenant) : null,
            'plan'          => $plan,
            'isCourtesy'    => $isCourtesy,
            'unpaid'        => $unpaid,
            'paid'          => $paid,
            'pixKey'        => $pixKey,
            'pixKeyType'    => $pixKeyType,
            'pixHolderName' => $pixHolderName,
        ]);
    }

    /**
     * POST /minha-conta/faturas/{invoice}/pagar — gera um link de pagamento
     * novo na hora (o anterior pode ter expirado) e leva para a AbacatePay.
     */
    public function pay(int $invoice): \Illuminate\Http\RedirectResponse
    {
        // Filtro explícito pela organização do usuário (não depende do escopo global).
        $inv = Invoice::withoutGlobalScopes()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($invoice);
        if (!$inv->isPayable()) {
            return redirect()->route('client.invoices.index')->with('success', 'Esta fatura já está paga.');
        }
        $url = app(\App\Services\Billing\InvoiceService::class)->freshPaymentUrl($inv);
        if (!$url) {
            return redirect()->route('client.invoices.index')
                ->with('error', 'Não foi possível gerar o link de pagamento agora. Tente de novo em instantes ou use o PIX manual.');
        }
        return redirect()->away($url);
    }

    /** POST /minha-conta/assinatura/cancelar */
    public function cancel(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        abort_unless($this->isAccountAdmin(), 403, 'Só o administrador da conta pode cancelar a assinatura.');
        $data = $request->validate([
            'reason'  => ['nullable', 'string', 'max:120'],
            'details' => ['nullable', 'string', 'max:370'],
            'confirm' => ['accepted'],
        ], ['confirm.accepted' => 'Marque a confirmação para cancelar.']);

        $tenant = auth()->user()->tenant;
        abort_unless($tenant, 403);
        if ($tenant->plan?->is_courtesy || $tenant->subscription_status !== 'active') {
            return back()->with('error', 'Não há assinatura paga ativa para cancelar.');
        }
        if ($tenant->cancel_requested_at) {
            return back()->with('error', 'O cancelamento já foi pedido.');
        }

        $reason = trim(implode(' — ', array_filter([$data['reason'] ?? null, $data['details'] ?? null])));
        $res = app(\App\Services\Billing\SubscriptionCancellation::class)->cancel($tenant, auth()->user(), $reason ?: null);

        if ($res['refund']) {
            // Acesso encerrado agora: sai do painel com a confirmação.
            \Illuminate\Support\Facades\Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('success', 'Assinatura cancelada. Como foi em até 7 dias da contratação, vamos devolver o valor integral — você recebe a confirmação por e-mail.');
        }

        return redirect()->route('client.invoices.index')->with('success',
            'Assinatura cancelada. Não haverá novas cobranças e o painel continua liberado até ' . $res['access_until']->format('d/m/Y') . '.');
    }

    /** POST /minha-conta/assinatura/manter — desiste do cancelamento. */
    public function keep(): \Illuminate\Http\RedirectResponse
    {
        abort_unless($this->isAccountAdmin(), 403);
        $ok = app(\App\Services\Billing\SubscriptionCancellation::class)->keep(auth()->user()->tenant);
        return redirect()->route('client.invoices.index')->with($ok ? 'success' : 'error',
            $ok ? 'Pronto! A assinatura continua ativa e as próximas faturas voltam a ser geradas normalmente.' : 'Não há cancelamento agendado.');
    }

    /** Dono da conta: administrador da ONG (ou o titular nas contas que não são ONG). */
    private function isAccountAdmin(): bool
    {
        $u = auth()->user();
        return $u && in_array($u->role, ['ngo', 'manager', 'common', 'super_admin'], true) && !\App\Support\TeamAccess::appliesTo($u);
    }
}
