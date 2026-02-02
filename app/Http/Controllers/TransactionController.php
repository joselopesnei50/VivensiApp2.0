<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index()
    {
        $tenant_id = auth()->user()->tenant_id;
        
        $transactions = Transaction::where('tenant_id', $tenant_id)
                                   ->with(['category', 'project'])
                                   ->orderBy('date', 'desc')
                                   ->paginate(20);

        $stats = [
            'income' => Transaction::where('tenant_id', $tenant_id)
                                   ->where('type', 'income')
                                   ->sum('amount'),
            'expense' => Transaction::where('tenant_id', $tenant_id)
                                   ->where('type', 'expense')
                                   ->sum('amount')
        ];
        $stats['balance'] = $stats['income'] - $stats['expense'];

        return view('transactions.index', compact('transactions', 'stats'));
    }

    public function create()
    {
        // Precisamos das categorias p/ o select
        // Estamos usando DB table direto para evitar criar model Category agora, mas ideal é criar.
        $categories = DB::table('financial_categories')
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->orderBy('name')
                        ->get();

        $user = auth()->user();
        
        // Se for usuário comum, não deve ver projetos de jeito nenhum
        if (!in_array($user->role, ['manager', 'ngo', 'super_admin'])) {
            $projects = collect();
        } else {
            $projects = Project::where('tenant_id', $user->tenant_id)
                               ->where('status', 'active')
                               ->get();
        }

        return view('transactions.create', compact('categories', 'projects'));
    }

    public function store(Request $request)
    {
        // Sanitização de Moeda Brasileira (R$ 1.000,00 -> 1000.00)
        $data = $request->all();
        if (isset($data['amount'])) {
             // Remove ponto de milhar e troca vírgula por ponto
             $data['amount'] = str_replace('.', '', $data['amount']);
             $data['amount'] = str_replace(',', '.', $data['amount']);
        }

        // Validamos os dados sanitizados
        $validator = \Illuminate\Support\Facades\Validator::make($data, [
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'date' => 'required|date',
            'type' => 'required|in:income,expense',
            'category_id' => 'nullable|integer',
            'project_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        $transaction = new Transaction($validated);
        $transaction->tenant_id = auth()->user()->tenant_id;
        $transaction->status = 'paid'; // Default para MVP

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('attachments', 'public');
            $transaction->attachment_path = $path;
        }

        $transaction->save(); 

        return redirect('/transactions')->with('success', 'Lançamento registrado com sucesso!');
    }

    public function export()
    {
        $fileName = 'transacoes-' . date('Y-m-d') . '.csv';
        $transactions = Transaction::where('tenant_id', auth()->user()->tenant_id)
                                    ->orderBy('date', 'desc')
                                    ->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('Data', 'Descricao', 'Tipo', 'Valor', 'Status');

        $callback = function() use($transactions, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($transactions as $t) {
                $row['Data']  = $t->date;
                $row['Descricao']    = $t->description;
                $row['Tipo']    = $t->type == 'income' ? 'Entrada' : 'Saida';
                $row['Valor']  = number_format($t->amount, 2, ',', '.');
                $row['Status']  = $t->status;

                fputcsv($file, array($row['Data'], $row['Descricao'], $row['Tipo'], $row['Valor'], $row['Status']));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function destroy($id)
    {
        $transaction = Transaction::where('id', $id)
                                   ->where('tenant_id', auth()->user()->tenant_id)
                                   ->firstOrFail();
        
        $transaction->delete();

        return redirect('/transactions')->with('success', 'Lançamento excluído com sucesso!');
    }
}
