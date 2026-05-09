<?php

namespace App\Mail;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PendingExpenseApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public Transaction $transaction;
    public User $submittedBy;

    public function __construct(Transaction $transaction, User $submittedBy)
    {
        $this->transaction  = $transaction;
        $this->submittedBy  = $submittedBy;
    }

    public function build(): self
    {
        return $this->subject('⏳ Despesa aguardando sua aprovação — Vivensi')
                    ->view('emails.pending_expense_approval');
    }
}
