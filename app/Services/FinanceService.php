<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Mail\PendingExpenseApprovalMail;
use App\Services\DonorRetentionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class FinanceService
{
    // ── Criação de Transação ──────────────────────────────────────────────────

    public function createTransaction(array $data, User $user, $attachment = null): Transaction
    {
        $data = $this->sanitizeCurrency($data, 'amount');

        Validator::make($data, [
            'description' => 'required|string|max:255',
            'amount'      => 'required|numeric|min:0.01',
            'date'        => 'required|date',
            'type'        => 'required|in:income,expense',
            'category_id' => 'nullable|integer',
            'project_id'  => 'nullable|integer',
        ])->validate();

        $isExpense     = ($data['type'] === 'expense');
        $needsApproval = $isExpense && !in_array($user->role, ['manager', 'ngo', 'super_admin'], true);

        $transaction             = new Transaction($data);
        $transaction->tenant_id  = $user->tenant_id;
        $transaction->status     = $needsApproval ? 'pending' : 'paid';
        $transaction->approval_status = $needsApproval ? 'pending' : 'approved';

        if ($attachment) {
            $path = $attachment->store('attachments', 'public');
            $transaction->attachment_path = $path;
            if ($isExpense) {
                $transaction->receipt_path = $path;
            }
        }

        $transaction->save();

        $this->flushCache($user->tenant_id);

        if ($needsApproval) {
            $this->notifyManagersOfPendingExpense($transaction, $user);
        }

        if (!$needsApproval && $transaction->type === 'income' && $transaction->status === 'paid') {
            $this->notifyDonorRetention($transaction);
        }

        return $transaction;
    }

    // ── Aprovação/Rejeição ────────────────────────────────────────────────────

    public function approve(Transaction $transaction, User $approver): Transaction
    {
        $transaction->update([
            'approval_status' => 'approved',
            'status'          => 'paid',
            'approved_by'     => $approver->id,
            'approved_at'     => now(),
        ]);

        $this->flushCache($transaction->tenant_id);
        return $transaction;
    }

    public function reject(Transaction $transaction, User $approver, string $reason = ''): Transaction
    {
        $transaction->update([
            'approval_status' => 'rejected',
            'status'          => 'canceled',
            'approved_by'     => $approver->id,
            'approved_at'     => now(),
            'rejection_reason'=> $reason,
        ]);

        $this->flushCache($transaction->tenant_id);
        return $transaction;
    }

    // ── Resumo Financeiro (com cache) ─────────────────────────────────────────

    public function monthlySummary(int $tenantId): array
    {
        return Cache::remember("finance.monthly.{$tenantId}." . now()->format('Y-m'), 900, function () use ($tenantId) {
            $income = (float) Transaction::where('tenant_id', $tenantId)
                ->where('type', 'income')->where('status', 'paid')
                ->whereMonth('date', now()->month)->whereYear('date', now()->year)
                ->sum('amount');

            $expense = (float) Transaction::where('tenant_id', $tenantId)
                ->where('type', 'expense')->where('status', 'paid')
                ->whereMonth('date', now()->month)->whereYear('date', now()->year)
                ->sum('amount');

            $pending = Transaction::where('tenant_id', $tenantId)
                ->where('approval_status', 'pending')->count();

            return [
                'income'       => $income,
                'expense'      => $expense,
                'balance'      => $income - $expense,
                'pending_count'=> $pending,
            ];
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function flushCache(int $tenantId): void
    {
        Cache::forget("finance.monthly.{$tenantId}." . now()->format('Y-m'));
        Cache::forget("dashboard.manager.stats.{$tenantId}");
        Cache::forget("dashboard.ngo.stats.{$tenantId}");
    }

    private function sanitizeCurrency(array $data, string $field): array
    {
        if (isset($data[$field])) {
            $data[$field] = str_replace('.', '', (string) $data[$field]);
            $data[$field] = str_replace(',', '.', $data[$field]);
        }
        return $data;
    }

    private function notifyManagersOfPendingExpense(Transaction $transaction, User $requester): void
    {
        try {
            User::where('tenant_id', $requester->tenant_id)
                ->whereIn('role', ['manager', 'ngo', 'super_admin'])
                ->whereNotNull('email')
                ->get()
                ->each(fn($m) => Mail::to($m->email)->queue(
                    new PendingExpenseApprovalMail($transaction, $requester)
                ));
        } catch (\Throwable $e) {
            \Log::error('Falha ao notificar gestores sobre despesa: ' . $e->getMessage());
        }
    }

    private function notifyDonorRetention(Transaction $transaction): void
    {
        try {
            (new DonorRetentionService())->notifyDonationReceived($transaction);
        } catch (\Throwable $e) {
            \Log::warning('DonorRetention: ' . $e->getMessage());
        }
    }
}
