<?php

namespace App\Services;

use App\Models\NgoDonor;
use App\Models\Transaction;
use App\Models\WhatsappConfig;
use Illuminate\Support\Facades\Log;

/**
 * DonorRetentionService
 *
 * Centraliza toda a lógica de relacionamento automático com doadores via WhatsApp.
 * Responsável por: agradecimento após doação, envio do link do Portal VIP,
 * notificações de retenção e régua de relacionamento.
 */
class DonorRetentionService
{
    /**
     * Dispara o fluxo completo após uma doação ser confirmada (status: paid).
     * Envia agradecimento + link do Portal VIP em uma única mensagem humanizada.
     */
    public function notifyDonationReceived(Transaction $transaction): void
    {
        // Só processa transações de receita (doações) confirmadas
        if ($transaction->type !== 'income' || $transaction->status !== 'paid') {
            return;
        }

        // Só executa se houver um doador vinculado
        if (!$transaction->ngo_donor_id) {
            return;
        }

        $donor = NgoDonor::withoutGlobalScopes()->find($transaction->ngo_donor_id);
        if (!$donor || empty($donor->phone)) {
            return;
        }

        $this->sendThankYouMessage($donor, $transaction);
    }

    /**
     * Envia mensagem de agradecimento personalizada com o link do Portal VIP.
     */
    public function sendThankYouMessage(NgoDonor $donor, ?Transaction $transaction = null): bool
    {
        $config = WhatsappConfig::withoutGlobalScopes()
            ->where('tenant_id', $donor->tenant_id)
            ->where('is_active', true)
            ->first();

        if (!$config) {
            Log::info('DonorRetention: Nenhuma instância WhatsApp ativa para tenant ' . $donor->tenant_id);
            return false;
        }

        $evo        = new EvolutionApiService($config);
        $portalLink = url('/portal-doador/' . $donor->portal_token);
        $firstName  = explode(' ', $donor->name)[0];
        $amount     = $transaction ? 'R$ ' . number_format($transaction->amount, 2, ',', '.') : null;

        $message = $this->buildThankYouMessage($firstName, $portalLink, $amount);
        $phone   = preg_replace('/\D/', '', $donor->phone);

        // Garante DDI 55 (Brasil)
        if (!str_starts_with($phone, '55')) {
            $phone = '55' . $phone;
        }

        try {
            $result = $evo->sendMessage($phone, $message, null, 3);

            if (isset($result['error'])) {
                Log::warning('DonorRetention: Falha no envio', [
                    'donor_id' => $donor->id,
                    'error'    => $result['error'],
                ]);
                return false;
            }

            Log::info('DonorRetention: Mensagem enviada com sucesso', [
                'donor_id'  => $donor->id,
                'phone'     => $phone,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('DonorRetention: Exceção ao enviar mensagem', [
                'donor_id' => $donor->id,
                'error'    => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Constrói a mensagem de agradecimento humanizada com spintax (variação natural).
     */
    protected function buildThankYouMessage(string $firstName, string $portalLink, ?string $amount = null): string
    {
        $amountLine = $amount ? "\n💚 Valor confirmado: *{$amount}*" : '';

        $templates = [
            "Olá, *{$firstName}*! 💙\n\nRecebemos sua contribuição com muito carinho e gratidão.{$amountLine}\n\nPara você, criamos um *Portal VIP exclusivo* onde pode acompanhar o impacto real das suas doações e baixar seus informes de rendimentos:\n\n🔗 {$portalLink}\n\n_Obrigado por fazer parte da nossa missão!_ 🙏",

            "*{$firstName}*, sua doação chegou! 🎉{$amountLine}\n\nCriamos um espaço especial para você ver de perto o impacto que está gerando. Acesse seu *Portal do Doador VIP*:\n\n🔗 {$portalLink}\n\nCom gratidão, nossa equipe 💚",

            "Que alegria receber seu apoio, *{$firstName}*! 🌟{$amountLine}\n\nSeu gesto de generosidade transforma vidas. Acesse seu portal exclusivo e veja o impacto da sua contribuição:\n\n🔗 {$portalLink}\n\nObrigado por confiar em nosso trabalho! 🙏💙",
        ];

        return $templates[array_rand($templates)];
    }

    /**
     * Envia lembretes de renovação para doadores que não doam há X dias.
     * Pode ser chamado por um Artisan Command agendado.
     *
     * @param int $tenantId
     * @param int $inactiveDays
     */
    public function sendReactivationReminders(int $tenantId, int $inactiveDays = 60): int
    {
        $config = WhatsappConfig::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (!$config) {
            return 0;
        }

        $cutoff = now()->subDays($inactiveDays);

        // Busca doadores ativos que não tiveram transação recente
        $donors = NgoDonor::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('phone')
            ->whereDoesntHave('transactions', function ($q) use ($cutoff) {
                $q->where('type', 'income')
                  ->where('status', 'paid')
                  ->where('date', '>=', $cutoff);
            })
            ->whereHas('transactions', function ($q) {
                // Garante que já doou ao menos 1x no passado
                $q->where('type', 'income')->where('status', 'paid');
            })
            ->get();

        $sent = 0;
        foreach ($donors as $donor) {
            $result = $this->sendReactivationMessage($donor, $config);
            if ($result) $sent++;

            // Anti-ban: pausa entre envios
            sleep(rand(8, 15));
        }

        Log::info("DonorRetention reactivation: {$sent}/{$donors->count()} enviados para tenant {$tenantId}");
        return $sent;
    }

    /**
     * Constrói e envia mensagem de reativação para doador inativo.
     */
    protected function sendReactivationMessage(NgoDonor $donor, WhatsappConfig $config): bool
    {
        $evo        = new EvolutionApiService($config);
        $firstName  = explode(' ', $donor->name)[0];
        $portalLink = url('/portal-doador/' . $donor->portal_token);
        $phone      = preg_replace('/\D/', '', $donor->phone);

        if (!str_starts_with($phone, '55')) {
            $phone = '55' . $phone;
        }

        $message = "Olá, *{$firstName}*! 👋\n\nEstamos com saudades e queremos compartilhar as novidades da nossa organização.\n\nPara ver o impacto que suas contribuições passadas geraram, acesse seu portal:\n🔗 {$portalLink}\n\n_Qualquer dúvida, estamos à disposição!_ 💙";

        try {
            $result = $evo->sendMessage($phone, $message, null, 5);
            return !isset($result['error']);
        } catch (\Throwable $e) {
            Log::error('DonorRetention reactivation error: ' . $e->getMessage());
            return false;
        }
    }
}
