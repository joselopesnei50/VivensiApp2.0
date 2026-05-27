<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campanha;
use App\Services\CampanhaService;
use Illuminate\Http\Request;

class CampanhaController extends Controller
{
    public function index()
    {
        $tenantId  = auth()->user()->tenant_id;
        $campanhas = Campanha::where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.whatsapp.optin.campanhas', compact('campanhas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo'             => 'required|string|max:255',
            'mensagem'           => 'required|string|max:4000',
            'intervalo_segundos' => 'required|integer|in:1,2,3,5,10',
            'agendada_para'      => 'nullable|date|after:now',
        ]);

        $tenantId = auth()->user()->tenant_id;

        Campanha::create([
            'tenant_id'          => $tenantId,
            'titulo'             => $request->input('titulo'),
            'mensagem'           => $request->input('mensagem'),
            'intervalo_segundos' => $request->input('intervalo_segundos'),
            'agendada_para'      => $request->input('agendada_para'),
            'status'             => $request->filled('agendada_para') ? 'agendada' : 'rascunho',
        ]);

        return redirect()->route('whatsapp.optin.campanhas')->with('success', 'Campanha criada com sucesso.');
    }

    public function edit(Campanha $campanha)
    {
        abort_if($campanha->tenant_id !== auth()->user()->tenant_id, 403);
        abort_if($campanha->status !== 'rascunho', 422, 'Apenas rascunhos podem ser editados.');

        $tenantId  = auth()->user()->tenant_id;
        $campanhas = Campanha::where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.whatsapp.optin.campanhas', compact('campanhas', 'campanha'));
    }

    public function update(Request $request, Campanha $campanha)
    {
        abort_if($campanha->tenant_id !== auth()->user()->tenant_id, 403);
        abort_if($campanha->status !== 'rascunho', 422, 'Apenas rascunhos podem ser editados.');

        $request->validate([
            'titulo'             => 'required|string|max:255',
            'mensagem'           => 'required|string|max:4000',
            'intervalo_segundos' => 'required|integer|in:1,2,3,5,10',
            'agendada_para'      => 'nullable|date|after:now',
        ]);

        $campanha->update([
            'titulo'             => $request->input('titulo'),
            'mensagem'           => $request->input('mensagem'),
            'intervalo_segundos' => $request->input('intervalo_segundos'),
            'agendada_para'      => $request->input('agendada_para'),
            'status'             => $request->filled('agendada_para') ? 'agendada' : 'rascunho',
        ]);

        return redirect()->route('whatsapp.optin.campanhas')->with('success', 'Campanha atualizada com sucesso.');
    }

    public function duplicate(Campanha $campanha)
    {
        abort_if($campanha->tenant_id !== auth()->user()->tenant_id, 403);

        Campanha::create([
            'tenant_id'          => $campanha->tenant_id,
            'titulo'             => 'Cópia — ' . $campanha->titulo,
            'mensagem'           => $campanha->mensagem,
            'intervalo_segundos' => $campanha->intervalo_segundos,
            'status'             => 'rascunho',
        ]);

        return redirect()->route('whatsapp.optin.campanhas')->with('success', 'Campanha duplicada como rascunho.');
    }

    public function destroy(Campanha $campanha)
    {
        abort_if($campanha->tenant_id !== auth()->user()->tenant_id, 403);
        abort_if(!in_array($campanha->status, ['rascunho', 'cancelada', 'concluida']), 422, 'Não é possível excluir esta campanha.');

        $campanha->delete();

        return redirect()->route('whatsapp.optin.campanhas')->with('success', 'Campanha removida.');
    }

    public function disparar(Campanha $campanha, CampanhaService $service)
    {
        abort_if($campanha->tenant_id !== auth()->user()->tenant_id, 403);
        abort_if($campanha->status !== 'rascunho', 422, 'Campanha não pode ser disparada.');

        $service->disparar($campanha);

        return redirect()->route('whatsapp.optin.campanhas')
            ->with('success', 'Campanha em processamento! Acompanhe o progresso abaixo.');
    }

    public function cancelar(Campanha $campanha)
    {
        abort_if($campanha->tenant_id !== auth()->user()->tenant_id, 403);
        abort_if(!in_array($campanha->status, ['rascunho', 'agendada']), 422, 'Campanha não pode ser cancelada.');

        $campanha->update(['status' => 'cancelada']);

        return redirect()->route('whatsapp.optin.campanhas')->with('success', 'Campanha cancelada.');
    }

    public function status(Campanha $campanha)
    {
        abort_if($campanha->tenant_id !== auth()->user()->tenant_id, 403);

        $campanha->refresh();

        return response()->json([
            'status'         => $campanha->status,
            'total_contatos' => $campanha->total_contatos,
            'total_enviados' => $campanha->total_enviados,
            'total_falhas'   => $campanha->total_falhas,
            'progresso'      => $campanha->progresso(),
        ]);
    }
}
