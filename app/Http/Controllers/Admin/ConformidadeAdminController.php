<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegraAvaliacao;
use App\Models\Tenant;
use App\Services\ComplianceCalculationService;
use Illuminate\Http\Request;

class ConformidadeAdminController extends Controller
{
    public function __construct(private ComplianceCalculationService $service) {}

    public function index()
    {
        $regras = RegraAvaliacao::with('requisito')
            ->whereHas('requisito', fn($q) => $q->where('ativo', true))
            ->get()
            ->groupBy(fn($r) => $r->requisito?->eixo ?? 'sem_eixo');

        return view('admin.conformidade.requisitos', compact('regras'));
    }

    public function update(Request $request, int $regra)
    {
        $r = RegraAvaliacao::where('threshold_editavel_admin', true)->findOrFail($regra);

        $validated = $request->validate([
            'threshold' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $r->update(['threshold' => $validated['threshold']]);

        Tenant::where('subscription_status', 'active')
            ->pluck('id')
            ->each(fn($id) => $this->service->invalidarCache($id));

        return back()->with('success', "Threshold de {$r->requisito->codigo} atualizado para {$validated['threshold']}.");
    }
}
