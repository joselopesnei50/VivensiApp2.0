<?php

namespace App\Http\Controllers;

use App\Models\AgendaEvent;
use App\Models\Client;
use Illuminate\Http\Request;

class AgendaEventController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:access-personal');
    }

    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $today    = now()->toDateString();

        $query = AgendaEvent::where('tenant_id', $tenantId)
            ->with(['client:id,name']);

        if ($status = $request->input('status')) {
            if (array_key_exists($status, AgendaEvent::STATUSES)) {
                $query->where('status', $status);
            } elseif ($status === 'overdue') {
                $query->overdue();
            } elseif ($status === 'today') {
                $query->where('status', 'pending')->whereDate('starts_on', $today);
            } elseif ($status === 'upcoming') {
                $query->where('status', 'pending')->where('starts_on', '>=', $today);
            }
        } else {
            $query->where(function ($q) use ($today) {
                $q->where('status', 'pending')
                  ->orWhere('starts_on', '>=', $today);
            });
        }

        if ($kind = $request->input('kind')) {
            if (array_key_exists($kind, AgendaEvent::KINDS)) {
                $query->where('kind', $kind);
            }
        }

        if ($clientId = $request->input('client_id')) {
            $query->where('client_id', $clientId);
        }

        if ($q = trim((string) $request->input('q', ''))) {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('location', 'like', "%{$q}%");
            });
        }

        if ($from = $request->input('from')) {
            $query->where('starts_on', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->where('starts_on', '<=', $to);
        }

        $items = $query->orderBy('starts_on')->orderBy('starts_at')
            ->paginate(25)->withQueryString();

        $baseCount = AgendaEvent::where('tenant_id', $tenantId);
        $stats = [
            'overdue'  => (clone $baseCount)->overdue()->count(),
            'today'    => (clone $baseCount)->where('status', 'pending')->whereDate('starts_on', $today)->count(),
            'upcoming' => (clone $baseCount)->where('status', 'pending')->where('starts_on', '>', $today)->count(),
            'done'     => (clone $baseCount)->where('status', 'done')->count(),
        ];

        $clients = Client::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('personal.agenda.index', compact('items', 'stats', 'clients'));
    }

    public function create()
    {
        $clients = Client::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('personal.agenda.create', compact('clients'));
    }

    private function validationRules(): array
    {
        return [
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'location'    => 'nullable|string|max:200',
            'client_id'   => 'nullable|integer|exists:clients,id',
            'starts_on'   => 'required|date',
            'starts_at'   => 'nullable|date_format:H:i',
            'ends_at'     => 'nullable|date_format:H:i|after:starts_at',
            'all_day'     => 'nullable|boolean',
            'status'      => 'nullable|in:' . implode(',', array_keys(AgendaEvent::STATUSES)),
            'kind'        => 'required|in:' . implode(',', array_keys(AgendaEvent::KINDS)),
            'color'       => 'nullable|string|max:20',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules());
        $data = $this->normalizeInput($validated, $request);
        $data['tenant_id']  = auth()->user()->tenant_id;
        $data['created_by'] = auth()->id();

        $this->assertClientBelongsToTenant($data['client_id'] ?? null);

        AgendaEvent::create($data);

        return redirect()->route('agenda.index')->with('success', 'Compromisso cadastrado na agenda.');
    }

    public function show(AgendaEvent $agenda)
    {
        abort_unless($agenda->tenant_id === auth()->user()->tenant_id, 403);
        $agenda->load(['client:id,name,phone,email', 'creator:id,name']);
        return view('personal.agenda.show', ['item' => $agenda]);
    }

    public function edit(AgendaEvent $agenda)
    {
        abort_unless($agenda->tenant_id === auth()->user()->tenant_id, 403);
        $clients = Client::where('tenant_id', $agenda->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name']);
        return view('personal.agenda.edit', ['item' => $agenda, 'clients' => $clients]);
    }

    public function update(Request $request, AgendaEvent $agenda)
    {
        abort_unless($agenda->tenant_id === auth()->user()->tenant_id, 403);

        $validated = $request->validate($this->validationRules());
        $data = $this->normalizeInput($validated, $request);

        $this->assertClientBelongsToTenant($data['client_id'] ?? null);

        $agenda->update($data);

        return redirect()->route('agenda.show', $agenda)->with('success', 'Compromisso atualizado.');
    }

    public function destroy(AgendaEvent $agenda)
    {
        abort_unless($agenda->tenant_id === auth()->user()->tenant_id, 403);
        $agenda->delete();
        return redirect()->route('agenda.index')->with('success', 'Compromisso removido.');
    }

    public function markDone(AgendaEvent $agenda)
    {
        abort_unless($agenda->tenant_id === auth()->user()->tenant_id, 403);
        $agenda->update(['status' => 'done']);
        return back()->with('success', 'Compromisso marcado como concluído.');
    }

    public function reopen(AgendaEvent $agenda)
    {
        abort_unless($agenda->tenant_id === auth()->user()->tenant_id, 403);
        $agenda->update(['status' => 'pending']);
        return back()->with('success', 'Compromisso reaberto.');
    }

    public function cancel(AgendaEvent $agenda)
    {
        abort_unless($agenda->tenant_id === auth()->user()->tenant_id, 403);
        $agenda->update(['status' => 'cancelled']);
        return back()->with('success', 'Compromisso cancelado.');
    }

    private function normalizeInput(array $validated, Request $request): array
    {
        $data = $validated;
        $data['all_day'] = $request->boolean('all_day');
        $data['status']  = $validated['status'] ?? 'pending';

        if ($data['all_day']) {
            $data['starts_at'] = null;
            $data['ends_at']   = null;
        }

        foreach (['description', 'location', 'color'] as $k) {
            if (array_key_exists($k, $data)) {
                $data[$k] = trim((string) $data[$k]) ?: null;
            }
        }

        if (empty($data['client_id'])) {
            $data['client_id'] = null;
        }

        return $data;
    }

    private function assertClientBelongsToTenant(?int $clientId): void
    {
        if (!$clientId) {
            return;
        }
        $owned = Client::where('id', $clientId)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->exists();
        abort_unless($owned, 403, 'Cliente não pertence à sua conta.');
    }
}
