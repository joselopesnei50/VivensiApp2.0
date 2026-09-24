<?php

namespace App\Http\Controllers;

use App\Models\CatalogProduct;
use Illuminate\Http\Request;

class CatalogProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:access-personal');
    }

    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = CatalogProduct::where('tenant_id', $tenantId);

        if ($q = trim((string) $request->input('q', ''))) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($type = $request->input('type')) {
            if (array_key_exists($type, CatalogProduct::TYPES)) {
                $query->where('type', $type);
            }
        }

        if ($category = trim((string) $request->input('category', ''))) {
            $query->where('category', $category);
        }

        if ($request->boolean('inactive')) {
            $query->where('active', false);
        } else {
            $query->where('active', true);
        }

        $items = $query->orderBy('name')->paginate(20)->withQueryString();

        $stats = [
            'products' => CatalogProduct::where('tenant_id', $tenantId)->products()->active()->count(),
            'services' => CatalogProduct::where('tenant_id', $tenantId)->services()->active()->count(),
            'inactive' => CatalogProduct::where('tenant_id', $tenantId)->where('active', false)->count(),
        ];

        $categories = CatalogProduct::where('tenant_id', $tenantId)
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('personal.catalog.index', compact('items', 'stats', 'categories'));
    }

    public function create()
    {
        $tenantId = auth()->user()->tenant_id;
        $categories = CatalogProduct::where('tenant_id', $tenantId)
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('personal.catalog.create', compact('categories'));
    }

    private function validationRules(?CatalogProduct $item = null): array
    {
        $tenantId = auth()->user()->tenant_id;
        $skuUnique = 'unique:catalog_products,sku,'
            . ($item?->id ?? 'NULL')
            . ',id,tenant_id,' . $tenantId
            . ',deleted_at,NULL';

        return [
            'name'           => 'required|string|max:200',
            'type'           => 'required|in:' . implode(',', array_keys(CatalogProduct::TYPES)),
            'sku'            => 'nullable|string|max:60|' . $skuUnique,
            'description'    => 'nullable|string|max:2000',
            'unit_price'     => 'required|numeric|min:0|max:99999999.99',
            'unit'           => 'required|string|max:20',
            'category'       => 'nullable|string|max:60',
            'stock_quantity' => 'nullable|integer|min:0',
            'track_stock'    => 'nullable|boolean',
            'active'         => 'nullable|boolean',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules());

        $data = $this->normalizeInput($validated, $request);
        $data['tenant_id'] = auth()->user()->tenant_id;

        CatalogProduct::create($data);

        return redirect()->route('catalog.index')->with('success', 'Item cadastrado no catálogo.');
    }

    public function show(CatalogProduct $catalog)
    {
        abort_unless($catalog->tenant_id === auth()->user()->tenant_id, 403);
        return view('personal.catalog.show', ['item' => $catalog]);
    }

    public function edit(CatalogProduct $catalog)
    {
        abort_unless($catalog->tenant_id === auth()->user()->tenant_id, 403);
        $categories = CatalogProduct::where('tenant_id', $catalog->tenant_id)
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
        return view('personal.catalog.edit', ['item' => $catalog, 'categories' => $categories]);
    }

    public function update(Request $request, CatalogProduct $catalog)
    {
        abort_unless($catalog->tenant_id === auth()->user()->tenant_id, 403);

        $validated = $request->validate($this->validationRules($catalog));
        $data = $this->normalizeInput($validated, $request);

        $catalog->update($data);

        return redirect()->route('catalog.show', $catalog)->with('success', 'Item atualizado.');
    }

    public function destroy(CatalogProduct $catalog)
    {
        abort_unless($catalog->tenant_id === auth()->user()->tenant_id, 403);
        $catalog->delete();
        return redirect()->route('catalog.index')->with('success', 'Item removido do catálogo.');
    }

    private function normalizeInput(array $validated, Request $request): array
    {
        $data = $validated;
        $data['track_stock'] = $request->boolean('track_stock');
        $data['active']      = $request->has('active') ? $request->boolean('active') : true;

        if ($data['type'] === 'service' || !$data['track_stock']) {
            $data['stock_quantity'] = null;
        }

        if (isset($data['sku'])) {
            $data['sku'] = trim($data['sku']) ?: null;
        }
        if (isset($data['category'])) {
            $data['category'] = trim($data['category']) ?: null;
        }

        return $data;
    }
}
