<?php

namespace App\Http\Controllers;

use App\Services\OfxParserService;
use App\Models\Transaction;
use App\Models\FinancialCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PersonalReconciliationController extends Controller
{
    public function index()
    {
        return view('dashboards.common.reconciliation');
    }

    public function upload(Request $request, OfxParserService $parser)
    {
        $request->validate([
            'ofx_file' => 'required|file|max:2048',
        ]);

        $file = $request->file('ofx_file');
        $path = $file->storeAs('temp', 'upload_personal.ofx');
        
        try {
            $parsedTransactions = $parser->parse(storage_path('app/' . $path));
            $tenantId = auth()->user()->tenant_id;
            
            $categories = FinancialCategory::where('tenant_id', $tenantId)
                            ->orderBy('name')
                            ->get();

            $matches = [];
            foreach ($parsedTransactions as $pt) {
                $dbTrn = Transaction::where('tenant_id', $tenantId)
                    ->where('amount', $pt['amount'])
                    ->where('type', $pt['type'])
                    ->whereBetween('date', [
                        Carbon::parse($pt['date'])->subDays(3), 
                        Carbon::parse($pt['date'])->addDays(3)
                    ])
                    ->first();

                $matches[] = [
                    'ofx' => $pt,
                    'system' => $dbTrn
                ];
            }

            Storage::delete($path);
            return view('dashboards.common.reconciliation_match', compact('matches', 'categories'));

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao processar OFX: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $data = $request->input('transactions');
        if (!$data) return redirect('/dashboard')->with('error', 'Sem dados para importar.');

        $count = 0;
        foreach ($data as $trnData) {
            if (isset($trnData['checked']) && $trnData['checked'] == 1) {
                Transaction::create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'description' => $trnData['description'],
                    'amount' => $trnData['amount'],
                    'type' => $trnData['type'],
                    'date' => $trnData['date'],
                    'category_id' => $trnData['category_id'] ?: null,
                    'status' => 'paid',
                ]);
                $count++;
            }
        }

        return redirect('/dashboard')->with('success', "$count transações importadas!");
    }
}
