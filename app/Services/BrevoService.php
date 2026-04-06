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

