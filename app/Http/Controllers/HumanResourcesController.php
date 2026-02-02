<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Volunteer;
use App\Models\Project;
use Illuminate\Http\Request;

class HumanResourcesController extends Controller
{
    public function index()
    {
        $tenant_id = auth()->user()->tenant_id;
        
        $employees = Employee::where('tenant_id', $tenant_id)->get();
        $volunteers = Volunteer::where('tenant_id', $tenant_id)->get();
        $projects = Project::where('tenant_id', $tenant_id)->get();

        return view('ngo.hr.index', compact('employees', 'volunteers', 'projects'));
    }

    public function storeEmployee(Request $request)
    {
        $data = $request->all();
        
        // Sanitização de Moeda
        if (isset($data['salary'])) {
             $data['salary'] = str_replace('.', '', $data['salary']);
             $data['salary'] = str_replace(',', '.', $data['salary']);
        }

        $validated = $request->validate([
            'name' => 'required|string',
            'position' => 'required|string',
            'contract_type' => 'required|in:clt,pj,trainee,temporary',
            'salary' => 'required|numeric',
            'work_hours_weekly' => 'required|string',
            'hired_at' => 'required|date',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $employee = new Employee($validated);
        $employee->tenant_id = auth()->user()->tenant_id;
        $employee->save();

        return redirect()->back()->with('success', 'Funcionário cadastrado com sucesso!');
    }

    public function storeVolunteer(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'skills' => 'nullable|string',
            'availability' => 'nullable|in:morning,afternoon,night,weekends',
        ]);

        $volunteer = new Volunteer($validated);
        $volunteer->tenant_id = auth()->user()->tenant_id;
        $volunteer->save();

        return redirect()->back()->with('success', 'Voluntário cadastrado com sucesso!');
    }
}
