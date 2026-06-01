<?php

namespace App\Jobs;

use App\Models\NgoGrant;
use App\Services\DeepSeekService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateGrantProposalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $backoff = 30;
    public int $timeout = 120;

    public function __construct(private int $grantId, private int $tenantId) {}

    public function handle(): void
    {
        $grant = NgoGrant::where('id', $this->grantId)
            ->where('tenant_id', $this->tenantId)
            ->first();

        if (!$grant) return;

        try {
            $tenant  = \App\Models\Tenant::find($this->tenantId);
            $orgName = $tenant?->corpo_name ?? 'Nossa Organização';

            $prompt = "Aja como um consultor sênior em captação de recursos para o Terceiro Setor.
        Crie um rascunho estruturado de proposta de projeto para o edital abaixo:

        Título do Edital: {$grant->title}
        Órgão Concessor: {$grant->agency}
        Valor solicitado: R$ " . number_format($grant->value, 2, ',', '.') . "
        Notas/Requisitos: {$grant->notes}

        Nome da ONG: {$orgName}

        Estruture a proposta com:
        1. Resumo Executivo
        2. Justificativa e Impacto Social
        3. Objetivos Gerais e Específicos
        4. Metodologia de Execução
        5. Plano de Sustentabilidade

        Use um tom profissional, persuasivo e focado em resultados sociais mensuráveis. Formate em Markdown.";

            $ds       = new DeepSeekService();
            $result   = $ds->chat([['role' => 'user', 'content' => $prompt]]);
            $proposal = $result['choices'][0]['message']['content'] ?? null;

            if (!$proposal) {
                throw new \RuntimeException('DeepSeek retornou resposta vazia para proposta do edital #' . $this->grantId);
            }

            $grant->update([
                'ai_proposal'        => trim($proposal),
                'ai_proposal_status' => 'done',
            ]);
        } catch (\Throwable $e) {
            Log::error("GenerateGrantProposalJob grant #{$this->grantId}: {$e->getMessage()}");
            $grant->update(['ai_proposal_status' => 'failed']);
            throw $e;
        }
    }
}
