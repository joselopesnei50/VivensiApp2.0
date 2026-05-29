<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Support\Facades\Log;

class BrevoService
{
    protected ?string $apiKey = null;
    protected ?string $senderEmail = null;
    protected ?string $senderName = null;
    protected string $baseUrl = 'https://api.brevo.com/v3/smtp/email';
    public ?string $lastBrevoError = null;

    public function __construct()
    {
        // Intentionally do not hit the database here.
        // Artisan commands (e.g., route:list) may instantiate controllers/services without DB connectivity.
    }

    protected function resolveConfig(): void
    {
        if ($this->apiKey !== null && $this->senderEmail !== null && $this->senderName !== null) {
            return;
        }

        $this->apiKey = SystemSetting::getValue('brevo_api_key');
        $this->senderEmail = SystemSetting::getValue('email_from', 'noreply@vivensi.com.br');
        $this->senderName = SystemSetting::getValue('email_from_name', 'Vivensi 2.0');
    }

    /**
     * Wrap content in a premium responsive HTML layout
     */
    private function wrapContent($title, $content, $buttonText = null, $buttonUrl = null)
    {
        $year = date('Y');
        $buttonHtml = '';
        
        if ($buttonText && $buttonUrl) {
            $buttonHtml = "
                <div style='text-align: center; margin-top: 30px;'>
                    <a href='{$buttonUrl}' style='background: #4f46e5; color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: 600; display: inline-block; box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);'>
                        {$buttonText}
                    </a>
                </div>
            ";
        }

        return "
        <!DOCTYPE html>
        <html lang='pt-br'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$title}</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: \"Inter\", \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; line-height: 1.6;'>
            <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                <tr>
                    <td align='center' style='padding: 40px 0;'>
                        <table width='600' border='0' cellspacing='0' cellpadding='0' style='background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0;'>
                            <tr>
                                <td style='background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); padding: 40px; text-align: center;'>
                                    <h1 style='color: #ffffff; margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -0.025em;'>Vivensi 2.0</h1>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 40px;'>
                                    <h2 style='color: #0f172a; margin-top: 0; margin-bottom: 20px; font-size: 22px; font-weight: 600;'>{$title}</h2>
                                    <div style='font-size: 16px; color: #475569;'>
                                        {$content}
                                    </div>
                                    {$buttonHtml}
                                </td>
                            </tr>
                            <tr>
                                <td style='background-color: #f1f5f9; padding: 30px; text-align: center; font-size: 13px; color: #64748b; border-top: 1px solid #e2e8f0;'>
                                    <p style='margin: 0 0 10px 0;'>&copy; {$year} <strong>Vivensi</strong>. Tecnologia e Propósito.</p>
                                    <p style='margin: 0;'>Este é um e-mail automático do sistema. Favor não responder.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";
    }

    /**
     * Send a transactional email with logging
     */
    public function sendEmail($toEmail, $toName, $subject, $htmlContent, $tenantId = null)
    {
        $this->resolveConfig();

        if (!$this->apiKey) {
            Log::warning('Tentativa de envio de e-mail sem API Key do Brevo configurada.');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->baseUrl, [
                'sender' => [
                    'name' => $this->senderName,
                    'email' => $this->senderEmail
                ],
                'to' => [[
                    'email' => $toEmail,
                    'name' => $toName
                ]],
                'subject' => $subject,
                'htmlContent' => $htmlContent
            ]);

            $success = $response->successful();
            
            // Log in database (Lazy Table creation is not ideal for Laravel, but we'll use a try-catch for now)
            try {
                \DB::table('email_logs')->insert([
                    'tenant_id' => $tenantId,
                    'to_email' => $toEmail,
                    'subject' => $subject,
                    'status' => $success ? 'sent' : 'failed',
                    'response' => $response->body(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                // If table doesn't exist, we just log to Laravel log for now
                if (!$success) {
                    Log::error('Erro ao enviar e-mail via Brevo: ' . $response->body());
                }
            }

            return $success;
        } catch (Exception $e) {
            Log::error('Exceção ao enviar e-mail via Brevo: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send Welcome Email
     */
    public function sendWelcomeEmail($user, $planName)
    {
        $subject = "✨ Bem-vindo à Vivensi! Sua jornada começou.";
        $content = "
            <p>Olá, <strong>{$user->name}</strong>!</p>
            <p>É um prazer ter você conosco na <strong>Vivensi 2.0</strong>.</p>
            <p>Sua assinatura do plano <strong>{$planName}</strong> foi iniciada com sucesso.</p>
            <p>Estamos ansiosos para ajudar você a alcançar novos patamares de eficiência e impacto.</p>
        ";

        $html = $this->wrapContent("Sua jornada começa aqui", $content, "Acessar Meu Painel", url('/dashboard'));
        return $this->sendEmail($user->email, $user->name, $subject, $html, $user->tenant_id);
    }

    /**
     * Send Payment Confirmation
     */
    public function sendPaymentConfirmedEmail($user)
    {
        $subject = "💳 Pagamento Confirmado! Acesso Liberado.";
        $content = "
            <p>Olá, <strong>{$user->name}</strong>!</p>
            <p>Recebemos a confirmação do seu pagamento com sucesso.</p>
            <p>Seu painel administrativo foi totalmente desbloqueado. Agora você tem acesso a todas as nossas ferramentas de gestão e inteligência artificial.</p>
        ";

        $html = $this->wrapContent("Acesso Liberado!", $content, "Começar a Usar Agora", url('/dashboard'));
        return $this->sendEmail($user->email, $user->name, $subject, $html, $user->tenant_id);
    }

    /**
     * Notify Admin of new support ticket
     */
    public function sendNewTicketToAdmin($adminEmail, $userName, $ticketSubject, $ticketId, $tenantId = null)
    {
        $subject = "🎫 Novo Chamado de Suporte: #{$ticketId}";
        $content = "
            <p>Um novo chamado de suporte foi aberto.</p>
            <div style='background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; margin: 20px 0;'>
                <p style='margin: 5px 0;'><strong>Ticket:</strong> #{$ticketId}</p>
                <p style='margin: 5px 0;'><strong>Cliente:</strong> {$userName}</p>
                <p style='margin: 5px 0;'><strong>Assunto:</strong> {$ticketSubject}</p>
            </div>
            <p>Responda o quanto antes através do painel do SaaS.</p>
        ";
        
        $html = $this->wrapContent("Novo Chamado", $content, "Ver Chamado", url('/admin/support'));
        return $this->sendEmail($adminEmail, 'Administrador', $subject, $html, $tenantId);
    }

    /**
     * Notify User of support reply
     */
    public function sendTicketReplyToUser($user, $ticketId, $tenantId = null)
    {
        $subject = "📩 Nova Resposta no Ticket #{$ticketId}";
        $content = "
            <p>Olá, <strong>{$user->name}</strong>.</p>
            <p>Nossa equipe de suporte respondeu ao seu chamado <strong>#{$ticketId}</strong>.</p>
            <p>Você pode conferir a resposta e interagir clicando no botão abaixo.</p>
        ";
        
        $html = $this->wrapContent("Suporte Respondeu", $content, "Ver Resposta", url('/support/'.$ticketId));
        return $this->sendEmail($user->email, $user->name, $subject, $html, $tenantId);
    }

    /**
     * Send Trial Ending Reminder
     */
    public function sendTrialEndingEmail($user, $daysLeft)
    {
        $subject = "⚠️ Seu período de teste Vivensi está terminando!";
        $content = "
            <p>Olá, <strong>{$user->name}</strong>!</p>
            <p>Espero que você esteja aproveitando muito sua experiência com a <strong>Vivensi 2.0</strong>.</p>
            <p>Passando para avisar que restam apenas <strong>{$daysLeft} dias</strong> do seu período de teste grátis.</p>
            <p>Para não perder o acesso às suas ferramentas e dados, realize a ativação do seu plano agora mesmo.</p>
        ";

        $html = $this->wrapContent("Seu teste está expirando", $content, "Ativar Meu Plano Agora", url('/dashboard'));
        return $this->sendEmail($user->email, $user->name, $subject, $html, $user->tenant_id);
    }

    /**
     * Send Meeting Booking Confirmation
     */
    public function sendMeetingConfirmationEmail(\App\Models\MeetingBooking $booking, string $formattedDate): bool
    {
        $cancelUrl = route('booking.cancel', $booking->confirmation_token);

        $notesHtml = $booking->notes
            ? "<div style='background:#f8fafc;border-left:3px solid #4f46e5;padding:12px 16px;border-radius:0 8px 8px 0;margin:16px 0;font-size:14px;color:#475569;'>" . e($booking->notes) . "</div>"
            : '';

        $content = "
            <p>Olá, <strong>" . e($booking->name) . "</strong>!</p>
            <p>Sua reunião com a equipe <strong>Vivensi</strong> está confirmada. Abaixo estão os detalhes:</p>

            <div style='background:#f1f5f9;border-radius:12px;overflow:hidden;margin:20px 0;'>
                <table width='100%' cellpadding='0' cellspacing='0'>
                    <tr><td style='padding:14px 20px;border-bottom:1px solid #e2e8f0;'>
                        <span style='font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;'>Data</span><br>
                        <strong style='font-size:15px;color:#0f172a;text-transform:capitalize;'>{$formattedDate}</strong>
                    </td></tr>
                    <tr><td style='padding:14px 20px;border-bottom:1px solid #e2e8f0;'>
                        <span style='font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;'>Horário</span><br>
                        <strong style='font-size:15px;color:#0f172a;'>{$booking->meeting_time} (Horário de Brasília)</strong>
                    </td></tr>
                    <tr><td style='padding:14px 20px;'>
                        <span style='font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;'>Duração</span><br>
                        <strong style='font-size:15px;color:#0f172a;'>30 minutos · Videoconferência</strong>
                    </td></tr>
                </table>
            </div>

            {$notesHtml}

            <p style='font-size:14px;color:#64748b;'>Nossa equipe enviará o link de videoconferência até 24 horas antes da reunião.</p>
            <p style='font-size:14px;color:#64748b;'>Caso precise cancelar, <a href='{$cancelUrl}' style='color:#4f46e5;'>clique aqui</a>.</p>
        ";

        $subject = "✅ Reunião confirmada – {$formattedDate} às {$booking->meeting_time}";
        $html = $this->wrapContent('Reunião Confirmada!', $content);

        return $this->sendEmail($booking->email, $booking->name, $subject, $html);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // CAMPAIGN API — Disparos em massa com métricas (Brevo /v3/emailCampaigns)
    // ══════════════════════════════════════════════════════════════════════════

    protected string $baseApiUrl = 'https://api.brevo.com/v3';

    protected function apiHeaders(): array
    {
        $this->resolveConfig();
        return [
            'api-key'      => $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
    }

    /**
     * Cria uma lista de contatos no Brevo e retorna o ID da lista.
     * folderId é obrigatório na API v3 — busca o primeiro folder disponível.
     */
    public function createContactList(string $name): ?int
    {
        // Busca o primeiro folder disponível na conta
        $folderId = $this->getFirstFolderId();

        $payload = ['name' => $name];
        if ($folderId) {
            $payload['folderId'] = $folderId;
        }

        $response = Http::withHeaders($this->apiHeaders())
            ->post("{$this->baseApiUrl}/contacts/lists", $payload);

        if ($response->successful()) {
            Log::info('Brevo createContactList ok', ['id' => $response->json('id'), 'folderId' => $folderId]);
            return $response->json('id');
        }

        Log::error('Brevo createContactList failed', [
            'status'   => $response->status(),
            'body'     => $response->body(),
            'folderId' => $folderId,
        ]);
        return null;
    }

    /**
     * Retorna o ID do primeiro folder de contatos da conta Brevo.
     */
    protected function getFirstFolderId(): ?int
    {
        try {
            $response = Http::withHeaders($this->apiHeaders())
                ->get("{$this->baseApiUrl}/contacts/folders", ['limit' => 5, 'offset' => 0]);

            if ($response->successful()) {
                $folders = $response->json('folders') ?? [];
                if (!empty($folders)) {
                    return (int) $folders[0]['id'];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Brevo getFirstFolderId failed: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Importa contatos na lista usando chamadas individuais síncronas.
     * O endpoint /contacts/batch pode ser assíncrono no Brevo, causando race condition
     * onde a campanha é enviada antes dos contatos estarem na lista.
     * Usando POST /contacts individualmente garantimos que cada contato está na lista
     * antes de prosseguir.
     *
     * Retorna o número de contatos adicionados com sucesso.
     */
    public function importContacts(int $listId, array $contacts): int
    {
        if (empty($contacts)) return 0;

        $added  = 0;
        $errors = 0;

        foreach ($contacts as $c) {
            $response = Http::withHeaders($this->apiHeaders())
                ->post("{$this->baseApiUrl}/contacts", [
                    'email'         => $c['email'],
                    'listIds'       => [$listId],
                    'attributes'    => ['FIRSTNAME' => $c['name'] ?? ''],
                    'updateEnabled' => true,
                ]);

            // 201 = criado, 204 = atualizado, 400 c/ "Contact already exist" = já existe (conta como ok)
            if ($response->successful()) {
                $added++;
            } elseif ($response->status() === 400) {
                $body = $response->json('message') ?? '';
                if (str_contains(strtolower($body), 'already exist') || str_contains(strtolower($body), 'duplicate')) {
                    // Contato já existe no Brevo — garante que está na lista
                    $this->addContactToList($c['email'], $listId);
                    $added++;
                } else {
                    $errors++;
                    Log::warning('Brevo importContacts: contato rejeitado', ['email' => $c['email'], 'msg' => $body]);
                }
            } else {
                $errors++;
                Log::warning('Brevo importContacts: erro ao adicionar contato', [
                    'email'  => $c['email'],
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }
        }

        Log::info('Brevo importContacts concluído', [
            'listId' => $listId,
            'total'  => count($contacts),
            'added'  => $added,
            'errors' => $errors,
        ]);

        return $added;
    }

    /**
     * Adiciona um contato existente a uma lista específica.
     */
    protected function addContactToList(string $email, int $listId): void
    {
        Http::withHeaders($this->apiHeaders())
            ->post("{$this->baseApiUrl}/contacts/lists/{$listId}/contacts/add", [
                'emails' => [$email],
            ]);
    }

    /**
     * Retorna o número de contatos em uma lista Brevo.
     */
    public function getListContactCount(int $listId): int
    {
        try {
            $response = Http::withHeaders($this->apiHeaders())
                ->get("{$this->baseApiUrl}/contacts/lists/{$listId}");

            if ($response->successful()) {
                return (int) ($response->json('uniqueSubscribers') ?? $response->json('totalSubscribers') ?? 0);
            }
        } catch (\Throwable $e) {
            Log::warning('Brevo getListContactCount failed: ' . $e->getMessage());
        }
        return 0;
    }

    /**
     * Cria uma campanha de e-mail no Brevo e retorna o campaignId.
     * Payload mínimo — campos nulos causam erro 400 na API v3.
     */
    public function createBrevoEmailCampaign(array $data): ?int
    {
        $this->resolveConfig();

        $senderName  = $data['sender_name']  ?: $this->senderName;
        $senderEmail = $data['sender_email'] ?: $this->senderEmail;

        $payload = [
            'name'        => $data['name'],
            'subject'     => $data['subject'],
            'sender'      => ['name' => $senderName, 'email' => $senderEmail],
            'htmlContent' => $data['html_content'],
            'recipients'  => ['listIds' => [(int) $data['brevo_list_id']]],
        ];

        // replyTo na API de campanhas Brevo aceita apenas string (e-mail), não objeto
        if (!empty($data['reply_to_email'])) {
            $payload['replyTo'] = $data['reply_to_email'];
        }

        Log::info('Brevo createBrevoEmailCampaign payload', [
            'name'    => $payload['name'],
            'subject' => $payload['subject'],
            'sender'  => $payload['sender'],
            'replyTo' => $payload['replyTo'] ?? null,
            'listId'  => $data['brevo_list_id'],
        ]);

        $response = Http::withHeaders($this->apiHeaders())
            ->post("{$this->baseApiUrl}/emailCampaigns", $payload);

        if ($response->successful()) {
            return $response->json('id');
        }

        $brevoError = $response->json('message') ?? $response->body();
        Log::error('Brevo createBrevoEmailCampaign failed', [
            'status'      => $response->status(),
            'brevoMessage'=> $brevoError,
            'payload'     => array_merge($payload, ['htmlContent' => '[omitted]']),
        ]);

        // Armazena o erro real para ser exibido no controller
        $this->lastBrevoError = $brevoError;
        return null;
    }

    /**
     * Dispara uma campanha imediatamente.
     * O Brevo enfileira o envio — a entrega real ocorre em segundos a alguns minutos.
     */
    public function sendBrevoEmailCampaign(int $campaignId): bool
    {
        $this->resolveConfig(); // garante API key carregada

        $response = Http::withHeaders($this->apiHeaders())
            ->post("{$this->baseApiUrl}/emailCampaigns/{$campaignId}/sendNow");

        if (!$response->successful()) {
            Log::error('Brevo sendBrevoEmailCampaign failed', [
                'campaignId' => $campaignId,
                'status'     => $response->status(),
                'body'       => $response->body(),
            ]);
            return false;
        }

        Log::info('Brevo sendBrevoEmailCampaign: campanha enfileirada', ['campaignId' => $campaignId]);
        return true;
    }

    /**
     * Busca estatísticas de uma campanha enviada.
     * Retorna array com chaves normalizadas, ou [] se a API falhar.
     * Retorna ['_no_data' => true] se a API respondeu mas ainda sem métricas.
     */
    public function getBrevoEmailCampaignStats(int $campaignId): array
    {
        $response = Http::withHeaders($this->apiHeaders())
            ->get("{$this->baseApiUrl}/emailCampaigns/{$campaignId}");

        if (!$response->successful()) {
            Log::warning('Brevo getBrevoEmailCampaignStats failed', [
                'campaignId' => $campaignId,
                'status'     => $response->status(),
                'body'       => $response->body(),
            ]);
            return [];
        }

        $data     = $response->json();
        $rawStats = $data['statistics'] ?? null;

        Log::info('Brevo campaign stats full', [
            'campaignId'  => $campaignId,
            'brevoStatus' => $data['status'] ?? null,
            'statistics'  => $rawStats,
        ]);

        if (!is_array($rawStats)) {
            return ['_no_data' => true];
        }

        // Brevo coloca os totais em globalStats, mas às vezes os zera e coloca
        // os dados reais por lista em campaignStats. Agregamos campaignStats quando
        // globalStats.delivered = 0 mas há entregues reais em campaignStats.
        $global = $rawStats['globalStats'] ?? [];
        $campaignStats = $rawStats['campaignStats'] ?? [];

        $stats = $global;

        if ((int)($global['delivered'] ?? 0) === 0 && !empty($campaignStats)) {
            $agg = [
                'delivered'       => 0,
                'uniqueViews'     => 0,
                'uniqueClicks'    => 0,
                'clickers'        => 0,
                'softBounces'     => 0,
                'hardBounces'     => 0,
                'unsubscriptions' => 0,
                'complaints'      => 0,
            ];
            foreach ($campaignStats as $cs) {
                foreach (array_keys($agg) as $key) {
                    $agg[$key] += (int)($cs[$key] ?? 0);
                }
            }
            // Usa o agregado se tiver pelo menos 1 entregue
            if ($agg['delivered'] > 0) {
                $stats = array_merge($global, $agg);
            }
        }

        if (empty($stats)) {
            return ['_no_data' => true];
        }

        $toInt = fn($v) => $v !== null ? (int) $v : null;

        $opens  = $stats['uniqueViews']  ?? $stats['viewed']   ?? $stats['opens']   ?? null;
        $clicks = $stats['uniqueClicks'] ?? $stats['clickers'] ?? $stats['clicks']   ?? null;

        return [
            'delivered'    => $toInt($stats['delivered']       ?? null),
            'opens'        => $toInt($opens),
            'clicks'       => $toInt($clicks),
            'bounces'      => $toInt(($stats['softBounces'] ?? 0) + ($stats['hardBounces'] ?? 0)),
            'unsubscribes' => $toInt($stats['unsubscriptions'] ?? $stats['unsubscribes'] ?? null),
            'spam'         => $toInt($stats['complaints']      ?? $stats['spam']         ?? null),
            'status'       => $data['status'] ?? null,
        ];
    }

    /**
     * Retorna o JSON bruto da API do Brevo para uma campanha — apenas para debug admin.
     */
    public function getRawBrevoResponse(int $campaignId): array
    {
        $this->resolveConfig();
        $response = Http::withHeaders($this->apiHeaders())
            ->get("{$this->baseApiUrl}/emailCampaigns/{$campaignId}");

        return [
            'http_status' => $response->status(),
            'body'        => $response->json() ?? $response->body(),
        ];
    }

    /**
     * Send Manual Welcome Email (Created by Super Admin)
     */
    public function sendManualWelcomeEmail($user, $password, $planName, $billingMode)
    {
        $subject = "🚀 Sua conta Vivensi foi criada com sucesso!";
        
        $billingText = match($billingMode) {
            'courtesy' => "Como um parceiro especial, sua conta é uma <strong>Cortesia</strong> e já está totalmente liberada para uso.",
            'manual_pay' => "Sua conta foi criada no modo de <strong>Pagamento Manual</strong>. Para liberar seu acesso completo, basta realizar o pagamento através do botão abaixo.",
            'trial' => "Sua conta foi criada no modo <strong>Trial</strong> e você tem 7 dias para testar todas as nossas funcionalidades gratuitamente.",
            default => ""
        };

        $buttonText = $billingMode === 'manual_pay' ? "Realizar Pagamento Agora" : "Acessar Meu Painel";
        $buttonUrl = $billingMode === 'manual_pay' ? route('checkout.index', ['plan_id' => $user->tenant->plan_id]) : url('/dashboard');

        $content = "
            <p>Olá, <strong>{$user->name}</strong>!</p>
            <p>Sua conta na <strong>Vivensi 2.0</strong> foi configurada e está pronta para uso.</p>
            <p>{$billingText}</p>
            
            <div style='background: #f8fafc; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; margin: 25px 0;'>
                <p style='margin: 0 0 10px 0; color: #64748b; font-size: 14px; text-transform: uppercase; font-weight: 700;'>Dados de Acesso:</p>
                <p style='margin: 5px 0;'><strong>E-mail:</strong> {$user->email}</p>
                <p style='margin: 5px 0;'><strong>Senha:</strong> {$password}</p>
                <p style='margin: 5px 0;'><strong>Plano:</strong> {$planName}</p>
            </div>
            
            <p style='color: #ef4444; font-size: 14px;'><em>* Recomendamos alterar sua senha após o primeiro acesso.</em></p>
        ";

        $html = $this->wrapContent("Bem-vindo à Vivensi", $content, $buttonText, $buttonUrl);
        return $this->sendEmail($user->email, $user->name, $subject, $html, $user->tenant_id);
    }
}

