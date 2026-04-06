<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InstanceStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $tenantId;
    public string $instanceName;
    public string $status;

    /**
     * Create a new event instance.
     *
     * @param int $tenantId
     * @param string $instanceName
     * @param string $status (e.g. 'open', 'close', 'connecting')
     */
    public function __construct(int $tenantId, string $instanceName, string $status)
    {
        $this->tenantId = $tenantId;
        $this->instanceName = $instanceName;
        $this->status = $status;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // Broadcast no canal privado do Tenant (organização) para evitar vazamento
        // O Dashboard consumirá esse evento validando a org do usuário.
        return new PrivateChannel('tenant.' . $this->tenantId . '.whatsapp');
    }

    /**
     * Data to broadcast with the event.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'instance_name' => $this->instanceName,
            'status' => $this->status,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
