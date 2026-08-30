<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Support\AuditDownload;

class InventoryController extends Controller
{
    public function __construct()
    {
        // Auditoria 2026-08-29 P2 (media): fecha bypass de role em Almoxarifado.
        // Compartilha gate com AssetController.
        $this->middleware('can:manage-assets');
    }

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
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string|max:2000',
            'sku'            => 'nullable|string|max:100',
            'unit'           => 'required|string|max:20',
            'minimum_stock'  => 'required|numeric|min:0',
            'value_per_unit' => 'nullable|numeric|min:0',
            'expires_at'     => 'nullable|date|after:today',
        ]);

        $item = new InventoryItem($request->validated());
        $item->tenant_id = auth()->user()->tenant_id;
        $item->quantity = 0;
        $item->save();

        return redirect()->back()->with('success', 'Item adicionado ao estoque com sucesso!');
    }

    public function update(Request $request, $id)
    {
        $item = InventoryItem::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        
        $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string|max:2000',
            'sku'            => 'nullable|string|max:100',
            'unit'           => 'required|string|max:20',
            'minimum_stock'  => 'required|numeric|min:0',
            'value_per_unit' => 'nullable|numeric|min:0',
            'expires_at'     => 'nullable|date',
        ]);

        $item->update($request->validated());

        return redirect()->back()->with('success', 'Item atualizado com sucesso!');
    }

    public function destroy($id)
    {
        $item = InventoryItem::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $item->delete();

        return redirect()->back()->with('success', 'Item removido do estoque.');
    }

    public function exportCsv(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        AuditDownload::log('Inventory', null, ['format' => 'csv']);

        return response()->streamDownload(function () use ($tenantId) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['SKU', 'Nome', 'Unidade', 'Quantidade', 'Estoque Mínimo', 'Valor Unit.', 'Validade', 'Descrição']);

            InventoryItem::where('tenant_id', $tenantId)
                ->orderBy('name')
                ->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $item) {
                        fputcsv($out, [
                            $item->sku,
                            $item->name,
                            $item->unit,
                            $item->quantity,
                            $item->minimum_stock,
                            $item->value_per_unit,
                            $item->expires_at?->format('d/m/Y'),
                            $item->description,
                        ]);
                    }
                });

            fclose($out);
        }, 'estoque-' . date('Y-m-d_His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportMovementsCsv(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $from = $request->get('from');
        $to   = $request->get('to');

        AuditDownload::log('InventoryMovements', null, ['format' => 'csv', 'from' => $from, 'to' => $to]);

        return response()->streamDownload(function () use ($tenantId, $from, $to) {
            $out = fopen('php://output', 'w');
            if ($out === false) return;

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Data', 'Item', 'Tipo', 'Quantidade', 'Beneficiário', 'Projeto', 'Descrição', 'Registrado por']);

            $q = InventoryMovement::with(['item', 'beneficiary', 'project', 'creator'])
                ->where('tenant_id', $tenantId)
                ->orderBy('date', 'desc');

            if ($from) $q->whereDate('date', '>=', $from);
            if ($to)   $q->whereDate('date', '<=', $to);

            $q->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $mov) {
                    fputcsv($out, [
                        $mov->date,
                        optional($mov->item)->name,
                        $mov->type === 'in' ? 'Entrada' : 'Saída',
                        $mov->quantity,
                        optional($mov->beneficiary)->name,
                        optional($mov->project)->name,
                        $mov->description,
                        optional($mov->creator)->name,
                    ]);
                }
            });

            fclose($out);
        }, 'movimentacoes-' . date('Y-m-d_His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function movement(Request $request, $id)
    {
        $item = InventoryItem::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);

        $request->validate([
            'type' => 'required|in:in,out',
            'quantity' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'beneficiary_id' => [
                'nullable',
                Rule::exists('beneficiaries', 'id')->where('tenant_id', auth()->user()->tenant_id),
            ],
            'project_id' => [
                'nullable',
                Rule::exists('projects', 'id')->where('tenant_id', auth()->user()->tenant_id),
            ],
        ]);

        try { DB::transaction(function () use ($request, $item) {
            $item = InventoryItem::where('tenant_id', auth()->user()->tenant_id)
                ->lockForUpdate()
                ->findOrFail($item->id);

            if ($request->type === 'out' && $item->quantity < $request->quantity) {
                throw new \RuntimeException('Quantidade insuficiente em estoque!');
            }

            if ($request->type === 'in') {
                $item->quantity += $request->quantity;
            } else {
                $item->quantity -= $request->quantity;
            }
            $item->save();

            InventoryMovement::create([
                'tenant_id'          => auth()->user()->tenant_id,
                'inventory_item_id'  => $item->id,
                'type'               => $request->type,
                'quantity'           => $request->quantity,
                'date'               => $request->date,
                'description'        => $request->description,
                'beneficiary_id'     => $request->beneficiary_id,
                'project_id'         => $request->project_id,
                'created_by'         => auth()->id(),
            ]);
        }); } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Movimentação de estoque registrada com sucesso!');
    }
}
