<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailCampaign extends Model
{
    protected $fillable = [
        'created_by', 'name', 'subject', 'html_content',
        'sender_name', 'sender_email', 'audience_type',
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
        if (!$this->stat_delivered || !$this->stat_opens) return null;
        return round(($this->stat_opens / $this->stat_delivered) * 100, 1);
    }

    public function clickRate(): ?float
    {
        if (!$this->stat_delivered || !$this->stat_clicks) return null;
        return round(($this->stat_clicks / $this->stat_delivered) * 100, 1);
    }

    public function bounceRate(): ?float
    {
        if (!$this->recipient_count || !$this->stat_bounces) return null;
        return round(($this->stat_bounces / $this->recipient_count) * 100, 1);
    }

    public function audienceLabel(): string
    {
        return match($this->audience_type) {
            'tenant_admins' => 'Administradores de Clientes',
            'all_users'     => 'Todos os Usuários',
            'leads'         => 'Leads (Landing Pages)',
            'all'           => 'Todos (Usuários + Leads)',
            default         => $this->audience_type,
        };
    }
}
