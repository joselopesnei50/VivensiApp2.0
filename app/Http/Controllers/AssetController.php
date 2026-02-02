<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index()
    {
        $assets = Asset::where('tenant_id', auth()->user()->tenant_id)
                       ->orderBy('acquisition_date', 'desc')
                       ->paginate(15);

        return view('ngo.assets.index', compact('assets'));
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
}
