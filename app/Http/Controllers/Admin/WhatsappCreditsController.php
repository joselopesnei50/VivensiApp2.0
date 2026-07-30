<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\WhatsappCreditBalance;
use App\Models\WhatsappCreditTransaction;
use App\Services\WhatsAppService\WhatsappCreditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Painel super_admin — gestão de saldos pré-pagos WhatsApp (Fase 1 MVP).
 *
 * Escopo:
 *  - Listar tenants com saldo (pré-pago ativo ou não)
 *  - Ativar/desativar pré-pago por tenant
 *  - Lançar recarga manual (topup) após confirmar PIX/pagamento fora
 *
 * Fase 2 (backlog): recarga self-service via AbacatePay (webhook cria
 * a transaction automática, e este controller vira só relatório).
 */
class WhatsappCreditsController extends Controller
{
    public function index(Request $request): View
    {
        // Todos os tenants + saldo (LEFT JOIN pra listar até quem ainda não
        // tem WhatsappCreditBalance). Ordenação: pré-pagos ativos primeiro,
        // depois quem tem saldo, depois demais.
        $tenants = Tenant::orderBy('name')->get()->map(function ($tenant) {
            $bal = WhatsappCreditBalance::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->first();
            $tenant->credit_balance = $bal;
            return $tenant;
        });

        // Estatísticas agregadas
        $stats = [
            'prepaid_count'   => WhatsappCreditBalance::whereNotNull('prepaid_enabled_at')->count(),
            'total_balance'   => (int) WhatsappCreditBalance::whereNotNull('prepaid_enabled_at')->sum('balance_brl_micros'),
            'low_balance'     => WhatsappCreditBalance::whereNotNull('prepaid_enabled_at')
                                    ->where('balance_brl_micros', '<', 10_000_000) // < R$ 10
                                    ->count(),
        ];

        return view('admin.whatsapp.creditos.index', [
            'tenants'         => $tenants,
            'stats'           => $stats,
            'suggestedTopups' => (array) config('whatsapp_pricing.prepaid.suggested_topups', [50, 100, 250, 500]),
        ]);
    }

    /** Ativa ou desativa pré-pago pra um tenant. */
    public function toggle(Tenant $tenant, Request $request): RedirectResponse
    {
        $service = app(WhatsappCreditService::class);
        $balance = $service->getBalance($tenant->id);

        if ($balance->isPrepaidEnabled()) {
            $service->disablePrepaid($tenant->id, auth()->id());
            $msg = "Pré-pago desativado para {$tenant->name}. Saldo preservado.";
        } else {
            $service->enablePrepaid($tenant->id, auth()->id());
            $msg = "Pré-pago ATIVADO para {$tenant->name}. Envios via Cloud API agora consomem saldo.";
        }

        return back()->with('success', $msg);
    }

    /** Recarga manual — super_admin lança valor após confirmar PIX fora. */
    public function topup(Tenant $tenant, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount_brl'  => 'required|numeric|min:1|max:100000',
            'description' => 'required|string|max:400',
        ]);

        $amountMicros = (int) round($data['amount_brl'] * 1_000_000);
        $minMicros    = (int) config('whatsapp_pricing.prepaid.min_topup_brl_micros', 50_000_000);

        if ($amountMicros < $minMicros) {
            $minBrl = number_format($minMicros / 1_000_000, 2, ',', '.');
            return back()->with('error', "Valor mínimo de recarga é R$ {$minBrl}.");
        }

        $service = app(WhatsappCreditService::class);

        // Ativa pré-pago automaticamente se ainda não estava — recarga
        // implica que o cliente aceitou o modelo.
        $service->enablePrepaid($tenant->id, auth()->id());

        $tx = $service->credit(
            $tenant->id,
            $amountMicros,
            $data['description'],
            auth()->id(),
            WhatsappCreditTransaction::TYPE_TOPUP
        );

        $newBrl = number_format($tx->balance_after_brl_micros / 1_000_000, 2, ',', '.');
        return back()->with('success', "Recarga aplicada. Novo saldo de {$tenant->name}: R$ {$newBrl}.");
    }
}
