<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\UpdatePerfilOperacionalRequest;
use App\Models\AuditLog;
use App\Models\TenantOperationalProfile;
use App\Services\PerfilOperacionalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * PerfilOperacionalController — Fase 1, Etapa B do roadmap.
 *
 * Tela de edição do Perfil Operacional do tenant atual. Mudança de categoria
 * é auditada (LGPD/eleitoral): trocar de 'outro' para 'campanha_eleitoral'
 * altera o comportamento legal do Bruce, então quem fez, quando e o que
 * mudou ficam registrados em audit_logs.
 */
class PerfilOperacionalController extends Controller
{
    public function __construct(private PerfilOperacionalService $service)
    {
    }

    public function edit(Request $request)
    {
        $tenant  = $this->resolveTenant($request);
        $profile = $this->service->get($tenant);

        return view('manager.perfil-operacional.edit', [
            'profile'    => $profile,
            'categorias' => PerfilOperacionalService::categorias(),
            'tenant'     => $tenant,
        ]);
    }

    public function update(UpdatePerfilOperacionalRequest $request)
    {
        $tenant   = $this->resolveTenant($request);
        $existing = $this->service->get($tenant);
        $data     = $request->validated();

        DB::transaction(function () use ($tenant, $existing, $data, $request): void {
            $oldCategoria = $existing?->categoria ?? TenantOperationalProfile::CATEGORIA_OUTRO;
            $oldInstrucao = $existing?->instrucao;

            $profile = TenantOperationalProfile::updateOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'categoria'   => $data['categoria'],
                    'instrucao'   => $data['instrucao']   ?? null,
                    'vocabulario' => $data['vocabulario'] ?? null,
                ]
            );

            // Audit log — guarda só o que mudou; sem PII (já bloqueada na request).
            $changed = [];
            if ($oldCategoria !== $profile->categoria) {
                $changed['categoria'] = ['from' => $oldCategoria, 'to' => $profile->categoria];
            }
            if (($oldInstrucao ?? '') !== ($profile->instrucao ?? '')) {
                $changed['instrucao'] = [
                    'from_len' => mb_strlen((string) $oldInstrucao),
                    'to_len'   => mb_strlen((string) $profile->instrucao),
                ];
            }

            if (!empty($changed)) {
                AuditLog::create([
                    'tenant_id'      => $tenant->id,
                    'user_id'        => $request->user()->id,
                    'event'          => 'tenant_operational_profile.updated',
                    'auditable_type' => TenantOperationalProfile::class,
                    'auditable_id'   => $profile->id,
                    'old_values'     => null,
                    'new_values'     => $changed,
                    'ip_address'     => $request->ip(),
                    'user_agent'     => mb_substr((string) $request->userAgent(), 0, 1000),
                    'url'            => $request->fullUrl(),
                ]);
            }
        });

        return redirect()
            ->route('manager.perfil_operacional.edit')
            ->with('status', 'Perfil operacional atualizado.');
    }

    /**
     * Resolve o tenant da sessão atual. Mantém isolamento — nunca aceita
     * tenant_id por input do usuário.
     */
    private function resolveTenant(Request $request)
    {
        $user = $request->user();
        abort_if($user === null || $user->tenant === null, 403, 'Usuário sem tenant ativo.');
        return $user->tenant;
    }
}
