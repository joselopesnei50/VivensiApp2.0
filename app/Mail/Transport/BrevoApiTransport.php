<?php

namespace App\Mail\Transport;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\RawMessage;

/**
 * Transport para o Mailer do Laravel/Symfony que envia via API Brevo
 * (POST /v3/smtp/email). Elimina dependência de SMTP — configura em
 * config/mail.php como driver 'brevo' e ajusta MAIL_MAILER no .env.
 *
 * Chave da API vem de SystemSetting.brevo_api_key (mesma fonte usada pelo
 * BrevoService pra emails custom + campanhas).
 */
class BrevoApiTransport extends AbstractTransport
{
    private string $endpoint = 'https://api.brevo.com/v3/smtp/email';

    public function __toString(): string
    {
        return 'brevo+api://';
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $payload = $this->buildPayload($email, $message->getEnvelope());

        $apiKey = SystemSetting::getValue('brevo_api_key');
        if (!$apiKey) {
            throw new \RuntimeException('brevo_api_key nao configurada em SystemSetting.');
        }

        $response = Http::withHeaders([
            'api-key'      => $apiKey,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ])->timeout(30)->post($this->endpoint, $payload);

        if (!$response->successful()) {
            Log::error('BrevoApiTransport: envio falhou', [
                'status'  => $response->status(),
                'body'    => $response->body(),
                'subject' => $email->getSubject(),
                'to'      => array_map(fn ($a) => $a->getAddress(), $email->getTo()),
            ]);

            throw new \RuntimeException(sprintf(
                'Brevo API respondeu HTTP %d: %s',
                $response->status(),
                $response->json('message') ?? substr($response->body(), 0, 200)
            ));
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function buildPayload(Email $email, Envelope $envelope): array
    {
        // SystemSetting.email_from é a fonte da verdade — sender do Symfony
        // Email so' e' usado como fallback (caso SystemSetting nao esteja setado).
        // Motivo: Brevo rejeita qualquer sender nao verificado, e o Laravel injeta
        // MAIL_FROM_ADDRESS ('hello@example.com' se nao setado no .env) no Symfony
        // Email antes do transport rodar. Confiar no SystemSetting garante que sempre
        // usamos o mesmo sender verificado que o BrevoService (welcome, tickets, etc).
        $sender         = $email->getFrom()[0] ?? null;
        $configuredAddr = SystemSetting::getValue('email_from');
        $configuredName = SystemSetting::getValue('email_from_name');
        $senderAddr     = $configuredAddr ?: ($sender?->getAddress() ?: 'noreply@vivensi.app.br');
        $senderName     = $configuredName ?: ($sender?->getName()    ?: 'Vivensi');

        $payload = [
            'sender'  => ['email' => $senderAddr, 'name' => $senderName],
            'to'      => $this->addressesToBrevo($email->getTo() ?: $envelope->getRecipients()),
            'subject' => $email->getSubject() ?: '(sem assunto)',
        ];

        if ($html = $email->getHtmlBody()) {
            $payload['htmlContent'] = $html;
        }
        if ($text = $email->getTextBody()) {
            $payload['textContent'] = $text;
        }

        if ($cc = $email->getCc()) {
            $payload['cc'] = $this->addressesToBrevo($cc);
        }
        if ($bcc = $email->getBcc()) {
            $payload['bcc'] = $this->addressesToBrevo($bcc);
        }

        $reply = $email->getReplyTo()[0] ?? null;
        if ($reply) {
            $payload['replyTo'] = ['email' => $reply->getAddress(), 'name' => $reply->getName() ?: ''];
        }

        return $payload;
    }

    /**
     * @param  iterable<\Symfony\Component\Mime\Address>  $addresses
     * @return array<int,array<string,string>>
     */
    private function addressesToBrevo(iterable $addresses): array
    {
        $out = [];
        foreach ($addresses as $a) {
            $entry = ['email' => $a->getAddress()];
            if ($a->getName()) {
                $entry['name'] = $a->getName();
            }
            $out[] = $entry;
        }
        return $out;
    }
}
