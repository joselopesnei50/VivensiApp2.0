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
            'name'                 => 'required|string|max:255',
            'email'                => 'nullable|email',
            'phone'                => 'nullable|string',
            'type'                 => 'required|in:individual,company,government',
            'document'             => 'nullable|string',
            'address_zip'          => 'nullable|string|max:10',
            'address_street'       => 'nullable|string|max:255',
            'address_number'       => 'nullable|string|max:20',
            'address_complement'   => 'nullable|string|max:100',
            'address_neighborhood' => 'nullable|string|max:255',
            'address_city'         => 'nullable|string|max:255',
            'address_state'        => 'nullable|string|max:2',
        ]);

        $validated['address'] = $this->composeAddress($validated);

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
            'name'                 => 'required|string|max:255',
            'email'                => 'nullable|email',
            'phone'                => 'nullable|string',
            'type'                 => 'required|in:individual,company,government',
            'document'             => 'nullable|string',
            'address_zip'          => 'nullable|string|max:10',
            'address_street'       => 'nullable|string|max:255',
            'address_number'       => 'nullable|string|max:20',
            'address_complement'   => 'nullable|string|max:100',
            'address_neighborhood' => 'nullable|string|max:255',
            'address_city'         => 'nullable|string|max:255',
            'address_state'        => 'nullable|string|max:2',
        ]);

        $oldAddress = $donor->address;
        $validated['address'] = $this->composeAddress($validated);
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

    private function composeAddress(array $data): ?string
    {
        $parts = array_filter([
            trim($data['address_street'] ?? ''),
            trim($data['address_number'] ?? ''),
            trim($data['address_complement'] ?? ''),
            trim($data['address_neighborhood'] ?? ''),
            trim($data['address_city'] ?? ''),
            trim($data['address_state'] ?? ''),
            trim($data['address_zip'] ?? ''),
        ]);

        if (empty($parts)) return null;

        $street = trim(($data['address_street'] ?? '') . ', ' . ($data['address_number'] ?? ''), ', ');
        $complement = trim($data['address_complement'] ?? '');
        $neighborhood = trim($data['address_neighborhood'] ?? '');
        $city = trim($data['address_city'] ?? '');
        $state = trim($data['address_state'] ?? '');
        $zip = trim($data['address_zip'] ?? '');

        $line1 = implode(', ', array_filter([$street, $complement, $neighborhood]));
        $line2 = implode(' - ', array_filter([$city, $state]));
        $full  = implode(', ', array_filter([$line1, $line2, $zip, 'Brasil']));

        return $full ?: null;
    }
}
