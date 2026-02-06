<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Services\BrevoService;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Conectando à tabela existente do sistema atual
    protected $table = 'users';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'department',
        'is_platform_team',
        'supervisor_id',
        'status',
        'last_seen_at',
        'last_login_at',
        'phone',
        'onboarding_steps',
        'onboarding_completed_at'
    ];

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function subordinates()
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }


    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
        'onboarding_steps' => 'array'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }


    public function projectMembers()
    {
        return $this->hasMany(ProjectMember::class, 'user_id');
    }

    /**
     * Password reset notification using Brevo transactional API.
     * This avoids dependency on SMTP configuration.
     */
    public function sendPasswordResetNotification($token)
    {
        try {
            $resetUrl = route('password.reset', ['token' => $token, 'email' => $this->email]);
            $expire = (int) config('auth.passwords.users.expire', 60);

            $subject = 'Redefinir senha - Vivensi';
            $content = "<p>Olá, <strong>{$this->name}</strong>.</p>"
                . "<p>Recebemos uma solicitação para redefinir sua senha.</p>"
                . "<p>Este link expira em <strong>{$expire} minutos</strong>.</p>"
                . "<p>Se você não solicitou isso, pode ignorar este e-mail com segurança.</p>";

            $html = "<!doctype html><html lang=\"pt-br\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"><title>{$subject}</title></head><body style=\"margin:0;padding:0;background:#f8fafc;font-family:Inter,Segoe UI,Roboto,Arial,sans-serif;color:#0f172a;\"><div style=\"max-width:640px;margin:0 auto;padding:28px;\"><div style=\"background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 10px 25px rgba(15,23,42,.06);\"><div style=\"padding:22px 22px;background:linear-gradient(135deg,#4f46e5,#3730a3);color:#fff;\"><div style=\"font-weight:800;font-size:18px;\">Vivensi</div></div><div style=\"padding:22px;\"><h2 style=\"margin:0 0 12px 0;font-size:18px;\">Redefinição de senha</h2><div style=\"color:#334155;font-size:14px;line-height:1.6;\">{$content}</div><p style=\"margin:24px 0 0 0;\"><a href=\"{$resetUrl}\" style=\"background:#4f46e5;color:#fff;padding:12px 18px;border-radius:10px;text-decoration:none;font-weight:700;display:inline-block;\">Redefinir senha</a></p></div><div style=\"padding:16px 22px;background:#f1f5f9;color:#64748b;font-size:12px;border-top:1px solid #e2e8f0;\">Mensagem automática. Não responda.</div></div></div></body></html>";

            app(BrevoService::class)->sendEmail($this->email, $this->name, $subject, $html, $this->tenant_id);
        } catch (\Throwable $e) {
            // Fail-safe: if email fails, keep the reset flow stable.
            $masked = $this->email ? preg_replace('/(^.).*(@.*$)/', '$1***$2', $this->email) : null;
            Log::warning('Password reset email failed', [
                'user_id' => $this->id,
                'tenant_id' => $this->tenant_id,
                'email' => $masked,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

