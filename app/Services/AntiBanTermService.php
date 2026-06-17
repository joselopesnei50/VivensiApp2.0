<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappAntiBanAcceptance;
use Illuminate\Http\Request;

/**
 * AntiBanTermService — Fase 2 do roadmap (item 4.2).
 *
 * Gerencia o aceite versionado do Termo de Responsabilidade Anti-Ban que o
 * gestor do tenant precisa aceitar ANTES de criar uma instância Evolution.
 *
 * Versão nova em config('whatsapp.anti_ban_terms.current_version') invalida
 * aceites antigos — força reaceite na próxima criação.
 */
class AntiBanTermService
{
    public function currentVersion(): string
    {
        return (string) config('whatsapp.anti_ban_terms.current_version', '1.0');
    }

    /**
     * @return array{title:string,text:string,effective_at:?string}|null
     */
    public function termFor(string $version): ?array
    {
        // Pega o array de versões inteiro e indexa pelo nome — não usa dot
        // notation porque o Laravel Config interpreta '1.0' como caminho
        // aninhado (1 -> 0) e perde o termo.
        $versions = (array) config('whatsapp.anti_ban_terms.versions', []);
        $term     = $versions[$version] ?? null;

        if (!is_array($term) || empty($term['text'])) {
            return null;
        }
        return [
            'title'        => (string) ($term['title'] ?? 'Termo de Responsabilidade'),
            'text'         => (string) $term['text'],
            'effective_at' => $term['effective_at'] ?? null,
        ];
    }

    /**
     * @return array{title:string,text:string,effective_at:?string}|null
     */
    public function currentTerm(): ?array
    {
        return $this->termFor($this->currentVersion());
    }

    /**
     * Hash determinístico do texto do termo. Vai pra coluna terms_hash do
     * aceite — permite provar, depois, que o termo mostrado naquele momento
     * é exatamente o que está versionado hoje.
     *
     * Texto é normalizado antes do hash (trim + colapso de whitespace) pra
     * que retoques cosméticos no config (indentação, EOL) não invalidem
     * aceites antigos. Mudança real de wording continua invalidando.
     */
    public function hashFor(string $version): ?string
    {
        $term = $this->termFor($version);
        if ($term === null) {
            return null;
        }
        $normalized = trim(preg_replace('/\s+/u', ' ', $term['text']));
        return hash('sha256', $normalized);
    }

    public function currentHash(): ?string
    {
        return $this->hashFor($this->currentVersion());
    }

    public function latestAcceptance(Tenant $tenant): ?WhatsappAntiBanAcceptance
    {
        return WhatsappAntiBanAcceptance::where('tenant_id', $tenant->id)
            ->orderByDesc('accepted_at')
            ->first();
    }

    /**
     * O tenant aceitou a versão vigente do termo? Decisão é por (tenant, versão)
     * — versão nova invalida aceites antigos (re-prompt automático).
     */
    public function hasAcceptedCurrent(Tenant $tenant): bool
    {
        return WhatsappAntiBanAcceptance::where('tenant_id', $tenant->id)
            ->where('version', $this->currentVersion())
            ->exists();
    }

    /**
     * Registra o aceite da versão vigente. Idempotente — re-aceitar a mesma
     * versão devolve o registro existente (a unique key (tenant, version)
     * impede duplicidade).
     */
    public function accept(User $user, Request $request): WhatsappAntiBanAcceptance
    {
        $version = $this->currentVersion();
        $hash    = $this->currentHash();

        if ($hash === null) {
            throw new \RuntimeException("Termo anti-ban versão {$version} não encontrado em config('whatsapp.anti_ban_terms').");
        }

        return WhatsappAntiBanAcceptance::firstOrCreate(
            [
                'tenant_id' => $user->tenant_id,
                'version'   => $version,
            ],
            [
                'user_id'     => $user->id,
                'terms_hash'  => $hash,
                'ip_address'  => $request->ip(),
                'user_agent'  => mb_substr((string) $request->userAgent(), 0, 1000),
                'accepted_at' => now(),
            ]
        );
    }
}
