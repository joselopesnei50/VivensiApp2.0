<?php

namespace App\Observers;

use App\Models\FamilyMember;
use App\Models\Beneficiary;

class FamilyMemberObserver
{
    public function saved(FamilyMember $member): void
    {
        $tenantId = $this->resolveTenantId($member);
        if ($tenantId !== null) {
            BeneficiaryObserver::forgetPortalCache($tenantId);
        }
    }

    public function deleted(FamilyMember $member): void
    {
        $tenantId = $this->resolveTenantId($member);
        if ($tenantId !== null) {
            BeneficiaryObserver::forgetPortalCache($tenantId);
        }
    }

    /**
     * FamilyMember não tem tenant_id próprio — herda via beneficiary_id.
     * Carrega o beneficiary mínimo para descobrir o tenant.
     */
    private function resolveTenantId(FamilyMember $member): ?int
    {
        try {
            $tenantId = Beneficiary::whereKey($member->beneficiary_id)->value('tenant_id');
            return $tenantId ? (int) $tenantId : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
