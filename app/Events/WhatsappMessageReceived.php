<?php

namespace App\Events;

use App\Models\WhatsappChat;
use App\Models\WhatsappMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Emitido quando uma mensagem inbound chega no webhook (Cloud API ou
 * Evolution). Escutado em tempo real pelo front do Vivensi (toast + badge)
 * via Echo/Pusher no canal privado tenant.{tenantId}.whatsapp — que já
 * tem autorização por tenant em routes/channels.php.
 *
 * Payload enxuto de propósito: só o que a UI precisa pra mostrar o toast
 * e/ou pra o listener decidir se recarrega o chat aberto.
 */
class WhatsappMessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $tenantId;
    public array $payload;

    public function __construct(WhatsappMessage $message, WhatsappChat $chat)
    {
        $this->tenantId = (int) $chat->tenant_id;
        $this->payload  = [
            'message_id'   => $message->id,
            'chat_id'      => $chat->id,
            'wa_id'        => $chat->wa_id,
            'contact_name' => $chat->contact_name,
            // Preview truncado — evita payload grande no WebSocket.
            'preview'      => mb_substr((string) ($message->content ?? ''), 0, 120),
            'type'         => $message->type,
            'received_at'  => optional($message->created_at)->toIso8601String(),
        ];
    }

    public function broadcastOn(): array
    {
        // Isolamento por tenant: canal privado só é autorizado pelos users
        // do tenant certo (regra em routes/channels.php).
        return [new PrivateChannel('tenant.' . $this->tenantId . '.whatsapp')];
    }

    public function broadcastAs(): string
    {
        return 'whatsapp.message.received';
    }
}
