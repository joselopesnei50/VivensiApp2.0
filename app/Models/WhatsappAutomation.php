<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class WhatsappAutomation extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'trigger', 'trigger_days',
        'message_template', 'audience', 'is_active',
        'send_window_start', 'send_window_end',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function logs()
    {
        return $this->hasMany(WhatsappAutomationLog::class, 'automation_id');
    }

    /** Substitui variáveis {nome} e {organizacao} na mensagem */
    public function renderMessage(string $contactName, string $orgName): string
    {
        return str_replace(
            ['{nome}', '{organizacao}'],
            [$contactName, $orgName],
            $this->message_template
        );
    }

    /** Verifica se agora está dentro da janela de envio configurada */
    public function isWithinSendWindow(): bool
    {
        $now   = now()->format('H:i');
        return $now >= $this->send_window_start && $now <= $this->send_window_end;
    }
}
