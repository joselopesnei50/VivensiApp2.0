<?php

namespace App\Http\Controllers;

use App\Models\GeneratedReport;
use Illuminate\Support\Facades\Storage;

class GeneratedReportController extends Controller
{
    public function status(int $id)
    {
        $report = GeneratedReport::where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();

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
        $report = GeneratedReport::where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('status', 'done')
            ->firstOrFail();

        if (!$report->file_path || !Storage::disk('local')->exists($report->file_path)) {
            abort(404, 'Relatório não encontrado.');
        }

        return Storage::disk('local')->download($report->file_path, $report->filename);
    }
}
