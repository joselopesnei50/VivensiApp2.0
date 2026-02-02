<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContractController extends Controller
{
    public function index()
    {
        $contracts = Contract::where('tenant_id', auth()->user()->tenant_id)
                             ->orderBy('created_at', 'desc')
                             ->get();
        return view('ngo.contracts.index', compact('contracts'));
    }

    public function create()
    {
        return view('ngo.contracts.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'signer_name' => 'required|string|max:255',
            'content' => 'required',
        ]);

        $contract = new Contract();
        $contract->tenant_id = auth()->user()->tenant_id;
        $contract->title = $request->title;
        $contract->signer_name = $request->signer_name;
        $contract->signer_email = $request->signer_email;
        $contract->signer_address = $request->signer_address;
        $contract->signer_phone = $request->signer_phone;
        $contract->signer_cpf = $request->signer_cpf;
        $contract->signer_rg = $request->signer_rg;
        $contract->content = $request->content;
        $contract->status = 'sent';
        $contract->token = Str::random(40);
        $contract->save();

        return redirect('/ngo/contracts')->with('success', 'Contrato gerado com sucesso!');
    }

    public function showPublic($token)
    {
        $contract = Contract::where('token', $token)->firstOrFail();
        return view('public.contract_sign', compact('contract'));
    }

    public function sign(Request $request, $token)
    {
        $contract = Contract::where('token', $token)->firstOrFail();
        
        $request->validate([
            'signature' => 'required'
        ]);

        $contract->signature_image = $request->signature;
        $contract->status = 'signed';
        $contract->signer_ip = $request->ip() ?? $_SERVER['REMOTE_ADDR'] ?? 'Desconhecido';
        $contract->signed_at = now();
        $contract->save();

        // Aqui poderiamos disparar notificação, email, etc.
        
        return back()->with('success', 'Contrato assinado com sucesso!');
    }
}
