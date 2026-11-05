<?php

namespace App\Jobs;

use App\Services\WebChat\WebChatService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Bruno responde no chat do site (mesmo cérebro do WhatsApp). */
class GenerateWebChatReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 110;

    public function __construct(public int $conversationId)
    {
        // Fila "ai" do Horizon (timeout 300s, 2 processos em produção).
        $this->onQueue('ai');
    }

    public function handle(WebChatService $service): void
    {
        $service->generateReply($this->conversationId);
    }
}
