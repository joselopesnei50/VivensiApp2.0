<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\NgoGrant;
use App\Models\NgoDonor;
use App\Models\Contract;
use App\Models\Transaction;
use App\Models\Task;

class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $user     = auth()->user();
        $tid      = $user->tenant_id;
        $role     = $user->role;
        $like     = "%{$q}%";
        $results  = [];

        // ── Projetos (manager / super_admin) ──────────────────────────────
        if (in_array($role, ['manager', 'super_admin'])) {
            $projects = Project::where('tenant_id', $tid)
                ->where(fn($q) => $q->where('name', 'like', $like)->orWhere('description', 'like', $like))
                ->limit(5)
                ->get(['id', 'name', 'status']);

            foreach ($projects as $p) {
                $results[] = [
                    'category' => 'Projetos',
                    'icon'     => 'fa-diagram-project',
                    'color'    => '#818cf8',
                    'label'    => $p->name,
                    'sub'      => ucfirst($p->status ?? 'em andamento'),
                    'url'      => url("/manager/projects/{$p->id}"),
                ];
            }

            // Tarefas
            $tasks = Task::where('tenant_id', $tid)
                ->where('title', 'like', $like)
                ->limit(4)
                ->get(['id', 'title', 'status']);

            foreach ($tasks as $t) {
                $results[] = [
                    'category' => 'Tarefas',
                    'icon'     => 'fa-check-square',
                    'color'    => '#34d399',
                    'label'    => $t->title,
                    'sub'      => ucfirst($t->status ?? 'pendente'),
                    'url'      => url("/manager/tasks"),
                ];
            }
        }

        // ── Editais (ngo / super_admin) ───────────────────────────────────
        if (in_array($role, ['ngo', 'super_admin'])) {
            $grants = NgoGrant::where('tenant_id', $tid)
                ->where(fn($q) => $q->where('title', 'like', $like)->orWhere('description', 'like', $like))
                ->limit(5)
                ->get(['id', 'title', 'status']);

            foreach ($grants as $g) {
                $results[] = [
                    'category' => 'Editais',
                    'icon'     => 'fa-file-signature',
                    'color'    => '#f59e0b',
                    'label'    => $g->title,
                    'sub'      => ucfirst($g->status ?? 'ativo'),
                    'url'      => url("/ngo/grants/{$g->id}"),
                ];
            }

            // Doadores
            $donors = NgoDonor::where('tenant_id', $tid)
                ->where(fn($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like))
                ->limit(5)
                ->get(['id', 'name', 'email']);

            foreach ($donors as $d) {
                $results[] = [
                    'category' => 'Doadores',
                    'icon'     => 'fa-heart',
                    'color'    => '#ef4444',
                    'label'    => $d->name,
                    'sub'      => $d->email ?? '',
                    'url'      => url("/ngo/donors/{$d->id}"),
                ];
            }
        }

        // ── Transações (todos os papéis) ──────────────────────────────────
        $transactions = Transaction::where('tenant_id', $tid)
            ->where(fn($q) => $q->where('description', 'like', $like)->orWhere('category', 'like', $like))
            ->limit(4)
            ->get(['id', 'description', 'amount', 'type']);

        foreach ($transactions as $t) {
            $results[] = [
                'category' => 'Transações',
                'icon'     => $t->type === 'income' ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down',
                'color'    => $t->type === 'income' ? '#34d399' : '#ef4444',
                'label'    => $t->description,
                'sub'      => 'R$ ' . number_format((float) $t->amount, 2, ',', '.'),
                'url'      => url("/finances/transactions"),
            ];
        }

        return response()->json($results);
    }
}
