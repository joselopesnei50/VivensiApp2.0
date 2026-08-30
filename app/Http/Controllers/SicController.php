<?php

namespace App\Http\Controllers;

use App\Models\SicRequest;
use App\Models\TransparencyPortal;
use Illuminate\Http\Request;

class SicController extends Controller
{
    public function __construct()
    {
        // Auditoria 2026-08-29 #4 (alta): fecha bypass de role em SIC/LAI.
        // Rotas publicas (publicForm/publicStore/publicStatus) sao para o
        // cidadao, NAO exigem auth — excluidas do gate.
        $this->middleware('can:manage-sic')->except([
            'publicForm', 'publicStore', 'publicStatus',
        ]);
    }

    // ── Public ────────────────────────────────────────────────────────────────

    public function publicForm(string $slug)
    {
        $portal = TransparencyPortal::withoutGlobalScope('tenant')->where('slug', $slug)->where('is_published', true)->firstOrFail();
        return view('transparency.sic_form', compact('portal'));
    }

    public function publicStore(Request $request, string $slug)
    {
        $portal = TransparencyPortal::withoutGlobalScope('tenant')->where('slug', $slug)->where('is_published', true)->firstOrFail();

        $request->validate([
            'requester_name'  => 'required|string|max:255',
            'requester_email' => 'required|email|max:255',
            'subject'         => 'required|string|max:500',
            'message'         => 'required|string|max:5000',
        ]);

        // 20 business days deadline (approx. 28 calendar days)
        $deadline = now()->addWeekdays(20)->toDateString();

        $sic = SicRequest::create([
            'tenant_id'      => $portal->tenant_id,
            'protocol'       => SicRequest::generateProtocol($portal->tenant_id),
            'requester_name' => $request->requester_name,
            'requester_email'=> $request->requester_email,
            'subject'        => $request->subject,
            'message'        => $request->message,
            'status'         => 'pending',
            'deadline_at'    => $deadline,
        ]);

        return redirect()->route('sic.public.status', ['slug' => $slug, 'protocol' => $sic->protocol])
            ->with('success', 'Sua solicitação foi registrada com sucesso! Guarde o número de protocolo.');
    }

    public function publicStatus(string $slug, string $protocol)
    {
        $portal = TransparencyPortal::withoutGlobalScope('tenant')->where('slug', $slug)->where('is_published', true)->firstOrFail();
        $sic    = SicRequest::where('tenant_id', $portal->tenant_id)
            ->where('protocol', strtoupper($protocol))
            ->firstOrFail();

        return view('transparency.sic_status', compact('portal', 'sic'));
    }

    // ── Admin ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $status   = $request->get('status', '');
        $q        = trim((string) $request->get('q', ''));

        $query = SicRequest::where('tenant_id', $tenantId)
            ->orderByRaw("FIELD(status, 'pending', 'in_review', 'answered', 'denied', 'closed')")
            ->orderBy('deadline_at');

        if ($status !== '') $query->where('status', $status);
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('protocol', 'like', "%{$q}%")
                  ->orWhere('subject', 'like', "%{$q}%")
                  ->orWhere('requester_name', 'like', "%{$q}%");
            });
        }

        $requests = $query->paginate(20)->appends($request->query());

        $counts = SicRequest::where('tenant_id', $tenantId)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return view('ngo.sic.index', compact('requests', 'counts', 'status', 'q'));
    }

    public function show(int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $sic      = SicRequest::where('tenant_id', $tenantId)->with('responder')->findOrFail($id);
        return view('ngo.sic.show', compact('sic'));
    }

    public function respond(Request $request, int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $sic      = SicRequest::where('tenant_id', $tenantId)->findOrFail($id);

        $request->validate([
            'response' => 'required|string|max:10000',
            'status'   => 'required|in:answered,denied,closed',
        ]);

        $sic->update([
            'response'     => $request->response,
            'status'       => $request->status,
            'responded_at' => now(),
            'responded_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Resposta registrada com sucesso!');
    }

    public function updateStatus(Request $request, int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $sic      = SicRequest::where('tenant_id', $tenantId)->findOrFail($id);

        $request->validate(['status' => 'required|in:pending,in_review,answered,denied,closed']);
        $sic->update(['status' => $request->status]);

        return redirect()->back()->with('success', 'Status atualizado.');
    }
}
