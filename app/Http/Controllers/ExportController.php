<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Project;
use App\Models\LandingPage;
use Illuminate\Support\Facades\Auth;

class ExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function csv(Request $request)
    {
        $user = Auth::user();
        $role = $user->role;
        
        $filename = "vivensi_export_" . date('Y-m-d_H-i') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($role, $user) {
            $file = fopen('php://output', 'w');
            // Add BOM for Excel compatibility in UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($role === 'ngo') {
                // Export Transactions for NGO
                fputcsv($file, ['ID', 'Título', 'Tipo', 'Valor', 'Data', 'Status', 'Projeto']);
                $transactions = Transaction::where('tenant_id', $user->tenant_id)->get();
                foreach ($transactions as $t) {
                    fputcsv($file, [
                        $t->id,
                        $t->title,
                        $t->type === 'income' ? 'Receita' : 'Despesa',
                        number_format($t->amount, 2, ',', '.'),
                        $t->date,
                        $t->status,
                        $t->project ? $t->project->name : 'N/A'
                    ]);
                }
            } elseif ($role === 'manager') {
                // Export Projects for Manager
                fputcsv($file, ['ID', 'Nome', 'Status', 'Início', 'Fim', 'Orçamento']);
                $projects = Project::where('tenant_id', $user->tenant_id)->get();
                foreach ($projects as $p) {
                    fputcsv($file, [
                        $p->id,
                        $p->name,
                        $p->status,
                        $p->start_date,
                        $p->end_date,
                        number_format($p->budget, 2, ',', '.')
                    ]);
                }
            } else {
                // Generic Export for others (Landing Pages)
                fputcsv($file, ['ID', 'Título', 'Visualizações', 'Leads', 'Status']);
                $pages = LandingPage::where('tenant_id', $user->tenant_id)->get();
                foreach ($pages as $page) {
                    fputcsv($file, [
                        $page->id,
                        $page->title,
                        $page->views_count ?? 0,
                        $page->leads_count ?? 0,
                        $page->is_published ? 'Ativa' : 'Rascunho'
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
