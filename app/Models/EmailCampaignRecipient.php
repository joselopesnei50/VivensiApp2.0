<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Destinatário de uma campanha com envio próprio (delivery_driver = 'own').
 * Sem BelongsToTenant de propósito: é acessado por rotas públicas de tracking
 * (pixel/clique) e pelo webhook do SES, sempre via token ou message id.
 */
class EmailCampaignRecipient extends Model
{
    public const STATUS_QUEUED     = 'queued';
    public const STATUS_SENDING    = 'sending';
    public const STATUS_SENT       = 'sent';
    public const STATUS_FAILED     = 'failed';
    public const STATUS_SKIPPED    = 'skipped';
    public const STATUS_BOUNCED    = 'bounced';
    public const STATUS_COMPLAINED = 'complained';

    protected $fillable = [
        'email_campaign_id', 'email', 'name', 'token', 'status', 'claim_token',
        'provider_message_id', 'error', 'sent_at', 'delivered_at', 'bounced_at',
        'complained_at', 'opened_at', 'open_count', 'clicked_at', 'click_count',
        'unsubscribed_at',
    ];

    protected $casts = [
        'sent_at'         => 'datetime',
        'delivered_at'    => 'datetime',
        'bounced_at'      => 'datetime',
        'complained_at'   => 'datetime',
        'opened_at'       => 'datetime',
        'clicked_at'      => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(EmailCampaign::class, 'email_campaign_id')->withoutGlobalScopes();
    }
}
