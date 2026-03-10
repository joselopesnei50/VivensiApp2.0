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

    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'expected_value' => 'nullable|numeric',
        ]);

        SponsorshipDeal::create([
            'tenant_id' => auth()->user()->tenant_id,
            'company_name' => $request->company_name,
            'contact_person' => $request->contact_person,
            'email' => $request->email,
            'phone' => $request->phone,
            'expected_value' => str_replace(',', '.', str_replace('.', '', $request->expected_value)) ?? 0,
            'stage' => 'prospecting',
            'contact_date' => $request->contact_date,
            'notes' => $request->notes,
        ]);

        return redirect()->back()->with('success', 'Patrocínio adicionado ao funil!');
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
