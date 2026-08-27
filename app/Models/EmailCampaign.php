<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class EmailCampaign extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'created_by', 'name', 'subject', 'html_content',
        'sender_name', 'sender_email', 'reply_to_email', 'audience_type', 'manual_emails',
        'email_contact_list_id',
        'recipient_count', 'brevo_list_id', 'brevo_campaign_id',
        'status', 'scheduled_at', 'sent_at', 'error_message',
        'stat_delivered', 'stat_opens', 'stat_clicks',
        'stat_bounces', 'stat_unsubscribes', 'stat_spam',
        'stats_fetched_at',
    ];

    protected $casts = [
        'scheduled_at'     => 'datetime',
        'sent_at'          => 'datetime',
        'stats_fetched_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function openRate(): ?float
    {
        if ($this->stat_delivered === null || $this->stat_opens === null) return null;
        if ($this->stat_delivered == 0) return null;
        return round(($this->stat_opens / $this->stat_delivered) * 100, 1);
    }

    public function clickRate(): ?float
    {
        if ($this->stat_delivered === null || $this->stat_clicks === null) return null;
        if ($this->stat_delivered == 0) return null;
        return round(($this->stat_clicks / $this->stat_delivered) * 100, 1);
    }

    public function bounceRate(): ?float
    {
        if (!$this->recipient_count || $this->stat_bounces === null) return null;
        return round(($this->stat_bounces / $this->recipient_count) * 100, 1);
    }

    public function audienceLabel(): string
    {
        return match($this->audience_type) {
            'tenant_admins'  => 'Administradores de Clientes',
            'all_users'      => 'Todos os Usuários',
            'leads'          => 'Leads (Landing Pages)',
            'all'            => 'Todos (Usuários + Leads)',
            'manual'         => 'E-mails Manuais',
            'none'           => 'Somente E-mails Adicionais',
            'donors'         => 'Todos os Doadores',
            'donors_optins'  => 'Doadores com Opt-in',
            default          => $this->audience_type,
        };
    }

    public function parsedManualEmails(): array
    {
        if (!$this->manual_emails) return [];
        return json_decode($this->manual_emails, true) ?? [];
    }
}
