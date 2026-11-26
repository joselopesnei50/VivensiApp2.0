<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingAddon;
use App\Models\Tenant;
use App\Models\TenantAddon;
use App\Services\Billing\AddonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin → Adicionais: catálogo (preço e produto da AbacatePay) e quem
 * contratou o quê. O preço aqui TEM que ser igual ao do produto avulso na
 * AbacatePay — o checkout soma os produtos de lá e a fatura usa este valor.
 */
class AddonController extends Controller
{
    public function index(): View
    {
        $catalog = BillingAddon::orderBy('id')->get();
        $contracts = TenantAddon::withoutGlobalScope('tenant')
            ->where('quantity', '>', 0)
            ->with('addon')
            ->orderBy('tenant_id')
            ->get();
        $tenants = Tenant::with('plan')->whereIn('id', $contracts->pluck('tenant_id')->unique())->get()->keyBy('id');
        $service = app(AddonService::class);
        $free = $tenants->map(fn ($t) => $service->isFreeAccount($t));

        // Receita: só contas que geram fatura (cortesia sai sem cobrança).
        $monthlyCents = $contracts->sum(fn ($c) => ($free[$c->tenant_id] ?? false) ? 0 : $c->quantity * ($c->addon?->unitCents() ?? 0));

        return view('admin.addons.index', compact('catalog', 'contracts', 'tenants', 'monthlyCents', 'free'));
    }

    public function updateCatalog(Request $request, BillingAddon $addon): RedirectResponse
    {
        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:120'],
            'description'           => ['nullable', 'string', 'max:300'],
            'unit_price'            => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'unit_amount'           => ['required', 'integer', 'min:1', 'max:100000'],
            'abacatepay_product_id' => ['nullable', 'string', 'max:255', 'regex:/^prod_[A-Za-z0-9]+$/'],
            'max_quantity'          => ['required', 'integer', 'min:1', 'max:1000'],
        ], ['abacatepay_product_id.regex' => 'O ID do produto começa com prod_.']);
        $data['is_active'] = $request->boolean('is_active');

        $addon->update($data);
        return back()->with('success', "Adicional \"{$addon->name}\" atualizado. Confira se o preço é o mesmo do produto na AbacatePay.");
    }

    /** Ajuste manual da quantidade de uma organização (suporte). */
    public function setTenantQuantity(Request $request, Tenant $tenant, BillingAddon $addon, AddonService $service): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:' . $addon->max_quantity]]);
        $error = $service->setQuantity($tenant, $addon, (int) $data['quantity'], auth()->user(), byPlatformAdmin: true);
        if ($error) {
            return back()->with('error', $error);
        }
        $free = $service->isFreeAccount($tenant) ? ' Conta sem fatura (cortesia): sem cobrança.' : ' Entra na próxima fatura.';
        return back()->with('success', "{$tenant->name}: {$addon->name} agora com {$data['quantity']}." . ($data['quantity'] > 0 ? $free : ''));
    }
}
