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

        return view('client.invoices.index', [
            'plan'          => $plan,
            'isCourtesy'    => $isCourtesy,
            'unpaid'        => $unpaid,
            'paid'          => $paid,
            'pixKey'        => $pixKey,
            'pixKeyType'    => $pixKeyType,
            'pixHolderName' => $pixHolderName,
        ]);
    }
}
