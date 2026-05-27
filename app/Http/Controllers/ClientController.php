<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = \App\Models\Client::where('tenant_id', $tenantId)->latest();

        if ($q = $request->input('q')) {
            $query->where(function ($q2) use ($q) {
                $q2->where('name', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%")
                   ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $clients = $query->paginate(25)->withQueryString();

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

        $data = $request->only(['name', 'type', 'document', 'email', 'phone', 'purchase_history', 'relationship_notes']);
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

        $client->update($request->only(['name', 'type', 'document', 'email', 'phone', 'purchase_history', 'relationship_notes']));

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
