<?php

namespace App\Services;

use App\Models\NgoDonor;
use App\Models\NgoGrant;
use Illuminate\Support\Facades\Cache;

class NgoService
{
    // ── Doadores ─────────────────────────────────────────────────────────────

    public function listDonors(int $tenantId, array $filters = [])
    {
        $query = NgoDonor::where('tenant_id', $tenantId);

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('document', 'like', "%{$s}%")
            );
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->orderBy('name')->paginate(20);
    }

    public function createDonor(array $data, int $tenantId): NgoDonor
    {
        $donor = NgoDonor::create(array_merge($data, ['tenant_id' => $tenantId]));
        $this->flushDonorCache($tenantId);
        return $donor;
    }

    public function updateDonor(NgoDonor $donor, array $data): NgoDonor
    {
        $donor->update($data);
        $this->flushDonorCache($donor->tenant_id);
        return $donor;
    }

    public function donorStats(int $tenantId): array
    {
        return Cache::remember("ngo.donor.stats.{$tenantId}", 600, function () use ($tenantId) {
            return [
                'total'      => NgoDonor::where('tenant_id', $tenantId)->count(),
                'individual' => NgoDonor::where('tenant_id', $tenantId)->where('type', 'individual')->count(),
                'company'    => NgoDonor::where('tenant_id', $tenantId)->where('type', 'company')->count(),
                'government' => NgoDonor::where('tenant_id', $tenantId)->where('type', 'government')->count(),
            ];
        });
    }

    // ── Editais ───────────────────────────────────────────────────────────────

    public function listGrants(int $tenantId, array $filters = [])
    {
        $query = NgoGrant::where('tenant_id', $tenantId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        return $query->orderBy('deadline')->paginate(15);
    }

    public function grantStats(int $tenantId): array
    {
        return Cache::remember("ngo.grant.stats.{$tenantId}", 600, function () use ($tenantId) {
            $grants = NgoGrant::where('tenant_id', $tenantId)->get();
            return [
                'total'      => $grants->count(),
                'open'       => $grants->whereIn('status', ['open', 'in_progress'])->count(),
                'approved'   => $grants->where('status', 'approved')->count(),
                'total_value'=> $grants->where('status', 'approved')->sum('value'),
                'expiring'   => NgoGrant::where('tenant_id', $tenantId)
                    ->whereIn('status', ['open', 'in_progress'])
                    ->where('deadline', '<=', now()->addDays(15))
                    ->count(),
            ];
        });
    }

    // ── Cache ─────────────────────────────────────────────────────────────────

    public function flushDonorCache(int $tenantId): void
    {
        Cache::forget("ngo.donor.stats.{$tenantId}");
        Cache::forget("dashboard.ngo.stats.{$tenantId}");
    }

    public function flushGrantCache(int $tenantId): void
    {
        Cache::forget("ngo.grant.stats.{$tenantId}");
    }
}
