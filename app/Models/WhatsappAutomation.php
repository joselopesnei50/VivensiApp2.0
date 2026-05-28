<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class WhatsappAutomation extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'trigger', 'trigger_days', 'keyword',
        'message_template', 'audience', 'is_active',
        'send_window_start', 'send_window_end', 'send_once',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'send_once' => 'boolean',
    ];

    public function logs()
    {
        return $this->hasMany(WhatsappAutomationLog::class, 'automation_id');
    }

    /** Substitui variáveis dinâmicas na mensagem */
    public function renderMessage(string $contactName, string $orgName, int $days = 0): string
    {
        return str_replace(
            ['{nome}', '{organizacao}', '{data}', '{dias_sem_contato}'],
            [$contactName, $orgName, now()->format('d/m/Y'), $days],
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
