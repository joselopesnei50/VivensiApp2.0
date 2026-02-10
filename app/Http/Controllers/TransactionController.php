<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
            'project_id' => 'nullable|integer',
            'attachment' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png,zip'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        $transaction = new Transaction($validated);
        $transaction->tenant_id = auth()->user()->tenant_id;
        // Despesas lançadas por colaborador devem passar por aprovação do gestor
        $user = auth()->user();
        $isExpense = (($validated['type'] ?? null) === 'expense');
        $needsApproval = $isExpense && !in_array($user->role, ['manager', 'ngo', 'super_admin'], true);

        $transaction->status = $needsApproval ? 'pending' : 'paid';
        if (Schema::hasColumn('transactions', 'approval_status')) {
            $transaction->approval_status = $needsApproval ? 'pending' : 'approved';
        }

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('attachments', 'public');
            $transaction->attachment_path = $path;
            // compat: algumas telas usam receipt_path para mostrar o anexo
            if ($isExpense && empty($transaction->receipt_path)) {
                $transaction->receipt_path = $path;
            }
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

    public function show($id)
    {
        $transaction = Transaction::where('id', $id)
                                   ->where('tenant_id', auth()->user()->tenant_id)
                                   ->firstOrFail();
        
        return response()->json($transaction);
    }

    public function update(Request $request, $id)
    {
        $transaction = Transaction::where('id', $id)
                                   ->where('tenant_id', auth()->user()->tenant_id)
                                   ->firstOrFail();
        
        // Sanitização de Moeda Brasileira (R$ 1.000,00 -> 1000.00)
        $data = $request->all();
        if (isset($data['amount'])) {
             $data['amount'] = str_replace('.', '', $data['amount']);
             $data['amount'] = str_replace(',', '.', $data['amount']);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($data, [
            'description' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric',
            'date' => 'nullable|date',
            'type' => 'nullable|in:income,expense',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();
        
        // Security Logic: If amount changed and user is NOT manager, reset to pending
        if (isset($validated['amount']) && $validated['amount'] != $transaction->amount) {
             $user = auth()->user();
             if (!in_array($user->role, ['manager', 'ngo', 'super_admin'], true)) {
                 $transaction->status = 'pending';
                 if (Schema::hasColumn('transactions', 'approval_status')) {
                     $transaction->approval_status = 'pending';
                 }
             }
        }

        $transaction->update($validated);

        return back()->with('success', 'Lançamento atualizado com sucesso!');
    }

    public function destroy($id)
    {
        $transaction = Transaction::where('id', $id)
                                   ->where('tenant_id', auth()->user()->tenant_id)
                                   ->firstOrFail();
        
        $transaction->delete();

        return redirect('/transactions')->with('success', 'Lançamento excluído com sucesso!');
    }

    public function approve($id)
    {
        // Security Check: Only Managers/Admins can approve
        if (!in_array(auth()->user()->role, ['manager', 'ngo', 'super_admin'], true)) {
            abort(403, 'Apenas gestores podem aprovar transações.');
        }

        $transaction = Transaction::where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();

        $updates = ['status' => 'paid'];
        if (Schema::hasColumn('transactions', 'approval_status')) {
            $updates['approval_status'] = 'approved';
        }

        $transaction->update($updates);

        return back()->with('success', 'Lançamento aprovado!');
    }

    public function reject($id)
    {
        // Security Check: Only Managers/Admins can reject
        if (!in_array(auth()->user()->role, ['manager', 'ngo', 'super_admin'], true)) {
            abort(403, 'Apenas gestores podem rejeitar transações.');
        }

        $transaction = Transaction::where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();

        $updates = ['status' => 'canceled'];
        if (Schema::hasColumn('transactions', 'approval_status')) {
            $updates['approval_status'] = 'rejected';
        }

        $transaction->update($updates);

        return back()->with('success', 'Lançamento recusado.');
    }
}
