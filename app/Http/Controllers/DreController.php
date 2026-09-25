<?php

namespace App\Http\Controllers;

use App\Services\DreService;
use Illuminate\Http\Request;

class DreController extends Controller
{
    public function __construct(private DreService $dre)
    {
        $this->middleware('can:access-personal');
    }

    public function index(Request $request)
    {
        $preset = $request->input('period', 'current_month');
        if (!array_key_exists($preset, DreService::PRESETS) && $preset !== 'custom') {
            $preset = 'current_month';
        }

        [$from, $to] = $this->dre->resolvePeriod(
            $preset,
            $request->input('from'),
            $request->input('to'),
        );

        $tenantId = auth()->user()->tenant_id;
        $data     = $this->dre->calculate($tenantId, $from, $to);

        return view('personal.dre.index', array_merge($data, [
            'preset'  => $preset,
            'presets' => DreService::PRESETS,
        ]));
    }
}
