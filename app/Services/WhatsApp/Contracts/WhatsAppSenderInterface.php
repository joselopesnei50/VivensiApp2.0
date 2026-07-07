<?php

namespace App\Services\WhatsApp\Contracts;

/**
 * Contrato para providers de envio WhatsApp.
 *
 * Implementações atuais:
 *   - EvolutionWhatsAppSender (Evolution API auto-hospedada)
 *   - CloudApiWhatsAppSender  (Meta WhatsApp Business Cloud API)
 *
 * Formato de resposta normalizado:
 *   [
 *     'ok'                  => bool,           // sucesso da chamada ao provider
 *     'provider'            => string,         // 'evolution' | 'cloud_api'
 *     'provider_message_id' => ?string,        // ID retornado pelo provider (WA message ID)
 *     'error'               => ?string,        // mensagem de erro se ok=false
 *     'raw'                 => array,          // resposta bruta do provider (pra logs)
 *   ]
 */
interface WhatsAppSenderInterface
{
    /**
     * Envia mensagem de texto simples.
     *
     * @param string $to    Número do destinatário (formato internacional, com ou sem +)
     * @param string $text  Conteúdo textual
     * @return array        Ver docblock da interface
     */
    public function sendText(string $to, string $text): array;

    /**
     * Envia mídia (imagem, vídeo, áudio, documento).
     *
     * @param string $to       Número do destinatário
     * @param string $type     'image' | 'video' | 'audio' | 'document'
     * @param string $media    Base64 (Evolution) ou URL pública (Cloud API)
     * @param string $caption  Legenda opcional (ignorada para audio)
     * @return array           Ver docblock da interface
     */
    public function sendMedia(string $to, string $type, string $media, string $caption = ''): array;

    /**
     * Envia template pré-aprovado (Cloud API) ou renderiza texto (Evolution fallback).
     *
     * @param string $to            Número do destinatário
     * @param string $templateName  Nome do template aprovado na Meta
     * @param string $languageCode  Ex: 'pt_BR'
     * @param array  $variables     Variáveis {{1}}, {{2}} do corpo do template
     * @return array                Ver docblock da interface
     */
    public function sendTemplate(string $to, string $templateName, string $languageCode = 'pt_BR', array $variables = []): array;

    /**
     * Retorna o identificador do provider ('evolution' | 'cloud_api').
     */
    public function providerName(): string;

    /**
     * Sinaliza se o provider está configurado e apto a enviar (credenciais presentes).
     */
    public function isConfigured(): bool;
}
