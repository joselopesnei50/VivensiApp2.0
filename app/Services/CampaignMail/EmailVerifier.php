<?php

namespace App\Services\CampaignMail;

use App\Models\EmailSuppression;
use Aws\Exception\AwsException;
use Aws\SesV2\SesV2Client;

/**
 * Verifica um endereço antes de mandar campanha para ele.
 *
 * Básica (gratuita): formato, bloqueio anterior (bounce/spam/descadastro)
 * e se o domínio ainda recebe e-mail (registro MX ou A).
 *
 * Aprofundada (opcional, US$ 0,01 por endereço na AWS): SES
 * GetEmailAddressInsights — diz se a caixa provavelmente existe, se é
 * descartável etc. Exige a permissão IAM ses:GetEmailAddressInsights.
 */
class EmailVerifier
{
    public const VALID   = 'valid';
    public const RISKY   = 'risky';
    public const INVALID = 'invalid';

    /** @var array<string,bool> cache de domínios por execução */
    private array $domainCache = [];

    /** @var callable(string): bool */
    private $domainCheck;

    private ?object $ses = null;

    public function __construct(?callable $domainCheck = null, ?object $sesClient = null)
    {
        $this->domainCheck = $domainCheck ?? static fn (string $d): bool => checkdnsrr($d, 'MX') || checkdnsrr($d, 'A');
        $this->ses = $sesClient;
    }

    /**
     * @return array{result: string, detail: ?string, suppression: ?string}
     *
     * @throws VerificationUnavailableException quando a verificação aprofundada não pode ser feita (permissão/credencial)
     */
    public function verify(string $email, bool $deep = false): array
    {
        $email = EmailSuppression::normalizeEmail($email);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->out(self::INVALID, 'Formato de e-mail inválido');
        }

        $suppressed = EmailSuppression::where('email', $email)->value('reason');
        if ($suppressed) {
            $labels = [
                EmailSuppression::REASON_HARD_BOUNCE => 'Bounce em envio anterior',
                EmailSuppression::REASON_COMPLAINT   => 'Marcou como spam em envio anterior',
                EmailSuppression::REASON_UNSUBSCRIBE => 'Pediu descadastro',
            ];
            return $this->out(self::INVALID, $labels[$suppressed] ?? 'Bloqueado manualmente', $suppressed);
        }

        $domain = substr(strrchr($email, '@'), 1);
        if (!$this->domainAcceptsMail($domain)) {
            return $this->out(self::INVALID, 'O domínio não recebe e-mails (não existe ou está fora do ar)');
        }

        if (!$deep) {
            return $this->out(self::VALID, null);
        }

        return $this->deepCheck($email);
    }

    private function domainAcceptsMail(string $domain): bool
    {
        if (!array_key_exists($domain, $this->domainCache)) {
            $this->domainCache[$domain] = (bool) ($this->domainCheck)($domain);
        }
        return $this->domainCache[$domain];
    }

    private function deepCheck(string $email): array
    {
        try {
            $res = $this->sesClient()->getEmailAddressInsights(['EmailAddress' => $email]);
        } catch (AwsException $e) {
            $code = (string) $e->getAwsErrorCode();
            if (in_array($code, ['AccessDeniedException', 'AccessDenied', 'UnrecognizedClientException', 'InvalidClientTokenId', 'SignatureDoesNotMatch', 'NotAuthorized'], true)
                || str_contains(mb_strtolower($e->getMessage()), 'not authorized')) {
                throw new VerificationUnavailableException('Sem permissão na AWS para a validação aprofundada (ses:GetEmailAddressInsights).', 0, $e);
            }
            // Erro pontual (limite, rede): não derruba a lista — fica como verificado só no básico
            return $this->out(self::VALID, 'Validação AWS indisponível para este endereço; verificado só no básico');
        }

        $mv    = $res['MailboxValidation'] ?? [];
        $ev    = $mv['Evaluations'] ?? [];
        $level = fn (?array $v) => $v['ConfidenceVerdict'] ?? null;

        $isValid = $level($mv['IsValid'] ?? null);

        if ($isValid === 'HIGH') {
            return $this->out(self::VALID, null);
        }

        $reasons = [];
        if ($level($ev['MailboxExists'] ?? null) === 'LOW')       $reasons[] = 'caixa de e-mail provavelmente não existe';
        if ($level($ev['HasValidDnsRecords'] ?? null) === 'LOW')  $reasons[] = 'domínio sem configuração de e-mail';
        if ($level($ev['IsDisposable'] ?? null) === 'HIGH')       $reasons[] = 'endereço descartável';
        if ($level($ev['IsRandomInput'] ?? null) === 'HIGH')      $reasons[] = 'parece digitado aleatoriamente';
        if ($level($ev['IsRoleAddress'] ?? null) === 'HIGH')      $reasons[] = 'endereço genérico (ex.: contato@, financeiro@)';

        if ($isValid === 'LOW') {
            return $this->out(self::INVALID, 'AWS: ' . ($reasons ? implode('; ', $reasons) : 'baixa confiança de entrega'));
        }

        return $this->out(self::RISKY, 'AWS: confiança média' . ($reasons ? ' — ' . implode('; ', $reasons) : ''));
    }

    private function sesClient(): object
    {
        if ($this->ses) {
            return $this->ses;
        }
        if (app()->bound('campaign.sesv2')) {
            return $this->ses = app('campaign.sesv2');
        }

        $cfg  = config('mail.mailers.campaign_ses', []);
        $args = ['version' => 'latest', 'region' => $cfg['region'] ?? 'us-east-1'];
        if (!empty($cfg['key']) && !empty($cfg['secret'])) {
            $args['credentials'] = ['key' => $cfg['key'], 'secret' => $cfg['secret']];
        }

        return $this->ses = new SesV2Client($args);
    }

    private function out(string $result, ?string $detail, ?string $suppression = null): array
    {
        return ['result' => $result, 'detail' => $detail, 'suppression' => $suppression];
    }
}
