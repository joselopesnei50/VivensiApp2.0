<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\FamilyMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as RequestFacade;

/**
 * Familiares vinculados a beneficiarios (rota /ngo/beneficiaries/{id}/family-members).
 *
 * Extraido de BeneficiaryController em 2026-07-18. Zero mudanca de logica:
 * mantido o padrao de auditoria manual pois FamilyMember nao carrega
 * tenant_id (o vinculo passa pelo beneficiary_id).
 */
class BeneficiaryFamilyController extends Controller
{
    public function storeFamilyMember(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $beneficiary = Beneficiary::where('tenant_id', $tenantId)->where('id', $id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'kinship' => 'required|string|max:100',
            'birth_date' => 'nullable|date',
        ]);

        $member = new FamilyMember($validated);
        $member->beneficiary_id = $beneficiary->id;
        $member->save();

        // Manual audit (family_members doesn't carry tenant_id)
        try {
            AuditLog::create([
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
                'event' => 'created',
                'auditable_type' => FamilyMember::class,
                'auditable_id' => $member->id,
                'old_values' => null,
                'new_values' => [
                    'beneficiary_id' => $beneficiary->id,
                    'name' => $member->name,
                    'kinship' => $member->kinship,
                    'birth_date' => optional($member->birth_date)->format('Y-m-d'),
                ],
                'ip_address' => RequestFacade::ip(),
                'user_agent' => RequestFacade::userAgent(),
                'url' => RequestFacade::fullUrl(),
            ]);
        } catch (\Throwable $e) {
        }

        return redirect()->back()->with('success', 'Familiar adicionado com sucesso!');
    }

    public function destroyFamilyMember($id, $memberId)
    {
        $tenantId = auth()->user()->tenant_id;
        $beneficiary = Beneficiary::where('tenant_id', $tenantId)->where('id', $id)->firstOrFail();

        $member = FamilyMember::where('beneficiary_id', $beneficiary->id)->where('id', $memberId)->firstOrFail();
        $old = $member->toArray();
        $member->delete();

        try {
            AuditLog::create([
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
                'event' => 'deleted',
                'auditable_type' => FamilyMember::class,
                'auditable_id' => (int) $memberId,
                'old_values' => $old,
                'new_values' => null,
                'ip_address' => RequestFacade::ip(),
                'user_agent' => RequestFacade::userAgent(),
                'url' => RequestFacade::fullUrl(),
            ]);
        } catch (\Throwable $e) {
        }

        return redirect()->back()->with('success', 'Familiar removido.');
    }
}
