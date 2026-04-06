<?php

namespace App\Http\Controllers;

use App\Models\NgoDonor;
use Illuminate\Http\Request;

class NgoDonorController extends Controller
{
    public function index()
    {
        $donors = NgoDonor::where('tenant_id', auth()->user()->tenant_id)
                          ->orderBy('created_at', 'desc')
                          ->paginate(15);
                          
        return view('ngo.donors.index', compact('donors'));
    }

    public function create()
    {
        return view('ngo.donors.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'type' => 'required|in:individual,company,government',
            'document' => 'nullable|string'
        ]);

        $donor = new NgoDonor($validated);
        $donor->tenant_id = auth()->user()->tenant_id;
        $donor->save();

        return redirect('/ngo/donors')->with('success', 'Doador cadastrado com sucesso!');
    }

    public function edit($id)
    {
        $donor = NgoDonor::where('id', $id)
                         ->where('tenant_id', auth()->user()->tenant_id)
                         ->firstOrFail();
                         
        return view('ngo.donors.edit', compact('donor'));
    }

    public function update(Request $request, $id)
    {
        $donor = NgoDonor::where('id', $id)
                         ->where('tenant_id', auth()->user()->tenant_id)
                         ->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'type' => 'required|in:individual,company,government',
            'document' => 'nullable|string'
        ]);

        $donor->update($validated);

        return redirect('/ngo/donors')->with('success', 'Doador atualizado com sucesso!');
    }

    public function destroy($id)
    {
        $donor = NgoDonor::where('id', $id)
                         ->where('tenant_id', auth()->user()->tenant_id)
                         ->firstOrFail();
                         
        $donor->delete();

        return redirect('/ngo/donors')->with('success', 'Doador excluído com sucesso!');
    }
}
