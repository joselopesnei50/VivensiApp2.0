<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\AuditDownload;

class AssetController extends Controller
{
    public function term(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));

        $assetsQ = Asset::where('tenant_id', $tenantId)->orderBy('acquisition_date', 'desc');
        if ($q !== '') {
            $assetsQ->where(function ($w) use ($q) {
                $w->where('name', 'like', '%' . $q . '%')
                  ->orWhere('code', 'like', '%' . $q . '%')
                  ->orWhere('location', 'like', '%' . $q . '%')
                  ->orWhere('responsible', 'like', '%' . $q . '%');
            });
        }
        if ($status !== '') {
            $assetsQ->where('status', $status);
        }

        $assets = $assetsQ->get();

        $totals = [
            'count' => $assets->count(),
            'value' => (float) $assets->sum('value'),
            'active' => (int) $assets->where('status', 'active')->count(),
            'maintenance' => (int) $assets->where('status', 'maintenance')->count(),
            'disposed' => (int) $assets->whereIn('status', ['disposed', 'lost'])->count(),
        ];

        return view('ngo.assets.term', compact('assets', 'totals'));
    }

    public function termPdf(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));

        AuditDownload::log('Assets:Inventory', null, [
            'format' => 'pdf',
            'q' => $q,
            'status' => $status,
        ]);

        $assetsQ = Asset::where('tenant_id', $tenantId)->orderBy('acquisition_date', 'desc');
        if ($q !== '') {
            $assetsQ->where(function ($w) use ($q) {
                $w->where('name', 'like', '%' . $q . '%')
                  ->orWhere('code', 'like', '%' . $q . '%')
                  ->orWhere('location', 'like', '%' . $q . '%')
                  ->orWhere('responsible', 'like', '%' . $q . '%');
            });
        }
        if ($status !== '') {
            $assetsQ->where('status', $status);
        }

        $assets = $assetsQ->get();
        $totals = [
            'count' => $assets->count(),
            'value' => (float) $assets->sum('value'),
            'active' => (int) $assets->where('status', 'active')->count(),
            'maintenance' => (int) $assets->where('status', 'maintenance')->count(),
            'disposed' => (int) $assets->whereIn('status', ['disposed', 'lost'])->count(),
        ];

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadView('ngo.assets.term_pdf', [
            'assets' => $assets,
            'totals' => $totals,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'orgName' => (auth()->user()->tenant_id == 1) ? 'INSTITUTO VIVENSI' : 'ORGANIZAÇÃO SOCIAL',
            'emitter' => auth()->user()->name,
        ]);

        $filename = 'inventario-' . date('Y-m-d_His') . '.pdf';
        return $pdf->download($filename);
    }

    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));

        $assetsQ = Asset::where('tenant_id', $tenantId)->orderBy('acquisition_date', 'desc');
        if ($q !== '') {
            $assetsQ->where(function ($w) use ($q) {
                $w->where('name', 'like', '%' . $q . '%')
                  ->orWhere('code', 'like', '%' . $q . '%')
                  ->orWhere('location', 'like', '%' . $q . '%')
                  ->orWhere('responsible', 'like', '%' . $q . '%');
            });
        }
        if ($status !== '') {
            $assetsQ->where('status', $status);
        }

        $assets = $assetsQ->paginate(15)->appends($request->query());

        $statsRows = DB::table('assets')
            ->where('tenant_id', $tenantId)
            ->select('status', DB::raw('COUNT(*) as c'), DB::raw('SUM(value) as total_value'))
            ->groupBy('status')
            ->get();

        $stats = [
            'total_count' => (int) $statsRows->sum('c'),
            'total_value' => (float) $statsRows->sum('total_value'),
            'active_count' => (int) ($statsRows->firstWhere('status', 'active')->c ?? 0),
            'maintenance_count' => (int) ($statsRows->firstWhere('status', 'maintenance')->c ?? 0),
            'disposed_count' => (int) ($statsRows->firstWhere('status', 'disposed')->c ?? 0),
            'lost_count' => (int) ($statsRows->firstWhere('status', 'lost')->c ?? 0),
        ];

        return view('ngo.assets.index', compact('assets', 'stats'));
    }

    public function store(Request $request)
    {
        $data = $request->all();

        // Sanitização de Moeda
        if (isset($data['value'])) {
             $data['value'] = str_replace('.', '', $data['value']);
             $data['value'] = str_replace(',', '.', $data['value']);
        }

        $validated = \Illuminate\Support\Facades\Validator::make($data, [
            'name' => 'required|string',
            'code' => 'nullable|string',
            'acquisition_date' => 'required|date',
            'value' => 'required|numeric',
            'location' => 'nullable|string',
            'responsible' => 'nullable|string',
            'status' => 'required|in:active,maintenance,disposed,lost'
        ])->validate();

        $asset = new Asset($validated);
        $asset->tenant_id = auth()->user()->tenant_id;
        $asset->save();

        return redirect()->back()->with('success', 'Patrimônio registrado com sucesso!');
    }

    public function destroy($id)
    {
        $asset = Asset::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $asset->delete();
        return redirect()->back()->with('success', 'Item removido.');
    }

    public function exportCsv(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));

        AuditDownload::log('Assets', null, [
            'format' => 'csv',
            'q' => $q,
            'status' => $status,
        ]);

        $filename = 'assets-' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($tenantId, $q, $status) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Código', 'Nome', 'Localização', 'Responsável', 'Status', 'Data Aquisição', 'Valor']);

            $baseQ = Asset::where('tenant_id', $tenantId)->orderBy('acquisition_date', 'desc');
            if ($q !== '') {
                $baseQ->where(function ($w) use ($q) {
                    $w->where('name', 'like', '%' . $q . '%')
                      ->orWhere('code', 'like', '%' . $q . '%')
                      ->orWhere('location', 'like', '%' . $q . '%')
                      ->orWhere('responsible', 'like', '%' . $q . '%');
                });
            }
            if ($status !== '') $baseQ->where('status', $status);

            $baseQ->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $a) {
                    fputcsv($out, [
                        $a->code,
                        $a->name,
                        $a->location,
                        $a->responsible,
                        $a->status,
                        $a->acquisition_date,
                        $a->value,
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
