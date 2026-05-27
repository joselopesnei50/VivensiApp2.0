<?php

namespace App\Jobs;

use App\Models\WhatsappChat;
use App\Models\WhatsappConfig;
use App\Models\WhatsappInstance;
use App\Models\WhatsappMessage;
use App\Services\EvolutionApiService;
use App\Services\WhatsappOutboundPolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 60;
    public array $backoff = [10, 30];

    public function __construct(
        public int    $chatId,
        public int    $configId,
        public string $base64,
        public ?int   $actorUserId = null
    ) {
        $this->onQueue('whatsapp');
    }

    public function handle(): void
    {
        $chat   = WhatsappChat::find($this->chatId);
        $config = WhatsappConfig::withoutGlobalScopes()->find($this->configId);

        if (!$chat || !$config) {
            return;
        }

        $policy = app(WhatsappOutboundPolicy::class);
        $reason = null;
        $code   = null;

        if (!$policy->canSend($config, $chat, false, $reason, $code)) {
            Log::info('SendWhatsAppAudioJob: bloqueado por policy', ['chat_id' => $this->chatId, 'code' => $code]);
            return;
        }

        $instance = WhatsappInstance::where('tenant_id', $chat->tenant_id)
            ->where('status', 'open')
            ->first();

        if (!$instance) {
            Log::error('SendWhatsAppAudioJob: nenhuma instância conectada', ['tenant_id' => $chat->tenant_id]);
            throw new \RuntimeException('Nenhuma instância WhatsApp conectada.');
        }

        // Anti-ban delay fora do worker HTTP — não bloqueia mais a requisição do usuário
        sleep(2);

        $evo = new EvolutionApiService($instance);
        $res = $evo->sendAudio($chat->wa_id, $this->base64);

        if (isset($res['error']) || empty($res)) {
            throw new \RuntimeException($res['message'] ?? $res['error'] ?? 'Erro na Evolution API ao enviar áudio.');
        }

        $messageId = $res['key']['id'] ?? ('AUDIO_' . uniqid());

        WhatsappMessage::create([
            'chat_id'    => $this->chatId,
            'message_id' => $messageId,
            'content'    => '🎙️ [Áudio enviado]',
            'direction'  => 'outbound',
            'type'       => 'audio',
        ]);

        $chat->update(['last_message_at' => now()]);
        $policy->recordSend($config, $chat);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendWhatsAppAudioJob falhou definitivamente', [
            'chat_id' => $this->chatId,
            'error'   => $e->getMessage(),
        ]);
    }
}
