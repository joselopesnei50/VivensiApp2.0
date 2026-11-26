<?php

namespace App\Http\Controllers;

use App\Models\GeneratedReport;
use Illuminate\Support\Facades\Storage;

class GeneratedReportController extends Controller
{
    public function status(int $id)
    {
        $report = $this->findForUser($id);

        return response()->json([
            'status'       => $report->status,
            'download_url' => $report->status === 'done'
                ? route('reports.download', $report->id)
                : null,
            'error'        => $report->error_message,
        ]);
    }

    public function download(int $id)
    {
        $report = $this->findForUser($id, onlyDone: true);

        if (!$report->file_path || !Storage::disk('local')->exists($report->file_path)) {
            abort(404, 'Relatório não encontrado.');
        }

        return Storage::disk('local')->download($report->file_path, $report->filename);
    }
    /**
     * O relatório (DRE, projeto, beneficiários…) é de quem gerou. O
     * administrador da conta também pode abrir. Antes bastava ser da mesma
     * organização: um Colaborador baixava o DRE pelo número (auditoria
     * 26/11/2026, item 5). Para os outros, 404 — não revela que existe.
     */
    private function findForUser(int $id, bool $onlyDone = false): GeneratedReport
    {
        $user = auth()->user();
        $q = GeneratedReport::where('id', $id)->where('tenant_id', $user->tenant_id);
        if ($onlyDone) {
            $q->where('status', 'done');
        }
        $isAccountAdmin = in_array($user->role, ['ngo', 'manager', 'common', 'super_admin'], true)
            && !\App\Support\TeamAccess::appliesTo($user);
        if (!$isAccountAdmin) {
            $q->where('user_id', $user->id);
        }
        return $q->firstOrFail();
    }
}
