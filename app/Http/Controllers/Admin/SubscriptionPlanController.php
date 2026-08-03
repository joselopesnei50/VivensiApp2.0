<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::orderBy('target_audience')->get();
        return view('admin.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('admin.plans.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                     => 'required|string|max:255',
            'target_audience'          => 'required|in:ngo,manager,common',
            'price'                    => 'required|numeric|min:0',
            'price_yearly'             => 'nullable|numeric|min:0',
            'abacatepay_product_id'    => 'nullable|string|max:255',
            'interval'                 => 'required|in:monthly,yearly',
            'features'                 => 'nullable|array',
            'capabilities'             => 'nullable|array',
            'capabilities.whatsapp_cloud' => 'nullable|boolean',
            'is_active'                => 'boolean',
            'is_courtesy'              => 'boolean',
            'whatsapp_conversations_included' => 'nullable|integer|min:0|max:1000000',
            'whatsapp_extra_pack_size'        => 'nullable|integer|min:0|max:1000000',
            'whatsapp_extra_pack_price_brl'   => 'nullable|numeric|min:0|max:100000',
        ]);

        // Normaliza capabilities pra manter só chaves conhecidas + booleanas.
        // Evita usuário injetar chaves arbitrárias no JSON.
        if (isset($validated['capabilities']) && is_array($validated['capabilities'])) {
            $known = ['whatsapp_cloud'];
            $validated['capabilities'] = array_intersect_key(
                array_map(fn ($v) => (bool) $v, $validated['capabilities']),
                array_flip($known)
            );
        }

        SubscriptionPlan::create($validated);

        return redirect()->route('admin.plans.index')->with('success', 'Plano criado com sucesso!');
    }

    public function edit(SubscriptionPlan $plan)
    {
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, SubscriptionPlan $plan)
    {
        $validated = $request->validate([
            'name'                     => 'required|string|max:255',
            'target_audience'          => 'required|in:ngo,manager,common',
            'price'                    => 'required|numeric|min:0',
            'price_yearly'             => 'nullable|numeric|min:0',
            'abacatepay_product_id'    => 'nullable|string|max:255',
            'interval'                 => 'required|in:monthly,yearly',
            'features'                 => 'nullable|array',
            'capabilities'             => 'nullable|array',
            'capabilities.whatsapp_cloud' => 'nullable|boolean',
            'is_active'                => 'boolean',
            'is_courtesy'              => 'boolean',
            'whatsapp_conversations_included' => 'nullable|integer|min:0|max:1000000',
            'whatsapp_extra_pack_size'        => 'nullable|integer|min:0|max:1000000',
            'whatsapp_extra_pack_price_brl'   => 'nullable|numeric|min:0|max:100000',
        ]);

        // Normaliza capabilities pra manter só chaves conhecidas + booleanas.
        // Evita usuário injetar chaves arbitrárias no JSON.
        if (isset($validated['capabilities']) && is_array($validated['capabilities'])) {
            $known = ['whatsapp_cloud'];
            $validated['capabilities'] = array_intersect_key(
                array_map(fn ($v) => (bool) $v, $validated['capabilities']),
                array_flip($known)
            );
        }

        $plan->update($validated);

        return redirect()->route('admin.plans.index')->with('success', 'Plano atualizado com sucesso!');
    }

    public function destroy(SubscriptionPlan $plan)
    {
        $plan->delete();
        return redirect()->route('admin.plans.index')->with('success', 'Plano excluído com sucesso!');
    }
}
