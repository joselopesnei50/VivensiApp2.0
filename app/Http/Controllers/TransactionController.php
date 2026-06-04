<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Project;
use App\Models\User;
use App\Mail\PendingExpenseApprovalMail;
use App\Services\DonorRetentionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TransactionController extends Controller
{
    /**
     * Aplica filtros vindos da querystring numa query de transactions.
     * Usado por index() e export() para garantir que o CSV reflita exatamente
     * o que o usuário está vendo na tela.
     */
    private function applyTransactionFilters($query, Request $request): void
    {
        $type = $request->query('type');
        if (in_array($type, ['income', 'expense'], true)) {
            $query->where('transactions.type', $type);
        }

        $status = $request->query('status');
        if (in_array($status, ['pending', 'paid', 'rejected', 'canceled'], true)) {
            $query->where('transactions.status', $status);
        }

        if ($categoryId = $request->query('category_id')) {
            $query->where('transactions.category_id', (int) $categoryId);
        }

        if ($projectId = $request->query('project_id')) {
            $query->where('transactions.project_id', (int) $projectId);
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('transactions.date', '>=', $dateFrom);
        }
        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('transactions.date', '<=', $dateTo);
        }

        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $query->where('transactions.description', 'like', '%' . $q . '%');
        }
    }

    public function index(Request $request)
    {
        $tenant_id = auth()->user()->tenant_id;

        // Lista paginada com TODOS os filtros aplicados
        $listQuery = Transaction::where('tenant_id', $tenant_id);
        $this->applyTransactionFilters($listQuery, $request);

        $transactions = $listQuery
            ->with(['category', 'project'])
            ->orderBy('date', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Stats: aplica só filtros "de contexto" (data, projeto, categoria) e
        // ignora tipo/status/busca para que receita/despesa façam sentido mesmo
        // quando o usuário filtra "status=pending" na listagem.
        $statsBase = Transaction::where('tenant_id', $tenant_id);
        foreach (['category_id', 'project_id'] as $filter) {
            if ($v = $request->query($filter)) {
                $statsBase->where($filter, (int) $v);
            }
        }
        if ($df = $request->query('date_from')) { $statsBase->whereDate('date', '>=', $df); }
        if ($dt = $request->query('date_to'))   { $statsBase->whereDate('date', '<=', $dt); }

        $stats = [
            'income'  => (clone $statsBase)->where('type', 'income')->where('status', 'paid')->sum('amount'),
            'expense' => (clone $statsBase)->where('type', 'expense')->where('status', 'paid')->sum('amount'),
        ];
        $stats['balance'] = $stats['income'] - $stats['expense'];

        // Selects de filtro
        $categories = DB::table('financial_categories')
            ->where('tenant_id', $tenant_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $projects = Project::where('tenant_id', $tenant_id)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        $filters = [
            'type'        => $request->query('type', ''),
            'status'      => $request->query('status', ''),
            'category_id' => $request->query('category_id', ''),
            'project_id'  => $request->query('project_id', ''),
            'date_from'   => $request->query('date_from', ''),
            'date_to'     => $request->query('date_to', ''),
            'q'           => trim((string) $request->query('q', '')),
        ];

        return view('transactions.index', compact('transactions', 'stats', 'categories', 'projects', 'filters'));
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
            $data['amount'] = sanitize_br_currency($data['amount']);
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
        $transaction->approval_status = $needsApproval ? 'pending' : 'approved';

        if ($request->hasFile('attachment')) {
            $tenantId = auth()->user()->tenant_id;
            $path = $request->file('attachment')->store("tenants/{$tenantId}/attachments", 'public');
            $transaction->attachment_path = $path;
            // compat: algumas telas usam receipt_path para mostrar o anexo
            if ($isExpense && empty($transaction->receipt_path)) {
                $transaction->receipt_path = $path;
            }
        }

        $transaction->save();

        // Notifica gestores quando despesa precisa de aprovação
        if ($needsApproval) {
            try {
                $managers = User::where('tenant_id', auth()->user()->tenant_id)
                    ->whereIn('role', ['manager', 'ngo', 'super_admin'])
                    ->whereNotNull('email')
                    ->get();

                foreach ($managers as $manager) {
                    Mail::to($manager->email)
                        ->queue(new PendingExpenseApprovalMail($transaction, auth()->user()));
                }
            } catch (\Exception $e) {
                \Log::error('Erro ao notificar gestores sobre despesa pendente: ' . $e->getMessage());
            }
        }

        // Dispara mensagem de agradecimento via WhatsApp para doações confirmadas
        if (!$needsApproval && $transaction->type === 'income' && $transaction->status === 'paid') {
            try {
                (new DonorRetentionService())->notifyDonationReceived($transaction);
            } catch (\Throwable $e) {
                \Log::warning('DonorRetention: não foi possível enviar agradecimento: ' . $e->getMessage());
            }
        }

        return redirect('/transactions')->with('success', 'Lançamento registrado com sucesso!');
    }

    public function export(Request $request)
    {
        $fileName = 'transacoes-' . date('Y-m-d') . '.csv';

        $query = Transaction::where('tenant_id', auth()->user()->tenant_id);
        $this->applyTransactionFilters($query, $request);

        $transactions = $query->orderBy('date', 'desc')->get();

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
        
        $data = $request->all();
        if (isset($data['amount'])) {
            $data['amount'] = sanitize_br_currency($data['amount']);
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
                 $transaction->approval_status = 'pending';
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

        $updates = ['status' => 'paid', 'approval_status' => 'approved'];

        $transaction->update($updates);

        // Dispara mensagem de agradecimento via WhatsApp quando aprovação confirma uma doação
        if ($transaction->type === 'income' && $updates['status'] === 'paid') {
            try {
                $transaction->refresh();
                (new DonorRetentionService())->notifyDonationReceived($transaction);
            } catch (\Throwable $e) {
                \Log::warning('DonorRetention: não foi possível enviar agradecimento: ' . $e->getMessage());
            }
        }

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

        $updates = ['status' => 'canceled', 'approval_status' => 'rejected'];

        $transaction->update($updates);

        return back()->with('success', 'Lançamento recusado.');
    }
}
