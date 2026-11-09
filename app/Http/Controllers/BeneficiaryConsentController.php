<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use App\Models\TransparencyPortal;
use App\Services\ConsentService;
use App\Support\AuditDownload;
use Illuminate\Http\Request;

/**
 * Termo de consentimento LGPD do beneficiário
 * (rotas /ngo/beneficiaries/{id}/consent...).
 *
 *  - term:   PDF do termo para imprimir e colher a assinatura;
 *  - store:  registra que o termo foi assinado (titular ou responsável legal);
 *  - revoke: registra a revogação do consentimento.
 */
class BeneficiaryConsentController extends Controller
{
    public function __construct(private ConsentService $consents)
    {
    }

    public function term($id)
    {
        $beneficiary = $this->findBeneficiary($id);
        $tenant = auth()->user()->tenant;
        $portal = TransparencyPortal::where('tenant_id', $tenant->id)->first();

        AuditDownload::log('Beneficiaries:ConsentTerm', (int) $beneficiary->id, ['format' => 'pdf']);

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadView('ngo.beneficiaries.consent_term_pdf', [
            'beneficiary' => $beneficiary,
            'minor'       => $this->consents->isMinor($beneficiary),
            'orgName'     => $tenant->brand_name ?: ($tenant->razao_social ?: $tenant->name),
            'orgDocument' => $this->formatCnpj($tenant->document ?: ($portal->cnpj ?? null)),
            'orgCity'     => $tenant->cidade,
            'contactEmail' => $portal->sic_email ?? null,
            'contactPhone' => $portal->sic_phone ?? null,
            'version'     => ConsentService::TERM_VERSION,
        ]);

        return $pdf->download('termo-consentimento-' . \Illuminate\Support\Str::slug($beneficiary->name) . '.pdf');
    }

    public function store(Request $request, $id)
    {
        $beneficiary = $this->findBeneficiary($id);
        $validated = $request->validate(
            $this->consents->rules($beneficiary, $this->consents->isMinor($beneficiary)),
            [],
            ['signer_name' => 'nome de quem assinou', 'signer_role' => 'quem assinou', 'signed_at' => 'data da assinatura']
        );

        $this->consents->grant($beneficiary, $validated, auth()->id());

        return redirect()->back()->with('success', 'Assinatura do termo de consentimento registrada.');
    }

    public function revoke(Request $request, $id)
    {
        $beneficiary = $this->findBeneficiary($id);
        $validated = $request->validate(['notes' => 'nullable|string|max:1000']);

        if ($beneficiary->consent_at === null) {
            return redirect()->back()->with('error', 'Este beneficiário não tem termo assinado para revogar.');
        }

        $this->consents->revoke($beneficiary, $validated['notes'] ?? null, auth()->id());

        return redirect()->back()->with('success', 'Revogação registrada. Combine com a coordenação quais dados podem continuar guardados por obrigação legal ou de prestação de contas.');
    }

    private function formatCnpj(?string $doc): ?string
    {
        $d = preg_replace('/\D/', '', (string) $doc);
        return strlen($d) === 14
            ? substr($d, 0, 2) . '.' . substr($d, 2, 3) . '.' . substr($d, 5, 3) . '/' . substr($d, 8, 4) . '-' . substr($d, 12)
            : ($doc ?: null);
    }

    private function findBeneficiary($id): Beneficiary
    {
        return Beneficiary::where('tenant_id', auth()->user()->tenant_id)->where('id', $id)->firstOrFail();
    }
}
