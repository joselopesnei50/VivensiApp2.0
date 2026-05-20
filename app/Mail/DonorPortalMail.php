<?php

namespace App\Mail;

use App\Models\NgoDonor;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DonorPortalMail extends Mailable
{
    use Queueable, SerializesModels;

    public NgoDonor $donor;
    public Tenant   $tenant;
    public string   $portalUrl;

    public function __construct(NgoDonor $donor, Tenant $tenant)
    {
        $this->donor     = $donor;
        $this->tenant    = $tenant;
        $this->portalUrl = url('/portal-doador/' . $donor->portal_token);
    }

    public function build(): self
    {
        $orgName = $this->tenant->name ?? 'Vivensi';

        return $this->subject("Seu Portal VIP de Doador — {$orgName}")
                    ->view('emails.donor_portal');
    }
}
