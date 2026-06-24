<?php

namespace App\Http\Controllers\Mei;

use App\Http\Controllers\Controller;
use App\Services\MeiPanelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Vivensi — Módulo MEI.
 *
 * Endpoint do botão "Marcar DAS como pago" do widget no dashboard common.
 * Cria uma Transaction expense com description marker (idempotente via
 * MeiPanelService) e redireciona de volta com flash de sucesso.
 */
class MeiDasController extends Controller
{
    public function __construct(private MeiPanelService $service)
    {
    }

    public function marcarPago(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);
        abort_unless($user->tenant_id !== null, 403, 'Tenant ausente.');

        $this->service->marcarDasPago($user->tenant_id);

        return back()->with('success', 'DAS marcado como pago! 🎯 Sua mini-DRE foi atualizada.');
    }
}
