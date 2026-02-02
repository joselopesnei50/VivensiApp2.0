<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReceiptController extends Controller
{
    public function index()
    {
        // Listar apenas entradas (doações) para emissão de recibo
        $donations = Transaction::where('tenant_id', auth()->user()->tenant_id)
                                ->where('type', 'income')
                                ->orderBy('date', 'desc')
                                ->paginate(10);
                                
        return view('ngo.receipts.index', compact('donations'));
    }

    public function create()
    {
        // Buscar doadores cadastrados para o autocomplete
        $donors = \App\Models\NgoDonor::where('tenant_id', auth()->user()->tenant_id)
                                      ->orderBy('name')
                                      ->get();
                                      
        return view('ngo.receipts.create', compact('donors'));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        
        // Sanitização (R$ 1.000,00 -> 1000.00)
        if (isset($data['amount'])) {
             $data['amount'] = str_replace('.', '', $data['amount']);
             $data['amount'] = str_replace(',', '.', $data['amount']);
        }

        $transaction = new Transaction();
        $transaction->tenant_id = auth()->user()->tenant_id;
        $transaction->description = $request->description; // Doador
        $transaction->amount = $data['amount'];
        $transaction->type = 'income';
        $transaction->date = $request->date;
        $transaction->category_id = null; // Doação
        $transaction->status = 'paid';
        $transaction->save();

        return redirect('/ngo/receipts')->with('success', 'Recibo gerado com sucesso! Agora você pode enviar.');
    }

    // Exibe o recibo publicamente
    public function show($id)
    {
        // Na prática, usaríamos um hash ou UUID para não expor o ID sequencial
        // Para o MVP, validamos se a transação é 'income' e status 'paid'
        
        $transaction = Transaction::findOrFail($id);
        
        if ($transaction->type !== 'income' || $transaction->status !== 'paid') {
            abort(404);
        }

        $tenant = \Illuminate\Support\Facades\DB::table('tenants')->where('id', $transaction->tenant_id)->first();

        return view('public.receipt', compact('transaction', 'tenant'));
    }
}
