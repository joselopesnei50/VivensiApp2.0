<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NgoDonor;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class DonorPortalController extends Controller
{
    public function show($token)
    {
        // Find donor matching the token, ignore tenant scope since this is public
        $donor = NgoDonor::withoutGlobalScopes()->where('portal_token_bidx', hash_hmac('sha256', $token, config('app.key')))->firstOrFail();

        // Get their donation history across the tenant
        $donations = Transaction::withoutGlobalScopes()
            ->where('tenant_id', $donor->tenant_id)
            ->where('ngo_donor_id', $donor->id)
            ->where('type', 'income')
            ->orderBy('date', 'desc')
            ->get();

        $totalDonated = $donations->sum('amount');
        
        // Let's get the distinct years they donated for the IR filter
        $years = $donations->pluck('date')->map(fn($date) => Carbon::parse($date)->year)->unique()->sortDesc();

        return view('donor.portal', compact('donor', 'donations', 'totalDonated', 'years'));
    }

    public function downloadIrPdf(Request $request, $token)
    {
        $donor = NgoDonor::withoutGlobalScopes()->where('portal_token_bidx', hash_hmac('sha256', $token, config('app.key')))->firstOrFail();
        
        $year = $request->query('year', date('Y') - 1); // Default to last year

        $donations = Transaction::withoutGlobalScopes()
            ->where('tenant_id', $donor->tenant_id)
            ->where('ngo_donor_id', $donor->id)
            ->where('type', 'income')
            ->whereYear('date', $year)
            ->orderBy('date', 'asc')
            ->get();

        if ($donations->isEmpty()) {
            return redirect()->back()->with('error', "Nenhuma doação encontrada para o ano de {$year}.");
        }

        $total = $donations->sum('amount');

        // Note: the tenant data should ideally be passed, but for simplicity we fetch the tenant name
        $tenant = \App\Models\Tenant::withoutGlobalScopes()->find($donor->tenant_id);

        $data = [
            'donor' => $donor,
            'donations' => $donations,
            'total' => $total,
            'year' => $year,
            'tenant' => $tenant,
            'date' => now()->format('d/m/Y')
        ];

        $pdf = Pdf::loadView('donor.ir_pdf', $data);
        return $pdf->download("Informe_de_Rendimentos_{$year}_{$donor->name}.pdf");
    }

    public function update(Request $request, $token)
    {
        $donor = NgoDonor::withoutGlobalScopes()->where('portal_token_bidx', hash_hmac('sha256', $token, config('app.key')))->firstOrFail();

        // Email removido das atualizações públicas: alteração de email via link
        // público poderia permitir sequestro de comunicações futuras do doador.
        // Para trocar o email, o doador deve contatar a ONG diretamente.
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
        ]);

        $donor->update($validated);

        return redirect()->back()->with('success', 'Dados atualizados com sucesso!');
    }
}
