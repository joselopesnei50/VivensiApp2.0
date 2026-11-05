<?php

namespace App\Jobs;

use App\Models\EmailContact;
use App\Models\EmailContactList;
use App\Models\EmailSuppression;
use App\Services\CampaignMail\EmailVerifier;
use App\Services\CampaignMail\VerificationUnavailableException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * "Verificar lista": verifica os contatos em blocos de CHUNK e se re-agenda
 * até o fim. Inválido → status invalid/bounced/unsubscribed (fora de qualquer
 * campanha). Válido/arriscado que estava "invalid" volta a "active".
 */
class VerifyEmailContactListJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const CHUNK = 200;

    public int $tries = 1;
    public int $timeout = 900;

    public function __construct(
        public int $listId,
        public bool $deep = false,
        public int $afterId = 0,
        public ?string $warning = null,
    ) {
    }

    public function handle(EmailVerifier $verifier): void
    {
        $list = EmailContactList::withoutGlobalScopes()->find($this->listId);
        if (!$list || $list->verification_status !== 'running') {
            return;
        }

        $contacts = EmailContact::withoutGlobalScopes()
            ->where('email_contact_list_id', $list->id)
            ->whereIn('status', [EmailContact::STATUS_ACTIVE, EmailContact::STATUS_INVALID])
            ->where('id', '>', $this->afterId)
            ->orderBy('id')
            ->limit(self::CHUNK)
            ->get();

        $deep    = $this->deep;
        $warning = $this->warning;
        $lastId  = $this->afterId;

        foreach ($contacts as $contact) {
            try {
                $r = $verifier->verify($contact->email, $deep);
            } catch (VerificationUnavailableException $e) {
                // Sem permissão na AWS: segue só com a verificação básica
                $deep    = false;
                $warning = $e->getMessage() . ' Foi feita só a verificação básica (gratuita).';
                $r       = $verifier->verify($contact->email, false);
            }

            $contact->forceFill([
                'verification_result' => $r['result'],
                'verification_detail' => $r['detail'] ? mb_substr($r['detail'], 0, 255) : null,
                'verified_at'         => now(),
                'status'              => $this->statusFor($contact->status, $r),
            ])->save();

            $lastId = $contact->id;
        }

        if ($contacts->count() === self::CHUNK) {
            self::dispatch($list->id, $deep, $lastId, $warning);
            return;
        }

        $this->finish($list, $warning);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('VerifyEmailContactListJob falhou', ['list_id' => $this->listId, 'error' => $e->getMessage()]);
        EmailContactList::withoutGlobalScopes()->whereKey($this->listId)->update([
            'verification_status'      => 'failed',
            'verification_finished_at' => now(),
            'verification_summary'     => json_encode(['warning' => 'A verificação parou por um erro. Tente de novo.']),
        ]);
    }

    private function statusFor(string $current, array $r): string
    {
        if ($r['result'] !== EmailVerifier::INVALID) {
            return $current === EmailContact::STATUS_INVALID ? EmailContact::STATUS_ACTIVE : $current;
        }

        return match ($r['suppression']) {
            EmailSuppression::REASON_HARD_BOUNCE => EmailContact::STATUS_BOUNCED,
            EmailSuppression::REASON_COMPLAINT,
            EmailSuppression::REASON_UNSUBSCRIBE => EmailContact::STATUS_UNSUBSCRIBED,
            default                              => EmailContact::STATUS_INVALID,
        };
    }

    private function finish(EmailContactList $list, ?string $warning): void
    {
        $base = EmailContact::withoutGlobalScopes()
            ->where('email_contact_list_id', $list->id)
            ->where('verified_at', '>=', $list->verification_started_at);

        $counts = (clone $base)->selectRaw('verification_result, COUNT(*) as c')
            ->groupBy('verification_result')->pluck('c', 'verification_result');

        $checked = (int) $counts->sum();

        $list->forceFill([
            'verification_status'      => 'done',
            'verification_finished_at' => now(),
            'verification_summary'     => [
                'checked' => $checked,
                'valid'   => (int) ($counts[EmailVerifier::VALID] ?? 0),
                'risky'   => (int) ($counts[EmailVerifier::RISKY] ?? 0),
                'invalid' => (int) ($counts[EmailVerifier::INVALID] ?? 0),
                'warning' => $warning,
            ],
        ])->save();

        Log::info('Lista verificada', ['list_id' => $list->id, 'checked' => $checked, 'deep' => $list->verification_deep]);
    }
}
