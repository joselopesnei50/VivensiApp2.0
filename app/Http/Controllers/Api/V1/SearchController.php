<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\Transaction;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(['q' => 'required|string|min:2|max:100']);

        $q        = $request->q;
        $tenantId = $request->user()->tenant_id;
        $results  = [];

        // Transactions
        $txs = Transaction::where('tenant_id', $tenantId)
            ->where('description', 'like', "%{$q}%")
            ->select('id', 'description', 'amount', 'type', 'date', 'status')
            ->limit(5)
            ->get();

        foreach ($txs as $t) {
            $results[] = [
                'type'     => 'transaction',
                'id'       => $t->id,
                'title'    => $t->description,
                'subtitle' => 'R$ ' . number_format($t->amount, 2, ',', '.') . ' — ' . $t->type,
                'url'      => '/transactions/' . $t->id,
                'meta'     => ['status' => $t->status, 'date' => $t->date?->toDateString()],
            ];
        }

        // Projects
        $projects = Project::where('tenant_id', $tenantId)
            ->where(fn ($q2) => $q2->where('name', 'like', "%{$q}%")->orWhere('description', 'like', "%{$q}%"))
            ->select('id', 'name', 'status', 'progress')
            ->limit(5)
            ->get();

        foreach ($projects as $p) {
            $results[] = [
                'type'     => 'project',
                'id'       => $p->id,
                'title'    => $p->name,
                'subtitle' => 'Projeto — ' . $p->progress . '% concluído',
                'url'      => '/projects/' . $p->id,
                'meta'     => ['status' => $p->status],
            ];
        }

        // Tasks
        $tasks = Task::where('tenant_id', $tenantId)
            ->where('title', 'like', "%{$q}%")
            ->select('id', 'title', 'status', 'priority')
            ->limit(5)
            ->get();

        foreach ($tasks as $t) {
            $results[] = [
                'type'     => 'task',
                'id'       => $t->id,
                'title'    => $t->title,
                'subtitle' => 'Tarefa — ' . ucfirst($t->priority ?? 'medium'),
                'url'      => '/tasks',
                'meta'     => ['status' => $t->status, 'priority' => $t->priority],
            ];
        }

        return response()->json([
            'data'  => $results,
            'query' => $q,
            'total' => count($results),
        ]);
    }
}
