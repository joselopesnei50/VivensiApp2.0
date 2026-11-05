<?php

namespace App\Mail;

use App\Models\WebChatConversation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Link para o visitante continuar a conversa do chat do site. */
class WebChatResumeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public WebChatConversation $conversation, public string $resumeUrl)
    {
        $this->onQueue('emails');
    }

    public function build(): static
    {
        return $this->subject('Sua conversa com o Vivensi')
            ->view('emails.web_chat_resume', [
                'name'      => $this->conversation->visitor_name ? strtok($this->conversation->visitor_name, ' ') : null,
                'resumeUrl' => $this->resumeUrl,
                'days'      => (int) config('web_chat.resume_link_days'),
            ]);
    }
}
