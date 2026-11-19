<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SponsorshipDeal;

class SponsorshipDealController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        
        $deals = SponsorshipDeal::where('tenant_id', $tenantId)->get();
        
        // Group deals by stage
        $groupedDeals = [
            'prospecting' => $deals->where('stage', 'prospecting'),
            'meeting_scheduled' => $deals->where('stage', 'meeting_scheduled'),
            'negotiating' => $deals->where('stage', 'negotiating'),
            'won' => $deals->where('stage', 'won'),
            'lost' => $deals->where('stage', 'lost'),
        ];
        
        return view('ngo.sponsorships.index', compact('groupedDeals', 'deals'));
    }

    /** Valor em reais do formulário ("50.000,00", "50000", "50000.00"). */
    public static function parseMoney($input): float
    {
        $v = \App\Support\BrMoney::parse($input === null ? null : (string) $input);
        return is_numeric($v) ? round((float) $v, 2) : 0.0;
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'expected_value' => 'nullable|string|max:30',
        ]);

        SponsorshipDeal::create([
            'tenant_id' => auth()->user()->tenant_id,
            'company_name' => $request->company_name,
            'contact_person' => $request->contact_person,
            'email' => $request->email,
            'phone' => $request->phone,
            'expected_value' => self::parseMoney($request->expected_value),
            'stage' => 'prospecting',
            'contact_date' => $request->contact_date,
            'notes' => $request->notes,
        ]);

        return redirect()->back()->with('success', 'Patrocínio adicionado ao funil!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'company_name'   => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:50',
            'expected_value' => 'nullable|string',
            'contact_date'   => 'nullable|date',
            'notes'          => 'nullable|string',
            'stage'          => 'nullable|in:prospecting,meeting_scheduled,negotiating,won,lost',
        ]);

        $deal = SponsorshipDeal::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);

        $value = self::parseMoney($request->expected_value);

        $deal->update([
            'company_name'   => $request->company_name,
            'contact_person' => $request->contact_person,
            'email'          => $request->email,
            'phone'          => $request->phone,
            'expected_value' => $value,
            'stage'          => $request->stage ?? $deal->stage,
            'contact_date'   => $request->contact_date ?: null,
            'notes'          => $request->notes,
        ]);

        return response()->json(['success' => true]);
    }

    public function updateStage(Request $request, $id)
    {
        $request->validate([
            'stage' => 'required|in:prospecting,meeting_scheduled,negotiating,won,lost'
        ]);

        $deal = SponsorshipDeal::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $deal->update(['stage' => $request->stage]);

        return response()->json(['success' => true, 'message' => 'Estágio atualizado!']);
    }
    
    public function destroy($id)
    {
        $deal = SponsorshipDeal::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $deal->delete();
        
        return redirect()->back()->with('success', 'Negociação removida do funil.');
    }
}
