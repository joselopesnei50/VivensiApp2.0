<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;

class InventoryController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $items = InventoryItem::where('tenant_id', $tenantId)->get();
        
        // Also get some recent movements to show
        $recentMovements = InventoryMovement::with(['item', 'beneficiary', 'project', 'creator'])
                            ->where('tenant_id', $tenantId)
                            ->orderBy('date', 'desc')
                            ->orderBy('created_at', 'desc')
                            ->limit(10)
                            ->get();

        return view('ngo.inventory.index', compact('items', 'recentMovements'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:100',
            'unit' => 'required|string|max:20',
            'minimum_stock' => 'required|numeric|min:0',
            'value_per_unit' => 'nullable|numeric|min:0',
        ]);

        $item = new InventoryItem($request->all());
        $item->tenant_id = auth()->user()->tenant_id;
        $item->quantity = 0; // Starts at 0
        $item->save();

        return redirect()->back()->with('success', 'Item adicionado ao estoque com sucesso!');
    }

    public function update(Request $request, $id)
    {
        $item = InventoryItem::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:100',
            'unit' => 'required|string|max:20',
            'minimum_stock' => 'required|numeric|min:0',
            'value_per_unit' => 'nullable|numeric|min:0',
        ]);

        $item->update($request->all());

        return redirect()->back()->with('success', 'Item atualizado com sucesso!');
    }

    public function destroy($id)
    {
        $item = InventoryItem::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $item->delete();

        return redirect()->back()->with('success', 'Item removido do estoque.');
    }

    public function movement(Request $request, $id)
    {
        $item = InventoryItem::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);

        $request->validate([
            'type' => 'required|in:in,out',
            'quantity' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'beneficiary_id' => 'nullable|exists:beneficiaries,id',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        if ($request->type === 'out' && $item->quantity < $request->quantity) {
            return redirect()->back()->with('error', 'Quantidade insuficiente em estoque!');
        }

        // Adjust quantity
        if ($request->type === 'in') {
            $item->quantity += $request->quantity;
        } else {
            $item->quantity -= $request->quantity;
        }
        $item->save();

        // Register movement
        InventoryMovement::create([
            'tenant_id' => auth()->user()->tenant_id,
            'inventory_item_id' => $item->id,
            'type' => $request->type,
            'quantity' => $request->quantity,
            'date' => $request->date,
            'description' => $request->description,
            'beneficiary_id' => $request->beneficiary_id,
            'project_id' => $request->project_id,
            'created_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Movimentação de estoque registrada com sucesso!');
    }
}
