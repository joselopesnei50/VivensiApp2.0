<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LgpdBreachNotification;
use App\Models\LgpdDataRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LgpdController extends Controller
{
    public function index()
    {
        $pending   = LgpdDataRequest::with('user')->where('status', 'pending')->latest()->get();
        $breaches  = LgpdBreachNotification::with('reporter')->latest()->get();
        $overdueBreaches = $breaches->filter(fn($b) => $b->isOverdue());

        return view('admin.lgpd.index', compact('pending', 'breaches', 'overdueBreaches'));
    }

    // ── Processar solicitação de apagamento ──────────────────────────────────
    public function processRequest(Request $request, LgpdDataRequest $lgpdRequest)
    {
        $request->validate([
            'action' => ['required', 'in:complete,reject'],
            'notes'  => ['nullable', 'string', 'max:1000'],
        ]);

        if ($request->action === 'complete' && $lgpdRequest->type === 'delete') {
            $user = User::find($lgpdRequest->user_id);
            if ($user) {
                Log::critical('LGPD_USER_DELETED', [
                    'user_id'    => $user->id,
                    'user_email' => $user->email,
                    'tenant_id'  => $user->tenant_id,
                    'admin_id'   => auth()->id(),
                ]);
                // Anonimiza em vez de deletar para preservar integridade referencial
                $user->update([
                    'name'    => 'Usuário Excluído',
                    'email'   => 'deleted_' . $user->id . '@excluido.vivensi',
                    'phone'   => null,
                    'status'  => 'inactive',
                ]);
                // Revoga tokens de autenticação
                $user->tokens()->delete();
            }
        }

        $lgpdRequest->update([
            'status'       => $request->action === 'complete' ? 'completed' : 'rejected',
            'notes'        => $request->notes,
            'processed_at' => now(),
            'processed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Solicitação processada com sucesso.');
    }

    // ── Registrar incidente de segurança ────────────────────────────────────
    public function storeBreach(Request $request)
    {
        $validated = $request->validate([
            'title'                    => ['required', 'string', 'max:255'],
            'description'              => ['required', 'string'],
            'severity'                 => ['required', 'in:low,medium,high,critical'],
            'occurred_at'              => ['nullable', 'date'],
            'anpd_required'            => ['boolean'],
            'affected_data_types'      => ['nullable', 'string', 'max:500'],
            'estimated_affected_count' => ['nullable', 'integer', 'min:0'],
        ]);

        $breach = LgpdBreachNotification::create(array_merge($validated, [
            'reported_by'   => auth()->id(),
            'identified_at' => now(),
        ]));

        Log::critical('LGPD_BREACH_REGISTERED', [
            'breach_id'   => $breach->id,
            'severity'    => $breach->severity,
            'anpd_required' => $breach->anpd_required,
            'reported_by' => auth()->id(),
        ]);

        return back()->with('success', 'Incidente registrado. Protocolo #' . $breach->id . '.');
    }

    public function updateBreachStatus(Request $request, LgpdBreachNotification $breach)
    {
        $request->validate(['status' => ['required', 'in:identified,contained,notified_anpd,closed']]);

        $data = ['status' => $request->status];
        if ($request->status === 'notified_anpd') {
            $data['anpd_notified_at'] = now();
        }

        $breach->update($data);

        return back()->with('success', 'Status do incidente atualizado.');
    }
}
