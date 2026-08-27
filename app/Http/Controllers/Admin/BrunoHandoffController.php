<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrunoHandoff;
use App\Services\Bruno\BrunoHandoffService;
use Illuminate\Http\Request;

/**
 * Inbox pro time humano assumir leads escalados pelo Bruno. Cada handoff
 * chega com briefing TL;DR gerado por IA + dados estruturados extraidos da
 * conversa — o humano nao precisa re-ler o historico inteiro.
 */
class BrunoHandoffController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pendente');
        $allowed = ['pendente', 'assumido', 'resolvido'];
        if (!in_array($status, $allowed, true)) {
            $status = 'pendente';
        }

        // BelongsToTenant filtra automaticamente por tenant do usuario logado
        $handoffs = BrunoHandoff::with(['chat', 'assumedBy'])
            ->where('status', $status)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->appends(['status' => $status]);

        $counts = [
            'pendente'  => BrunoHandoff::pending()->count(),
            'assumido'  => BrunoHandoff::assumed()->count(),
            'resolvido' => BrunoHandoff::resolved()->count(),
        ];

        return view('admin.bruno.handoffs.index', compact('handoffs', 'status', 'counts'));
    }

    public function show(BrunoHandoff $handoff)
    {
        $this->authorizeTenant($handoff);

        $handoff->load(['chat.messages' => function ($q) {
            $q->orderBy('created_at', 'asc')->limit(80);
        }, 'assumedBy']);

        return view('admin.bruno.handoffs.show', compact('handoff'));
    }

    public function assume(BrunoHandoff $handoff, BrunoHandoffService $service)
    {
        $this->authorizeTenant($handoff);

        if (!$handoff->isPending()) {
            return back()->with('error', 'Este handoff ja foi assumido ou resolvido.');
        }

        $service->assume($handoff, auth()->id());
        return redirect()->route('admin.bruno.handoffs.show', $handoff)
            ->with('success', 'Handoff assumido. Voce agora eh o responsavel — leia o briefing e siga a proxima acao.');
    }

    public function resolve(Request $request, BrunoHandoff $handoff, BrunoHandoffService $service)
    {
        $this->authorizeTenant($handoff);

        if ($handoff->isResolved()) {
            return back()->with('error', 'Este handoff ja esta resolvido.');
        }

        $note = trim((string) $request->input('resolution_note', ''));
        $service->resolve($handoff, $note !== '' ? $note : null);

        return redirect()->route('admin.bruno.handoffs.index', ['status' => 'resolvido'])
            ->with('success', 'Handoff marcado como resolvido.');
    }

    /**
     * BelongsToTenant ja filtra no scope, mas se rota carregar por ID direto
     * pode passar handoff de outro tenant. Reforca aqui.
     */
    private function authorizeTenant(BrunoHandoff $handoff): void
    {
        $tenantId = auth()->user()->tenant_id;
        if ($tenantId !== null && (int) $handoff->tenant_id !== (int) $tenantId) {
            abort(404);
        }
    }
}
