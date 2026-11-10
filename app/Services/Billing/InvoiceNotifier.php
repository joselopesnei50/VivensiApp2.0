<?php

namespace App\Services\Billing;

use App\Mail\InvoiceNoticeMail;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envia os e-mails da cobrança recorrente para os usuários ativos da
 * organização e registra em invoices.notifications_sent (cada tipo sai uma vez).
 */
class InvoiceNotifier
{
    public function send(Invoice $invoice, string $kind): bool
    {
        $sent = (array) ($invoice->notifications_sent ?? []);
        if (isset($sent[$kind])) {
            return false;
        }
        $tenant = Tenant::find($invoice->tenant_id);
        if (!$tenant) {
            return false;
        }

        $users = User::query()->where('tenant_id', $tenant->id)->where('status', 'active')
            ->where('role', '!=', 'super_admin')->whereNotNull('email')->limit(5)->get(['id', 'name', 'email']);

        $ok = false;
        foreach ($users as $u) {
            try {
                Mail::to($u->email)->send(new InvoiceNoticeMail($invoice, $tenant, $kind, (string) $u->name));
                $ok = true;
            } catch (\Throwable $e) {
                Log::error('Fatura: falha ao enviar e-mail', ['invoice_id' => $invoice->id, 'kind' => $kind, 'error' => $e->getMessage()]);
            }
        }

        $sent[$kind] = now()->toDateTimeString();
        $invoice->forceFill(['notifications_sent' => $sent])->saveQuietly();
        return $ok;
    }

    /** Rotina diária: nova / lembrete (véspera) / vencida / bloqueio. */
    public function runDaily(): array
    {
        $today = now()->startOfDay();
        $count = ['nova' => 0, 'lembrete' => 0, 'vencida' => 0, 'bloqueio' => 0];

        Invoice::withoutGlobalScopes()
            ->whereIn('status', [Invoice::STATUS_OPEN, Invoice::STATUS_OVERDUE])
            ->whereDate('due_date', '>=', InvoiceService::ENFORCE_FROM)
            ->orderBy('id')
            ->chunk(200, function ($invoices) use ($today, &$count) {
                foreach ($invoices as $inv) {
                    $due = $inv->due_date->copy()->startOfDay();
                    $kind = null;
                    if ($due->lte($today->copy()->subDays(InvoiceService::BLOCK_DAYS_AFTER_DUE))) {
                        $kind = 'bloqueio';
                    } elseif ($due->lt($today)) {
                        $kind = 'vencida';
                    } elseif ($due->eq($today->copy()->addDay())) {
                        $kind = 'lembrete';
                    } else {
                        $kind = 'nova';
                    }
                    if ($this->send($inv, $kind)) {
                        $count[$kind]++;
                    }
                }
            });

        return $count;
    }
}
