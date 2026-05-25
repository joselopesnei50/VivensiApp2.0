<?php

namespace App\Http\Controllers;

use App\Jobs\GeocodeAddressJob;
use App\Mail\DonorPortalMail;
use App\Models\NgoDonor;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NgoDonorController extends Controller
{
    public function index()
    {
        $donors = NgoDonor::where('tenant_id', auth()->user()->tenant_id)
                          ->orderBy('created_at', 'desc')
                          ->paginate(15);
                          
        return view('ngo.donors.index', compact('donors'));
    }

    public function create()
    {
        return view('ngo.donors.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'type' => 'required|in:individual,company,government',
            'document' => 'nullable|string',
            'address' => 'nullable|string|max:255'
        ]);

        $donor = new NgoDonor($validated);
        $donor->tenant_id = auth()->user()->tenant_id;
        $donor->save();

        if (!empty($donor->address)) {
            GeocodeAddressJob::dispatch($donor)->delay(now()->addSeconds(3));
        }

        return redirect('/ngo/donors')->with('success', 'Doador cadastrado com sucesso!');
    }

    public function edit($id)
    {
        $donor = NgoDonor::where('id', $id)
                         ->where('tenant_id', auth()->user()->tenant_id)
                         ->firstOrFail();
                         
        return view('ngo.donors.edit', compact('donor'));
    }

    public function update(Request $request, $id)
    {
        $donor = NgoDonor::where('id', $id)
                         ->where('tenant_id', auth()->user()->tenant_id)
                         ->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'type' => 'required|in:individual,company,government',
            'document' => 'nullable|string',
            'address' => 'nullable|string|max:255'
        ]);

        $oldAddress = $donor->address;
        $donor->update($validated);

        if (!empty($donor->address) && $donor->address !== $oldAddress) {
            $donor->update(['latitude' => null, 'longitude' => null]);
            GeocodeAddressJob::dispatch($donor->fresh())->delay(now()->addSeconds(3));
        }

        return redirect('/ngo/donors')->with('success', 'Doador atualizado com sucesso!');
    }

    public function destroy($id)
    {
        $donor = NgoDonor::where('id', $id)
                         ->where('tenant_id', auth()->user()->tenant_id)
                         ->firstOrFail();

        $donor->delete();

        return redirect('/ngo/donors')->with('success', 'Doador excluído com sucesso!');
    }

    public function sendPortalEmail($id)
    {
        $tenantId = auth()->user()->tenant_id;
        $donor    = NgoDonor::where('id', $id)->where('tenant_id', $tenantId)->firstOrFail();

        if (empty($donor->email)) {
            return back()->with('error', 'Este doador não tem e-mail cadastrado.');
        }

        $tenant = Tenant::find($tenantId);

        Mail::to($donor->email)->send(new DonorPortalMail($donor, $tenant));

        return back()->with('success', "Portal VIP enviado para {$donor->email} com sucesso!");
    }
}
