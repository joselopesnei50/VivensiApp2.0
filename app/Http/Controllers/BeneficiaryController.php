<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use App\Models\Attendance;
use Illuminate\Http\Request;

class BeneficiaryController extends Controller
{
    public function index()
    {
        $beneficiaries = Beneficiary::where('tenant_id', auth()->user()->tenant_id)
                                    ->withCount('attendances')
                                    ->paginate(10);
                                    
        return view('ngo.beneficiaries.index', compact('beneficiaries'));
    }

    public function create()
    {
        return view('ngo.beneficiaries.create');
    }

    public function show($id)
    {
        $beneficiary = Beneficiary::where('tenant_id', auth()->user()->tenant_id)
                                  ->where('id', $id)
                                  ->with(['familyMembers', 'attendances.user'])
                                  ->firstOrFail();

        return view('ngo.beneficiaries.show', compact('beneficiary'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'cpf' => 'nullable|string',
            'nis' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        $beneficiary = new Beneficiary($validated);
        $beneficiary->tenant_id = auth()->user()->tenant_id;
        $beneficiary->save();

        return redirect('/ngo/beneficiaries')->with('success', 'Beneficiário cadastrado com sucesso!');
    }

    public function storeAttendance(Request $request, $id)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|string',
            'description' => 'required|string'
        ]);

        $attendance = new Attendance($validated);
        $attendance->tenant_id = auth()->user()->tenant_id;
        $attendance->beneficiary_id = $id;
        $attendance->user_id = auth()->id();
        $attendance->save();

        return redirect()->back()->with('success', 'Atendimento registrado com sucesso!');
    }
}
