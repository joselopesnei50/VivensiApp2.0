<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\NgoDonorResource;
use App\Http\Resources\Api\NgoGrantResource;
use App\Models\NgoDonor;
use App\Models\NgoGrant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NgoController extends Controller
{
    // ── Donors ────────────────────────────────────────────────────────────────

    public function donors(Request $request)
    {
        $this->requireNgo($request);

        $query = NgoDonor::where('tenant_id', $request->user()->tenant_id)
            ->when($request->type,   fn ($q) => $q->where('type', $request->type))
            ->when($request->search, fn ($q) => $q->where(function ($q2) use ($request) {
                $q2->where('name', 'like', '%'.$request->search.'%')
                   ->orWhere('email', 'like', '%'.$request->search.'%');
            }))
            ->orderBy('name');

        return NgoDonorResource::collection(
            $query->paginate(min((int) ($request->per_page ?? 20), 100))
        );
    }

    public function showDonor(Request $request, int $id)
    {
        $this->requireNgo($request);
        $donor = NgoDonor::where('tenant_id', $request->user()->tenant_id)->findOrFail($id);
        return new NgoDonorResource($donor);
    }

    public function createDonor(Request $request)
    {
        $this->requireNgo($request);

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'nullable|email|max:255',
            'phone'    => 'nullable|string|max:30',
            'type'     => ['nullable', Rule::in(['individual', 'company', 'government'])],
            'address'  => 'nullable|string|max:500',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $donor = NgoDonor::create($validated);

        return (new NgoDonorResource($donor))->response()->setStatusCode(201);
    }

    // ── Grants ────────────────────────────────────────────────────────────────

    public function grants(Request $request)
    {
        $this->requireNgo($request);

        $query = NgoGrant::where('tenant_id', $request->user()->tenant_id)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('deadline', 'asc');

        return NgoGrantResource::collection(
            $query->paginate(min((int) ($request->per_page ?? 20), 100))
        );
    }

    public function showGrant(Request $request, int $id)
    {
        $this->requireNgo($request);
        $grant = NgoGrant::where('tenant_id', $request->user()->tenant_id)->findOrFail($id);
        return new NgoGrantResource($grant);
    }

    // ── Summary ───────────────────────────────────────────────────────────────

    public function summary(Request $request)
    {
        $this->requireNgo($request);
        $tenantId = $request->user()->tenant_id;

        return response()->json([
            'data' => [
                'donors_total'   => NgoDonor::where('tenant_id', $tenantId)->count(),
                'donors_by_type' => NgoDonor::where('tenant_id', $tenantId)
                    ->selectRaw('type, count(*) as total')
                    ->groupBy('type')
                    ->pluck('total', 'type'),
                'grants_total'   => NgoGrant::where('tenant_id', $tenantId)->count(),
                'grants_by_status' => NgoGrant::where('tenant_id', $tenantId)
                    ->selectRaw('status, count(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status'),
                'grants_value_total' => (float) NgoGrant::where('tenant_id', $tenantId)
                    ->whereIn('status', ['approved', 'executing'])
                    ->sum('value'),
            ],
        ]);
    }

    private function requireNgo(Request $request): void
    {
        $user = $request->user();
        if (!in_array($user->role, ['ngo', 'super_admin'], true)) {
            abort(403, 'Este endpoint é exclusivo para organizações do terceiro setor.');
        }
    }
}
