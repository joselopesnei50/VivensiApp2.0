<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\Billing\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Painel super_admin pra gestão consolidada de invoices de todos tenants.
 * Fluxos:
 *  - Listar com filtros (status, tenant, período)
 *  - Marcar manual como paga (PIX manual OU ajuste admin)
 *  - Cancelar invoice
 */
class InvoicesController extends Controller
{
    public function index(Request $request): View
    {
        $status   = $request->query('status');   // open|paid|canceled|overdue
        $tenantId = (int) $request->query('tenant_id', 0);
        $from     = $request->query('from');
        $to       = $request->query('to');

        $q = Invoice::withoutGlobalScopes()->with(['tenant:id,name', 'plan:id,name']);

        if ($status && in_array($status, [Invoice::STATUS_OPEN, Invoice::STATUS_PAID, Invoice::STATUS_CANCELED, Invoice::STATUS_OVERDUE], true)) {
            $q->where('status', $status);
        }
        if ($tenantId > 0) {
            $q->where('tenant_id', $tenantId);
        }
        if ($from) {
            $q->whereDate('due_date', '>=', $from);
        }
        if ($to) {
            $q->whereDate('due_date', '<=', $to);
        }

        $invoices = $q->latest('id')->paginate(30)->appends($request->query());

        // Stats agregados
        $stats = [
            'total_open'    => Invoice::withoutGlobalScopes()->where('status', Invoice::STATUS_OPEN)->count(),
            'total_overdue' => Invoice::withoutGlobalScopes()->where('status', Invoice::STATUS_OVERDUE)->count(),
            'sum_open_brl'  => Invoice::withoutGlobalScopes()->whereIn('status', [Invoice::STATUS_OPEN, Invoice::STATUS_OVERDUE])->sum('amount_cents') / 100,
            'sum_paid_month_brl' => Invoice::withoutGlobalScopes()->where('status', Invoice::STATUS_PAID)
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount_cents') / 100,
        ];

        $tenants = Tenant::orderBy('name')->pluck('name', 'id');

        return view('admin.invoices.index', compact('invoices', 'stats', 'tenants', 'status', 'tenantId', 'from', 'to'));
    }

    public function markPaid(Invoice $invoice, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'paid_via' => 'required|in:pix_manual,manual_admin',
        ]);

        app(InvoiceService::class)->markAsPaid(
            $invoice,
            $data['paid_via'],
            auth()->id()
        );

        return back()->with('success', "Fatura #{$invoice->id} marcada como paga.");
    }

    public function cancel(Invoice $invoice): RedirectResponse
    {
        if ($invoice->status === Invoice::STATUS_PAID) {
            return back()->with('error', 'Fatura já paga não pode ser cancelada.');
        }
        $invoice->status = Invoice::STATUS_CANCELED;
        $invoice->save();
        return back()->with('success', "Fatura #{$invoice->id} cancelada.");
    }
}
