<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContatoWhatsapp;
use Illuminate\Http\Request;

class ContatoWhatsappController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $query    = ContatoWhatsapp::where('tenant_id', $tenantId);

        if ($request->filled('opt_in')) {
            $query->where('opt_in', (bool) $request->input('opt_in'));
        }
        if ($request->filled('origem')) {
            $query->where('opt_in_origem', $request->input('origem'));
        }
        if ($request->filled('busca')) {
            $query->where(function ($q) use ($request) {
                $q->where('nome', 'like', '%' . $request->input('busca') . '%')
                  ->orWhere('telefone', 'like', '%' . $request->input('busca') . '%');
            });
        }

        $contatos = $query->orderByDesc('opt_in_at')->paginate(30)->withQueryString();

        $stats = [
            'total'   => ContatoWhatsapp::where('tenant_id', $tenantId)->count(),
            'ativos'  => ContatoWhatsapp::where('tenant_id', $tenantId)->ativos()->count(),
            'opt_out' => ContatoWhatsapp::where('tenant_id', $tenantId)->where('opt_out', true)->count(),
        ];

        return view('admin.whatsapp.optin.index', compact('contatos', 'stats'));
    }

    public function removerOptIn(ContatoWhatsapp $contato)
    {
        $this->autorizarContato($contato);
        $contato->registrarOptOut();

        return redirect()->back()->with('success', 'Opt-in removido com sucesso.');
    }

    public function registrarManual(Request $request)
    {
        $request->validate([
            'telefone' => 'required|string|max:20',
            'nome'     => 'nullable|string|max:255',
        ]);

        $tenantId = auth()->user()->tenant_id;

        $contato = ContatoWhatsapp::firstOrCreate(
            ['tenant_id' => $tenantId, 'telefone' => $request->input('telefone')],
            ['nome' => $request->input('nome')]
        );

        $contato->registrarOptIn('formulario');

        return redirect()->back()->with('success', 'Contato registrado com opt-in via formulário.');
    }

    private function autorizarContato(ContatoWhatsapp $contato): void
    {
        abort_if($contato->tenant_id !== auth()->user()->tenant_id, 403);
    }
}
