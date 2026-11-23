<?php

use App\Jobs\ProcessProspect;
use App\Models\Prospect;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

/**
 * Prospecção (2026-11-22): 20 análises de uma busca saíam juntas e o Gemini
 * recusava todas. Agora saem espaçadas, com espera entre tentativas.
 */
uses(RefreshDatabase::class);

function ppProspect(int $tenantId, string $name): Prospect
{
    return Prospect::withoutGlobalScope('tenant')->forceCreate(['tenant_id' => $tenantId, 'company_name' => $name, 'status' => 'raw']);
}

it('análises da mesma busca saem espaçadas', function () {
    Queue::fake();
    $t = Tenant::factory()->create();
    foreach (range(1, 3) as $i) {
        ProcessProspect::dispatchSpread(ppProspect($t->id, "Empresa {$i} (exemplo)"));
    }

    $delays = [];
    Queue::assertPushed(ProcessProspect::class, function ($job) use (&$delays) {
        $delays[] = $job->delay instanceof \DateTimeInterface ? now()->diffInSeconds($job->delay, false) : (int) $job->delay;

        return true;
    });
    sort($delays);
    expect(count($delays))->toBe(3)
        ->and($delays[1] - $delays[0])->toBeGreaterThanOrEqual(ProcessProspect::SPREAD_SECONDS - 1)
        ->and($delays[2] - $delays[1])->toBeGreaterThanOrEqual(ProcessProspect::SPREAD_SECONDS - 1);
});

it('espera antes de tentar de novo', function () {
    $job = new ProcessProspect(ppProspect(Tenant::factory()->create()->id, 'X (exemplo)'));
    expect($job->tries)->toBe(3)->and($job->backoff)->toBe([60, 300]);
});

it('sem chave do Gemini o job termina sem falhar e o lead fica para depois', function () {
    $p = ppProspect(Tenant::factory()->create()->id, 'Sem chave (exemplo)');
    (new ProcessProspect($p))->handle(app(\App\Services\GeminiAnalysisService::class));
    expect($p->fresh()->status)->toBe('raw');
});

it('alerta de jobs falhados mostra o motivo do erro', function () {
    Mail::fake();
    DB::table('failed_jobs')->insert([
        'uuid' => 'u-1', 'connection' => 'database', 'queue' => 'default',
        'payload' => json_encode(['displayName' => 'App\\Jobs\\ProcessProspect']),
        'exception' => "Exception: Bruce AI está offline. Detalhe: 429\n#0 stack", 'failed_at' => now(),
    ]);
    $this->artisan('queue:alert-failed')->expectsOutputToContain('erro: Exception: Bruce AI está offline. Detalhe: 429')->assertSuccessful();
});
