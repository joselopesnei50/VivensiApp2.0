<?php

namespace App\Mail;

use App\Models\LandingPage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Email transacional de confirmacao de inscricao via landing page (2026-08-06).
 * Enviado pelo driver 'brevo' custom (default apos commit b8241ba).
 */
class LandingPageLeadConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public LandingPage $page;
    public string $leadName;
    public string $leadEmail;
    public string $tenantName;
    public string $pageUrl;

    public function __construct(LandingPage $page, string $leadName, string $leadEmail, string $tenantName)
    {
        $this->page       = $page;
        $this->leadName   = $leadName ?: 'olá';
        $this->leadEmail  = $leadEmail;
        $this->tenantName = $tenantName;
        $this->pageUrl    = url('/lp/' . $page->slug);
    }

    public function build(): static
    {
        $subject = 'Recebemos sua inscrição — ' . mb_substr($this->tenantName, 0, 80);

        return $this
            ->subject($subject)
            ->view('emails.landing_page_lead_confirmation');
    }
}
