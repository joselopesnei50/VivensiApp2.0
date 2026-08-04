<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Billing\InvoiceService;
use Illuminate\Console\Command;

/**
 * Comando de teste manual — força geração de invoice para 1 tenant sem
 * esperar o scheduler do dia 1. Mostra por que pulou (se pular) e o link
 * do checkout AbacatePay (se gerar).
 *
 * Uso:
 *   php artisan invoices:test              # lista candidatos
 *   php artisan invoices:test 28           # gera invoice pro tenant 28
 */
class TestInvoiceGeneration extends Command
{
    protected $signature = 'invoices:test {tenant_id? : ID do tenant. Se omitido, lista candidatos.}';
    protected $description = 'Testa geração de invoice + checkout AbacatePay para 1 tenant (manual).';

    public function handle(InvoiceService $service): int
    {
        $tenantId = $this->argument('tenant_id');

        if (!$tenantId) {
            $this->listCandidates();
            return self::SUCCESS;
        }

        $tenant = Tenant::find((int) $tenantId);
        if (!$tenant) {
            $this->error("Tenant {$tenantId} não existe.");
            return self::FAILURE;
        }
        $tenant->load('plan');

        $this->info("TENANT #{$tenant->id} — {$tenant->name}");
        $this->line("  subscription_status : {$tenant->subscription_status}");
        $this->line("  plan_id             : " . ($tenant->plan_id ?? 'null'));
        $this->line("  plan.name           : " . ($tenant->plan?->name ?? 'null'));
        $this->line("  plan.price          : " . ($tenant->plan?->price ?? 'null'));
        $this->line("  plan.is_courtesy    : " . ($tenant->plan?->is_courtesy ? 'sim' : 'nao'));
        $this->line("  plan.product_id     : " . ($tenant->plan?->abacatepay_product_id ?: 'VAZIO'));
        $this->newLine();

        $reasons = [];
        if ($tenant->subscription_status !== 'active') $reasons[] = "status != 'active'";
        if (!$tenant->plan)                            $reasons[] = 'sem plano';
        if ($tenant->plan?->is_courtesy)               $reasons[] = 'plano cortesia';
        if ((float) ($tenant->plan?->price ?? 0) <= 0) $reasons[] = 'price <= 0';

        if ($reasons) {
            $this->warn('generateForTenant vai PULAR — motivo(s): ' . implode(', ', $reasons));
            return self::SUCCESS;
        }

        $invoice = $service->generateForTenant($tenant);

        if (!$invoice) {
            $this->error('generateForTenant retornou NULL inesperadamente.');
            return self::FAILURE;
        }

        $this->info("INVOICE #{$invoice->id}");
        $this->line("  status              : {$invoice->status}");
        $this->line("  amount_cents        : {$invoice->amount_cents} (R$ " . number_format($invoice->amount_cents / 100, 2, ',', '.') . ")");
        $this->line("  period_start        : {$invoice->period_start}");
        $this->line("  due_date            : {$invoice->due_date}");
        $this->line("  abacatepay_charge_id: " . ($invoice->abacatepay_charge_id ?? 'null'));
        $this->line("  abacatepay_billing  : " . ($invoice->abacatepay_billing_url ?? 'null'));
        $this->newLine();

        if ($invoice->abacatepay_billing_url) {
            $this->info('OK — abra a URL acima no navegador pra ver o checkout PIX da AbacatePay.');
        } else {
            $this->warn('Invoice criada, mas SEM link AbacatePay. Verifique storage/logs/laravel.log (AbacatePay: createCheckout falhou).');
        }

        return self::SUCCESS;
    }

    private function listCandidates(): void
    {
        $rows = [];
        Tenant::where('subscription_status', 'active')
            ->whereNotNull('plan_id')
            ->with('plan')
            ->orderBy('id')
            ->each(function (Tenant $t) use (&$rows) {
                $rows[] = [
                    $t->id,
                    mb_strimwidth($t->name, 0, 40, '…'),
                    $t->plan?->name ?? 'null',
                    $t->plan?->is_courtesy ? 'SIM' : 'nao',
                    number_format(((float) ($t->plan?->price ?? 0)), 2, ',', '.'),
                    $t->plan?->abacatepay_product_id ?: 'VAZIO',
                ];
            });

        if (!$rows) {
            $this->warn('Nenhum tenant active com plano encontrado.');
            return;
        }

        $this->table(['id', 'name', 'plan', 'courtesy', 'price', 'product_id'], $rows);
        $this->newLine();
        $this->info('Escolha um tenant com courtesy=nao, price>0 e product_id != VAZIO e rode:');
        $this->line('  sudo -u www-data php artisan invoices:test <ID>');
    }
}
