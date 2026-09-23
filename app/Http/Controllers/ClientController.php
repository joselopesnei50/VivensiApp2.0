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

        if ($stage = $request->input('stage')) {
            if (array_key_exists($stage, \App\Models\Client::STAGES)) {
                $query->where('stage', $stage);
            }
        }

        $clients = $query->paginate(25)->withQueryString();

        $stageCounts = \App\Models\Client::where('tenant_id', $tenantId)
            ->selectRaw('stage, COUNT(*) as total')
            ->groupBy('stage')
            ->pluck('total', 'stage');

        return view('personal.clients.index', compact('clients', 'stageCounts'));
    }

    public function create()
    {
        return view('personal.clients.create');
    }

    private function validationRules(): array
    {
        return [
            'name'  => 'required|string|max:255',
            'type'  => 'required|in:individual,company',
            'stage' => 'nullable|in:' . implode(',', array_keys(\App\Models\Client::STAGES)),
        ];
    }

    public function store(Request $request)
    {
        $request->validate($this->validationRules());

        $data = $request->only(['name', 'type', 'stage', 'document', 'email', 'phone', 'purchase_history', 'relationship_notes']);
        $data['tenant_id']       = auth()->user()->tenant_id;
        $data['stage']           = $data['stage'] ?? 'active';
        $data['last_contact_at'] = now();

        \App\Models\Client::create($data);

        return redirect()->route('clients.index')->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function show(\App\Models\Client $client)
    {
        if ($client->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $transactions = $client->transactions()
            ->orderBy('date', 'desc')
            ->limit(50)
            ->get();

        $stats = [
            'ltv'              => $client->ltv,
            'pending'          => $client->pending_amount,
            'count'            => $client->transaction_count,
            'last_transaction' => $client->last_transaction_at,
        ];

        return view('personal.clients.show', compact('client', 'transactions', 'stats'));
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

        $request->validate($this->validationRules());

        $client->update($request->only(['name', 'type', 'stage', 'document', 'email', 'phone', 'purchase_history', 'relationship_notes']));

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
