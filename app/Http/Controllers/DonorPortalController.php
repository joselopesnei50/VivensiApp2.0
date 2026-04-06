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
        $donor = NgoDonor::withoutGlobalScopes()->where('portal_token', $token)->firstOrFail();

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
        $donor = NgoDonor::withoutGlobalScopes()->where('portal_token', $token)->firstOrFail();
        
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
}
