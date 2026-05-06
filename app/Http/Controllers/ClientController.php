<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $clients = \App\Models\Client::where('tenant_id', $tenantId)->latest()->get();
        return view('personal.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('personal.clients.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:individual,company',
        ]);

        $data = $request->all();
        $data['tenant_id'] = auth()->user()->tenant_id;
        
        \App\Models\Client::create($data);

        return redirect()->route('clients.index')->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function show(\App\Models\Client $client)
    {
        if ($client->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
        return view('personal.clients.show', compact('client'));
    }

    public function edit(\App\Models\Client $client)
    {
        if ($client->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }
        return view('personal.clients.edit', compact('client'));
    }

    public function update(Request $request, \App\Models\Client $client)
    {
        if ($client->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:individual,company',
        ]);

        $client->update($request->all());

        return redirect()->route('clients.index')->with('success', 'Cliente atualizado com sucesso!');
    }

    public function destroy(\App\Models\Client $client)
    {
        if ($client->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Cliente removido com sucesso!');
    }
}
