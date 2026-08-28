<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Notification;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class AdesaoController extends Controller
{
    public function show()
    {
        $tenant = $this->currentTenant();
        $plan   = $tenant->plan;

        return view('adesao.show', compact('tenant', 'plan'));
    }

    public function storeData(Request $request)
    {
        $tenant = $this->currentTenant();

        $validated = $request->validate([
            'razao_social'      => 'required|string|max:255',
            'document'          => 'required|string|max:32',
            'endereco'          => 'required|string|max:255',
            'numero_endereco'   => 'required|string|max:20',
            'complemento'       => 'nullable|string|max:255',
            'bairro'            => 'required|string|max:255',
            'cidade'            => 'required|string|max:255',
            'estado'            => 'required|string|size:2',
            'cep'               => 'required|string|max:9',
            'signer_name'       => 'required|string|max:255',
            'signer_email'      => 'required|email|max:255',
            'signer_cpf'        => 'required|string|max:32',
        ]);

        $validated['document'] = preg_replace('/\D/', '', $validated['document']);
        $validated['cep']      = preg_replace('/\D/', '', $validated['cep']);
        $validated['estado']   = strtoupper($validated['estado']);

        $tenant->fill([
            'razao_social'    => $validated['razao_social'],
            'document'        => $validated['document'],
            'endereco'        => $validated['endereco'],
            'numero_endereco' => $validated['numero_endereco'],
            'complemento'     => $validated['complemento'] ?? null,
            'bairro'          => $validated['bairro'],
            'cidade'          => $validated['cidade'],
            'estado'          => $validated['estado'],
            'cep'             => $validated['cep'],
        ])->save();

        Cache::forget("tenant.{$tenant->id}");

        $contract = $this->upsertAdesaoContract($tenant, [
            'signer_name'  => $validated['signer_name'],
            'signer_email' => $validated['signer_email'],
            'signer_cpf'   => preg_replace('/\D/', '', $validated['signer_cpf']),
        ]);

        return redirect()->route('adesao.review', $contract->id)
            ->with('success', 'Dados salvos. Revise o contrato e assine abaixo.');
    }

    public function review($contractId)
    {
        $tenant   = $this->currentTenant();
        $contract = Contract::where('tenant_id', $tenant->id)
            ->where('kind', 'adesao')
            ->findOrFail($contractId);
        $plan     = $tenant->plan;

        return view('adesao.review', compact('tenant', 'contract', 'plan'));
    }

    public function sign(Request $request, $contractId)
    {
        $tenant   = $this->currentTenant();
        $contract = Contract::where('tenant_id', $tenant->id)
            ->where('kind', 'adesao')
            ->findOrFail($contractId);
        $plan     = $tenant->plan;

        if ($contract->status === 'signed') {
            return $this->redirectAfterSign($tenant, $plan);
        }

        $request->validate(['signature' => 'required|string|max:1000000']);

        $raw = (string) $request->input('signature');
        if (!preg_match('~^data:image/png;base64,([A-Za-z0-9+/=]+)$~', $raw, $m)) {
            return back()->withErrors(['signature' => 'Assinatura invalida. Tente assinar novamente.']);
        }
        $b64 = $m[1];
        if (strlen($b64) < 2000) {
            return back()->withErrors(['signature' => 'Assinatura muito curta. Assine novamente.']);
        }
        $bytes = base64_decode($b64, true);
        if ($bytes === false || strlen($bytes) > 300_000) {
            return back()->withErrors(['signature' => 'Assinatura invalida.']);
        }

        $contract->content         = $this->renderContractHtml($tenant, $plan, $contract);
        $contract->document_hash   = hash_hmac('sha256', $contract->content . '|' . $contract->tenant_id, (string) config('app.key'));
        $contract->signature_image = 'data:image/png;base64,' . base64_encode($bytes);
        $contract->signature_hash  = hash_hmac('sha256', $bytes . '|' . $contract->document_hash, (string) config('app.key'));
        $contract->signer_ip       = $request->ip() ?? 'Desconhecido';
        $contract->signer_user_agent = substr((string) $request->userAgent(), 0, 255) ?: null;
        $contract->status          = 'signed';
        $contract->signed_at       = now();
        $contract->save();

        $tenant->contract_signed_at = now();

        // Cortesia libera na hora; pago vai pro checkout.
        if ($plan && $plan->is_courtesy) {
            $tenant->subscription_status = 'active';
        } else {
            $tenant->subscription_status = 'pending';
        }
        $tenant->save();
        Cache::forget("tenant.{$tenant->id}");

        // Bell notification pros usuarios do tenant (fail-safe).
        try {
            $recipients = User::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', 'active')
                ->where('role', '!=', 'super_admin')
                ->pluck('id');
            foreach ($recipients as $userId) {
                Notification::create([
                    'user_id' => $userId,
                    'title'   => 'Contrato de adesao assinado',
                    'message' => "Contrato de adesao Vivensi assinado por {$contract->signer_name}.",
                    'type'    => 'success',
                    'link'    => route('adesao.download', $contract->id),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('AdesaoController::sign notification failed: ' . $e->getMessage());
        }

        return $this->redirectAfterSign($tenant, $plan);
    }

    public function download($contractId)
    {
        $tenant   = $this->currentTenant();
        $contract = Contract::where('tenant_id', $tenant->id)
            ->where('kind', 'adesao')
            ->findOrFail($contractId);

        $plan = $tenant->plan;
        $html = View::make('adesao.pdf', compact('tenant', 'contract', 'plan'))->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4');

        return $pdf->download("contrato-adesao-vivensi-{$tenant->id}.pdf");
    }

    private function currentTenant(): Tenant
    {
        $user = Auth::user();
        abort_unless($user && $user->tenant_id, 403);
        return Tenant::findOrFail($user->tenant_id);
    }

    private function upsertAdesaoContract(Tenant $tenant, array $signer): Contract
    {
        $contract = Contract::where('tenant_id', $tenant->id)
            ->where('kind', 'adesao')
            ->where('status', '!=', 'signed')
            ->first();

        if (!$contract) {
            $contract = new Contract();
            $contract->tenant_id = $tenant->id;
            $contract->kind      = 'adesao';
            $contract->token     = Str::random(64);
            $contract->status    = 'draft';
        }

        $contract->title         = 'Contrato de Adesao Vivensi SaaS';
        $contract->signer_name   = $signer['signer_name'];
        $contract->signer_email  = $signer['signer_email'];
        $contract->signer_cpf    = $signer['signer_cpf'];
        $contract->signer_address = trim(sprintf(
            '%s, %s%s - %s, %s/%s - CEP %s',
            $tenant->endereco,
            $tenant->numero_endereco,
            $tenant->complemento ? ' (' . $tenant->complemento . ')' : '',
            $tenant->bairro,
            $tenant->cidade,
            $tenant->estado,
            $tenant->cep
        ));
        $contract->content = $this->renderContractHtml($tenant, $tenant->plan, $contract);
        $contract->save();

        return $contract;
    }

    private function renderContractHtml(Tenant $tenant, ?SubscriptionPlan $plan, Contract $contract): string
    {
        $view = $tenant->type === 'ngo'
            ? 'adesao.contract-content-ngo'
            : 'adesao.contract-content';

        return View::make($view, [
            'tenant'   => $tenant,
            'plan'     => $plan,
            'contract' => $contract,
        ])->render();
    }

    private function redirectAfterSign(Tenant $tenant, ?SubscriptionPlan $plan)
    {
        if ($plan && $plan->is_courtesy) {
            return redirect()->route('dashboard')
                ->with('success', 'Contrato assinado! Sua conta cortesia esta ativa.');
        }
        return redirect()->route('checkout.index', ['plan_id' => $tenant->plan_id])
            ->with('success', 'Contrato assinado! Conclua o pagamento para ativar a conta.');
    }
}
