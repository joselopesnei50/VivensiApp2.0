<?php

namespace App\Services\WebChat;

/** Erro de regra do chat (limite, bot, validação) com mensagem para o visitante. */
class WebChatException extends \RuntimeException
{
    public function __construct(string $message, private int $status = 422)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}
