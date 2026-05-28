<?php

namespace App\Services;

use App\Models\ContatoWhatsapp;
use Illuminate\Support\Facades\Redis;

class ContatoOptInService
{
    const ESTADO_AGUARDANDO_RESPOSTA = 'aguardando_optin';
    const ESTADO_CONFIRMADO          = 'confirmado';

    private string $redisPrefix = 'optin_state_';

    /**
     * Ponto de entrada chamado pelo webhook da Evolution API.
     * Retorna a resposta que deve ser enviada ao contato, ou null
     * se a mensagem deve seguir para o módulo de mensageria normal.
     */
    public function handle(string $telefone, string $texto, int $tenantId): ?string
    {
        $contato = ContatoWhatsapp::firstOrCreate(
            ['tenant_id' => $tenantId, 'telefone' => $telefone]
        );

        if ($contato->opt_out) {
            return null;
        }

        if ($contato->podeReceberMensagem()) {
            return null;
        }

        $estado            = $this->getEstado($telefone, $tenantId);
        $textoNormalizado  = $this->normalizarTexto($texto);

        if (in_array($textoNormalizado, ['sair', 'cancelar', 'parar', 'nao', 'não'])) {
            $contato->registrarOptOut();
            Redis::del($this->chaveRedis($telefone, $tenantId));
            $nomeOrg = $contato->tenant?->name ?? 'nossa organização';
            return $this->mensagemOptOut($nomeOrg);
        }

        if (!$estado) {
            $this->setEstado($telefone, $tenantId, self::ESTADO_AGUARDANDO_RESPOSTA);
            $nomeOrg = $contato->tenant?->name ?? 'nossa organização';
            return $this->mensagemBoasVindas($nomeOrg);
        }

        if ($estado === self::ESTADO_AGUARDANDO_RESPOSTA) {
            if (in_array($textoNormalizado, ['sim', 's', 'yes', 'quero', 'ok'])) {
                $contato->registrarOptIn('bot');
                $this->setEstado($telefone, $tenantId, self::ESTADO_CONFIRMADO);
                return $this->mensagemConfirmacaoOptIn();
            }

            return "Não consegui entender sua resposta. 😅\n\nDigite *SIM* para ativar ou *NÃO* para não receber mensagens.";
        }

        return null;
    }

    private function normalizarTexto(string $texto): string
    {
        return strtolower(trim(
            iconv('UTF-8', 'ASCII//TRANSLIT', $texto)
        ));
    }

    private function chaveRedis(string $telefone, int $tenantId): string
    {
        return $this->redisPrefix . $tenantId . '_' . $telefone;
    }

    private function getEstado(string $telefone, int $tenantId): ?string
    {
        return Redis::get($this->chaveRedis($telefone, $tenantId));
    }

    private function setEstado(string $telefone, int $tenantId, string $estado): void
    {
        Redis::setex($this->chaveRedis($telefone, $tenantId), 86400, $estado);
    }

    private function mensagemBoasVindas(string $nomeOrg): string
    {
        return "Olá! Tudo bem? 😊\n\n"
            . "Sou o assistente virtual da *{$nomeOrg}*. Estou aqui para te ajudar com informações, "
            . "novidades e suporte direto pelo WhatsApp.\n\n"
            . "Para continuar, preciso da sua autorização para enviar mensagens por aqui:\n\n"
            . "✅ *SIM* — quero receber conteúdos e comunicados\n"
            . "❌ *NÃO* — prefiro não receber\n\n"
            . "_Você pode cancelar a qualquer momento enviando *SAIR*._";
    }

    private function mensagemConfirmacaoOptIn(): string
    {
        return "Perfeito, obrigado! 🎉\n\n"
            . "Você está cadastrado e agora pode receber nossas mensagens por aqui.\n\n"
            . "Em breve entraremos em contato. Se precisar de algo antes, é só chamar! 💬\n\n"
            . "_Para cancelar a qualquer momento, envie *SAIR*._";
    }

    private function mensagemOptOut(string $nomeOrg): string
    {
        return "Entendido! ✅\n\n"
            . "Você foi removido da nossa lista de contatos da *{$nomeOrg}* e não receberá mais mensagens.\n\n"
            . "Se mudar de ideia ou precisar de ajuda, basta nos enviar uma mensagem. Estamos sempre por aqui. 😊";
    }
}
